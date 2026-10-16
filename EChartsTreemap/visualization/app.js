(function () {
    'use strict';

    var bootstrap = window.SYMC_VISUALIZATION || {};
    var chartElement = document.getElementById('echarts-treemap-chart');
    var errorElement = document.getElementById('echarts-treemap-error');
    var emptyElement = document.getElementById('echarts-treemap-empty');
    var warningElement = document.getElementById('echarts-treemap-warning');
    var design = window.SYMC_ECHARTS_DESIGN;
    var chart = null;
    var currentTheme = null;
    var currentState = bootstrap.state || null;

    function translate(value) { return (bootstrap.translations || {})[value] || value; }
    var retainOnTransientError = design.createTransientFailureRetainer(
        chartElement, warningElement, translate, 'The Treemap values could not be loaded.'
    );

    function palette(theme) {
        var palettes = bootstrap.options && bootstrap.options.echartsThemes || {};
        return palettes[theme] || palettes.auto || {
            background: '#151619', text: '#F4F5F7', muted: '#969AA2',
            accent: '#55CBB5', seriesColors: ['#5070DD', '#B6D634', '#FF994D']
        };
    }

    function normalizeTheme(theme) {
        var palettes = bootstrap.options && bootstrap.options.echartsThemes || {};
        return Object.prototype.hasOwnProperty.call(palettes, theme) ? theme : 'auto';
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

    function escapeHTML(value) {
        return String(value).replace(/[&<>"']/g, function (character) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[character];
        });
    }

    function nodesFor(data, colors) {
        var seriesColors = colors.seriesColors && colors.seriesColors.length ? colors.seriesColors : [colors.accent];
        return (Array.isArray(data) ? data : []).map(function (group, index) {
            var color = seriesColors[index % seriesColors.length];
            var readable = design.readableTextColor(color, colors.text, colors.background);
            function colorChildren(children) {
                return (children || []).map(function (child) {
                    return Object.assign({}, child, {
                        label: { color: readable }, upperLabel: { color: readable },
                        children: child.children ? colorChildren(child.children) : undefined
                    });
                });
            }
            return Object.assign({}, group, {
                itemStyle: { color: color },
                label: { color: readable }, upperLabel: { color: readable },
                children: colorChildren(group.children)
            });
        });
    }

    function buildOption(model, theme) {
        var colors = colorsFor(theme);
        var treemap = model.treemap || {};
        var style = treemap.style || {};
        var width = Math.max(1, chartElement.clientWidth || 1);
        var height = Math.max(1, chartElement.clientHeight || 1);
        var compact = width < 420 || height < 260;
        var showTitle = Boolean(treemap.title) && (bootstrap.mode === 'ipsview'
            || bootstrap.options.tileHeaderVisible === false);
        var titleSize = design.fontSizePercent(style.titleFontSizePercent, 18);
        var labelSize = design.fontSizePercent(style.labelFontSizePercent, 12);
        var textColor = style.labelColor || colors.text;
        return {
            backgroundColor: bootstrap.mode === 'ipsview' && bootstrap.options.adaptToBackground === true
                ? 'transparent' : colors.background,
            ...design.animationOptions(style, { enabled: true, initialDuration: 350, updateDuration: 500 }),
            aria: { enabled: true, decal: { show: false } },
            title: {
                show: showTitle, text: String(treemap.title || ''), left: 'center', top: 4,
                textStyle: { color: style.titleColor || colors.text, fontSize: titleSize }
            },
            tooltip: {
                trigger: 'item',
                formatter: function (parameter) {
                    var path = (parameter.treePathInfo || []).map(function (part) { return part.name; })
                        .filter(Boolean).join(' / ');
                    var value = Number(parameter.value);
                    return escapeHTML(path || parameter.name) + ': '
                        + escapeHTML(value.toLocaleString(undefined, { maximumFractionDigits: 3 }));
                }
            },
            series: [{
                id: 'treemap', type: 'treemap', data: nodesFor(treemap.data, colors),
                left: 8, right: 8,
                top: (bootstrap.mode === 'symcon' && bootstrap.options.tileHeaderVisible !== false ? 58 : 8)
                    + (showTitle ? titleSize + 12 : 0),
                bottom: 28,
                roam: true,
                nodeClick: 'zoomToNode',
                breadcrumb: { show: !compact, bottom: 2, itemStyle: { color: colors.muted },
                    textStyle: { color: colors.text } },
                label: {
                    show: style.showLabels !== false && !compact,
                    color: textColor, fontSize: labelSize,
                    overflow: 'truncate', position: 'insideTopLeft'
                },
                upperLabel: { show: style.showLabels !== false, color: textColor,
                    fontSize: labelSize, height: Math.max(18, labelSize + 8), overflow: 'truncate' },
                levels: [
                    {},
                    { itemStyle: { borderWidth: 3, gapWidth: 3 }, upperLabel: { show: style.showLabels !== false } },
                    { itemStyle: { borderWidth: 2, gapWidth: 1 } },
                    { itemStyle: { borderWidth: 1, gapWidth: 1 } }
                ]
            }]
        };
    }

    function displayError(message) {
        if (chart) { chart.clear(); }
        chartElement.hidden = true;
        emptyElement.hidden = true;
        errorElement.textContent = translate(message || 'The Treemap values could not be loaded.');
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
        if (Number(state.chart.treemap && state.chart.treemap.positiveCount) < 1) {
            if (chart) { chart.clear(); }
            chartElement.hidden = true;
            errorElement.hidden = true;
            emptyElement.textContent = translate('No positive values');
            emptyElement.hidden = false;
            return;
        }
        var wasHidden = chartElement.hidden;
        chartElement.hidden = false;
        errorElement.hidden = true;
        emptyElement.hidden = true;
        var theme = normalizeTheme(state.chart.theme || 'auto');
        if (chart && currentTheme !== theme) { chart.dispose(); chart = null; }
        if (!chart) {
            chart = window.echarts.init(chartElement, theme === 'auto' ? null : theme, { renderer: 'canvas' });
            currentTheme = theme;
        } else if (wasHidden) {
            chart.resize();
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
