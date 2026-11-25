(function () {
    'use strict';

    var bootstrap = window.SYMC_VISUALIZATION || {};
    var echartsDesign = window.SYMC_ECHARTS_DESIGN;
    var chartElement = document.getElementById('echarts-bar-history-chart');
    var warningElement = document.getElementById('echarts-bar-history-warning');
    var errorElement = document.getElementById('echarts-bar-history-error');
    var chart = null;
    var currentState = bootstrap.state || null;
    var currentTheme = null;
    var archiveRefreshFailures = 0;
    var archiveLoadError = 'The Historical Bar values could not be loaded.';
    var zoomController = window.eChartsZoom;
    var seriesSelection = window.SYMC_ECHARTS_SERIES_SELECTION.create();
    var barPatterns = window.eChartsPattern.create(function () {
        if (typeof window.requestAnimationFrame === 'function') {
            window.requestAnimationFrame(function () { render(currentState); });
        } else if (typeof window.setTimeout === 'function') {
            window.setTimeout(function () { render(currentState); }, 0);
        }
    });

    function translate(text) {
        return (bootstrap.translations || {})[text] || text;
    }

    function palette(theme) {
        var palettes = bootstrap.options && bootstrap.options.echartsThemes || {};
        return palettes[theme] || palettes.auto || {
            background: '#151619', text: '#F4F5F7', muted: '#969AA2', border: '#A5A9B0',
            accent: '#55CBB5', seriesColors: ['#55CBB5']
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

    function normalizeTheme(theme) {
        var palettes = bootstrap.options && bootstrap.options.echartsThemes || {};
        return Object.prototype.hasOwnProperty.call(palettes, theme) ? theme : 'auto';
    }

    function colorsFor(theme) {
        var colors = palette(theme);
        if (theme !== 'auto') { return colors; }
        return {
            background: resolveColor('--symc-background', colors.background),
            text: resolveColor('--symc-text', colors.text),
            muted: resolveColor('--symc-text-muted', colors.muted),
            border: resolveColor('--symc-border', colors.border),
            accent: resolveColor('--symc-accent', colors.accent),
            seriesColors: colors.seriesColors
        };
    }

    function formatValue(value, decimals, unit) {
        return Number(value).toLocaleString(undefined, {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        }) + (unit ? ' ' + unit : '');
    }

    function formatTimestamp(timestamp, format) {
        var date = new Date(Number(timestamp));
        if (format === 'time') {
            return date.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' });
        }
        if (format === 'date') {
            return date.toLocaleDateString(undefined, { day: '2-digit', month: '2-digit' });
        }
        if (format === 'date-time') {
            return date.toLocaleString(undefined, {
                day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit'
            });
        }
        return date.toLocaleString(undefined, {
            month: 'short', day: '2-digit', hour: '2-digit', minute: '2-digit'
        });
    }

    function escapeHTML(value) {
        return String(value).replace(/[&<>"']/g, function (character) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[character];
        });
    }

    function buildOption(model, theme) {
        var colors = colorsFor(theme);
        var bar = model.bar || {};
        var style = bar.style || {};
        var horizontal = style.orientation === 'horizontal';
        var series = (Array.isArray(model.series) ? model.series : []).map(function (item, index) {
            return Object.assign({}, item, { id: item.id || 'series-' + index,
                axisIndex: Number.isInteger(item.axisIndex) ? item.axisIndex : 0 });
        });
        series = window.eChartsArchiveStack.project(series);
        var selected = seriesSelection.reconcile(series);
        var axes = Array.isArray(model.axes) && model.axes.length > 0
            ? model.axes : [{ unit: String(bar.unit || ''), position: 'left', positionIndex: 0 }];
        var multi = series.length > 1;
        var showLegend = multi || series.some(function (item) { return item.initiallyVisible === false; });
        var visibleCount = new Set(series.filter(function (item) { return selected[item.id]; })
            .map(function (item) { return item.stackGroup || item.id; })).size;
        var activeAxes = axes.map(function (_, index) {
            return series.some(function (item) { return item.axisIndex === index && selected[item.id]; });
        });
        var firstActiveAxis = activeAxes.indexOf(true);
        var zoom = style.enableZoom === true;
        var headerInset = ((bootstrap.options && bootstrap.options.tileHeaderInset) || 8);
        var radius = style.roundedBars === true
            ? (style.barCornerRadius == null ? 6 : Number(style.barCornerRadius)) : 0;
        var paletteColors = Array.isArray(colors.seriesColors) && colors.seriesColors.length > 0
            ? colors.seriesColors : [colors.accent];
        var seriesColors = series.map(function (item, index) {
            return item.color || paletteColors[index % paletteColors.length];
        });
        var titleColor = style.titleColor || colors.text;
        var legendColor = style.legendColor || colors.text;
        var axisColor = style.axisColor || colors.text;
        var axisLineColor = style.axisColor || colors.border;
        var valueColor = style.valueColor || colors.text;
        var gridColor = style.gridColor || colors.border;
        var titleFontSize = Math.round(18 * (Number(style.titleFontSizePercent) || 100) / 100);
        var legendFontSize = echartsDesign.fontSizePercent(style.legendFontSizePercent, 12);
        var axisFontSize = Math.round(12 * (Number(style.axisFontSizePercent) || 100) / 100);
        var valueFontSize = Math.round(12 * (Number(style.valueFontSizePercent) || 100) / 100);
        var axisCounts = { left: 0, right: 0 };
        var normalizedAxes = axes.map(function (axis, index) {
            var position = axis.position === 'right' ? 'right' : 'left';
            var positionIndex = axisCounts[position];
            if (activeAxes[index]) { axisCounts[position] += 1; }
            return { axis: axis, position: position, positionIndex: positionIndex, index: index };
        });
        var chartWidth = Math.max(320, Number(chartElement.clientWidth) || 750);
        var chartHeight = Math.max(160, Number(chartElement.clientHeight) || 420);
        var compact = chartHeight < 280;
        var automaticSliderSize = zoomController.sliderThickness(compact);
        var zoomSliderSize = zoomController.sliderThickness(compact, style.zoomSliderSize);
        var displayedTitleSize = compact ? Math.min(titleFontSize, 14) : titleFontSize;
        var displayedLegendSize = compact
            ? echartsDesign.fontSizePercent(style.legendFontSizePercent, 10) : legendFontSize;
        var gridTop = headerInset + (showLegend ? (bar.title ? (compact ? 39 : 78) : (compact ? 28 : 46))
            : (bar.title ? (compact ? 32 : 52) : (compact ? 12 : 16)))
            + (bar.title ? Math.max(0, displayedTitleSize - (compact ? 14 : 18)) : 0)
            + (showLegend ? Math.max(0, displayedLegendSize - (compact ? 10 : 12)) : 0);
        var maximumSideCount = Math.max(axisCounts.left, axisCounts.right);
        var axisOffsetStep, leftMargin, rightMargin, gridBottom;
        if (horizontal) {
            var labelTime = model.range && Number(model.range.endTimestamp) * 1000 || Date.now();
            var timeLabelLength = formatTimestamp(labelTime, bar.timeAxisLabelFormat || 'auto').length;
            leftMargin = Math.min(chartWidth * 0.38, Math.max(48, 20 + timeLabelLength * axisFontSize * 0.65));
            rightMargin = zoom ? 48 + zoomSliderSize - automaticSliderSize : 24;
            // Keep space for the bars even when several units share a short tile.
            var axisMargin = Math.min(axisFontSize * 2 + 14, (chartHeight - gridTop) * 0.28);
            var extraAxes = Math.max(0, axisCounts.left - 1) + Math.max(0, axisCounts.right - 1);
            var topMargin = axisCounts.right ? axisMargin : 0;
            gridBottom = axisCounts.left ? axisMargin : 16;
            var offsetBudget = Math.max(0, (chartHeight - gridTop) * 0.65 - topMargin - gridBottom);
            axisOffsetStep = echartsDesign.valueAxisSpacing(style.valueAxisSpacing, axisFontSize * 2.8, offsetBudget, extraAxes);
            gridTop += topMargin + axisOffsetStep * Math.max(0, axisCounts.right - 1);
            gridBottom += axisOffsetStep * Math.max(0, axisCounts.left - 1);
        } else {
            var maximumAxisMargin = Math.max(58, Math.min(190, chartWidth * 0.32));
            axisOffsetStep = echartsDesign.valueAxisSpacing(style.valueAxisSpacing, 54, maximumAxisMargin - 58, maximumSideCount - 1);
            leftMargin = axisCounts.left > 0 ? 52 + axisOffsetStep * (axisCounts.left - 1) : 28;
            rightMargin = axisCounts.right > 0 ? 52 + axisOffsetStep * (axisCounts.right - 1) : 28;
            gridBottom = zoom ? (compact ? 42 : 70) : (compact ? 32 : 58);
            if (zoom) {
                var availableExtra = Math.max(0, Math.floor(chartHeight - gridTop - gridBottom - 24));
                zoomSliderSize = Math.min(zoomSliderSize, automaticSliderSize + availableExtra);
                gridBottom += zoomSliderSize - automaticSliderSize;
            }
        }
        var plotWidth = chartWidth - leftMargin - rightMargin;
        var plotHeight = chartHeight - gridTop - gridBottom - 20;
        var timeTickCount = Math.max(2, Math.floor(horizontal ? plotHeight / (axisFontSize * 2) : plotWidth / (axisFontSize * 10)));
        var valueTickCount = Math.max(2, Math.floor(horizontal ? plotWidth / (axisFontSize * 6) : plotHeight / (axisFontSize * 2)));
        var dataZoom = zoomController.timeAxisOptions(zoom, bootstrap.mode, compact ? 18 : 22, compact, horizontal, zoomSliderSize);
        if (horizontal && zoom) {
            dataZoom[1].top = gridTop;
            dataZoom[1].bottom = gridBottom;
        }

        return {
            backgroundColor: echartsDesign.chartBackground(bootstrap, model, colors.background),
            ...echartsDesign.animationOptions(style, {
                enabled: true, initialDuration: 350, updateDuration: 500
            }),
            aria: {
                enabled: true,
                description: series.map(function (item) { return String(item.label || ''); }).join(', '),
                decal: { show: false }
            },
            title: {
                show: Boolean(bar.title),
                text: String(bar.title || ''),
                left: 'center',
                top: headerInset,
                textStyle: { color: titleColor, fontSize: displayedTitleSize }
            },
            legend: {
                show: showLegend,
                type: 'scroll', left: 12, right: 12,
                selected: Object.assign({}, selected),
                scrollDataIndex: seriesSelection.scrollDataIndex(),
                selector: multi ? [
                    { type: 'all', title: translate('All') },
                    { type: 'inverse', title: translate('Invert') }
                ] : [],
                selectorLabel: { color: legendColor, fontSize: displayedLegendSize },
                pageTextStyle: { color: legendColor },
                pageIconColor: legendColor,
                pageIconInactiveColor: colors.muted,
                tooltip: { show: true, confine: true, formatter: function (parameters) {
                    var item = series.find(function (entry) { return entry.id === parameters.name; });
                    return item ? escapeHTML(item.label) + (item.unit ? ' (' + escapeHTML(item.unit) + ')' : '') : '';
                } },
                top: headerInset + (bar.title ? (compact ? 21 : 34) : (compact ? 2 : 4))
                    + (bar.title ? Math.max(0, displayedTitleSize - (compact ? 14 : 18)) : 0),
                textStyle: { color: legendColor, fontSize: displayedLegendSize,
                    width: Math.max(40, Math.min(220, chartWidth * 0.32)), overflow: 'truncate' },
                data: series.map(function (item, index) { return item.id || 'series-' + index; }),
                formatter: function (name) {
                    var item = series.find(function (candidate, index) {
                        return (candidate.id || 'series-' + index) === name;
                    });
                    return item ? String(item.label || '') : '';
                }
            },
            grid: {
                top: gridTop,
                right: rightMargin,
                bottom: gridBottom,
                left: leftMargin,
                // Keep the inverse time axis stable when dataZoom filters its extent.
                containLabel: !horizontal,
                ...(horizontal ? { outerBoundsMode: 'none' } : {})
            },
            tooltip: {
                trigger: 'axis',
                confine: true,
                extraCssText: 'max-width:calc(100vw - 24px);max-height:70vh;overflow:auto;white-space:normal;overflow-wrap:anywhere;',
                axisPointer: { type: 'shadow' },
                formatter: function (parameters) {
                    var rows = (Array.isArray(parameters) ? parameters : [parameters]).filter(function (item) {
                        return item && Array.isArray(item.value);
                    });
                    if (rows.length === 0) { return ''; }
                    return [formatTimestamp(rows[0].value[0], 'date-time')].concat(rows.map(function (item) {
                        var source = series.find(function (entry) { return entry.id === item.seriesId; })
                            || series[item.seriesIndex] || {};
                        var decimals = Math.max(0, Math.min(6, Number(source.decimals ?? bar.decimals) || 0));
                        return String(item.marker || '') + escapeHTML(source.label || '') + ': '
                            + escapeHTML(formatValue(item.value[1], decimals, source.unit ?? bar.unit));
                    })).join('<br>');
                }
            },
            dataZoom: dataZoom,
            [horizontal ? 'yAxis' : 'xAxis']: {
                type: 'time',
                inverse: horizontal,
                splitNumber: timeTickCount,
                min: model.range && Number.isFinite(Number(model.range.startTimestamp))
                    ? Number(model.range.startTimestamp) * 1000 : null,
                max: model.range && Number.isFinite(Number(model.range.endTimestamp))
                    ? Number(model.range.endTimestamp) * 1000 : null,
                axisLabel: {
                    color: axisColor,
                    fontSize: axisFontSize,
                    hideOverlap: true,
                    ...(horizontal ? { width: leftMargin - 14, overflow: 'truncate' } : {}),
                    formatter: function (value) { return formatTimestamp(value, bar.timeAxisLabelFormat || 'auto'); }
                },
                axisLine: { onZero: !horizontal, lineStyle: { color: axisLineColor } },
                axisTick: { lineStyle: { color: axisLineColor } },
                splitLine: { show: false }
            },
            [horizontal ? 'xAxis' : 'yAxis']: (function () {
                var valueAxes = normalizedAxes.map(function (entry) {
                    var axis = entry.axis;
                    var firstSeries = series.findIndex(function (item) {
                        return item.axisIndex === entry.index && selected[item.id];
                    });
                    var color = multi && !style.axisColor && firstSeries >= 0
                        ? seriesColors[firstSeries] : axisColor;
                    return {
                        show: activeAxes[entry.index],
                        type: 'value',
                        splitNumber: valueTickCount,
                        name: String(axis.unit || ''),
                        ...(Number.isFinite(axis.minimum) && Number.isFinite(axis.maximum)
                            ? { min: axis.minimum, max: axis.maximum } : {}),
                        position: horizontal ? (entry.position === 'left' ? 'bottom' : 'top') : entry.position,
                        offset: entry.positionIndex * axisOffsetStep,
                        ...(horizontal ? { nameLocation: 'middle', nameGap: axisFontSize + 12 } : {}),
                        nameTextStyle: { color: color, fontSize: axisFontSize },
                        axisLabel: { color: color, fontSize: axisFontSize, hideOverlap: true },
                        axisLine: { show: true, lineStyle: { color: multi ? color : axisLineColor } },
                        axisTick: { show: true, lineStyle: { color: multi ? color : axisLineColor } },
                        splitLine: {
                            show: entry.index === firstActiveAxis && style.showGrid !== false,
                            lineStyle: { color: gridColor, opacity: style.gridColor ? 1 : 0.22 }
                        }
                    };
                });
                return multi ? valueAxes : valueAxes[0];
            }()),
            series: series.map(function (item, index) {
                var color = seriesColors[index];
                var fill = style.barFillMode === 'gradient' ? {
                    type: 'linear', x: 0, y: horizontal ? 0 : 1, x2: horizontal ? 1 : 0, y2: 0,
                    colorStops: [
                        { offset: 0, color: color },
                        { offset: 1, color: style.barGradientColor || colors.background }
                    ]
                } : color;
                if (style.barFillMode === 'svg') {
                    fill = barPatterns.resolve(style.barPatternImage, style.barSVGSizePercent,
                        style.barPatternAspectRatio) || color;
                }
                var decimals = Math.max(0, Math.min(6, Number(item.decimals ?? bar.decimals) || 0));
                return {
                    id: item.id || 'series-' + index,
                    name: item.id || 'series-' + index,
                    type: 'bar',
                    stack: item.stackGroup ? 'stack-' + item.stackGroup : undefined,
                    [horizontal ? 'xAxisIndex' : 'yAxisIndex']: item.axisIndex,
                    encode: { x: horizontal ? 1 : 0, y: horizontal ? 0 : 1 },
                    barGap: '20%',
                    barMaxWidth: visibleCount > 1
                        ? Math.max(0.5, Math.round(480 * Math.max(20, Math.min(100, Number(style.barWidthPercent) || 70)) / 100 / visibleCount) / 10)
                        : Math.max(8, Math.round(48 * Math.max(20, Math.min(100, Number(style.barWidthPercent) || 70)) / 100)),
                    itemStyle: {
                        color: fill,
                        opacity: echartsDesign.opacityFromPercent(style.barOpacityPercent, 100),
                        borderRadius: horizontal ? [0, radius, radius, 0] : [radius, radius, 0, 0]
                    },
                    label: {
                        show: style.showValues === true,
                        ...echartsDesign.valueLabel(style, horizontal ? 'right' : 'top',
                            ['top', 'bottom', 'left', 'right', 'inside', 'insideTop', 'insideBottom',
                                'insideLeft', 'insideRight'], 5),
                        color: valueColor,
                        fontSize: valueFontSize,
                        formatter: function (parameters) {
                            return Array.isArray(parameters.value)
                                ? formatValue(parameters.value[1], decimals, item.unit ?? bar.unit) : '';
                        }
                    },
                    data: (item.points || []).map(function (point) {
                        return [Number(point[0]) * 1000, Number(point[1])];
                    })
                };
            })
        };
    }

    function displayError(message) {
        if (chart) { chart.clear(); }
        chartElement.hidden = true;
        warningElement.hidden = true;
        errorElement.textContent = translate(message || 'The Historical Bar values could not be loaded.');
        errorElement.hidden = false;
    }

    function render(state) {
        if (state && state.status === 'error' && state.error === archiveLoadError
            && chart && currentState && currentState.status === 'ready') {
            archiveRefreshFailures++;
            if (archiveRefreshFailures > 1) {
                warningElement.textContent = translate(
                    'Historical Bar data could not be refreshed. Showing the last available values.'
                );
                warningElement.hidden = false;
            }
            return;
        }
        archiveRefreshFailures = 0;
        var previousRange = currentState && currentState.chart && currentState.chart.range;
        var nextRange = state && state.chart && state.chart.range;
        var savedZoom = zoomController.capture(chart, previousRange, nextRange);
        seriesSelection.capture(chart);
        currentState = state;
        if (!state || state.status !== 'ready' || !state.chart) {
            displayError(state && state.error);
            return;
        }
        if (!window.echarts || typeof window.echarts.init !== 'function') {
            displayError('Apache ECharts could not be initialized.');
            return;
        }
        var style = state.chart.bar && state.chart.bar.style || {};
        if (style.barFillMode === 'svg') {
            barPatterns.ensure(style.barPatternImage, style.barSVGSizePercent, style.barPatternAspectRatio);
        }
        errorElement.hidden = true;
        chartElement.hidden = false;
        warningElement.textContent = translate('The selected raw range was truncated by the point budget.');
        warningElement.hidden = state.chart.truncated !== true;
        var theme = normalizeTheme(state.chart.theme || 'auto');
        if (chart && currentTheme !== theme) {
            chart.dispose();
            chart = null;
        }
        if (!chart) {
            chart = window.echarts.init(chartElement, theme === 'auto' ? null : theme, { renderer: 'canvas' });
            currentTheme = theme;
            if (typeof chart.on === 'function') {
                ['legendselectchanged', 'legendselectall', 'legendinverseselect'].forEach(function (eventName) {
                    chart.on(eventName, function () { if (currentState) { render(currentState); } });
                });
            }
        }
        zoomController.apply(chart, buildOption(state.chart, theme), savedZoom);
    }

    window.handleMessage = function (message) {
        var state = echartsDesign.parseVisualizationState(message);
        if (state) {
            if (cancelStartup) { cancelStartup(); }
            render(state);
        }
    };
    if (window.ResizeObserver) {
        new ResizeObserver(function () {
            if (echartsDesign.resizeChartIfNeeded(chart, chartElement)) {
                render(currentState);
            }
        }).observe(chartElement);
    } else {
        window.addEventListener('resize', function () {
            if (echartsDesign.resizeChartIfNeeded(chart, chartElement)) {
                render(currentState);
            }
        });
    }
    if (bootstrap.mode === 'ipsview') {
        zoomController.attachIPSViewWheel(chartElement, function () { return chart; }, function () {
            return currentState && currentState.status === 'ready'
                && currentState.chart && currentState.chart.bar
                && currentState.chart.bar.style
                && currentState.chart.bar.style.enableZoom === true;
        }, function () {
            return currentState.chart.bar.style.orientation === 'horizontal'
                ? { vertical: true, inverse: true } : null;
        });
    }
    window.addEventListener('beforeunload', function () {
        if (cancelStartup) { cancelStartup(); }
        if (chart) { chart.dispose(); }
    });
    var cancelStartup = window.SYMC_ECHARTS_STARTUP(bootstrap, render, function () {
        displayError(archiveLoadError);
    });
}());
