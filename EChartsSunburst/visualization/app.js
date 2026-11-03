(function () {
    'use strict';

    var bootstrap = window.SYMC_VISUALIZATION || {};
    var chartElement = document.getElementById('echarts-sunburst-chart');
    var errorElement = document.getElementById('echarts-sunburst-error');
    var emptyElement = document.getElementById('echarts-sunburst-empty');
    var warningElement = document.getElementById('echarts-sunburst-warning');
    var design = window.SYMC_ECHARTS_DESIGN;
    var hierarchyValues = window.SYMC_ECHARTS_HIERARCHY_VALUES;
    var chart = null;
    var currentTheme = null;
    var currentState = bootstrap.state || null;

    function translate(value) { return (bootstrap.translations || {})[value] || value; }
    var retainOnTransientError = design.createTransientFailureRetainer(
        chartElement, warningElement, translate, 'The Sunburst values could not be loaded.'
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
        return (Array.isArray(data) ? data : []).map(function (group, index) {
            var chosen = style.groupColors || {};
            var color = Object.prototype.hasOwnProperty.call(chosen, group.name) && /^#[0-9a-f]{6}$/i.test(chosen[group.name])
                ? chosen[group.name] : seriesColors[index % seriesColors.length];
            var labelColor = style.labelColor || design.readableTextColor(color, colors.text, colors.background);
            function withLabels(node) {
                return Object.assign({}, node, {
                    label: { color: labelColor },
                    children: Array.isArray(node.children) ? node.children.map(withLabels) : undefined
                });
            }
            return Object.assign({}, withLabels(group), { itemStyle: { color: color } });
        });
    }

    function buildOption(model, theme) {
        var colors = colorsFor(theme);
        var sunburst = model.sunburst || {};
        var style = sunburst.style || {};
        var width = Math.max(1, chartElement.clientWidth || 1);
        var height = Math.max(1, chartElement.clientHeight || 1);
        var compact = width < 350 || height < 260;
        var titleSize = design.fontSizePercent(style.titleFontSizePercent, 18);
        var titleLayout = design.chartTitleLayout(bootstrap, sunburst.title, titleSize);
        var notice = sunburst.normalized === true ? translate('Normalized weights, not physical totals.') : '';
        var noticeSize = design.fontSizePercent(style.titleFontSizePercent, 12);
        if (notice) { titleLayout.contentTop += Math.ceil(notice.length * noticeSize * 0.65 / Math.max(80, width - 32)) * (noticeSize + 3) + 8; }
        var showTitle = titleLayout.show;
        var contentTop = titleLayout.contentTop;
        var contentHeight = Math.max(1, height - contentTop - 8);
        var outerRadius = Math.max(50, Math.min(95, Number(style.outerRadiusPercent) || 85));
        var fittedOuterRadius = Math.min(outerRadius,
            contentHeight * 100 / Math.min(width, height));
        var innerRadius = Math.min(Math.max(0, Math.min(50, Number(style.innerRadiusPercent) || 0)),
            Math.max(0, fittedOuterRadius - 1));
        var labelSize = design.fontSizePercent(style.labelFontSizePercent, 12);
        var showLabels = style.showLabels !== false && !compact;
        var ringLabels = [
            style.showInnerRingLabels !== false,
            style.showMiddleRingLabels !== false,
            style.showOuterRingLabels !== false
        ];
        var ringSizes = [
            style.innerRingLabelFontSizePercent,
            style.middleRingLabelFontSizePercent,
            style.outerRingLabelFontSizePercent
        ];
        var levels = [{}].concat(Array.from({ length: Math.max(3, Math.min(8, Number(sunburst.depth) || 3)) }, function (_, depth) {
            var index = Math.min(2, depth);
            return { label: {
                show: showLabels && ringLabels[index],
                fontSize: Number(ringSizes[index]) > 0
                    ? design.fontSizePercent(ringSizes[index], 12) : labelSize
            } };
        }));
        var backgroundColor = bootstrap.mode === 'ipsview' && bootstrap.options.adaptToBackground === true
            ? 'transparent' : colors.background;
        return {
            backgroundColor: backgroundColor,
            ...design.animationOptions(style, { enabled: true, initialDuration: 350, updateDuration: 500 }),
            aria: { enabled: true, decal: { show: false } },
            title: {
                show: showTitle || Boolean(notice), text: String(sunburst.title || ''), left: 'center', top: titleLayout.top,
                subtext: notice, subtextStyle: { color: colors.text, fontSize: noticeSize, lineHeight: noticeSize + 3,
                    width: Math.max(80, width - 32), overflow: 'break' },
                textStyle: { color: style.titleColor || colors.text, fontSize: titleSize }
            },
            tooltip: {
                show: style.showTooltip !== false,
                trigger: 'item', confine: true,
                extraCssText: 'max-width:min(520px,80vw);white-space:normal;overflow-wrap:anywhere',
                formatter: function (parameter) {
                    return hierarchyValues.tooltip(parameter, sunburst.normalized === true, translate);
                }
            },
            series: [{
                id: 'sunburst', type: 'sunburst', data: nodesFor(sunburst.data, colors, style),
                center: ['50%', contentTop + contentHeight / 2],
                radius: [innerRadius + '%', fittedOuterRadius + '%'],
                startAngle: Number.isFinite(Number(style.startAngle)) ? Number(style.startAngle) : 90,
                clockwise: style.clockwise !== false,
                sort: style.sortMode === 'asc' || style.sortMode === 'desc' ? style.sortMode : null,
                nodeClick: style.clickToRoot === false ? false : 'rootToNode',
                emphasis: { focus: 'ancestor' },
                levels: levels,
                itemStyle: {
                    borderColor: style.borderColor || backgroundColor,
                    borderWidth: Number(style.borderWidth) >= 0 ? Number(style.borderWidth) : 1,
                    borderRadius: Number(style.borderRadius) >= 0 ? Number(style.borderRadius) : 0
                },
                label: {
                    show: showLabels,
                    color: style.labelColor || colors.text,
                    fontSize: labelSize,
                    rotate: style.labelRotation === 'horizontal' ? 0
                        : style.labelRotation === 'tangential' ? 'tangential' : 'radial',
                    minAngle: Number(style.labelMinAngle) >= 0 ? Number(style.labelMinAngle) : 0,
                    overflow: 'truncate',
                    formatter: function (parameter) {
                        var name = String(parameter.name || '');
                        var text = name;
                        if (style.showValues === true && !(parameter.data && Array.isArray(parameter.data.children))) {
                            text += '\n' + hierarchyValues.valueText(Object.assign({ value: parameter.value }, parameter.data || {}));
                        }
                        // Sunburst's bundle has no LabelLayout feature. Bound deep labels using
                        // the native node geometry, which also changes after a root-to-node action.
                        if ((Number(sunburst.depth) || 3) <= 3 || !chart || typeof chart.getModel !== 'function') { return text; }
                        var series = chart.getModel().getSeriesByIndex(0);
                        var node = series && series.getData().tree.getNodeByDataIndex(parameter.dataIndex);
                        var layout = node && node.getLayout();
                        if (!layout || !window.echarts.format) { return text; }
                        var font = node.getModel().getModel('label').getFont();
                        var textHeight = window.echarts.format.getTextRect(text, font).height;
                        var angle = Math.abs(layout.endAngle - layout.startAngle);
                        var radialRoom = Math.max(0, layout.r - layout.r0 - 4);
                        var tangentRoom = Math.max(0, (layout.r + layout.r0)
                            * Math.sin(Math.min(Math.PI, angle) / 2) - 4);
                        var delta = style.labelRotation === 'horizontal' ? (layout.startAngle + layout.endAngle) / 2
                            : style.labelRotation === 'tangential' ? Math.PI / 2 : 0;
                        var radialFactor = Math.abs(Math.cos(delta));
                        var tangentFactor = Math.abs(Math.sin(delta));
                        if (textHeight * tangentFactor > radialRoom || textHeight * radialFactor > tangentRoom) { return ' '; }
                        var maxWidth = Math.min(
                            radialFactor > 0.0001 ? (radialRoom - textHeight * tangentFactor) / radialFactor : Infinity,
                            tangentFactor > 0.0001 ? (tangentRoom - textHeight * radialFactor) / tangentFactor : Infinity
                        );
                        // An empty string would make Sunburst restore the unabridged node name.
                        return window.echarts.format.truncateText(text, Math.max(0, maxWidth), font, '…') || ' ';
                    }
                }
            }]
        };
    }

    function displayError(message) {
        if (chart) { chart.clear(); }
        chartElement.hidden = true;
        emptyElement.hidden = true;
        errorElement.textContent = translate(message || 'The Sunburst values could not be loaded.');
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
        if (Number(state.chart.sunburst && state.chart.sunburst.positiveCount) < 1) {
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
        design.updateHierarchyChart(chart, buildOption(state.chart, theme), 'sunburstRootToNode',
            (state.chart.sunburst.style || {}).clickToRoot !== false);
    }

    window.handleMessage = function (message) {
        var state = design.parseVisualizationState(message);
        if (state) { render(state); }
    };
    if (window.ResizeObserver) {
        new ResizeObserver(function () {
            if (!design.resizeChartIfNeeded(chart, chartElement)) { return; }
            if (currentState && currentState.status === 'ready') {
                design.updateHierarchyChart(chart, buildOption(currentState.chart, currentTheme || 'auto'),
                    'sunburstRootToNode', (currentState.chart.sunburst.style || {}).clickToRoot !== false);
            }
        }).observe(chartElement);
    } else {
        window.addEventListener('resize', function () { if (chart) { chart.resize(); } });
    }
    window.addEventListener('beforeunload', function () { if (chart) { chart.dispose(); } });
    render(currentState);
}());
