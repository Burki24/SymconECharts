(function () {
    'use strict';

    var bootstrap = window.SYMC_VISUALIZATION || {};
    var design = window.SYMC_ECHARTS_DESIGN;
    var rootElement = document.getElementById('echarts-sankey-root');
    var chartElement = document.getElementById('echarts-sankey-chart');
    var emptyElement = document.getElementById('echarts-sankey-empty');
    var errorElement = document.getElementById('echarts-sankey-error');
    var warningElement = document.getElementById('echarts-sankey-warning');
    var chart = null;
    var currentState = bootstrap.state || null;
    var currentTheme = null;

    var tileHeaderInset = (bootstrap.options && bootstrap.options.tileHeaderInset) || 0;
    var chartBootstrap = tileHeaderInset ? { mode: 'symcon', options: { tileHeaderVisible: false } } : bootstrap;
    chartElement.style.top = tileHeaderInset + 'px';
    chartElement.style.height = tileHeaderInset ? 'calc(100% - ' + tileHeaderInset + 'px)' : '100%';

    function translate(text) { return (bootstrap.translations || {})[text] || text; }
    var retainOnTransientError = design.createTransientFailureRetainer(
        chartElement, warningElement, translate, 'The Sankey values could not be loaded.'
    );
    function normalizeTheme(theme) {
        var palettes = bootstrap.options && bootstrap.options.echartsThemes || {};
        return Object.prototype.hasOwnProperty.call(palettes, theme) ? theme : 'auto';
    }
    function escapeHTML(value) {
        return String(value).replace(/[&<>"']/g, function (character) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[character];
        });
    }
    function palette(theme) {
        var palettes = bootstrap.options && bootstrap.options.echartsThemes || {};
        return palettes[theme] || palettes.auto || {
            background: '#151619', text: '#F4F5F7', muted: '#969AA2',
            border: '#A5A9B0', accent: '#55CBB5'
        };
    }
    function resolveColor(variable, fallback) {
        var probe = document.createElement('span');
        probe.style.color = 'var(' + variable + ', ' + fallback + ')';
        probe.style.display = 'none';
        document.body.appendChild(probe);
        var resolved = window.getComputedStyle(probe).color;
        probe.remove();
        return resolved || fallback;
    }
    function colorsFor(theme) {
        var colors = palette(theme);
        if (theme !== 'auto') { return colors; }
        return {
            background: resolveColor('--symc-background', colors.background),
            text: resolveColor('--symc-text', colors.text),
            muted: resolveColor('--symc-text-muted', colors.muted),
            border: resolveColor('--symc-border', colors.border),
            accent: resolveColor('--symc-accent', colors.accent)
        };
    }
    function buildOption(model, theme) {
        var colors = colorsFor(theme);
        var flow = model.sankey || {};
        var style = flow.style || {};
        var nodes = Array.isArray(flow.data) ? flow.data : [];
        var links = Array.isArray(flow.links) ? flow.links : [];
        var compact = (chartElement.clientWidth || 1) < 430;
        var titleSize = design.fontSizePercent(style.titleFontSizePercent, 18);
        var titleLayout = design.chartTitleLayout(chartBootstrap, flow.title, titleSize);
        var vertical = style.orientation === 'vertical';
        var top = titleLayout.contentTop + 18;
        var bottom = vertical ? 45 : 24;
        var margin = compact ? 30 : 90;
        var crossSize = vertical ? (chartElement.clientWidth || 1) - 2 * margin
            : (chartElement.clientHeight || 1) - top - bottom;
        var nodeGap = Math.min(Number(style.nodeGap) || 0,
            Math.max(0, crossSize) / Math.max(1, nodes.length - 1) * 0.5);
        var labels = new Map(nodes.map(function (node) { return [node.name, String(node.label || '')]; }));
        var outgoing = new Set(links.map(function (link) { return link.source; }));
        var labelSize = design.fontSizePercent(style.labelFontSizePercent, compact ? 10 : 12);
        var labelWidth = Number(style.labelMaxWidth) || (compact ? 90 : 150);
        function labelPosition(name) {
            return style.labelPosition && style.labelPosition !== 'auto' ? style.labelPosition
                : (vertical ? (outgoing.has(name) ? 'bottom' : 'top') : (outgoing.has(name) ? 'right' : 'left'));
        }
        function formatValue(value) {
            return Number(value).toLocaleString(undefined, { maximumFractionDigits: 3 })
                + (flow.unit ? ' ' + String(flow.unit) : '');
        }
        return {
            backgroundColor: bootstrap.mode === 'ipsview' && bootstrap.options.adaptToBackground === true
                ? 'transparent' : colors.background,
            ...design.animationOptions(style, { enabled: true, initialDuration: 350, updateDuration: 500 }),
            aria: { enabled: true, decal: { show: false } },
            title: { show: titleLayout.show, text: String(flow.title || ''),
                left: 'center', top: titleLayout.top,
                textStyle: { color: style.titleColor || colors.text, fontSize: titleSize } },
            tooltip: {
                show: style.showTooltip !== false, trigger: 'item',
                formatter: function (parameter) {
                    var data = parameter.data || {};
                    if (parameter.dataType === 'edge') {
                        var text = escapeHTML(labels.get(data.source) || '') + ' → '
                            + escapeHTML(labels.get(data.target) || '') + '<br>'
                            + escapeHTML(formatValue(data.value));
                        if (data.reversed === true) {
                            text += '<br>' + escapeHTML(translate('Original value')) + ': '
                                + escapeHTML(formatValue(data.originalValue))
                                + '<br>' + escapeHTML(translate('Configured connection')) + ': '
                                + escapeHTML(labels.get(data.configuredSource) || '') + ' → '
                                + escapeHTML(labels.get(data.configuredTarget) || '');
                        }
                        return text;
                    }
                    return escapeHTML(data.displayName || '') + '<br>' + escapeHTML(formatValue(data.value));
                }
            },
            series: [{
                id: 'sankey', type: 'sankey', orient: vertical ? 'vertical' : 'horizontal',
                left: margin, right: margin,
                top: top, bottom: bottom,
                nodeAlign: style.nodeAlign || 'justify',
                nodeWidth: Number(style.nodeWidth) || 20,
                nodeGap: nodeGap, layoutIterations: style.layoutIterations == null ? 32 : Number(style.layoutIterations),
                draggable: style.draggable !== false,
                data: nodes.map(function (node) {
                    var nodeColors = style.nodeColors || {};
                    var color = Object.prototype.hasOwnProperty.call(nodeColors, node.name) ? nodeColors[node.name] : style.nodeColor;
                    return { name: node.name, displayName: node.label, value: node.value,
                        itemStyle: color ? { color: color } : {},
                        label: { position: labelPosition(node.name) } };
                }),
                links: links.map(function (link) {
                    return { source: link.source, target: link.target, value: link.value, timestamp: link.timestamp,
                        originalValue: link.originalValue, configuredSource: link.configuredSource,
                        configuredTarget: link.configuredTarget, reversed: link.reversed === true };
                }),
                label: { show: style.showLabels !== false, color: style.labelColor || colors.text,
                    fontSize: labelSize,
                    fontWeight: style.labelBold === true ? 'bold' : 'normal',
                    distance: style.labelDistance == null ? 5 : Number(style.labelDistance),
                    overflow: style.labelOverflow || 'truncate', lineOverflow: 'truncate', width: labelWidth,
                    formatter: function (parameter) {
                        var data = parameter.data || {};
                        return String(data.displayName || '')
                            + (style.showValues === true ? '\n' + formatValue(data.value) : '');
                    } },
                labelLayout: function (parameters) {
                    var layout = { hideOverlap: style.hideOverlappingLabels === true };
                    if (parameters.dataType === 'edge' || !parameters.rect) { return layout; }
                    var position = labelPosition((nodes[parameters.dataIndex] || {}).name);
                    var inside = position === 'inside';
                    // Fit text to the actual laid-out rectangle, not an estimate from node count.
                    if (vertical || inside) {
                        layout.width = Math.min(labelWidth, Math.max(0, parameters.rect.width - 4));
                    }
                    if (!vertical || inside) {
                        layout.height = Math.max(0, parameters.rect.height - 2);
                    }
                    return layout;
                },
                edgeLabel: { show: style.showEdgeValues === true,
                    color: style.edgeLabelColor || style.labelColor || colors.text,
                    fontSize: design.fontSizePercent(style.edgeLabelFontSizePercent, compact ? 10 : 12),
                    formatter: function (parameter) { return formatValue((parameter.data || {}).value); } },
                itemStyle: { opacity: style.nodeOpacityPercent == null ? 1 : Number(style.nodeOpacityPercent) / 100,
                    borderWidth: Number(style.nodeBorderWidth) || 0,
                    borderRadius: Number(style.nodeBorderRadius) || 0,
                    borderColor: style.nodeBorderColor || colors.border,
                    shadowBlur: Number(style.nodeShadowBlur) || 0,
                    shadowColor: style.nodeShadowColor || 'rgba(0,0,0,0.35)' },
                lineStyle: { color: style.edgeColorMode === 'fixed' ? (style.edgeColor || colors.muted)
                        : (style.edgeColorMode || 'gradient'),
                    opacity: Number(style.edgeOpacityPercent) / 100,
                    curveness: Number(style.edgeCurvenessPercent) / 100 },
                emphasis: { disabled: style.hoverFocus === 'none',
                    focus: style.hoverFocus === 'none' ? 'none' : (style.hoverFocus || 'adjacency'),
                    label: { show: style.showLabels !== false, width: Math.max(150, labelWidth), height: labelSize * 6, overflow: 'break' },
                    edgeLabel: { show: style.showEdgeValues === true },
                    lineStyle: { opacity: style.highlightEdgeOpacityPercent == null ? 0.85 : Number(style.highlightEdgeOpacityPercent) / 100 } },
                blur: { lineStyle: { opacity: style.blurEdgeOpacityPercent == null ? 0.05 : Number(style.blurEdgeOpacityPercent) / 100 } }
            }]
        };
    }
    function displayError(message) {
        if (chart) { chart.clear(); }
        chartElement.hidden = true;
        emptyElement.hidden = true;
        warningElement.hidden = true;
        errorElement.textContent = translate(message || 'The Sankey values could not be loaded.');
        errorElement.hidden = false;
    }
    function render(state) {
        if (retainOnTransientError(state, currentState, chart)) { return; }
        currentState = state;
        if (!state || state.status !== 'ready' || !state.chart) {
            displayError(state && state.error);
            return;
        }
        var model = state.chart;
        var sankey = model.sankey || {};
        if (!Array.isArray(sankey.links) || sankey.links.length === 0) {
            if (chart) { chart.clear(); }
            chartElement.hidden = true;
            errorElement.hidden = true;
            warningElement.hidden = true;
            emptyElement.textContent = translate('No positive flows');
            emptyElement.hidden = false;
            return;
        }
        if (!window.echarts || typeof window.echarts.init !== 'function') {
            displayError('Apache ECharts could not be initialized.');
            return;
        }
        var wasHidden = chartElement.hidden;
        chartElement.hidden = false;
        errorElement.hidden = true;
        emptyElement.hidden = true;
        warningElement.hidden = true;
        rootElement.style.backgroundColor = bootstrap.mode === 'ipsview' && bootstrap.options.adaptToBackground === true
            ? 'transparent' : colorsFor(normalizeTheme(model.theme || 'auto')).background;
        var theme = normalizeTheme(model.theme || 'auto');
        if (chart && currentTheme !== theme) { chart.dispose(); chart = null; }
        if (!chart) {
            chart = window.echarts.init(chartElement, theme === 'auto' ? null : theme, { renderer: 'canvas' });
            currentTheme = theme;
        } else if (wasHidden) {
            chart.resize();
        }
        chart.setOption(buildOption(model, theme), true);
    }
    window.handleMessage = function (message) {
        var state = design.parseVisualizationState(message);
        if (state) { render(state); }
    };
    if (window.ResizeObserver) {
        new ResizeObserver(function () {
            if (!design.resizeChartIfNeeded(chart, chartElement)) { return; }
            if (currentState && currentState.status === 'ready') {
                chart.setOption(buildOption(currentState.chart, currentTheme || 'auto'), true);
            }
        }).observe(chartElement);
    } else {
        window.addEventListener('resize', function () { if (chart) { chart.resize(); } });
    }
    window.addEventListener('beforeunload', function () { if (chart) { chart.dispose(); } });
    render(currentState);
}());
