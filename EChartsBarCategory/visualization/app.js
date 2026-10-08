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

    function uniqueInOrder(values) {
        return values.filter(function (value, index) { return values.indexOf(value) === index; });
    }

    function buildChartData(model, colors) {
        var bar = model.bar || {};
        var mode = ['grouped', 'stacked'].indexOf(bar.mode) >= 0 ? bar.mode : 'simple';
        var items = Array.isArray(model.items) ? model.items.slice() : [];
        var order = bar.sortOrder || 'configured';
        var paletteColors = Array.isArray(colors.seriesColors) && colors.seriesColors.length > 0
            ? colors.seriesColors : [colors.accent || colors.border];
        if (mode === 'simple') {
            if (order === 'ascending' || order === 'descending') {
                items.sort(function (left, right) {
                    var difference = Number(left.value) - Number(right.value);
                    if (difference === 0) { return Number(left.order) - Number(right.order); }
                    return order === 'ascending' ? difference : -difference;
                });
            }
            var baseLabels = items.map(function (item) { return String(item.category || item.series || ''); });
            var baseCounts = Object.create(null);
            baseLabels.forEach(function (label) { baseCounts[label] = (baseCounts[label] || 0) + 1; });
            return {
                mode: mode,
                categories: baseLabels.map(function (label, index) {
                    if (baseCounts[label] === 1) { return label; }
                    var id = String(items[index].variableID || items[index].id || index + 1);
                    return label + ' (#' + id.replace(/^variable-/, '') + ')';
                }),
                series: [{
                    name: String(bar.title || ''),
                    items: items.map(function (item, index) {
                        return { value: Number(item.value), color: item.color || paletteColors[index % paletteColors.length] };
                    })
                }]
            };
        }

        var categories = uniqueInOrder(items.map(function (item) { return String(item.category || ''); }));
        var seriesKeys = uniqueInOrder(items.map(function (item) { return String(item.seriesKey || item.series || ''); }));
        if (order === 'ascending' || order === 'descending') {
            var totals = Object.create(null);
            categories.forEach(function (category) { totals[category] = 0; });
            items.forEach(function (item) { totals[String(item.category || '')] += Number(item.value) || 0; });
            categories.sort(function (left, right) {
                var difference = totals[left] - totals[right];
                if (difference === 0) { return 0; }
                return order === 'ascending' ? difference : -difference;
            });
        }
        return {
            mode: mode,
            categories: categories,
            series: seriesKeys.map(function (seriesKey, seriesIndex) {
                var firstItem = items.find(function (item) {
                    return String(item.seriesKey || item.series || '') === seriesKey;
                });
                var seriesItems = categories.map(function (category) {
                    return items.find(function (item) {
                        return String(item.category || '') === category
                            && String(item.seriesKey || item.series || '') === seriesKey;
                    });
                });
                var configuredColor = '';
                seriesItems.some(function (item) {
                    if (item && item.color) { configuredColor = item.color; return true; }
                    return false;
                });
                var seriesColor = configuredColor || paletteColors[seriesIndex % paletteColors.length];
                return {
                    id: seriesKey,
                    name: String(firstItem && firstItem.series || ''),
                    color: seriesColor,
                    items: seriesItems.map(function (item) {
                        return { value: item ? Number(item.value) : null, color: item && item.color || seriesColor };
                    })
                };
            })
        };
    }

    function buildOption(model, theme) {
        var colors = colorsFor(theme);
        var design = window.SYMC_ECHARTS_DESIGN || {};
        var bar = model.bar || {};
        var style = bar.style || {};
        var horizontal = bar.orientation === 'horizontal';
        var chartData = buildChartData(model, colors);
        var multiSeries = chartData.mode !== 'simple';
        var stacked = chartData.mode === 'stacked';
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
            data: chartData.categories,
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
                top: headerInset + (multiSeries ? (titleVisible ? 82 : 48) : (titleVisible ? 56 : 20)),
                right: horizontal && style.showValues === true ? 84 : 30,
                bottom: horizontal ? 28 : 70,
                left: horizontal ? 28 : 52,
                containLabel: true
            },
            legend: {
                show: multiSeries,
                top: headerInset + (titleVisible ? 38 : 8),
                textStyle: { color: colors.text },
                data: chartData.series.map(function (item) { return item.name; })
            },
            tooltip: {
                trigger: 'axis',
                axisPointer: { type: 'shadow' },
                formatter: function (parameters) {
                    var values = Array.isArray(parameters) ? parameters : [parameters];
                    if (values.length === 0) { return ''; }
                    var categoryName = String(values[0].name || '');
                    var lines = categoryName ? [categoryName] : [];
                    values.forEach(function (parameter) {
                        var prefix = multiSeries ? String(parameter.seriesName || '') + ': ' : '';
                        lines.push(String(parameter.marker || '') + prefix
                            + formatValue(parameter.value, decimals, unit));
                    });
                    return lines.join('<br>');
                }
            },
            xAxis: horizontal ? valueAxis : categoryAxis,
            yAxis: horizontal ? categoryAxis : valueAxis,
            series: chartData.series.map(function (seriesItem) {
                var result = {
                    id: seriesItem.id,
                    name: seriesItem.name,
                    type: 'bar',
                    stack: stacked ? 'total' : undefined,
                    barCategoryGap: multiSeries
                        ? Math.max(0, 100 - Math.max(20, Math.min(100, Number(style.barWidthPercent) || 70))) + '%'
                        : undefined,
                    barWidth: multiSeries ? undefined
                        : Math.max(20, Math.min(100, Number(style.barWidthPercent) || 70)) + '%',
                    itemStyle: { color: seriesItem.color },
                    label: {
                        show: style.showValues === true,
                        position: stacked ? 'inside' : (horizontal ? 'right' : 'top'),
                        color: stacked ? colors.background : colors.text,
                        formatter: function (parameters) {
                            return formatValue(parameters.value, decimals, unit);
                        }
                    },
                    data: seriesItem.items.map(function (item) {
                        var labelColor = stacked && typeof design.readableTextColor === 'function'
                            ? design.readableTextColor(item.color, colors.text, colors.background)
                            : colors.text;
                        return {
                            value: item.value,
                            label: stacked ? { color: labelColor } : undefined,
                            itemStyle: {
                                color: item.color,
                                borderRadius: horizontal ? [0, radius, radius, 0] : [radius, radius, 0, 0]
                            }
                        };
                    })
                };
                return result;
            })
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
