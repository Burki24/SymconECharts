(function () {
    'use strict';

    var bootstrap = window.SYMC_VISUALIZATION || {};
    var chartElement = document.getElementById('echarts-timeseries-chart');
    var warningElement = document.getElementById('echarts-timeseries-warning');
    var errorElement = document.getElementById('echarts-timeseries-error');
    var chart = null;
    var currentState = bootstrap.state || null;
    var currentTheme = null;
    var areaPatternCache = Object.create(null);

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

    function resolveAreaPattern(design) {
        var source = design && design.areaPatternImage;
        if (typeof source !== 'string' || source.indexOf('data:image/svg+xml;base64,') !== 0
            || typeof window.Image !== 'function') {
            return null;
        }
        var sizePercent = Math.max(25, Math.min(400, Number(design.areaSVGSizePercent) || 100));
        var key = source + '|' + sizePercent;
        var cached = areaPatternCache[key];
        if (cached && cached.status === 'ready') { return cached.pattern; }
        if (cached) { return null; }

        var image = new window.Image();
        areaPatternCache[key] = { status: 'loading', pattern: null };
        image.onload = function () {
            var patternImage = image;
            var size = Math.max(8, Math.round(64 * sizePercent / 100));
            var aspectRatio = Math.max(0.05, Math.min(20, Number(design.areaPatternAspectRatio) || 1));
            if (typeof document.createElement === 'function') {
                var canvas = document.createElement('canvas');
                if (canvas && typeof canvas.getContext === 'function') {
                    canvas.width = size;
                    canvas.height = Math.max(8, Math.round(size / aspectRatio));
                    var context = canvas.getContext('2d');
                    if (context && typeof context.drawImage === 'function') {
                        context.drawImage(image, 0, 0, canvas.width, canvas.height);
                        patternImage = canvas;
                    }
                }
            }
            areaPatternCache[key] = {
                status: 'ready',
                pattern: { image: patternImage, repeat: 'repeat' }
            };
            schedulePatternRender();
        };
        image.onerror = function () { areaPatternCache[key] = { status: 'failed', pattern: null }; };
        image.src = source;

        return null;
    }

    function buildOption(model, theme) {
        var colors = colorsFor(theme);
        var axes = Array.isArray(model.axes) ? model.axes : [];
        var series = Array.isArray(model.series) ? model.series : [];
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
        var areaOpacity = Math.max(0, Math.min(100, Number(design.areaOpacityPercent) || 0)) / 100;
        if (design.areaOpacityPercent === undefined) { areaOpacity = 0.22; }
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
            dataZoom: zoom ? [
                {
                    type: 'inside',
                    xAxisIndex: 0,
                    start: 0,
                    end: 100,
                    zoomOnMouseWheel: bootstrap.mode !== 'ipsview',
                    moveOnMouseWheel: false
                },
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
            yAxis: normalizedAxes.map(function (entry, index) {
                var axis = entry.axis;
                var axisColor = axisColors[index] || colors.border;
                return {
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
                    name: item.label,
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
                    data: item.points.map(function (point) { return [point[0] * 1000, point[1]]; })
                };
                if (item.style === 'area') {
                    var sourceAreaOpacity = sourceDesign
                        ? Math.max(0, Math.min(100, Number(sourceDesign.areaOpacityPercent) || 0)) / 100
                        : areaOpacity;
                    if (sourceDesign && sourceDesign.areaOpacityPercent === undefined) {
                        sourceAreaOpacity = 0.22;
                    }
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
                        var pattern = resolveAreaPattern(sourceDesign);
                        if (pattern) { result.areaStyle.color = pattern; }
                    }
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

    function handleIPSViewWheel(event) {
        if (bootstrap.mode !== 'ipsview'
            || !chart
            || !currentState
            || currentState.status !== 'ready'
            || !currentState.chart
            || !currentState.chart.chart
            || currentState.chart.chart.enableZoom !== true) {
            return;
        }

        var delta = Number(event.deltaY);
        if (!Number.isFinite(delta) || delta === 0) { return; }

        var option = chart.getOption();
        var zoom = option && Array.isArray(option.dataZoom) ? option.dataZoom[0] : null;
        var start = zoom && Number.isFinite(Number(zoom.start)) ? Number(zoom.start) : 0;
        var end = zoom && Number.isFinite(Number(zoom.end)) ? Number(zoom.end) : 100;
        var span = Math.max(1, Math.min(100, end - start));
        var nextSpan = Math.max(1, Math.min(100, span * (delta < 0 ? 0.8 : 1.25)));
        var bounds = chartElement.getBoundingClientRect();
        var anchor = bounds.width > 0 ? (Number(event.clientX) - bounds.left) / bounds.width : 0.5;
        anchor = Math.max(0, Math.min(1, Number.isFinite(anchor) ? anchor : 0.5));
        var anchorValue = start + span * anchor;
        var nextStart = anchorValue - nextSpan * anchor;
        var nextEnd = anchorValue + nextSpan * (1 - anchor);

        if (nextStart < 0) {
            nextEnd -= nextStart;
            nextStart = 0;
        }
        if (nextEnd > 100) {
            nextStart -= nextEnd - 100;
            nextEnd = 100;
        }
        nextStart = Math.max(0, nextStart);
        nextEnd = Math.min(100, nextEnd);

        event.preventDefault();
        event.stopPropagation();
        chart.dispatchAction({
            type: 'dataZoom',
            dataZoomIndex: 0,
            start: nextStart,
            end: nextEnd
        });
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
        new ResizeObserver(function () {
            if (!chart) { return; }
            chart.resize();
            if (currentState && currentState.status === 'ready' && currentState.chart) {
                chart.setOption(buildOption(currentState.chart, currentTheme), true);
            }
        }).observe(chartElement);
    } else {
        window.addEventListener('resize', function () { if (chart) { chart.resize(); } });
    }
    if (bootstrap.mode === 'ipsview') {
        chartElement.addEventListener('wheel', handleIPSViewWheel, { passive: false });
    }
    window.addEventListener('beforeunload', function () { if (chart) { chart.dispose(); } });
    render(currentState);
}());
