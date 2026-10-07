(function () {
    'use strict';

    var bootstrap = window.SYMC_VISUALIZATION || {};
    var chartElement = document.getElementById('echarts-bar-category-chart');
    var errorElement = document.getElementById('echarts-bar-category-error');
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
            accent: '#55CBB5', surface: '#25272B', seriesColors: ['#55CBB5']
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
            surface: resolveColor('--symc-surface', colors.surface || colors.background),
            seriesColors: colors.seriesColors
        };
    }

    function formatValue(value, decimals, unit) {
        var formatted = Number(value).toLocaleString(undefined, {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        });
        return formatted + (unit ? ' ' + unit : '');
    }

    function sortedItems(model) {
        var items = Array.isArray(model.items) ? model.items.slice() : [];
        var order = model.bar && model.bar.sortOrder || 'configured';
        if (order === 'ascending' || order === 'descending') {
            items.sort(function (left, right) {
                var difference = Number(left.value) - Number(right.value);
                if (difference === 0) { return Number(left.order) - Number(right.order); }
                return order === 'ascending' ? difference : -difference;
            });
        }
        return items;
    }

    function buildOption(model, theme) {
        var colors = colorsFor(theme);
        var bar = model.bar || {};
        var style = bar.style || {};
        var horizontal = bar.orientation === 'horizontal';
        var items = sortedItems(model);
        var paletteColors = Array.isArray(colors.seriesColors) && colors.seriesColors.length > 0
            ? colors.seriesColors : [colors.accent || colors.border];
        var titleVisible = Boolean(bar.title);
        var headerInset = bootstrap.mode === 'symcon' && bootstrap.options.tileHeaderVisible !== false ? 58 : 8;
        var axisLabel = { color: colors.text, overflow: 'truncate', width: horizontal ? 180 : 100 };
        var valueAxis = {
            type: 'value',
            name: String(bar.unit || ''),
            nameTextStyle: { color: colors.muted },
            axisLabel: { color: colors.text },
            axisLine: { show: true, lineStyle: { color: colors.border } },
            axisTick: { show: true, lineStyle: { color: colors.border } },
            splitLine: { show: style.showGrid !== false, lineStyle: { color: colors.border, opacity: 0.22 } }
        };
        var categoryAxis = {
            type: 'category',
            data: items.map(function (item) { return String(item.label || ''); }),
            axisLabel: axisLabel,
            axisLine: { lineStyle: { color: colors.border } },
            axisTick: { alignWithLabel: true, lineStyle: { color: colors.border } }
        };
        var radius = style.roundedBars === true ? 7 : 0;
        var decimals = Math.max(0, Math.min(6, Number(bar.decimals) || 0));
        var unit = String(bar.unit || '');

        return {
            backgroundColor: bootstrap.mode === 'ipsview' && bootstrap.options.adaptToBackground === true
                ? 'transparent' : colors.background,
            animation: true,
            animationDuration: 350,
            aria: { enabled: true, decal: { show: false } },
            title: {
                show: titleVisible,
                text: String(bar.title || ''),
                left: 'center',
                top: headerInset,
                textStyle: { color: colors.text, fontSize: 18 }
            },
            grid: {
                top: headerInset + (titleVisible ? 56 : 20),
                right: horizontal && style.showValues === true ? 84 : 30,
                bottom: horizontal ? 28 : 70,
                left: horizontal ? 28 : 52,
                containLabel: true
            },
            tooltip: {
                trigger: 'axis',
                axisPointer: { type: 'shadow' },
                formatter: function (parameters) {
                    var parameter = Array.isArray(parameters) ? parameters[0] : parameters;
                    return String(parameter.name || '') + '<br>'
                        + formatValue(parameter.value, decimals, unit);
                }
            },
            xAxis: horizontal ? valueAxis : categoryAxis,
            yAxis: horizontal ? categoryAxis : valueAxis,
            series: [{
                name: String(bar.title || ''),
                type: 'bar',
                barWidth: Math.max(20, Math.min(100, Number(style.barWidthPercent) || 70)) + '%',
                label: {
                    show: style.showValues === true,
                    position: horizontal ? 'right' : 'top',
                    color: colors.text,
                    formatter: function (parameters) {
                        return formatValue(parameters.value, decimals, unit);
                    }
                },
                data: items.map(function (item, index) {
                    return {
                        value: Number(item.value),
                        itemStyle: {
                            color: item.color || paletteColors[index % paletteColors.length],
                            borderRadius: horizontal ? [0, radius, radius, 0] : [radius, radius, 0, 0]
                        }
                    };
                })
            }]
        };
    }

    function displayError(message) {
        if (chart) { chart.clear(); }
        chartElement.hidden = true;
        errorElement.textContent = translate(message || 'The Category Bar values could not be loaded.');
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
        chart.setOption(buildOption(state.chart, theme), true);
    }

    window.handleMessage = function (message) {
        var state = typeof message === 'string' ? JSON.parse(message) : message;
        if (state) { render(state); }
    };

    if (window.ResizeObserver) {
        new ResizeObserver(function () {
            if (!chart) { return; }
            chart.resize();
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
