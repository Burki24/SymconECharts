(function () {
    'use strict';

    var bootstrap = window.SYMC_VISUALIZATION || {};
    var chartElement = document.getElementById('echarts-treemap-chart');
    var errorElement = document.getElementById('echarts-treemap-error');
    var emptyElement = document.getElementById('echarts-treemap-empty');
    var warningElement = document.getElementById('echarts-treemap-warning');
    var design = window.SYMC_ECHARTS_DESIGN;
    var hierarchyValues = window.SYMC_ECHARTS_HIERARCHY_VALUES;
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

    function nodesFor(data, colors, style) {
        var seriesColors = colors.seriesColors && colors.seriesColors.length ? colors.seriesColors : [colors.accent];
        var frameColor = style.frameColor || '';
        return (Array.isArray(data) ? data : []).map(function (group, index) {
            var chosen = style.groupColors || {};
            var color = Object.prototype.hasOwnProperty.call(chosen, group.name) && /^#[0-9a-f]{6}$/i.test(chosen[group.name])
                ? chosen[group.name] : seriesColors[index % seriesColors.length];
            var readable = style.labelColor || design.readableTextColor(color, colors.text, colors.background);
            var groupColor = style.groupLabelColor || readable;
            var subgroupColor = style.subgroupLabelColor || readable;
            var valueSize = design.fontSizePercent(style.labelFontSizePercent, 12);
            var groupSize = design.fontSizePercent(style.groupLabelFontSizePercent || style.labelFontSizePercent, 12);
            var subgroupSize = design.fontSizePercent(style.subgroupLabelFontSizePercent || style.labelFontSizePercent, 12);
            function colorChildren(children) {
                return (children || []).map(function (child) {
                    var isSubgroup = Array.isArray(child.children);
                    var label = { color: isSubgroup ? subgroupColor : readable,
                        fontSize: isSubgroup ? subgroupSize : valueSize };
                    if (!isSubgroup && style.showValues === true) {
                        label.formatter = function (params) {
                            return params.name + '\n' + hierarchyValues.valueText(Object.assign({ value: params.value }, params.data || {}));
                        };
                    }
                    return Object.assign({}, child, isSubgroup || frameColor
                        ? { itemStyle: { borderColor: frameColor || color } } : {}, {
                        label: label,
                        upperLabel: { show: isSubgroup && style.showLabels !== false,
                            color: subgroupColor, fontSize: subgroupSize, height: Math.max(18, subgroupSize + 8) },
                        children: isSubgroup ? colorChildren(child.children) : undefined
                    });
                });
            }
            return Object.assign({}, group, {
                itemStyle: { color: color, borderColor: frameColor || color },
                label: { color: groupColor, fontSize: groupSize },
                upperLabel: { show: style.showLabels !== false, color: groupColor, fontSize: groupSize,
                    height: Math.max(18, groupSize + 8) },
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
        var titleSize = design.fontSizePercent(style.titleFontSizePercent, 18);
        var titleLayout = design.chartTitleLayout(bootstrap, treemap.title, titleSize);
        var notice = treemap.normalized === true ? translate('Normalized weights, not physical totals.') : '';
        var noticeSize = design.fontSizePercent(style.titleFontSizePercent, 12);
        if (notice) { titleLayout.contentTop += Math.ceil(notice.length * noticeSize * 0.65 / Math.max(80, width - 32)) * (noticeSize + 3) + 8; }
        var showTitle = titleLayout.show;
        var labelSize = design.fontSizePercent(style.labelFontSizePercent, 12);
        var textColor = style.labelColor || colors.text;
        var backgroundColor = bootstrap.mode === 'ipsview' && bootstrap.options.adaptToBackground === true
            ? 'transparent' : colors.background;
        return {
            backgroundColor: backgroundColor,
            ...design.animationOptions(style, { enabled: true, initialDuration: 350, updateDuration: 500 }),
            aria: { enabled: true, decal: { show: false } },
            title: {
                show: showTitle || Boolean(notice), text: String(treemap.title || ''), left: 'center', top: titleLayout.top,
                subtext: notice, subtextStyle: { color: colors.text, fontSize: noticeSize, lineHeight: noticeSize + 3,
                    width: Math.max(80, width - 32), overflow: 'break' },
                textStyle: { color: style.titleColor || colors.text, fontSize: titleSize }
            },
            tooltip: {
                trigger: 'item', confine: true,
                extraCssText: 'max-width:min(520px,80vw);white-space:normal;overflow-wrap:anywhere',
                formatter: function (parameter) {
                    return hierarchyValues.tooltip(parameter, treemap.normalized === true, translate);
                }
            },
            series: [{
                id: 'treemap', type: 'treemap', data: nodesFor(treemap.data, colors, style),
                left: 8, right: 8,
                top: titleLayout.contentTop,
                bottom: style.showBreadcrumb === false ? 8 : 28,
                roam: style.allowPanZoom !== false,
                nodeClick: style.clickToZoom === false ? false : 'zoomToNode',
                ...(Number(treemap.depth) > 3 && style.clickToZoom !== false ? { leafDepth: 3 } : {}),
                breadcrumb: { show: style.showBreadcrumb !== false && (!compact || Number(treemap.depth) > 3), bottom: 2,
                    itemStyle: { color: colors.muted },
                    textStyle: { color: colors.text } },
                label: {
                    show: style.showLabels !== false && !compact,
                    color: textColor, fontSize: labelSize,
                    overflow: 'truncate', position: 'insideTopLeft'
                },
                upperLabel: { show: style.showLabels !== false, color: textColor,
                    fontSize: labelSize, height: Math.max(18, labelSize + 8), overflow: 'truncate' },
                levels: [
                    { itemStyle: { gapWidth: style.groupGapWidth ?? 0,
                        borderColor: style.frameColor || backgroundColor } },
                    { itemStyle: { borderWidth: style.level1BorderWidth ?? 3,
                        gapWidth: style.level1GapWidth ?? 3,
                        ...(style.frameColor ? { borderColor: style.frameColor } : {}) },
                    upperLabel: { show: style.showLabels !== false } },
                    { itemStyle: { borderWidth: style.level2BorderWidth ?? 2,
                        gapWidth: style.level2GapWidth ?? 1,
                        ...(style.frameColor ? { borderColor: style.frameColor } : {}) } },
                    ...Array.from({ length: Math.max(1, Math.min(8, Number(treemap.depth) || 3) - 2) }, function () {
                        return { itemStyle: { borderWidth: style.level3BorderWidth ?? 1,
                            ...(style.frameColor ? { borderColor: style.frameColor } : {}) },
                        upperLabel: { show: style.showLabels !== false } };
                    })
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
        updateChart(state.chart, theme);
    }

    function updateChart(model, theme) {
        design.updateHierarchyChart(chart, buildOption(model, theme), 'treemapRootToNode',
            (model.treemap && model.treemap.style || {}).clickToZoom !== false);
    }

    window.handleMessage = function (message) {
        var state = design.parseVisualizationState(message);
        if (state) { render(state); }
    };
    if (window.ResizeObserver) {
        new ResizeObserver(function () {
            if (!design.resizeChartIfNeeded(chart, chartElement)) { return; }
            if (currentState && currentState.status === 'ready') {
                updateChart(currentState.chart, currentTheme || 'auto');
            }
        }).observe(chartElement);
    } else {
        window.addEventListener('resize', function () { if (chart) { chart.resize(); } });
    }
    window.addEventListener('beforeunload', function () { if (chart) { chart.dispose(); } });
    render(currentState);
}());
