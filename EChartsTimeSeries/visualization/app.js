(function () {
    'use strict';

    var bootstrap = window.SYMC_VISUALIZATION || {};
    var chartElement = document.getElementById('echarts-timeseries-chart');
    var warningElement = document.getElementById('echarts-timeseries-warning');
    var errorElement = document.getElementById('echarts-timeseries-error');
    var chart = null;
    var currentState = bootstrap.state || null;
    var currentTheme = null;

    function translate(text) {
        return (bootstrap.translations || {})[text] || text;
    }

    function palette(theme) {
        var palettes = bootstrap.options && bootstrap.options.echartsThemes || {};
        return palettes[theme] || palettes.auto || {
            background: '#151619', text: '#F4F5F7', muted: '#969AA2', border: '#A5A9B0'
        };
    }

    function normalizeTheme(theme) {
        return bootstrap.mode === 'symcon' && theme === 'auto'
            ? (document.documentElement.classList.contains('dark') ? 'dark' : 'auto')
            : theme;
    }

    function buildOption(model, theme) {
        var colors = palette(theme);
        var axes = Array.isArray(model.axes) ? model.axes : [];
        var series = Array.isArray(model.series) ? model.series : [];
        var headerInset = bootstrap.mode === 'symcon' && bootstrap.options.tileHeaderVisible !== false ? 58 : 10;
        var zoom = model.chart && model.chart.enableZoom === true;
        var design = model.chart && model.chart.design || {};
        var legendPosition = ['top', 'bottom', 'hidden'].indexOf(design.legendPosition) >= 0
            ? design.legendPosition : 'top';
        var showLegend = legendPosition !== 'hidden';
        var bottomLegend = legendPosition === 'bottom';
        var lineWidth = 2 * Math.max(50, Math.min(200, Number(design.lineWidthPercent) || 100)) / 100;
        var symbolSize = 6 * Math.max(50, Math.min(200, Number(design.symbolSizePercent) || 100)) / 100;
        var areaOpacity = Math.max(0, Math.min(100, Number(design.areaOpacityPercent) || 0)) / 100;
        if (design.areaOpacityPercent === undefined) { areaOpacity = 0.22; }
        var titleVisible = Boolean(model.chart && model.chart.title);
        var gridTop = headerInset + (legendPosition === 'top' ? 64 : (titleVisible ? 48 : 20));
        var gridBottom = bottomLegend ? (zoom ? 94 : 58) : (zoom ? 64 : 34);

        return {
            backgroundColor: colors.background,
            animation: false,
            aria: { enabled: true, decal: { show: false } },
            title: {
                show: titleVisible,
                text: model.chart && model.chart.title || '',
                left: 'center',
                top: headerInset,
                textStyle: { color: colors.text, fontSize: 18 }
            },
            legend: {
                show: showLegend,
                top: legendPosition === 'top' ? headerInset + (titleVisible ? 34 : 4) : null,
                bottom: bottomLegend ? 6 : null,
                textStyle: { color: colors.text },
                data: series.map(function (item) { return item.label; })
            },
            grid: { left: 58, right: axes.length > 1 ? 58 : 22, top: gridTop, bottom: gridBottom },
            tooltip: {
                trigger: 'axis',
                axisPointer: { type: 'cross' },
                formatter: function (parameters) {
                    if (!Array.isArray(parameters) || parameters.length === 0) { return ''; }
                    var heading = parameters[0].axisValueLabel || '';
                    return [heading].concat(parameters.map(function (parameter) {
                        var item = series[parameter.seriesIndex] || {};
                        var value = Array.isArray(parameter.value) ? Number(parameter.value[1]) : Number(parameter.value);
                        var decimals = Math.max(0, Math.min(6, Number(item.decimals) || 0));
                        var formatted = Number.isFinite(value)
                            ? value.toLocaleString(undefined, {
                                minimumFractionDigits: decimals,
                                maximumFractionDigits: decimals
                            })
                            : '–';
                        return parameter.marker + String(item.label || parameter.seriesName || '') + ': '
                            + formatted + (item.unit ? ' ' + item.unit : '');
                    })).join('<br>');
                }
            },
            dataZoom: zoom ? [
                { type: 'inside', xAxisIndex: 0 },
                { type: 'slider', xAxisIndex: 0, bottom: bottomLegend ? 38 : 12 }
            ] : [],
            xAxis: {
                type: 'time',
                min: model.range.startTimestamp * 1000,
                max: model.range.endTimestamp * 1000,
                axisLine: { show: design.showXAxis !== false, lineStyle: { color: colors.border } },
                axisTick: { show: design.showXAxis !== false },
                axisLabel: { show: design.showXAxis !== false, color: colors.muted },
                splitLine: { show: false }
            },
            yAxis: axes.map(function (axis, index) {
                return {
                    type: 'value',
                    name: design.showYAxis !== false ? axis.unit || '' : '',
                    position: index === 0 ? 'left' : 'right',
                    axisLine: { show: design.showYAxis !== false, lineStyle: { color: colors.border } },
                    axisTick: { show: design.showYAxis !== false },
                    axisLabel: {
                        show: design.showYAxis !== false,
                        color: colors.muted,
                        formatter: '{value}' + (axis.unit ? ' ' + axis.unit : '')
                    },
                    splitLine: {
                        show: design.showGrid !== false,
                        lineStyle: { color: colors.track || colors.border, opacity: 0.35 }
                    },
                    nameTextStyle: { color: colors.muted }
                };
            }),
            series: series.map(function (item) {
                var result = {
                    id: item.id,
                    name: item.label,
                    type: 'line',
                    yAxisIndex: item.axisIndex,
                    showSymbol: design.showSymbols === true,
                    symbolSize: symbolSize,
                    smooth: design.smoothLines === true,
                    connectNulls: false,
                    sampling: 'lttb',
                    lineStyle: { width: lineWidth },
                    data: item.points.map(function (point) { return [point[0] * 1000, point[1]]; })
                };
                if (item.color) {
                    result.lineStyle.color = item.color;
                    result.itemStyle = { color: item.color };
                }
                if (item.style === 'area') {
                    result.areaStyle = { opacity: areaOpacity };
                }
                return result;
            })
        };
    }

    function displayError(message) {
        if (chart) { chart.clear(); }
        chartElement.hidden = true;
        warningElement.hidden = true;
        errorElement.textContent = translate(message || 'The time series could not be loaded.');
        errorElement.hidden = false;
    }

    function render(state) {
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
        var theme = normalizeTheme(state.chart.theme || 'auto');
        if (chart && currentTheme !== theme) {
            chart.dispose();
            chart = null;
        }
        if (!chart) {
            chart = window.echarts.init(chartElement, theme === 'auto' ? null : theme, { renderer: 'canvas' });
            currentTheme = theme;
        }
        warningElement.textContent = translate('The selected raw range was truncated by the point budget.');
        warningElement.hidden = !state.chart.truncated;
        chart.setOption(buildOption(state.chart, theme), true);
    }

    function appendPoint(message) {
        if (!currentState || currentState.status !== 'ready' || !currentState.chart) { return; }
        var model = currentState.chart;
        var item = model.series.find(function (series) { return series.variableID === message.variableID; });
        if (!item) { return; }
        var points = item.points;
        var last = points[points.length - 1];
        if (last && last[0] === message.timestamp) {
            last[1] = message.value;
        } else {
            points.push([message.timestamp, message.value]);
        }
        var cutoff = message.timestamp - model.range.durationSeconds;
        item.points = points.filter(function (point) { return point[0] >= cutoff; });
        var limit = Math.max(1, Number(model.range.pointLimitPerSeries) || 1);
        if (item.points.length > limit) {
            item.points = item.points.slice(item.points.length - limit);
        }
        model.range.startTimestamp = cutoff;
        model.range.endTimestamp = message.timestamp;
        render(currentState);
    }

    window.handleMessage = function (message) {
        var value = typeof message === 'string' ? JSON.parse(message) : message;
        if (value && value.messageType === 'append') {
            appendPoint(value);
        } else if (value) {
            render(value);
        }
    };

    if (window.ResizeObserver) {
        new ResizeObserver(function () { if (chart) { chart.resize(); } }).observe(chartElement);
    } else {
        window.addEventListener('resize', function () { if (chart) { chart.resize(); } });
    }
    window.addEventListener('beforeunload', function () { if (chart) { chart.dispose(); } });
    render(currentState);
}());
