(function () {
    'use strict';

    var bootstrap = window.SYMC_VISUALIZATION || {};
    var chartElement = document.getElementById('echarts-zigbee-graph-chart');
    var metaElement = document.getElementById('echarts-zigbee-graph-meta');
    var legendElement = document.getElementById('echarts-zigbee-graph-legend');
    var errorElement = document.getElementById('echarts-zigbee-graph-error');
    var warningElement = document.getElementById('echarts-zigbee-graph-warning');
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

    function nodeColors(style, colors) {
        return {
            Coordinator: style.nodeColor || colors.seriesColors[0] || colors.accent,
            Router: style.routerColor || colors.seriesColors[1] || '#B6D634',
            EndDevice: style.endDeviceColor || colors.seriesColors[3] || '#FF994D'
        };
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
        var roleColors = nodeColors(style, colors);
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
                lineStyle: { opacity: 0.1 + 0.35 * lqi / 255 }
            };
        });
        return {
            backgroundColor: bootstrap.mode === 'ipsview' && bootstrap.options.adaptToBackground === true
                ? 'transparent' : colors.background,
            ...design.animationOptions(style, { enabled: true, initialDuration: 350, updateDuration: 500 }),
            aria: { enabled: true, decal: { show: false }, description: nodes.length + ' Zigbee devices' },
            title: {
                show: showTitle, text: String(graph.title || ''), left: 'center', top: bootstrap.mode === 'symcon' ? 8 : 4,
                textStyle: { color: style.titleColor || colors.text, fontSize: titleSize }
            },
            tooltip: {
                trigger: 'item',
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
                left: '6%', right: '6%', top: top, bottom: 42,
                symbol: 'circle', symbolSize: compact ? Math.max(6, nodeSize * 0.8) : nodeSize,
                edgeSymbol: style.showLinkArrows === true ? ['none', 'arrow'] : ['none', 'none'],
                edgeSymbolSize: 6,
                force: { repulsion: Math.max(140, Math.min(1200, 180 + nodes.length * 7)), edgeLength: compact ? 55 : 85 },
                circular: { rotateLabel: false },
                data: nodes, links: links,
                lineStyle: { color: style.edgeColor || colors.muted, width: 1, opacity: 0.45 },
                label: {
                    show: style.showLabels !== false && (width >= 800 || nodes.length <= 30),
                    color: style.labelColor || colors.text,
                    fontSize: labelSize,
                    position: 'right',
                    distance: 4,
                    width: compact ? 84 : 140,
                    overflow: 'truncate',
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
        legendElement.hidden = false;
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
