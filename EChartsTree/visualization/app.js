(function () {
    'use strict';

    var bootstrap = window.SYMC_VISUALIZATION || {};
    var chartElement = document.getElementById('echarts-tree-chart');
    var errorElement = document.getElementById('echarts-tree-error');
    var warningElement = document.getElementById('echarts-tree-warning');
    var design = window.SYMC_ECHARTS_DESIGN;
    var treeSupport = window.SYMC_ECHARTS_TREE;
    var chart = null;
    var currentTheme = null;
    var currentState = bootstrap.state || null;

    function translate(value) {
        return (bootstrap.translations || {})[value] || value;
    }

    var retainOnTransientError = design.createTransientFailureRetainer(
        chartElement, warningElement, translate, 'The Tree could not be loaded.'
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
            accent: '#55CBB5', seriesColors: ['#5070DD', '#B6D634', '#505372']
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

    function coloredNode(node, style, colors) {
        var fallback = colors.seriesColors || [colors.accent];
        var kind = node.kind === 'variable' ? 'variable' : (node.kind === 'instance' ? 'instance' : 'category');
        var color = style[kind + 'Color'] || fallback[{ category: 0, instance: 1, variable: 2 }[kind] % fallback.length];
        var result = {
            id: String(node.id), name: String(node.name || ''), kind: kind,
            displayValue: String(node.displayValue || ''),
            itemStyle: { color: color },
            children: (node.children || []).map(function (child) { return coloredNode(child, style, colors); })
        };
        return result;
    }

    function buildOption(model, theme) {
        var colors = colorsFor(theme);
        var tree = model.tree || {};
        var style = tree.style || {};
        var width = Math.max(1, chartElement.clientWidth || 1);
        var height = Math.max(1, chartElement.clientHeight || 1);
        var compact = width < 430 || height < 300;
        var radial = style.treeLayout === 'radial';
        var orient = ['LR', 'RL', 'TB', 'BT'].includes(style.orientation) ? style.orientation : 'LR';
        var titleSize = design.fontSizePercent(style.titleFontSizePercent, 18);
        var labelSize = design.fontSizePercent(style.labelFontSizePercent, compact ? 10 : 12);
        var titleLayout = design.chartTitleLayout(bootstrap, tree.title, titleSize);
        var showTitle = titleLayout.show;
        var top = titleLayout.contentTop;
        var bottom = 18;
        var nodeSize = Math.max(4, Math.min(30, Number(style.nodeSize) || 10));
        var root = coloredNode(tree.root || {}, style, colors);
        var initialDepth = Math.max(0, Math.min(Number(bootstrap.options.treeMaxDepth) || 12,
            Number(style.initialDepth) || 0));
        var labels = treeSupport.labelLayout(root, width, Math.max(1, height - top - 18), radial,
            orient, compact, nodeSize, initialDepth, labelSize, style.showValues === true, chart);
        var outerPadding = labels.outerPadding;
        top += outerPadding + labels.topPadding;
        bottom += outerPadding;
        if (outerPadding || labels.topPadding) {
            labels = treeSupport.labelLayout(root, Math.max(1, width - 2 * outerPadding),
                Math.max(1, height - top - bottom), radial, orient, compact, nodeSize,
                initialDepth, labelSize, style.showValues === true, chart);
        }
        return {
            backgroundColor: bootstrap.mode === 'ipsview' && bootstrap.options.adaptToBackground === true
                ? 'transparent' : colors.background,
            ...design.animationOptions(style, { enabled: true, initialDuration: 350, updateDuration: 500 }),
            aria: { enabled: true, decal: { show: false }, description: String(tree.count || 0) + ' Tree nodes' },
            title: {
                show: showTitle, text: String(tree.title || ''), left: 'center', top: titleLayout.top,
                textStyle: { color: style.titleColor || colors.text, fontSize: titleSize }
            },
            tooltip: {
                trigger: 'item', confine: true,
                extraCssText: 'max-width:280px;white-space:normal;overflow-wrap:anywhere',
                formatter: function (parameter) {
                    var data = parameter.data || {};
                    return escapeHTML(data.name || '') + (data.displayValue
                        ? '<br>' + escapeHTML(data.displayValue) : '');
                }
            },
            series: [{
                id: 'tree', type: 'tree', layout: radial ? 'radial' : 'orthogonal',
                orient: orient, edgeShape: style.edgeShape === 'polyline' ? 'polyline' : 'curve',
                roam: style.roamEnabled === true, expandAndCollapse: true,
                initialTreeDepth: initialDepth,
                left: radial ? (outerPadding ? width * 0.09 + outerPadding : '9%') : (orient === 'RL' ? '22%' : '5%'),
                right: radial ? (outerPadding ? width * 0.09 + outerPadding : '9%') : (orient === 'LR' ? '23%' : '5%'),
                top: top, bottom: bottom,
                symbol: 'circle', symbolSize: compact ? Math.max(4, nodeSize * 0.8) : nodeSize,
                lineStyle: { color: style.edgeColor || colors.muted, width: 1.5 },
                label: {
                    show: style.showLabels !== false,
                    position: radial ? 'outside' : (orient === 'RL' ? 'left' : (orient === 'LR' ? 'right' : 'top')),
                    color: style.labelColor || colors.text,
                    fontSize: labelSize,
                    width: labels.width,
                    overflow: 'truncate',
                    formatter: function (parameter) {
                        var data = parameter.data || {};
                        if (!labels.show(data, parameter.collapsed)) { return ''; }
                        return String(data.name || '') + (style.showValues === true && data.kind === 'variable'
                            && data.displayValue ? '\n' + data.displayValue : '');
                    }
                },
                leaves: { label: { show: style.showLabels !== false } },
                data: [root]
            }]
        };
    }

    function displayError(message) {
        if (chart) { chart.clear(); }
        chartElement.hidden = true;
        warningElement.hidden = true;
        errorElement.textContent = translate(message || 'The Tree could not be loaded.');
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
        warningElement.textContent = translate('Only the first configured nodes are shown.');
        warningElement.hidden = state.chart.tree.truncated !== true;
        var theme = normalizeTheme(state.chart.theme || 'auto');
        if (chart && currentTheme !== theme) {
            chart.dispose();
            chart = null;
        }
        if (!chart) {
            chart = window.echarts.init(chartElement, theme === 'auto' ? null : theme, { renderer: 'canvas' });
            currentTheme = theme;
            chart.on('treeExpandAndCollapse', function () {
                treeSupport.updateChart(chart, buildOption(currentState.chart, currentTheme || 'auto'));
            });
        }
        treeSupport.updateChart(chart, buildOption(state.chart, theme));
    }

    window.handleMessage = function (message) {
        var state = design.parseVisualizationState(message);
        if (state) { render(state); }
    };
    if (window.ResizeObserver) {
        new ResizeObserver(function () {
            if (!design.resizeChartIfNeeded(chart, chartElement)) { return; }
            if (currentState && currentState.status === 'ready') {
                treeSupport.updateChart(chart, buildOption(currentState.chart, currentTheme || 'auto'));
            }
        }).observe(chartElement);
    } else {
        window.addEventListener('resize', function () { if (chart) { chart.resize(); } });
    }
    window.addEventListener('beforeunload', function () { if (chart) { chart.dispose(); } });
    var compressedState = bootstrap.options && bootstrap.options.compressedStateGzipBase64;
    if (!compressedState) {
        render(currentState);
    } else if (typeof window.DecompressionStream !== 'function') {
        displayError('The Tree could not be loaded.');
    } else {
        try {
            var binary = window.atob(compressedState);
            var bytes = new Uint8Array(binary.length);
            for (var index = 0; index < binary.length; index++) { bytes[index] = binary.charCodeAt(index); }
            var stream = new window.Blob([bytes]).stream().pipeThrough(new window.DecompressionStream('gzip'));
            new window.Response(stream).text().then(function (json) {
                if (currentState === null) { render(JSON.parse(json)); }
            }).catch(function () {
                if (currentState === null) { displayError('The Tree could not be loaded.'); }
            });
        } catch (error) {
            displayError('The Tree could not be loaded.');
        }
    }
}());
