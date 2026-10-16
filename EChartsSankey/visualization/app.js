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

    var tileHeaderInset = bootstrap.mode === 'symcon'
        && (!bootstrap.options || bootstrap.options.tileHeaderVisible !== false) ? 58 : 0;
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
                        return escapeHTML(labels.get(data.source) || '') + ' → '
                            + escapeHTML(labels.get(data.target) || '') + '<br>'
                            + escapeHTML(formatValue(data.value));
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
                nodeGap: nodeGap, layoutIterations: 32,
                draggable: style.draggable !== false,
                data: nodes.map(function (node) {
                    return { name: node.name, displayName: node.label, value: node.value,
                        itemStyle: style.nodeColor ? { color: style.nodeColor } : {},
                        label: { position: vertical ? (outgoing.has(node.name) ? 'bottom' : 'top')
                            : (outgoing.has(node.name) ? 'right' : 'left') } };
                }),
                links: links.map(function (link) {
                    return { source: link.source, target: link.target, value: link.value, timestamp: link.timestamp };
                }),
                label: { show: style.showLabels !== false, color: style.labelColor || colors.text,
                    fontSize: design.fontSizePercent(style.labelFontSizePercent, compact ? 10 : 12),
                    overflow: 'truncate', width: compact ? 90 : 150,
                    formatter: function (parameter) {
                        var data = parameter.data || {};
                        return String(data.displayName || '')
                            + (style.showValues === true ? '\n' + formatValue(data.value) : '');
                    } },
                lineStyle: { color: style.edgeColorMode === 'fixed' ? (style.edgeColor || colors.muted)
                        : (style.edgeColorMode || 'gradient'),
                    opacity: Number(style.edgeOpacityPercent) / 100,
                    curveness: Number(style.edgeCurvenessPercent) / 100 },
                emphasis: { focus: 'adjacency', lineStyle: { opacity: 0.85 } },
                blur: { lineStyle: { opacity: 0.05 } }
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
