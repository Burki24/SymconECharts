(function () {
    'use strict';

    var bootstrap = window.SYMC_VISUALIZATION || {};
    var echartsDesign = window.SYMC_ECHARTS_DESIGN;
    var chartElement = document.getElementById('echarts-timeseries-chart');
    var warningElement = document.getElementById('echarts-timeseries-warning');
    var errorElement = document.getElementById('echarts-timeseries-error');
    var chart = null;
    var currentState = bootstrap.state || null;
    var currentTheme = null;
    var zoomController = window.SymconEChartsZoom;
    var areaPatterns = window.SymconEChartsPattern.create(schedulePatternRender);
    var seriesSelection = window.SYMC_ECHARTS_SERIES_SELECTION.create();
    var startupPending = Boolean(bootstrap.options && bootstrap.options.compressedStateGzipBase64);
    var pendingAppends = [];
    // Startup queues share the maximum accepted model point budget, not an unbounded history.
    var maximumPendingAppends = 32000;

    function escapeHTML(value) {
        return String(value == null ? '' : value).replace(/[&<>"']/g, function (character) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[character];
        });
    }

    function translate(text) {
        return (bootstrap.translations || {})[text] || text;
    }
    var retainOnTransientError = echartsDesign.createTransientFailureRetainer(
        chartElement, warningElement, translate, 'The time series could not be loaded.'
    );

    function palette(theme) {
        var palettes = bootstrap.options && bootstrap.options.echartsThemes || {};
        return palettes[theme] || palettes.auto || {
            background: '#151619', text: '#F4F5F7', muted: '#969AA2', border: '#A5A9B0'
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
        if (theme !== 'auto') {
            return colors;
        }

        return {
            background: resolveColor('--symc-background', colors.background),
            text: resolveColor('--symc-text', colors.text),
            muted: resolveColor('--symc-muted', colors.muted),
            border: resolveColor('--symc-border', colors.border),
            track: resolveColor('--symc-surface', colors.track),
            accent: resolveColor('--symc-accent', colors.accent),
            surface: resolveColor('--symc-surface', colors.surface || colors.background),
            seriesColors: colors.seriesColors
        };
    }

    function schedulePatternRender() {
        if (!currentState) { return; }
        if (typeof window.requestAnimationFrame === 'function') {
            window.requestAnimationFrame(function () { render(currentState); });
            return;
        }
        if (typeof window.setTimeout === 'function') {
            window.setTimeout(function () { render(currentState); }, 0);
        }
    }

    function areaPatternsReady(model) {
        var series = model && Array.isArray(model.series) ? model.series : [];
        return series.every(function (item) {
            return !item || item.style !== 'area' || !item.design || item.design.areaFillMode !== 'svg'
                || areaPatterns.ensure(item.design.areaPatternImage,
                    item.design.areaSVGSizePercent, item.design.areaPatternAspectRatio);
        });
    }

    function buildTimeAxisFormatter(mode) {
        var options = {
            time: { hour: '2-digit', minute: '2-digit' },
            date: { year: '2-digit', month: '2-digit', day: '2-digit' },
            'date-time': {
                year: '2-digit', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit'
            }
        }[mode];
        if (!options) { return undefined; }

        var formatter = typeof Intl === 'object' && typeof Intl.DateTimeFormat === 'function'
            ? new Intl.DateTimeFormat(undefined, options) : null;
        return function (value) {
            var date = new Date(Number(value));
            if (formatter) { return formatter.format(date); }
            var iso = date.toISOString();
            if (mode === 'time') { return iso.slice(11, 16); }
            if (mode === 'date') { return iso.slice(0, 10); }
            return iso.slice(0, 10) + ' ' + iso.slice(11, 16);
        };
    }

    function automaticGapThresholdSeconds(points) {
        if (!Array.isArray(points) || points.length < 3) { return 0; }
        var intervals = [];
        for (var index = 1; index < points.length; index += 1) {
            var interval = Number(points[index][0]) - Number(points[index - 1][0]);
            if (Number.isFinite(interval) && interval > 0) { intervals.push(interval); }
        }
        if (intervals.length < 2) { return 0; }
        intervals.sort(function (left, right) { return left - right; });
        var typicalInterval = intervals[Math.floor((intervals.length - 1) / 2)];
        return typicalInterval * 3;
    }

    function buildSeriesData(item, range) {
        var points = Array.isArray(item.points) ? item.points : [];
        var mode = range && typeof range.gapDetectionMode === 'string'
            ? range.gapDetectionMode : 'off';
        var threshold = mode === 'custom'
            ? Number(range.gapThresholdSeconds) || 0
            : (mode === 'automatic' ? automaticGapThresholdSeconds(points) : 0);
        var result = [];
        points.forEach(function (point, index) {
            if (threshold > 0 && index > 0) {
                var previousTimestamp = Number(points[index - 1][0]);
                var currentTimestamp = Number(point[0]);
                if (currentTimestamp - previousTimestamp > threshold) {
                    result.push([Math.round((previousTimestamp + currentTimestamp) / 2) * 1000, null]);
                }
            }
            result.push([point[0] * 1000, point[1]]);
        });
        return result;
    }

    function buildOption(model, theme) {
        var colors = colorsFor(theme);
        var axes = Array.isArray(model.axes) ? model.axes : [];
        var series = Array.isArray(model.series) ? model.series : [];
        var selectedSeries = seriesSelection.reconcile(series);
        var activeAxes = axes.map(function (_, index) {
            return series.some(function (item) { return item.axisIndex === index && selectedSeries[item.id]; });
        });
        var firstActiveAxis = activeAxes.indexOf(true);
        var annotations = Array.isArray(model.annotations) ? model.annotations : [];
        var seriesPalette = Array.isArray(colors.seriesColors) && colors.seriesColors.length > 0
            ? colors.seriesColors : [colors.accent || colors.border];
        var seriesColors = series.map(function (item, index) {
            return item.color || seriesPalette[index % seriesPalette.length];
        });
        var axisColors = axes.map(function (axis, axisIndex) {
            var seriesIndex = series.findIndex(function (item) {
                return item.axisIndex === axisIndex && selectedSeries[item.id];
            });
            return seriesIndex >= 0 ? seriesColors[seriesIndex] : colors.border;
        });
        var headerInset = ((bootstrap.options && bootstrap.options.tileHeaderInset) || 10);
        var zoom = model.chart && model.chart.enableZoom === true;
        var compact = chartElement.clientHeight < 280;
        var design = model.chart && model.chart.design || {};
        var legendPosition = ['top', 'bottom', 'hidden'].indexOf(design.legendPosition) >= 0
            ? design.legendPosition : 'top';
        var showLegend = legendPosition !== 'hidden';
        var bottomLegend = legendPosition === 'bottom';
        var lineWidth = 2 * Math.max(50, Math.min(200, Number(design.lineWidthPercent) || 100)) / 100;
        var symbolSize = 6 * Math.max(50, Math.min(200, Number(design.symbolSizePercent) || 100)) / 100;
        var areaOpacity = echartsDesign.opacityFromPercent(design.areaOpacityPercent, 22);
        function designColor(value, fallback) {
            return typeof value === 'string' && /^#[0-9a-f]{6}$/i.test(value) ? value : fallback;
        }
        function fontSize(value, base) {
            var percent = Number(value);
            return Math.round(base * (Number.isFinite(percent) && percent >= 50 && percent <= 200 ? percent : 100) / 100);
        }
        var titleColor = designColor(design.titleColor, colors.text);
        var legendColor = designColor(design.legendColor, colors.text);
        var configuredAxisColor = designColor(design.axisColor, '');
        var configuredGridColor = designColor(design.gridColor, '');
        var gridColor = configuredGridColor || colors.track || colors.border;
        var titleFontSize = fontSize(design.titleFontSizePercent, 18);
        var legendFontSize = fontSize(design.legendFontSizePercent, 12);
        var axisFontSize = fontSize(design.axisFontSizePercent, 12);
        var legendFontOverride = Number(design.legendFontSizePercent) >= 50
            && Number(design.legendFontSizePercent) <= 200
            && Number(design.legendFontSizePercent) !== 100;
        var axisFontOverride = Number(design.axisFontSizePercent) >= 50
            && Number(design.axisFontSizePercent) <= 200
            && Number(design.axisFontSizePercent) !== 100;
        var titleVisible = Boolean(model.chart && model.chart.title);
        var titleBlock = titleVisible ? Math.max(34, titleFontSize + 16) : 0;
        var gridTop = headerInset + (legendPosition === 'top'
            ? (titleVisible ? titleBlock : 34) + Math.max(30, legendFontSize + 18)
            : (titleVisible ? Math.max(48, titleFontSize + 30) : 20));
        var gridBottom = bottomLegend ? (zoom ? 94 : 58) : (zoom ? 64 : 34);
        var axisCounts = { left: 0, right: 0 };
        var normalizedAxes = axes.map(function (axis, index) {
            var position = axis.position === 'left' || axis.position === 'right'
                ? axis.position : (index === 0 ? 'left' : 'right');
            var positionIndex = axisCounts[position];
            if (activeAxes[index]) { axisCounts[position] += 1; }
            return { axis: axis, position: position, positionIndex: positionIndex };
        });
        var chartWidth = Math.max(320, Number(chartElement.clientWidth) || 750);
        var baseAxisMargin = Math.round(58 * Math.max(1, axisFontSize / 12));
        var maximumAxisMargin = Math.max(baseAxisMargin, Math.min(190, chartWidth * 0.32));
        var maximumSideCount = Math.max(axisCounts.left, axisCounts.right);
        var axisOffsetStep = maximumSideCount > 1
            ? Math.min(90, (maximumAxisMargin - baseAxisMargin) / (maximumSideCount - 1)) : 0;
        var leftMargin = axisCounts.left > 0 ? baseAxisMargin + axisOffsetStep * (axisCounts.left - 1) : 22;
        var rightMargin = axisCounts.right > 0 ? baseAxisMargin + axisOffsetStep * (axisCounts.right - 1) : 22;
        if (design.showYAxis === false) {
            leftMargin = 22;
            rightMargin = 22;
        }

        return {
            backgroundColor: bootstrap.mode === 'ipsview' && bootstrap.options.adaptToBackground === true
                ? 'transparent' : colors.background,
            color: seriesPalette,
            ...echartsDesign.animationOptions(design, {
                enabled: false, initialDuration: 350, updateDuration: 500
            }),
            aria: {
                enabled: true,
                description: series.map(function (item) { return String(item.label || ''); }).join(', '),
                decal: { show: false }
            },
            title: {
                show: titleVisible,
                text: model.chart && model.chart.title || '',
                left: 'center',
                top: headerInset,
                textStyle: { color: titleColor, fontSize: titleFontSize }
            },
            legend: {
                show: showLegend,
                type: 'scroll',
                left: 12,
                right: 12,
                selected: Object.assign({}, selectedSeries),
                scrollDataIndex: seriesSelection.scrollDataIndex(),
                selector: series.length > 1 ? [
                    { type: 'all', title: translate('All') },
                    { type: 'inverse', title: translate('Invert') }
                ] : [],
                selectorLabel: { color: legendColor, fontSize: legendFontSize },
                pageTextStyle: { color: legendColor },
                pageIconColor: legendColor,
                pageIconInactiveColor: colors.muted,
                tooltip: { show: true, confine: true, formatter: function (parameters) {
                    var item = series.find(function (entry) { return entry.id === parameters.name; });
                    return item ? escapeHTML(item.label) + (item.unit ? ' (' + escapeHTML(item.unit) + ')' : '') : '';
                } },
                top: legendPosition === 'top' ? headerInset + (titleVisible ? titleBlock : 4) : null,
                bottom: bottomLegend ? 6 : null,
                textStyle: {
                    color: legendColor, width: Math.max(40, Math.min(220, chartWidth * 0.32)),
                    overflow: 'truncate', ...(legendFontOverride ? { fontSize: legendFontSize } : {})
                },
                data: series.map(function (item) { return item.id; }),
                formatter: function (name) {
                    var source = series.find(function (item) { return item.id === name; });
                    return source ? String(source.label || '') : '';
                }
            },
            grid: { left: leftMargin, right: rightMargin, top: gridTop, bottom: gridBottom },
            tooltip: {
                trigger: 'axis',
                confine: true,
                extraCssText: 'max-width:calc(100vw - 24px);max-height:70vh;overflow:auto;white-space:normal;overflow-wrap:anywhere;',
                axisPointer: { type: 'cross' },
                formatter: function (parameters) {
                    if (!Array.isArray(parameters) || parameters.length === 0) { return ''; }
                    var heading = escapeHTML(parameters[0].axisValueLabel || '');
                    return [heading].concat(parameters.map(function (parameter) {
                        var item = series.find(function (entry) { return entry.id === parameter.seriesId; })
                            || series[parameter.seriesIndex] || {};
                        var value = Array.isArray(parameter.value) ? Number(parameter.value[1]) : Number(parameter.value);
                        var decimals = Math.max(0, Math.min(6, Number(item.decimals) || 0));
                        var formatted = Number.isFinite(value)
                            ? value.toLocaleString(undefined, {
                                minimumFractionDigits: decimals,
                                maximumFractionDigits: decimals
                            })
                            : '–';
                        return (parameter.marker || '') + escapeHTML(item.label || parameter.seriesName || '') + ': '
                            + escapeHTML(formatted) + (item.unit ? ' ' + escapeHTML(item.unit) : '');
                    })).join('<br>');
                }
            },
            dataZoom: zoomController.timeAxisOptions(zoom, bootstrap.mode, bottomLegend ? 38 : 12, compact),
            xAxis: {
                type: 'time',
                min: model.range.startTimestamp * 1000,
                max: model.range.endTimestamp * 1000,
                axisLine: { show: design.showXAxis !== false, lineStyle: { color: configuredAxisColor || colors.border } },
                axisTick: { show: design.showXAxis !== false },
                axisLabel: {
                    show: design.showXAxis !== false,
                    color: configuredAxisColor || colors.muted,
                    ...(axisFontOverride ? { fontSize: axisFontSize } : {}),
                    formatter: buildTimeAxisFormatter(model.chart && model.chart.timeAxisLabelFormat)
                },
                splitLine: { show: false }
            },
            yAxis: normalizedAxes.map(function (entry, index) {
                var axis = entry.axis;
                var axisColor = configuredAxisColor || axisColors[index] || colors.border;
                var showAxis = design.showYAxis !== false && activeAxes[index];
                var valueAxis = {
                    show: activeAxes[index],
                    type: 'value',
                    scale: true,
                    boundaryGap: ['5%', '5%'],
                    name: showAxis ? axis.unit || '' : '',
                    position: entry.position,
                    offset: entry.positionIndex * axisOffsetStep,
                    axisLine: { show: showAxis, lineStyle: { color: axisColor } },
                    axisTick: {
                        show: showAxis,
                        lineStyle: { color: axisColor }
                    },
                    axisLabel: {
                        show: showAxis,
                        color: axisColor,
                        ...(axisFontOverride ? { fontSize: axisFontSize } : {}),
                        formatter: '{value}' + (axis.unit ? ' ' + axis.unit : '')
                    },
                    splitLine: {
                        show: index === firstActiveAxis && design.showGrid !== false,
                        lineStyle: { color: gridColor, opacity: configuredGridColor ? 1 : 0.35 }
                    },
                    nameTextStyle: { color: axisColor, ...(axisFontOverride ? { fontSize: axisFontSize } : {}) }
                };
                var minimum = Number(axis.minimum);
                var maximum = Number(axis.maximum);
                if (Number.isFinite(minimum) && Number.isFinite(maximum) && minimum < maximum) {
                    valueAxis.min = minimum;
                    valueAxis.max = maximum;
                }
                return valueAxis;
            }),
            series: series.map(function (item, index) {
                var seriesColor = seriesColors[index];
                var sourceDesign = item.design
                    && typeof item.design === 'object'
                    && !Array.isArray(item.design)
                    && Object.keys(item.design).length > 0
                    ? item.design : null;
                var sourceLineWidth = sourceDesign
                    ? 2 * Math.max(50, Math.min(200, Number(sourceDesign.lineWidthPercent) || 100)) / 100
                    : lineWidth;
                var sourceSymbolSize = sourceDesign
                    ? 6 * Math.max(50, Math.min(200, Number(sourceDesign.pointSizePercent) || 100)) / 100
                    : symbolSize;
                var sourceSymbol = sourceDesign ? String(sourceDesign.pointSymbol || 'none') : 'circle';
                var sourceShowSymbol = sourceDesign ? sourceSymbol !== 'none' : design.showSymbols === true;
                var result = {
                    id: item.id,
                    name: item.id,
                    type: 'line',
                    yAxisIndex: item.axisIndex,
                    showSymbol: sourceShowSymbol,
                    symbol: sourceSymbol === 'none' ? 'circle' : sourceSymbol,
                    symbolSize: sourceSymbolSize,
                    smooth: sourceDesign ? sourceDesign.smoothLine === true : design.smoothLines === true,
                    connectNulls: false,
                    sampling: 'lttb',
                    lineStyle: {
                        width: sourceLineWidth,
                        type: sourceDesign ? String(sourceDesign.lineType || 'solid') : 'solid',
                        color: seriesColor
                    },
                    itemStyle: { color: seriesColor },
                    label: {
                        show: design.showValues === true,
                        ...echartsDesign.valueLabel(design, 'top',
                            ['top', 'bottom', 'left', 'right', 'inside', 'insideTop', 'insideBottom',
                                'insideLeft', 'insideRight'], 5),
                        color: design.valueColor || colors.text,
                        fontSize: echartsDesign.fontSizePercent(design.valueFontSizePercent, 12),
                        formatter: function (parameters) {
                            var value = Array.isArray(parameters.value) ? parameters.value[1] : parameters.value;
                            var decimals = Math.max(0, Math.min(6, Number(item.decimals) || 0));
                            return Number(value).toLocaleString(undefined, {
                                minimumFractionDigits: decimals, maximumFractionDigits: decimals
                            }) + (item.unit ? ' ' + item.unit : '');
                        }
                    },
                    data: buildSeriesData(item, model.range)
                };
                if (item.style === 'area') {
                    var sourceAreaOpacity = sourceDesign
                        ? echartsDesign.opacityFromPercent(sourceDesign.areaOpacityPercent, 22)
                        : areaOpacity;
                    result.areaStyle = { opacity: sourceAreaOpacity };
                    if (sourceDesign && sourceDesign.areaFillMode === 'gradient') {
                        result.areaStyle.color = {
                            type: 'linear', x: 0, y: 0, x2: 0, y2: 1,
                            colorStops: [
                                { offset: 0, color: seriesColor },
                                { offset: 1, color: sourceDesign.areaGradientColor || 'transparent' }
                            ],
                            global: false
                        };
                    } else if (sourceDesign && sourceDesign.areaFillMode === 'svg') {
                        var pattern = areaPatterns.resolve(sourceDesign.areaPatternImage,
                            sourceDesign.areaSVGSizePercent, sourceDesign.areaPatternAspectRatio);
                        if (pattern) { result.areaStyle.color = pattern; }
                    }
                }
                var seriesAnnotations = annotations.filter(function (annotation) {
                    return annotation && Number(annotation.seriesIndex) === index;
                });
                var referenceLines = seriesAnnotations.filter(function (annotation) {
                    return annotation.type === 'line';
                });
                if (referenceLines.length > 0) {
                    result.markLine = {
                        silent: true,
                        symbol: 'none',
                        animation: false,
                        data: referenceLines.map(function (annotation) {
                            var annotationColor = annotation.color || seriesColor;
                            var label = String(annotation.label || '');
                            return {
                                name: label,
                                yAxis: Number(annotation.value),
                                lineStyle: {
                                    color: annotationColor,
                                    type: String(annotation.lineType || 'solid'),
                                    width: 2 * Math.max(50, Math.min(200,
                                        Number(annotation.lineWidthPercent) || 100)) / 100
                                },
                                label: {
                                    show: label !== '',
                                    formatter: label,
                                    color: annotationColor,
                                    position: 'insideEndTop'
                                }
                            };
                        })
                    };
                }
                var valueRanges = seriesAnnotations.filter(function (annotation) {
                    return annotation.type === 'area';
                });
                if (valueRanges.length > 0) {
                    result.markArea = {
                        silent: true,
                        animation: false,
                        data: valueRanges.map(function (annotation) {
                            var annotationColor = annotation.color || seriesColor;
                            var label = String(annotation.label || '');
                            return [
                                {
                                    name: label,
                                    yAxis: Number(annotation.value),
                                    itemStyle: {
                                        color: annotationColor,
                                        opacity: echartsDesign.opacityFromPercent(annotation.opacityPercent, 0)
                                    },
                                    label: {
                                        show: label !== '',
                                        formatter: label,
                                        color: annotationColor,
                                        position: 'insideTopRight'
                                    }
                                },
                                { yAxis: Number(annotation.maximum) }
                            ];
                        })
                    };
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
        if (retainOnTransientError(state, currentState, chart)) { return; }
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
        if (!areaPatternsReady(state.chart)) {
            if (!chart) { chartElement.hidden = true; }
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
            if (typeof chart.on === 'function') {
                ['legendselectchanged', 'legendselectall', 'legendinverseselect'].forEach(function (eventName) {
                    chart.on(eventName, function () { if (currentState) { render(currentState); } });
                });
            }
        }
        warningElement.textContent = translate('The selected raw range was truncated by the point budget.');
        warningElement.hidden = !state.chart.truncated;
        zoomController.apply(chart, buildOption(state.chart, theme), savedZoom);
    }

    function appendPoint(message, updateView) {
        if (!currentState || currentState.status !== 'ready' || !currentState.chart) { return; }
        var model = currentState.chart;
        if (model.range.acceptLiveUpdates === false) { return; }
        var item = model.series.find(function (series) { return series.variableID === message.variableID; });
        if (!item) { return; }
        var points = item.points;
        var last = points[points.length - 1];
        if (last && last[0] === message.timestamp) {
            last[1] = message.value;
        } else {
            points.push([message.timestamp, message.value]);
        }
        var cutoff = model.range.calendarAligned === true
            ? model.range.startTimestamp
            : message.timestamp - model.range.durationSeconds;
        item.points = points.filter(function (point) { return point[0] >= cutoff; });
        var limit = Math.max(1, Number(model.range.pointLimitPerSeries) || 1);
        if (item.points.length > limit) {
            item.points = item.points.slice(item.points.length - limit);
        }
        if (model.range.calendarAligned !== true) {
            model.range.startTimestamp = cutoff;
        }
        model.range.endTimestamp = message.timestamp;
        if (updateView !== false) { render(currentState); }
    }

    window.handleMessage = function (message) {
        var value = message;
        if (typeof message === 'string') {
            try {
                value = JSON.parse(message);
            } catch (error) {
                return;
            }
        }
        if (value && value.messageType === 'append') {
            if (startupPending) {
                pendingAppends.push(value);
                if (pendingAppends.length > maximumPendingAppends) { pendingAppends.shift(); }
                return;
            }
            appendPoint(value);
        } else {
            var state = echartsDesign.parseVisualizationState(value);
            if (state) {
                if (cancelStartup) { cancelStartup(); }
                startupPending = false;
                pendingAppends = [];
                render(state);
            }
        }
    };

    if (window.ResizeObserver) {
        new ResizeObserver(function () {
            if (!echartsDesign.resizeChartIfNeeded(chart, chartElement)) { return; }
            if (currentState && currentState.status === 'ready' && currentState.chart) {
                var range = currentState.chart.range;
                var savedZoom = zoomController.capture(chart, range, range);
                seriesSelection.capture(chart);
                zoomController.apply(chart, buildOption(currentState.chart, currentTheme), savedZoom);
            }
        }).observe(chartElement);
    } else {
        window.addEventListener('resize', function () { if (chart) { chart.resize(); } });
    }
    if (bootstrap.mode === 'ipsview') {
        zoomController.attachIPSViewWheel(chartElement, function () { return chart; }, function () {
            return currentState && currentState.status === 'ready'
                && currentState.chart && currentState.chart.chart
                && currentState.chart.chart.enableZoom === true;
        });
    }
    window.addEventListener('beforeunload', function () {
        if (cancelStartup) { cancelStartup(); }
        startupPending = false;
        pendingAppends = [];
        if (chart) { chart.dispose(); }
    });
    var cancelStartup = window.SYMC_ECHARTS_STARTUP(bootstrap, function (state) {
        startupPending = false;
        render(state);
        if (pendingAppends.length > 0) {
            pendingAppends.forEach(function (message) { appendPoint(message, false); });
            pendingAppends = [];
            render(currentState);
        }
    }, function () {
        startupPending = false;
        pendingAppends = [];
        displayError('The time series could not be loaded.');
    });
}());
