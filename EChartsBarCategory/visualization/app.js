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
    var gradientLegendSelection = {};
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

    function escapeHTML(value) {
        return String(value).replace(/[&<>"']/g, function (character) {
            return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[character];
        });
    }

    function originalText(item, bar) {
        return bar.normalized === true && Number.isFinite(item.originalValue)
            ? formatValue(item.originalValue, Math.max(0, Math.min(6, Number(item.decimals) || 0)), String(item.unit || ''))
            : formatValue(item.value, Math.max(0, Math.min(6, Number(bar.decimals) || 0)), String(bar.unit || ''));
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
                        return { ...item, value: Number(item.value), color: item.color || paletteColors[index % paletteColors.length] };
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
                        return { ...item, id: item && item.id, value: item ? Number(item.value) : null, color: item && item.color || seriesColor };
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
        var barRatio = Math.max(20, Math.min(100, Number(style.barWidthPercent) || 70)) / 100;
        var right = horizontal && style.showValues === true ? 84 : 30;
        var bottom = horizontal ? (bar.normalized === true ? 54 : 28) : 70;
        var left = horizontal ? 28 : (bar.normalized === true ? 76 : 52);
        var groupSize = chartData.mode === 'grouped' ? chartData.series.length : 1;
        var valueSpan = valueSize * 1.8;
        if (!horizontal && style.showValues === true) {
            valueSpan = (model.items || []).reduce(function (maximum, item) {
                return item.value === null ? maximum : Math.max(maximum,
                    Array.from(originalText(item, bar)).length * valueSize * 0.65);
            }, valueSpan);
        }
        var minimumSpan = Math.max((horizontal ? 40 : 80) * groupSize * barRatio,
            axisSize * (horizontal ? 1.5 : 2.4) + 4,
            style.showValues === true ? valueSpan * groupSize + 4 : 0);
        // Leave room for value-axis tick text consumed by containLabel.
        var crossSpace = Math.max(1, horizontal ? (chartElement.clientHeight || 500) - top - bottom - axisSize * 1.5
            : (chartElement.clientWidth || 800) - left - right - axisSize * 4);
        var visibleCount = Math.max(1, Math.min(chartData.categories.length, Math.floor(crossSpace / minimumSpan)));
        var scroll = visibleCount < chartData.categories.length;
        if (scroll && horizontal) { right += 28; }
        if (scroll && !horizontal) { bottom += 28; }
        var categorySpan = Math.min(minimumSpan, crossSpace);
        var anchorIndex = chartData.categoryKeys.indexOf(categoryAnchor);
        var first = Math.max(0, Math.min(chartData.categories.length - visibleCount,
            anchorIndex >= 0 ? anchorIndex : categoryStart));
        var categoryKeys = scroll ? chartData.categoryKeys : chartData.categories;
        var axisLabel = { color: axisColor, fontSize: axisSize, overflow: 'truncate',
            width: horizontal ? 180 : Math.max(8, Math.min(100, categorySpan - 8)) };
        if (scroll) {
            axisLabel.interval = 0;
            axisLabel.formatter = function (key) {
                return chartData.categories[chartData.categoryKeys.indexOf(String(key))] || '';
            };
        }
        var valueAxis = {
            type: 'value',
            name: bar.normalized === true ? translate('Normalized 0–100 per source') : String(bar.unit || ''),
            ...(bar.normalized === true ? {nameLocation: 'middle', nameGap: horizontal ? 32 : 40} : {}),
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
        if (bar.normalized === true) {
            valueAxis.min = 0;
            valueAxis.max = stacked ? Math.max(100, ...chartData.categories.map(function (_, index) {
                return chartData.series.reduce(function (sum, entry) { return sum + (Number(entry.items[index].value) || 0); }, 0);
            })) : 100;
        }
        var categoryAxis = {
            type: 'category',
            data: categoryKeys,
            axisLabel: axisLabel,
            axisLine: { lineStyle: { color: axisLineColor } },
            axisTick: { alignWithLabel: true, lineStyle: { color: axisLineColor } }
        };
        var radius = style.roundedBars === true ? 7 : 0;
        var gradient = style.barFillMode === 'gradient';
        var colorMinimum = style.colorMinimum == null ? 0 : Number(style.colorMinimum);
        var colorMaximum = style.colorMaximum == null ? 100 : Number(style.colorMaximum);
        var rampColors = [style.lowColor || '#ff0000', style.middleColor || '#ffff00', style.highColor || '#00ff00'];
        function rampColor(value) {
            return design.interpolateColorStops(rampColors, (value - colorMinimum) / (colorMaximum - colorMinimum));
        }
        function segmentColor(start, end) {
            var low = Math.min(start, end), high = Math.max(start, end);
            if (low === high) { return rampColor(low); }
            var points = [low].concat([colorMinimum, colorMinimum + (colorMaximum - colorMinimum) / 2, colorMaximum]
                .filter(function (value) { return value > low && value < high; }), [high]);
            // Relative coordinates cover this segment only; the sampled colors
            // stay anchored to the configured absolute value-axis range.
            return { type: 'linear', x: 0, y: horizontal ? 0 : 1, x2: horizontal ? 1 : 0, y2: 0,
                global: false, colorStops: points.map(function (value) {
                    return { offset: (value - low) / (high - low), color: rampColor(value) };
                }) };
        }
        // Compute signed stack positions before DataZoom filters categories.
        var positiveStacks = chartData.categories.map(function () { return 0; });
        var negativeStacks = chartData.categories.map(function () { return 0; });
        var valueAxisPixels = Math.max(1, horizontal
            ? (chartElement.clientWidth || 800) - left - right - 190
            : (chartElement.clientHeight || 500) - top - bottom - axisSize * 1.5);

        return {
            backgroundColor: bootstrap.mode === 'ipsview' && bootstrap.options.adaptToBackground === true
                ? 'transparent' : colors.background,
            ...echartsDesign.animationOptions(style, {
                enabled: true, initialDuration: 350, updateDuration: 500
            }),
            aria: {
                enabled: true,
                description: (bar.normalized === true ? translate('Normalized 0–100 per source') + '. ' : '')
                    + chartData.series.map(function (item) { return item.name; }).join(', '),
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
                ...(horizontal ? {height: categorySpan * visibleCount + axisSize * 1.5}
                    : {width: categorySpan * visibleCount + axisSize * 4}),
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
                selected: gradient ? gradientLegendSelection : {},
                top: headerInset + (titleVisible ? 38 + Math.max(0, titleSize - 18) : 8),
                textStyle: { color: legendColor, fontSize: legendSize },
                data: chartData.series.map(function (item) { return item.id; }),
                formatter: function (name) {
                    var source = chartData.series.find(function (item) { return item.id === name; });
                    return source ? source.name : '';
                }
            },
            tooltip: {
                confine: bar.normalized === true,
                extraCssText: bar.normalized === true ? 'max-width:' + Math.max(80, chartElement.clientWidth - 24)
                    + 'px;white-space:normal;overflow-wrap:anywhere;box-sizing:border-box;' : '',
                trigger: 'axis',
                axisPointer: { type: 'shadow' },
                formatter: function (parameters) {
                    var values = Array.isArray(parameters) ? parameters : [parameters];
                    if (values.length === 0) { return ''; }
                    var categoryName = String(scroll ? chartData.categories[values[0].dataIndex] || '' : values[0].name || '');
                    var lines = categoryName ? [escapeHTML(categoryName)] : [];
                    values.forEach(function (parameter) {
                        if (parameter.value === null || parameter.value === undefined || parameter.value === '-') { return; }
                        var source = chartData.series[parameter.seriesIndex];
                        var prefix = multiSeries && source ? source.name + ': ' : '';
                        var item = parameter.data || source && source.items[parameter.dataIndex] || {value: parameter.value};
                        lines.push(String(parameter.marker || '') + escapeHTML(prefix + originalText(item, bar)));
                        if (bar.normalized === true && Number.isFinite(item.originalValue)) {
                            lines.push(escapeHTML(translate('Normalized') + ': ' + formatValue(item.value, 1, '') + ' / 100'
                                + ' · ' + translate('Range') + ': '
                                + formatValue(item.minimum, item.decimals || 0, item.unit || '') + ' … '
                                + formatValue(item.maximum, item.decimals || 0, item.unit || '')));
                        }
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
                var groupThickness = categorySpan * barRatio / (groupSize + 0.3 * (groupSize - 1));
                var valueLabelsFit = chartData.mode !== 'grouped'
                    || groupThickness >= (horizontal ? valueSize * 1.2 : valueSpan);
                var result = {
                    id: seriesItem.id,
                    name: seriesItem.id,
                    type: 'bar',
                    stack: stacked ? 'total' : undefined,
                    barCategoryGap: multiSeries ? (100 - barRatio * 100) + '%' : undefined,
                    barWidth: multiSeries ? undefined : barRatio * 100 + '%',
                    itemStyle: { color: pattern || seriesItem.color },
                    label: {
                        show: style.showValues === true && valueLabelsFit,
                        ...valueLabel,
                        color: style.valueColor || (labelIsInside ? colors.background : colors.text),
                        fontSize: valueSize,
                        formatter: function (parameters) {
                            return originalText(parameters.data || {value: parameters.value}, bar);
                        }
                    },
                    data: seriesItem.items.map(function (item, categoryIndex) {
                        var start = 0, end = item.value;
                        if (stacked && item.value !== null && (!gradient || gradientLegendSelection[seriesItem.id] !== false)) {
                            var accumulators = item.value < 0 ? negativeStacks : positiveStacks;
                            start = accumulators[categoryIndex];
                            end = start + item.value;
                            accumulators[categoryIndex] = end;
                        }
                        var fill = gradient && item.value !== null ? segmentColor(start, end) : pattern || item.color;
                        var contrastColor = gradient && item.value !== null ? rampColor((start + end) / 2) : item.color;
                        var labelColor = style.valueColor || (labelIsInside && typeof design.readableTextColor === 'function'
                            ? design.readableTextColor(contrastColor, colors.text, colors.background)
                            : valueColor);
                        // Zero/tiny normalized segments otherwise put multiple originals
                        // at the same origin. Keep every value in the tooltip instead.
                        var itemLabel = labelIsInside ? { color: labelColor } : undefined;
                        if (bar.normalized === true && multiSeries) {
                            var labelLength = horizontal ? Array.from(originalText(item, bar)).length * valueSize * 0.65 + 6
                                : valueSize * 1.4;
                            if (!(item.value > 0) || item.value / valueAxis.max * valueAxisPixels < labelLength) {
                                itemLabel = {show: false};
                            }
                        }
                        return {
                            id: item.id,
                            value: item.value,
                            ...(bar.normalized === true ? {originalValue: item.originalValue, minimum: item.minimum,
                                maximum: item.maximum, unit: item.unit, decimals: item.decimals} : {}),
                            label: itemLabel,
                            itemStyle: {
                                color: fill,
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
            chart.on('legendselectchanged', function (event) {
                if (!currentState || currentState.status !== 'ready') { return; }
                var bar = currentState.chart.bar || {};
                if (!bar.style || bar.style.barFillMode !== 'gradient') { return; }
                gradientLegendSelection = event.selected || {};
                rememberCategoryWindow();
                chart.setOption(buildOption(currentState.chart, currentTheme || 'auto'), true);
            });
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
        window.addEventListener('resize', function () {
            if (!chart) { return; }
            rememberCategoryWindow();
            chart.resize();
            if (currentState && currentState.status === 'ready') {
                chart.setOption(buildOption(currentState.chart, currentTheme || 'auto'), true);
            }
        });
    }
    window.addEventListener('beforeunload', function () { if (chart) { chart.dispose(); } });
    render(currentState);
}());
