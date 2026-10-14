(function () {
    'use strict';

    var bootstrap = window.SYMC_VISUALIZATION || {};
    var chartElement = document.getElementById('echarts-graph-chart');
    var errorElement = document.getElementById('echarts-graph-error');
    var warningElement = document.getElementById('echarts-graph-warning');
    var design = window.SYMC_ECHARTS_DESIGN;
    var chart = null;
    var currentTheme = null;
    var currentState = bootstrap.state || null;

    function translate(value) {
        return (bootstrap.translations || {})[value] || value;
    }

    var retainOnTransientError = design.createTransientFailureRetainer(
        chartElement, warningElement, translate, 'The Graph could not be loaded.'
    );

    function escapeHTML(value) {
        return String(value).replace(/[&<>"']/g, function (character) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[character];
        });
    }

    function palette(theme) {
        var palettes = bootstrap.options && bootstrap.options.echartsThemes || {};
        return palettes[theme] || palettes.auto || {
            background: '#151619', text: '#F4F5F7', muted: '#969AA2',
            accent: '#55CBB5', seriesColors: ['#5070DD']
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
            accent: resolveColor('--symc-accent', colors.accent),
            seriesColors: colors.seriesColors
        };
    }

    function normalizeTheme(theme) {
        var palettes = bootstrap.options && bootstrap.options.echartsThemes || {};
        return Object.prototype.hasOwnProperty.call(palettes, theme) ? theme : 'auto';
    }

    function buildOption(model, theme) {
        var colors = colorsFor(theme);
        var graph = model.graph || {};
        var style = graph.style || {};
        var width = Math.max(1, chartElement.clientWidth || 1);
        var height = Math.max(1, chartElement.clientHeight || 1);
        var compact = width < 430 || height < 300;
        var titleSize = design.fontSizePercent(style.titleFontSizePercent, 18);
        var labelSize = design.fontSizePercent(style.labelFontSizePercent, compact ? 10 : 12);
        var showTitle = Boolean(graph.title) && (bootstrap.mode === 'ipsview'
            || bootstrap.options.tileHeaderVisible === false);
        var top = (bootstrap.mode === 'symcon' && bootstrap.options.tileHeaderVisible !== false ? 58 : 8)
            + (showTitle ? titleSize + 12 : 0);
        var nodeSize = Math.max(6, Math.min(40, Number(style.nodeSize) || 16));
        var nodeColor = style.nodeColor || colors.seriesColors[0] || colors.accent;
        var nodes = (graph.nodes || []).map(function (node) {
            return {
                id: String(node.id), name: String(node.name || ''), kind: String(node.kind || ''),
                displayValue: String(node.displayValue || ''), itemStyle: { color: nodeColor }
            };
        });
        var links = (graph.links || []).map(function (link) {
            return { source: String(link.source), target: String(link.target) };
        });
        return {
            backgroundColor: bootstrap.mode === 'ipsview' && bootstrap.options.adaptToBackground === true
                ? 'transparent' : colors.background,
            ...design.animationOptions(style, { enabled: true, initialDuration: 350, updateDuration: 500 }),
            aria: { enabled: true, decal: { show: false }, description: nodes.length + ' Graph nodes' },
            title: {
                show: showTitle, text: String(graph.title || ''), left: 'center', top: bootstrap.mode === 'symcon' ? 8 : 4,
                textStyle: { color: style.titleColor || colors.text, fontSize: titleSize }
            },
            tooltip: {
                trigger: 'item',
                formatter: function (parameter) {
                    var data = parameter.data || {};
                    if (parameter.dataType === 'edge') { return ''; }
                    return escapeHTML(data.name || '') + (data.displayValue
                        ? '<br>' + escapeHTML(data.displayValue) : '');
                }
            },
            series: [{
                id: 'graph', type: 'graph', layout: style.graphLayout === 'circular' ? 'circular' : 'force',
                roam: style.roamEnabled === true, draggable: style.roamEnabled === true,
                left: '6%', right: '6%', top: top, bottom: 20,
                symbol: 'circle', symbolSize: compact ? Math.max(6, nodeSize * 0.8) : nodeSize,
                force: { repulsion: Math.max(80, Math.min(600, 160 + nodes.length * 4)), edgeLength: compact ? 65 : 100 },
                circular: { rotateLabel: false },
                data: nodes, links: links,
                lineStyle: { color: style.edgeColor || colors.muted, width: 1.5, opacity: 0.8 },
                label: {
                    show: style.showLabels !== false,
                    color: style.labelColor || colors.text,
                    fontSize: labelSize,
                    width: compact ? 84 : 140,
                    overflow: 'truncate',
                    formatter: function (parameter) {
                        var data = parameter.data || {};
                        return String(data.name || '') + (style.showValues === true && data.kind === 'variable'
                            && data.displayValue ? '\n' + data.displayValue : '');
                    }
                },
                emphasis: { focus: 'adjacency' }
            }]
        };
    }

    function displayError(message) {
        if (chart) { chart.clear(); }
        chartElement.hidden = true;
        warningElement.hidden = true;
        errorElement.textContent = translate(message || 'The Graph could not be loaded.');
        errorElement.hidden = false;
    }

    function render(state) {
        if (retainOnTransientError(state, currentState, chart)) { return; }
        currentState = state;
        if (!state || state.status !== 'ready' || !state.chart) {
            displayError(state && state.error);
            return;
        }
        if (!window.echarts || typeof window.echarts.init !== 'function') {
            displayError('Apache ECharts could not be initialized.');
            return;
        }
        errorElement.hidden = true;
        chartElement.hidden = false;
        warningElement.hidden = true;
        var theme = normalizeTheme(state.chart.theme || 'auto');
        if (chart && currentTheme !== theme) {
            chart.dispose();
            chart = null;
        }
        if (!chart) {
            chart = window.echarts.init(chartElement, theme === 'auto' ? null : theme, { renderer: 'canvas' });
            currentTheme = theme;
        }
        chart.setOption(buildOption(state.chart, theme), true);
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
