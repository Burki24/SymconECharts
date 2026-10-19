(function () {
    'use strict';

    var bootstrap = window.SYMC_VISUALIZATION || {};
    var rootElement = document.getElementById('echarts-zigbee-graph-root');
    var chartElement = document.getElementById('echarts-zigbee-graph-chart');
    var metaElement = document.getElementById('echarts-zigbee-graph-meta');
    var legendElement = document.getElementById('echarts-zigbee-graph-legend');
    var errorElement = document.getElementById('echarts-zigbee-graph-error');
    var warningElement = document.getElementById('echarts-zigbee-graph-warning');
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

    function nodeColors(style, colors) {
        return {
            Coordinator: style.nodeColor || colors.seriesColors[0] || colors.accent,
            Router: style.routerColor || colors.seriesColors[1] || '#B6D634',
            EndDevice: style.endDeviceColor || colors.seriesColors[3] || '#FF994D'
        };
    }

    function lqiColors(style) {
        return [style.lqiLowColor || '#D9534F', style.lqiMidColor || '#E6B94A', style.lqiHighColor || '#37A873'];
    }

    function lqiColor(lqi, anchors) {
        var segment = lqi <= 128 ? 0 : 1;
        var ratio = segment === 0 ? lqi / 128 : (lqi - 128) / 127;
        var start = anchors[segment];
        var end = anchors[segment + 1];
        var result = '#';
        for (var index = 1; index < 7; index += 2) {
            var channel = Math.round(parseInt(start.slice(index, index + 2), 16) * (1 - ratio)
                + parseInt(end.slice(index, index + 2), 16) * ratio);
            result += channel.toString(16).padStart(2, '0');
        }
        return result.toUpperCase();
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
        var roleColors = nodeColors(style, colors);
        var anchors = lqiColors(style);
        var nodes = (graph.nodes || []).map(function (node) {
            var kind = String(node.kind || 'Unknown');
            return {
                id: String(node.id), name: String(node.name || ''), kind: kind,
                networkAddress: node.networkAddress,
                itemStyle: { color: Object.prototype.hasOwnProperty.call(roleColors, kind)
                    ? roleColors[kind] : roleColors.Coordinator }
            };
        });
        var names = {};
        nodes.forEach(function (node) { names[node.id] = node.name; });
        var links = (graph.links || []).map(function (link) {
            var lqi = Math.max(0, Math.min(255, Number(link.lqi) || 0));
            return {
                source: String(link.source), target: String(link.target), lqi: lqi,
                lineStyle: {
                    opacity: 0.28 + 0.25 * lqi / 255,
                    color: lqiColor(lqi, anchors)
                }
            };
        });
        return {
            backgroundColor: bootstrap.mode === 'ipsview' && bootstrap.options.adaptToBackground === true
                ? 'transparent' : colors.background,
            ...design.animationOptions(style, { enabled: true, initialDuration: 350, updateDuration: 500 }),
            aria: { enabled: true, decal: { show: false }, description: nodes.length + ' Zigbee devices' },
            title: {
                show: false, text: String(graph.title || ''), left: 'center', top: 0,
                textStyle: { color: style.titleColor || colors.text, fontSize: titleSize }
            },
            tooltip: {
                ...graphSupport.tooltipOptions(),
                formatter: function (parameter) {
                    var data = parameter.data || {};
                    if (parameter.dataType === 'edge') {
                        return escapeHTML(names[data.source] || '') + ' → '
                            + escapeHTML(names[data.target] || '') + '<br>LQI: ' + escapeHTML(data.lqi);
                    }
                    return escapeHTML(data.name || '') + '<br>' + escapeHTML(translate(data.kind || 'Unknown'));
                }
            },
            series: [{
                id: 'graph', type: 'graph', layout: style.graphLayout === 'circular' ? 'circular' : 'force',
                roam: style.roamEnabled === true, draggable: style.roamEnabled === true,
                left: '6%', right: '6%', top: 12, bottom: 60,
                symbol: 'circle', symbolSize: compact ? Math.max(6, nodeSize * 0.8) : nodeSize,
                edgeSymbol: style.showLinkArrows === true ? ['none', 'arrow'] : ['none', 'none'],
                edgeSymbolSize: 6,
                force: { repulsion: Math.max(140, Math.min(1200, 180 + nodes.length * 7)), edgeLength: compact ? 55 : 85 },
                circular: { rotateLabel: false },
                data: nodes, links: links,
                lineStyle: { width: 1, opacity: 0.45 },
                label: {
                    ...graphSupport.labelOptions(style, width, height, labelSize),
                    show: style.showLabels !== false && (width >= 800 || nodes.length <= 30),
                    color: style.labelColor || colors.text,
                    formatter: function (parameter) {
                        var data = parameter.data || {};
                        return String(data.name || '');
                    }
                },
                labelLayout: { hideOverlap: true },
                emphasis: {
                    focus: 'adjacency',
                    label: { show: style.showLabels !== false },
                    lineStyle: { opacity: 0.9, width: 2 }
                }
            }]
        };
    }

    function displayError(message) {
        if (chart) { chart.clear(); }
        chartElement.hidden = true;
        metaElement.hidden = true;
        legendElement.hidden = true;
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
        var graph = state.chart.graph || {};
        var captured = Number(graph.capturedAt) > 0
            ? new Date(Number(graph.capturedAt) * 1000).toLocaleString() : '–';
        metaElement.textContent = (graph.nodes || []).length + ' ' + translate('devices') + ' · '
            + (graph.links || []).length + '/' + (Number(graph.totalLinks) || 0) + ' '
            + translate('links') + ' · ' + translate('snapshot') + ': ' + captured;
        metaElement.hidden = false;
        var colors = colorsFor(normalizeTheme(state.chart.theme || 'auto'));
        rootElement.style.backgroundColor = bootstrap.mode === 'ipsview'
            && bootstrap.options.adaptToBackground === true ? 'transparent' : colors.background;
        var style = graph.style || {};
        var roleColors = nodeColors(style, colors);
        var legend = [
            ['Coordinator', roleColors.Coordinator],
            ['Router', roleColors.Router],
            ['End device', roleColors.EndDevice]
        ];
        if ((graph.nodes || []).some(function (node) { return !Object.prototype.hasOwnProperty.call(roleColors, node.kind); })) {
            legend.push(['Unknown', roleColors.Coordinator]);
        }
        legendElement.innerHTML = legend.map(function (entry) {
            return '<span class="echarts-zigbee-graph-legend-item"><span class="echarts-zigbee-graph-legend-dot"'
                + ' style="background-color:' + escapeHTML(entry[1]) + '"></span>'
                + escapeHTML(translate(entry[0])) + '</span>';
        }).join('');
        legendElement.innerHTML += '<span class="echarts-zigbee-graph-legend-item">'
            + '<span class="echarts-zigbee-graph-legend-scale" style="background:linear-gradient(90deg,'
            + escapeHTML(lqiColors(style).join(',')) + ')"></span>LQI 0–255</span>';
        legendElement.hidden = false;
        var theme = normalizeTheme(state.chart.theme || 'auto');
        var option = buildOption(state.chart, theme);
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
