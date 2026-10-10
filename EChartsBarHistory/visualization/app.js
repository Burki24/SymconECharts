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
    var zoomController = window.SymconEChartsZoom;
    var barPatterns = window.SymconEChartsPattern.create(function () {
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
        var series = Array.isArray(model.series) ? model.series : [];
        var axes = Array.isArray(model.axes) && model.axes.length > 0
            ? model.axes : [{ unit: String(bar.unit || ''), position: 'left', positionIndex: 0 }];
        var multi = series.length > 1;
        var zoom = style.enableZoom === true;
        var headerInset = bootstrap.mode === 'symcon' && bootstrap.options.tileHeaderVisible !== false ? 58 : 8;
        var radius = style.roundedBars === true
            ? (style.barCornerRadius == null ? 6 : Number(style.barCornerRadius)) : 0;
        var paletteColors = Array.isArray(colors.seriesColors) && colors.seriesColors.length > 0
            ? colors.seriesColors : [colors.accent];
        var seriesColors = series.map(function (item, index) {
            return item.color || paletteColors[index % paletteColors.length];
        });
        var titleColor = style.titleColor || colors.text;
        var axisColor = style.axisColor || colors.text;
        var axisLineColor = style.axisColor || colors.border;
        var valueColor = style.valueColor || colors.text;
        var gridColor = style.gridColor || colors.border;
        var titleFontSize = Math.round(18 * (Number(style.titleFontSizePercent) || 100) / 100);
        var axisFontSize = Math.round(12 * (Number(style.axisFontSizePercent) || 100) / 100);
        var valueFontSize = Math.round(12 * (Number(style.valueFontSizePercent) || 100) / 100);
        var axisCounts = { left: 0, right: 0 };
        var normalizedAxes = axes.map(function (axis, index) {
            var position = axis.position === 'right' ? 'right' : 'left';
            var positionIndex = Number.isInteger(axis.positionIndex) && axis.positionIndex >= 0
                ? axis.positionIndex : axisCounts[position];
            axisCounts[position] = Math.max(axisCounts[position], positionIndex + 1);
            return { axis: axis, position: position, positionIndex: positionIndex, index: index };
        });
        var chartWidth = Math.max(320, Number(chartElement.clientWidth) || 750);
        var chartHeight = Math.max(160, Number(chartElement.clientHeight) || 420);
        var compact = chartHeight < 280;
        var maximumAxisMargin = Math.max(58, Math.min(190, chartWidth * 0.32));
        var maximumSideCount = Math.max(axisCounts.left, axisCounts.right);
        var axisOffsetStep = maximumSideCount > 1
            ? Math.min(54, (maximumAxisMargin - 58) / (maximumSideCount - 1)) : 0;
        var leftMargin = axisCounts.left > 0 ? 52 + axisOffsetStep * (axisCounts.left - 1) : 28;
        var rightMargin = axisCounts.right > 0 ? 52 + axisOffsetStep * (axisCounts.right - 1) : 28;
        var gridTop = headerInset + (multi ? (bar.title ? (compact ? 39 : 78) : (compact ? 28 : 46))
            : (bar.title ? (compact ? 32 : 52) : (compact ? 12 : 16)));
        var gridBottom = zoom ? (compact ? 42 : 70) : (compact ? 32 : 58);
        var plotWidth = chartWidth - leftMargin - rightMargin;
        var timeTickCount = Math.max(2, Math.floor(plotWidth / (axisFontSize * 10)));
        var valueTickCount = Math.max(2, Math.floor((chartHeight - gridTop - gridBottom - 20) / (axisFontSize * 2)));
        var dataZoom = zoomController.options(zoom, bootstrap.mode, compact ? 18 : 22);
        if (dataZoom.length > 1) {
            dataZoom[1].height = compact ? 8 : 12;
            if (compact) {
                dataZoom[1].showDetail = false;
            }
        }

        return {
            backgroundColor: bootstrap.mode === 'ipsview' && bootstrap.options.adaptToBackground === true
                ? 'transparent' : colors.background,
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
                textStyle: { color: titleColor, fontSize: compact ? Math.min(titleFontSize, 14) : titleFontSize }
            },
            legend: {
                show: multi,
                top: headerInset + (bar.title ? (compact ? 21 : 34) : (compact ? 2 : 4)),
                textStyle: { color: colors.text, fontSize: compact ? 10 : 12 },
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
                containLabel: true
            },
            tooltip: {
                trigger: 'axis',
                axisPointer: { type: 'shadow' },
                formatter: function (parameters) {
                    var rows = (Array.isArray(parameters) ? parameters : [parameters]).filter(function (item) {
                        return item && Array.isArray(item.value);
                    });
                    if (rows.length === 0) { return ''; }
                    return [formatTimestamp(rows[0].value[0], 'date-time')].concat(rows.map(function (item) {
                        var source = series[item.seriesIndex] || {};
                        var decimals = Math.max(0, Math.min(6, Number(source.decimals ?? bar.decimals) || 0));
                        return String(item.marker || '') + escapeHTML(source.label || '') + ': '
                            + escapeHTML(formatValue(item.value[1], decimals, source.unit ?? bar.unit));
                    })).join('<br>');
                }
            },
            dataZoom: dataZoom,
            xAxis: {
                type: 'time',
                splitNumber: timeTickCount,
                min: model.range && Number.isFinite(Number(model.range.startTimestamp))
                    ? Number(model.range.startTimestamp) * 1000 : null,
                max: model.range && Number.isFinite(Number(model.range.endTimestamp))
                    ? Number(model.range.endTimestamp) * 1000 : null,
                axisLabel: {
                    color: axisColor,
                    fontSize: axisFontSize,
                    hideOverlap: true,
                    formatter: function (value) { return formatTimestamp(value, bar.timeAxisLabelFormat || 'auto'); }
                },
                axisLine: { lineStyle: { color: axisLineColor } },
                axisTick: { lineStyle: { color: axisLineColor } },
                splitLine: { show: false }
            },
            yAxis: (function () {
                var valueAxes = normalizedAxes.map(function (entry) {
                    var axis = entry.axis;
                    var firstSeries = series.findIndex(function (item) { return item.axisIndex === entry.index; });
                    var color = multi && !style.axisColor && firstSeries >= 0
                        ? seriesColors[firstSeries] : axisColor;
                    return {
                        type: 'value',
                        splitNumber: valueTickCount,
                        name: String(axis.unit || ''),
                        position: entry.position,
                        offset: entry.positionIndex * axisOffsetStep,
                        nameTextStyle: { color: color },
                        axisLabel: { color: color, fontSize: axisFontSize, hideOverlap: true },
                        axisLine: { show: true, lineStyle: { color: multi ? color : axisLineColor } },
                        axisTick: { show: true, lineStyle: { color: multi ? color : axisLineColor } },
                        splitLine: {
                            show: entry.index === 0 && style.showGrid !== false,
                            lineStyle: { color: gridColor, opacity: style.gridColor ? 1 : 0.22 }
                        }
                    };
                });
                return multi ? valueAxes : valueAxes[0];
            }()),
            series: series.map(function (item, index) {
                var color = seriesColors[index];
                var fill = style.barFillMode === 'gradient' ? {
                    type: 'linear', x: 0, y: 1, x2: 0, y2: 0,
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
                    yAxisIndex: Number.isInteger(item.axisIndex) ? item.axisIndex : 0,
                    barGap: '20%',
                    barMaxWidth: multi
                        ? Math.max(3, Math.round(48 * Math.max(20, Math.min(100, Number(style.barWidthPercent) || 70)) / 100 / series.length))
                        : Math.max(8, Math.round(48 * Math.max(20, Math.min(100, Number(style.barWidthPercent) || 70)) / 100)),
                    itemStyle: {
                        color: fill,
                        opacity: echartsDesign.opacityFromPercent(style.barOpacityPercent, 100),
                        borderRadius: [radius, radius, 0, 0]
                    },
                    label: {
                        show: style.showValues === true,
                        position: 'top',
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
        var previousRange = currentState && currentState.chart && currentState.chart.range;
        var nextRange = state && state.chart && state.chart.range;
        var savedZoom = zoomController.capture(chart, previousRange, nextRange);
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
        }
        zoomController.apply(chart, buildOption(state.chart, theme), savedZoom);
    }

    window.handleMessage = function (message) {
        var state = typeof message === 'string' ? JSON.parse(message) : message;
        if (state) { render(state); }
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
        });
    }
    window.addEventListener('beforeunload', function () { if (chart) { chart.dispose(); } });
    render(currentState);
}());
