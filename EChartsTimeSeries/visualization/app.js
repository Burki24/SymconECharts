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

    function translate(text) {
        return (bootstrap.translations || {})[text] || text;
    }

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
        var annotations = Array.isArray(model.annotations) ? model.annotations : [];
        var seriesPalette = Array.isArray(colors.seriesColors) && colors.seriesColors.length > 0
            ? colors.seriesColors : [colors.accent || colors.border];
        var seriesColors = series.map(function (item, index) {
            return item.color || seriesPalette[index % seriesPalette.length];
        });
        var axisColors = axes.map(function (axis, axisIndex) {
            var seriesIndex = series.findIndex(function (item) { return item.axisIndex === axisIndex; });
            return seriesIndex >= 0 ? seriesColors[seriesIndex] : colors.border;
        });
        var headerInset = bootstrap.mode === 'symcon' && bootstrap.options.tileHeaderVisible !== false ? 58 : 10;
        var zoom = model.chart && model.chart.enableZoom === true;
        var design = model.chart && model.chart.design || {};
        var legendPosition = ['top', 'bottom', 'hidden'].indexOf(design.legendPosition) >= 0
            ? design.legendPosition : 'top';
        var showLegend = legendPosition !== 'hidden';
        var bottomLegend = legendPosition === 'bottom';
        var lineWidth = 2 * Math.max(50, Math.min(200, Number(design.lineWidthPercent) || 100)) / 100;
        var symbolSize = 6 * Math.max(50, Math.min(200, Number(design.symbolSizePercent) || 100)) / 100;
        var areaOpacity = echartsDesign.opacityFromPercent(design.areaOpacityPercent, 22);
        var titleVisible = Boolean(model.chart && model.chart.title);
        var gridTop = headerInset + (legendPosition === 'top' ? 64 : (titleVisible ? 48 : 20));
        var gridBottom = bottomLegend ? (zoom ? 94 : 58) : (zoom ? 64 : 34);
        var axisCounts = { left: 0, right: 0 };
        var normalizedAxes = axes.map(function (axis, index) {
            var position = axis.position === 'left' || axis.position === 'right'
                ? axis.position : (index === 0 ? 'left' : 'right');
            var positionIndex = Number.isInteger(axis.positionIndex) && axis.positionIndex >= 0
                ? axis.positionIndex : axisCounts[position];
            axisCounts[position] = Math.max(axisCounts[position], positionIndex + 1);
            return { axis: axis, position: position, positionIndex: positionIndex };
        });
        var chartWidth = Math.max(320, Number(chartElement.clientWidth) || 750);
        var maximumAxisMargin = Math.max(58, Math.min(190, chartWidth * 0.32));
        var maximumSideCount = Math.max(axisCounts.left, axisCounts.right);
        var axisOffsetStep = maximumSideCount > 1
            ? Math.min(54, (maximumAxisMargin - 58) / (maximumSideCount - 1)) : 0;
        var leftMargin = axisCounts.left > 0 ? 58 + axisOffsetStep * (axisCounts.left - 1) : 22;
        var rightMargin = axisCounts.right > 0 ? 58 + axisOffsetStep * (axisCounts.right - 1) : 22;
        if (design.showYAxis === false) {
            leftMargin = 22;
            rightMargin = 22;
        }

        return {
            backgroundColor: bootstrap.mode === 'ipsview' && bootstrap.options.adaptToBackground === true
                ? 'transparent' : colors.background,
            color: seriesPalette,
            animation: false,
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
                textStyle: { color: colors.text, fontSize: 18 }
            },
            legend: {
                show: showLegend,
                top: legendPosition === 'top' ? headerInset + (titleVisible ? 34 : 4) : null,
                bottom: bottomLegend ? 6 : null,
                textStyle: { color: colors.text },
                data: series.map(function (item) { return item.id; }),
                formatter: function (name) {
                    var source = series.find(function (item) { return item.id === name; });
                    return source ? String(source.label || '') : '';
                }
            },
            grid: { left: leftMargin, right: rightMargin, top: gridTop, bottom: gridBottom },
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
            dataZoom: zoomController.options(zoom, bootstrap.mode, bottomLegend ? 38 : 12),
            xAxis: {
                type: 'time',
                min: model.range.startTimestamp * 1000,
                max: model.range.endTimestamp * 1000,
                axisLine: { show: design.showXAxis !== false, lineStyle: { color: colors.border } },
                axisTick: { show: design.showXAxis !== false },
                axisLabel: {
                    show: design.showXAxis !== false,
                    color: colors.muted,
                    formatter: buildTimeAxisFormatter(model.chart && model.chart.timeAxisLabelFormat)
                },
                splitLine: { show: false }
            },
            yAxis: normalizedAxes.map(function (entry, index) {
                var axis = entry.axis;
                var axisColor = axisColors[index] || colors.border;
                var valueAxis = {
                    type: 'value',
                    name: design.showYAxis !== false ? axis.unit || '' : '',
                    position: entry.position,
                    offset: entry.positionIndex * axisOffsetStep,
                    axisLine: { show: design.showYAxis !== false, lineStyle: { color: axisColor } },
                    axisTick: {
                        show: design.showYAxis !== false,
                        lineStyle: { color: axisColor }
                    },
                    axisLabel: {
                        show: design.showYAxis !== false,
                        color: axisColor,
                        formatter: '{value}' + (axis.unit ? ' ' + axis.unit : '')
                    },
                    splitLine: {
                        show: index === 0 && design.showGrid !== false,
                        lineStyle: { color: colors.track || colors.border, opacity: 0.35 }
                    },
                    nameTextStyle: { color: axisColor }
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
        }
        warningElement.textContent = translate('The selected raw range was truncated by the point budget.');
        warningElement.hidden = !state.chart.truncated;
        zoomController.apply(chart, buildOption(state.chart, theme), savedZoom);
    }

    function appendPoint(message) {
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
        new ResizeObserver(function () {
            if (!chart) { return; }
            chart.resize();
            if (currentState && currentState.status === 'ready' && currentState.chart) {
                var range = currentState.chart.range;
                var savedZoom = zoomController.capture(chart, range, range);
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
    window.addEventListener('beforeunload', function () { if (chart) { chart.dispose(); } });
    render(currentState);
}());
