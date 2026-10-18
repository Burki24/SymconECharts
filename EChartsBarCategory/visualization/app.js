(function () {
    'use strict';

    var bootstrap = window.SYMC_VISUALIZATION || {};
    var chartElement = document.getElementById('echarts-bar-category-chart');
    var errorElement = document.getElementById('echarts-bar-category-error');
    var warningElement = document.getElementById('echarts-bar-category-warning');
    var chart = null;
    var currentState = bootstrap.state || null;
    var currentTheme = null;
    var categoryAnchor = null;
    var categoryStart = 0;
    var echartsDesign = window.SYMC_ECHARTS_DESIGN;
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
    var retainOnTransientError = echartsDesign.createTransientFailureRetainer(
        chartElement, warningElement, translate, 'The Category Bar values could not be loaded.'
    );

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

    function uniqueCategoryLabels(items) {
        return items.map(function (item) { return String(item.category || item.series || ''); });
    }

    function rememberCategoryWindow() {
        if (!chart || typeof chart.getOption !== 'function') { return; }
        var option = chart.getOption();
        var zoom = (option.dataZoom || []).find(function (entry) { return entry.id === 'category-scroll'; });
        if (!zoom) { return; }
        var axes = zoom.orient === 'vertical' ? option.yAxis : option.xAxis;
        var keys = axes && axes[0] && axes[0].data || [];
        var start = Number(zoom.startValue);
        if (!Number.isFinite(start)) {
            start = keys.indexOf(zoom.startValue);
            if (start < 0) { start = (Number(zoom.start) || 0) * Math.max(0, keys.length - 1) / 100; }
        }
        categoryStart = Math.max(0, Math.ceil(start - 1e-8));
        categoryAnchor = keys[categoryStart] || null;
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
            return {
                mode: mode,
                categories: uniqueCategoryLabels(items),
                categoryKeys: items.map(function (item, index) { return item.id || 'source-' + index; }),
                series: [{
                    id: 'simple',
                    name: String(bar.title || ''),
                    items: items.map(function (item, index) {
                        return { id: item.id, value: Number(item.value), color: item.color || paletteColors[index % paletteColors.length] };
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
            categoryKeys: categories,
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
                        return { id: item && item.id, value: item ? Number(item.value) : null, color: item && item.color || seriesColor };
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
        var pattern = style.barFillMode === 'svg'
            ? barPatterns.resolve(style.barPatternImage, style.barSVGSizePercent, style.barPatternAspectRatio)
            : null;
        var horizontal = bar.orientation === 'horizontal';
        var chartData = buildChartData(model, colors);
        var multiSeries = chartData.mode !== 'simple';
        var stacked = chartData.mode === 'stacked';
        var titleVisible = Boolean(bar.title);
        var headerInset = bootstrap.mode === 'symcon' && bootstrap.options.tileHeaderVisible !== false ? 58 : 8;
        var titleColor = style.titleColor || colors.text;
        var legendColor = style.legendColor || colors.text;
        var axisColor = style.axisColor || colors.text;
        var axisLineColor = style.axisColor || colors.border;
        var gridColor = style.gridColor || colors.border;
        var valueColor = style.valueColor || colors.text;
        var titleSize = echartsDesign.fontSizePercent(style.titleFontSizePercent, 18);
        var legendSize = echartsDesign.fontSizePercent(style.legendFontSizePercent, 12);
        var axisSize = echartsDesign.fontSizePercent(style.axisFontSizePercent, 12);
        var valueSize = echartsDesign.fontSizePercent(style.valueFontSizePercent, 12);
        var top = headerInset + (multiSeries ? (titleVisible ? 82 : 48) : (titleVisible ? 56 : 20))
            + Math.max(0, titleSize - 18) + (multiSeries ? Math.max(0, legendSize - 12) : 0);
        var scroll = chartData.categories.length > 16;
        var right = (horizontal && style.showValues === true ? 84 : 30) + (scroll && horizontal ? 28 : 0);
        var bottom = (horizontal ? 28 : 70) + (scroll && !horizontal ? 28 : 0);
        var left = horizontal ? 28 : 52;
        var crossSpace = horizontal ? (chartElement.clientHeight || 500) - top - bottom
            : (chartElement.clientWidth || 800) - left - right;
        var minimumSpan = Math.max(horizontal ? 28 : 56, axisSize * 1.8,
            style.showValues === true ? valueSize * 1.8 : 0);
        if (chartData.mode === 'grouped') {
            minimumSpan = Math.max(minimumSpan, chartData.series.length * 10);
        }
        var visibleCount = scroll ? Math.max(1, Math.min(16, Math.floor(crossSpace / minimumSpan))) : chartData.categories.length;
        var anchorIndex = chartData.categoryKeys.indexOf(categoryAnchor);
        var first = Math.max(0, Math.min(chartData.categories.length - visibleCount,
            anchorIndex >= 0 ? anchorIndex : categoryStart));
        var categoryKeys = scroll ? chartData.categoryKeys : chartData.categories;
        var axisLabel = { color: axisColor, fontSize: axisSize, overflow: 'truncate', width: horizontal ? 180 : 100 };
        if (scroll) {
            axisLabel.interval = 0;
            axisLabel.formatter = function (key) {
                return chartData.categories[chartData.categoryKeys.indexOf(String(key))] || '';
            };
        }
        var valueAxis = {
            type: 'value',
            name: String(bar.unit || ''),
            nameTextStyle: { color: style.axisColor || colors.muted, fontSize: axisSize },
            axisLabel: { color: axisColor, fontSize: axisSize },
            axisLine: { show: true, lineStyle: { color: axisLineColor } },
            axisTick: { show: true, lineStyle: { color: axisLineColor } },
            splitLine: { show: style.showGrid !== false, lineStyle: { color: gridColor, opacity: style.gridColor ? 1 : 0.22 } }
        };
        // DataZoom filters categories, not the shared comparison scale. Keep offscreen
        // signed values (and separate positive/negative stack totals) in that scale.
        if (scroll) {
            var minimum = 0, maximum = 0;
            chartData.categories.forEach(function (_, index) {
                var positive = 0, negative = 0;
                chartData.series.forEach(function (series) {
                    var value = Number(series.items[index].value) || 0;
                    if (stacked) { positive += Math.max(0, value); negative += Math.min(0, value); }
                    else { minimum = Math.min(minimum, value); maximum = Math.max(maximum, value); }
                });
                if (stacked) { minimum = Math.min(minimum, negative); maximum = Math.max(maximum, positive); }
            });
            valueAxis.min = minimum;
            valueAxis.max = maximum === minimum ? minimum + 1 : maximum;
        }
        var categoryAxis = {
            type: 'category',
            data: categoryKeys,
            axisLabel: axisLabel,
            axisLine: { lineStyle: { color: axisLineColor } },
            axisTick: { alignWithLabel: true, lineStyle: { color: axisLineColor } }
        };
        var radius = style.roundedBars === true ? 7 : 0;
        var decimals = Math.max(0, Math.min(6, Number(bar.decimals) || 0));
        var unit = String(bar.unit || '');

        return {
            backgroundColor: bootstrap.mode === 'ipsview' && bootstrap.options.adaptToBackground === true
                ? 'transparent' : colors.background,
            ...echartsDesign.animationOptions(style, {
                enabled: true, initialDuration: 350, updateDuration: 500
            }),
            aria: {
                enabled: true,
                description: chartData.series.map(function (item) { return item.name; }).join(', '),
                decal: { show: false }
            },
            title: {
                show: titleVisible,
                text: String(bar.title || ''),
                left: 'center',
                top: headerInset,
                textStyle: { color: titleColor, fontSize: titleSize }
            },
            grid: {
                top: top, right: right, bottom: bottom, left: left,
                containLabel: true
            },
            dataZoom: scroll ? [
                { id: 'category-scroll', type: 'slider',
                    ...(horizontal ? { yAxisIndex: 0, orient: 'vertical', right: 6, width: 12, top: top, bottom: bottom }
                        : { xAxisIndex: 0, orient: 'horizontal', bottom: 4, height: 12, left: left, right: right }),
                    startValue: first, endValue: first + visibleCount - 1, filterMode: 'filter', zoomLock: true,
                    showDetail: false, showDataShadow: false, brushSelect: false },
                { id: 'category-scroll-inside', type: 'inside',
                    ...(horizontal ? { yAxisIndex: 0 } : { xAxisIndex: 0 }),
                    startValue: first, endValue: first + visibleCount - 1, filterMode: 'filter', zoomLock: true,
                    zoomOnMouseWheel: false, moveOnMouseWheel: true, moveOnMouseMove: true }
            ] : [],
            legend: {
                show: multiSeries,
                type: 'scroll',
                top: headerInset + (titleVisible ? 38 + Math.max(0, titleSize - 18) : 8),
                textStyle: { color: legendColor, fontSize: legendSize },
                data: chartData.series.map(function (item) { return item.id; }),
                formatter: function (name) {
                    var source = chartData.series.find(function (item) { return item.id === name; });
                    return source ? source.name : '';
                }
            },
            tooltip: {
                trigger: 'axis',
                axisPointer: { type: 'shadow' },
                formatter: function (parameters) {
                    var values = Array.isArray(parameters) ? parameters : [parameters];
                    if (values.length === 0) { return ''; }
                    var categoryName = String(scroll ? chartData.categories[values[0].dataIndex] || '' : values[0].name || '');
                    var lines = categoryName ? [categoryName] : [];
                    values.forEach(function (parameter) {
                        if (parameter.value === null || parameter.value === undefined || parameter.value === '-') { return; }
                        var source = chartData.series[parameter.seriesIndex];
                        var prefix = multiSeries && source ? source.name + ': ' : '';
                        lines.push(String(parameter.marker || '') + prefix
                            + formatValue(parameter.value, decimals, unit));
                    });
                    return lines.join('<br>');
                }
            },
            xAxis: horizontal ? valueAxis : categoryAxis,
            yAxis: horizontal ? categoryAxis : valueAxis,
            series: chartData.series.map(function (seriesItem) {
                var valueLabel = echartsDesign.valueLabel(style,
                    stacked ? 'inside' : (horizontal ? 'right' : 'top'),
                    ['top', 'bottom', 'left', 'right', 'inside', 'insideTop', 'insideBottom',
                        'insideLeft', 'insideRight'], 5);
                var labelIsInside = valueLabel.position.indexOf('inside') === 0;
                var result = {
                    id: seriesItem.id,
                    name: seriesItem.id,
                    type: 'bar',
                    stack: stacked ? 'total' : undefined,
                    barCategoryGap: multiSeries
                        ? Math.max(0, 100 - Math.max(20, Math.min(100, Number(style.barWidthPercent) || 70))) + '%'
                        : undefined,
                    barWidth: multiSeries ? undefined
                        : Math.max(20, Math.min(100, Number(style.barWidthPercent) || 70)) + '%',
                    itemStyle: { color: pattern || seriesItem.color },
                    label: {
                        show: style.showValues === true,
                        ...valueLabel,
                        color: style.valueColor || (labelIsInside ? colors.background : colors.text),
                        fontSize: valueSize,
                        formatter: function (parameters) {
                            return formatValue(parameters.value, decimals, unit);
                        }
                    },
                    data: seriesItem.items.map(function (item) {
                        var labelColor = style.valueColor || (labelIsInside && typeof design.readableTextColor === 'function'
                            ? design.readableTextColor(item.color, colors.text, colors.background)
                            : valueColor);
                        return {
                            id: item.id,
                            value: item.value,
                            label: labelIsInside ? { color: labelColor } : undefined,
                            itemStyle: {
                                color: pattern || item.color,
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
        var style = state.chart.bar && state.chart.bar.style || {};
        if (style.barFillMode === 'svg') {
            barPatterns.ensure(style.barPatternImage, style.barSVGSizePercent, style.barPatternAspectRatio);
        }
        errorElement.hidden = true;
        chartElement.hidden = false;
        var theme = normalizeTheme(state.chart.theme || 'auto');
        rememberCategoryWindow();
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
        var state = echartsDesign.parseVisualizationState(message);
        if (state) { render(state); }
    };

    if (window.ResizeObserver) {
        new ResizeObserver(function () {
            rememberCategoryWindow();
            if (!echartsDesign.resizeChartIfNeeded(chart, chartElement)) { return; }
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
