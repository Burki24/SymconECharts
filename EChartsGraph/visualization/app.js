(function () {
    'use strict';

    var bootstrap = window.SYMC_VISUALIZATION || {};
    var rootElement = document.getElementById('echarts-graph-root');
    var chartElement = document.getElementById('echarts-graph-chart');
    var errorElement = document.getElementById('echarts-graph-error');
    var warningElement = document.getElementById('echarts-graph-warning');
    var design = window.SYMC_ECHARTS_DESIGN;
    var graphSupport = window.SYMC_ECHARTS_GRAPH;
    var chart = null;
    var currentTheme = null;
    var currentState = bootstrap.state || null;
    var chartBootstrap = graphSupport.chartFrame(bootstrap, chartElement);

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
        var titleSize = design.fontSizePercent(style.titleFontSizePercent, 18);
        graphSupport.chartTitle(bootstrap, chartBootstrap, chartElement, rootElement,
            graph.title, titleSize, style.titleColor || colors.text, design);
        var width = Math.max(1, chartElement.clientWidth || 1);
        var height = Math.max(1, chartElement.clientHeight || 1);
        var compact = width < 430 || height < 300;
        var labelSize = design.fontSizePercent(style.labelFontSizePercent, compact ? 10 : 12);
        var nodeSize = Math.max(6, Math.min(40, Number(style.nodeSize) || 16));
        var nodeColor = style.nodeColor || colors.seriesColors[0] || colors.accent;
        var directed = graph.directed === true;
        var directedCircular = directed && style.graphLayout === 'circular';
        var nodeNames = Object.create(null);
        var nodes = (graph.nodes || []).map(function (node, index, all) {
            nodeNames[String(node.id)] = String(node.name || '');
            // Native circular edges bend radially and coincide for reciprocal
            // pairs (even with signed curvature at diametric endpoints). Keep
            // a circular projection, using native simple edge layout instead.
            // A diagonal diameter also keeps the native view/roam rectangle
            // two-dimensional for the smallest valid, two-node graph.
            var angle = 2 * Math.PI * index / all.length + Math.PI / 4;
            return {
                id: String(node.id), name: String(node.name || ''), kind: String(node.kind || ''),
                displayValue: String(node.displayValue || ''), itemStyle: { color: nodeColor },
                ...(directedCircular ? { x: Math.round(Math.cos(angle) * 1000000) / 10000,
                    y: Math.round(Math.sin(angle) * 1000000) / 10000 } : {})
            };
        });
        var links = (graph.links || []).map(function (link) {
            return { source: String(link.source), target: String(link.target),
                lineStyle: { curveness: directed && link.reciprocal === true ? 0.16 : 0 } };
        });
        return {
            backgroundColor: bootstrap.mode === 'ipsview' && bootstrap.options.adaptToBackground === true
                ? 'transparent' : colors.background,
            ...design.animationOptions(style, { enabled: true, initialDuration: 350, updateDuration: 500 }),
            aria: { enabled: true, decal: { show: false }, description: nodes.length + ' Graph nodes'
                + (directed ? '. ' + translate('Directed connections') : '') },
            title: {
                show: false, text: String(graph.title || ''), left: 'center', top: 0,
                textStyle: { color: style.titleColor || colors.text, fontSize: titleSize }
            },
            tooltip: {
                ...graphSupport.tooltipOptions(),
                formatter: function (parameter) {
                    var data = parameter.data || {};
                    if (parameter.dataType === 'edge') {
                        return directed ? escapeHTML(nodeNames[data.source] || data.source) + ' → '
                            + escapeHTML(nodeNames[data.target] || data.target) : '';
                    }
                    return escapeHTML(data.name || '') + (data.displayValue
                        ? '<br>' + escapeHTML(data.displayValue) : '');
                }
            },
            series: [{
                id: 'graph', type: 'graph', layout: directedCircular ? 'none'
                    : style.graphLayout === 'circular' ? 'circular' : 'force',
                preserveAspect: directedCircular ? 'contain' : false,
                roam: style.roamEnabled === true, draggable: style.roamEnabled === true,
                left: '6%', right: '6%', top: 12, bottom: 20,
                symbol: 'circle', symbolSize: compact ? Math.max(6, nodeSize * 0.8) : nodeSize,
                edgeSymbol: directed ? ['none', 'arrow'] : ['none', 'none'],
                edgeSymbolSize: [0, Math.max(5, Math.min(10, nodeSize * 0.7))],
                force: { repulsion: Math.max(80, Math.min(600, 160 + nodes.length * 4)), edgeLength: compact ? 65 : 100 },
                circular: { rotateLabel: false },
                data: nodes, links: links,
                lineStyle: { color: style.edgeColor || colors.muted, width: 1.5,
                    opacity: nodes.length > 80 ? 0.25 : 0.8 },
                label: {
                    ...graphSupport.labelOptions(style, width, height, labelSize),
                    color: style.labelColor || colors.text,
                    formatter: function (parameter) {
                        var data = parameter.data || {};
                        return String(data.name || '') + (style.showValues === true && data.kind === 'variable'
                            && data.displayValue ? '\n' + data.displayValue : '');
                    }
                },
                labelLayout: { hideOverlap: true },
                emphasis: { focus: 'adjacency', label: { show: style.showLabels !== false },
                    lineStyle: { opacity: 1, width: 2 } }
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
        var option = buildOption(state.chart, theme);
        var colors = colorsFor(theme);
        rootElement.style.backgroundColor = bootstrap.mode === 'ipsview'
            && bootstrap.options.adaptToBackground === true ? 'transparent' : colors.background;
        if (chart && currentTheme !== theme) {
            chart.dispose();
            chart = null;
        }
        if (!chart) {
            chart = window.echarts.init(chartElement, theme === 'auto' ? null : theme, { renderer: 'canvas' });
            currentTheme = theme;
        }
        graphSupport.updateChart(chart, option);
    }

    window.handleMessage = function (message) {
        var state = design.parseVisualizationState(message);
        if (state) { if (cancelStartup) { cancelStartup(); } render(state); }
    };
    if (window.ResizeObserver) {
        new ResizeObserver(function () {
            if (!design.resizeChartIfNeeded(chart, chartElement)) { return; }
            if (currentState && currentState.status === 'ready') {
                graphSupport.updateChart(chart, buildOption(currentState.chart, currentTheme || 'auto'));
            }
        }).observe(chartElement);
    } else {
        window.addEventListener('resize', function () { if (chart) { chart.resize(); } });
    }
    window.addEventListener('beforeunload', function () {
        if (cancelStartup) { cancelStartup(); }
        if (chart) { chart.dispose(); }
    });
    var cancelStartup = graphSupport.startup(bootstrap, render,
        function () { displayError('The Graph could not be loaded.'); });
}());
