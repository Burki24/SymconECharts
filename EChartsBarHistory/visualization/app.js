(function () {
    'use strict';

    var bootstrap = window.SYMC_VISUALIZATION || {};
    var chartElement = document.getElementById('echarts-bar-history-chart');
    var warningElement = document.getElementById('echarts-bar-history-warning');
    var errorElement = document.getElementById('echarts-bar-history-error');
    var chart = null;
    var currentState = bootstrap.state || null;
    var currentTheme = null;

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

    function buildOption(model, theme) {
        var colors = colorsFor(theme);
        var bar = model.bar || {};
        var style = bar.style || {};
        var series = Array.isArray(model.series) && model.series[0] || { points: [] };
        var decimals = Math.max(0, Math.min(6, Number(bar.decimals) || 0));
        var unit = String(bar.unit || '');
        var headerInset = bootstrap.mode === 'symcon' && bootstrap.options.tileHeaderVisible !== false ? 58 : 8;
        var radius = style.roundedBars === true ? 6 : 0;
        var paletteColors = Array.isArray(colors.seriesColors) && colors.seriesColors.length > 0
            ? colors.seriesColors : [colors.accent];
        var barColor = series.color || paletteColors[0];

        return {
            backgroundColor: bootstrap.mode === 'ipsview' && bootstrap.options.adaptToBackground === true
                ? 'transparent' : colors.background,
            animation: true,
            animationDuration: 350,
            aria: { enabled: true, decal: { show: false } },
            title: {
                show: Boolean(bar.title),
                text: String(bar.title || ''),
                left: 'center',
                top: headerInset,
                textStyle: { color: colors.text, fontSize: 18 }
            },
            grid: {
                top: headerInset + (bar.title ? 52 : 16),
                right: 28,
                bottom: 58,
                left: 52,
                containLabel: true
            },
            tooltip: {
                trigger: 'axis',
                axisPointer: { type: 'shadow' },
                formatter: function (parameters) {
                    var parameter = Array.isArray(parameters) ? parameters[0] : parameters;
                    if (!parameter || !Array.isArray(parameter.value)) { return ''; }
                    return formatTimestamp(parameter.value[0], 'date-time') + '<br>'
                        + String(parameter.marker || '') + String(series.label || '') + ': '
                        + formatValue(parameter.value[1], decimals, unit);
                }
            },
            xAxis: {
                type: 'time',
                axisLabel: {
                    color: colors.text,
                    formatter: function (value) { return formatTimestamp(value, bar.timeAxisLabelFormat || 'auto'); }
                },
                axisLine: { lineStyle: { color: colors.border } },
                axisTick: { lineStyle: { color: colors.border } },
                splitLine: { show: false }
            },
            yAxis: {
                type: 'value',
                name: unit,
                nameTextStyle: { color: colors.muted },
                axisLabel: { color: colors.text },
                axisLine: { show: true, lineStyle: { color: colors.border } },
                axisTick: { show: true, lineStyle: { color: colors.border } },
                splitLine: { show: style.showGrid !== false, lineStyle: { color: colors.border, opacity: 0.22 } }
            },
            series: [{
                name: String(series.label || ''),
                type: 'bar',
                barMaxWidth: Math.max(8, Math.round(48 * Math.max(20, Math.min(100, Number(style.barWidthPercent) || 70)) / 100)),
                itemStyle: { color: barColor, borderRadius: [radius, radius, 0, 0] },
                label: {
                    show: style.showValues === true,
                    position: 'top',
                    color: colors.text,
                    formatter: function (parameters) {
                        return Array.isArray(parameters.value)
                            ? formatValue(parameters.value[1], decimals, unit) : '';
                    }
                },
                data: (series.points || []).map(function (point) {
                    return [Number(point[0]) * 1000, Number(point[1])];
                })
            }]
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
        chart.setOption(buildOption(state.chart, theme), true);
    }

    window.handleMessage = function (message) {
        var state = typeof message === 'string' ? JSON.parse(message) : message;
        if (state) { render(state); }
    };
    if (window.ResizeObserver) {
        new ResizeObserver(function () { if (chart) { chart.resize(); } }).observe(chartElement);
    } else {
        window.addEventListener('resize', function () { if (chart) { chart.resize(); } });
    }
    window.addEventListener('beforeunload', function () { if (chart) { chart.dispose(); } });
    render(currentState);
}());
