(function () {
    'use strict';

    var bootstrap = window.SYMC_VISUALIZATION || {};
    var chartElement = document.getElementById('echarts-bar-polar-chart');
    var errorElement = document.getElementById('echarts-bar-polar-error');
    var chart = null;
    var currentState = bootstrap.state || null;
    var currentTheme = null;
    var lastWidth = 0;
    var lastHeight = 0;
    var pinnedItemId = null;

    function translate(value) {
        return (bootstrap.translations || {})[value] || value;
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

    function escapeHtml(value) {
        return String(value).replace(/[&<>"']/g, function (character) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[character];
        });
    }

    function orderedItems(items, order) {
        var result = items.slice();
        if (order === 'ascending' || order === 'descending') {
            result.sort(function (left, right) {
                var difference = Number(left.value) - Number(right.value);
                return (order === 'ascending' ? difference : -difference)
                    || Number(left.order) - Number(right.order);
            });
        }
        return result;
    }

    function fontSize(value, fallback) {
        var size = Number(value);
        return Number.isInteger(size) && size >= 8 && size <= 24 ? size : fallback;
    }

    function polarLayout(polar) {
        var style = polar.style || {};
        var width = Math.max(1, chartElement.clientWidth || 1);
        var height = Math.max(1, chartElement.clientHeight || 1);
        var headerInset = bootstrap.mode === 'symcon' && bootstrap.options.tileHeaderVisible !== false ? 58 : 8;
        var labelWidth = Math.min(90, Math.max(36, Math.round(width * 0.13)));
        var labelMargin = Math.min(24, Math.max(16, Math.round(width * 0.05)));
        if (style.valueLabelPosition === 'outside') { labelMargin += 14; }
        var top = headerInset + (polar.title ? 50 : 12);
        var bottom = style.showCategoryLabels !== false ? Math.min(45, height * 0.14) : 24;
        var side = style.showCategoryLabels !== false ? labelWidth + labelMargin + 8
            : style.valueLabelPosition === 'outside' ? 64 : 36;
        var outerPercent = Math.max(20, Math.min(95, Number(style.outerRadiusPercent) || 76));
        var innerPercent = Math.max(0, Math.min(75, Number(style.innerRadiusPercent) || 0));
        var requestedRadius = Math.min(width, height) * outerPercent / 200;
        var availableRadius = Math.max(1, Math.min(width / 2 - side, (height - top - bottom) / 2));
        var outerRadius = Math.min(requestedRadius, availableRadius);
        return {
            geometry: {
                center: [width / 2, (top + height - bottom) / 2],
                radius: [outerRadius * innerPercent / outerPercent, outerRadius]
            },
            labelWidth: labelWidth,
            labelMargin: labelMargin,
            showValueScale: style.showGrid !== false && (outerRadius >= 110 || style.showValues !== true)
        };
    }

    function buildOption(model, theme) {
        var colors = colorsFor(theme);
        var design = window.SYMC_ECHARTS_DESIGN || {};
        var polar = model.polar || {};
        var style = polar.style || {};
        var highlightMode = ['outline', 'focus', 'off'].indexOf(style.barHighlightMode) >= 0
            ? style.barHighlightMode : 'standard';
        var mode = style.mode === 'tangential' ? 'tangential' : 'radial';
        var valueLabelPosition = ['middle', 'insideStart', 'insideEnd', 'outside']
            .indexOf(style.valueLabelPosition) >= 0
            ? style.valueLabelPosition : 'middle';
        var items = orderedItems(Array.isArray(model.items) ? model.items : [], style.sortOrder);
        var labels = items.map(function (item) { return String(item.label || ''); });
        var defaultDecimals = Math.max(0, Math.min(6, Number(polar.decimals) || 0));
        var unit = String(polar.unit || '');
        function itemDecimals(item) {
            return item && Number.isInteger(item.decimals)
                ? Math.max(0, Math.min(6, item.decimals)) : defaultDecimals;
        }
        var swatches = colors.seriesColors || [colors.accent];
        var titleVisible = Boolean(polar.title);
        var headerInset = bootstrap.mode === 'symcon' && bootstrap.options.tileHeaderVisible !== false ? 58 : 8;
        var layout = polarLayout(polar);
        var categoryFontSize = fontSize(style.categoryLabelFontSize, 10);
        var valueFontSize = fontSize(style.valueLabelFontSize, 10);
        var scaleFontSize = fontSize(style.scaleLabelFontSize, 10);
        var axisStyle = { color: colors.border, opacity: 0.7 };
        var splitStyle = { color: colors.border, opacity: 0.2 };
        var categoryAxis = {
            type: 'category',
            data: labels,
            z: mode === 'tangential' ? 3 : 0,
            axisLabel: {
                show: style.showCategoryLabels !== false, color: colors.text,
                interval: 0, hideOverlap: true,
                overflow: 'truncate', width: 90, fontSize: categoryFontSize, margin: 24,
                textBorderColor: colors.background, textBorderWidth: mode === 'tangential' ? 3 : 0
            },
            axisLine: { show: style.showGrid !== false, lineStyle: axisStyle },
            axisTick: { show: style.showGrid !== false, lineStyle: axisStyle },
            splitLine: { show: style.showGrid !== false, lineStyle: splitStyle }
        };
        var valueAxis = {
            type: 'value',
            axisLabel: { show: layout.showValueScale, color: colors.muted, fontSize: scaleFontSize },
            axisLine: { show: style.showGrid !== false, lineStyle: axisStyle },
            axisTick: { show: style.showGrid !== false, lineStyle: axisStyle },
            splitLine: { show: style.showGrid !== false, lineStyle: splitStyle }
        };
        var scaleMinimum = Number(style.valueAxisMinimum);
        var scaleMaximum = Number(style.valueAxisMaximum);
        if (style.valueAxisRangeMode === 'manual'
            && Number.isFinite(scaleMinimum) && Number.isFinite(scaleMaximum)
            && scaleMinimum < scaleMaximum) {
            valueAxis.min = scaleMinimum;
            valueAxis.max = scaleMaximum;
            valueAxis.startValue = scaleMinimum;
        }
        var trackOpacity = design.opacityFromPercent(style.barBackgroundOpacityPercent, 25);
        var barOpacity = design.opacityFromPercent(style.barOpacityPercent, 100);
        var normalOutlineWidth = Number(style.barOutlineWidth) || 0;
        var highlightStyle = design.barOutlineStyle(
            Math.max(3, Math.min(10, normalOutlineWidth + 2)),
            style.barHighlightColor, colors.text
        );
        highlightStyle.opacity = 1;
        var startAngle = Math.max(0, Math.min(360, Number(style.startAngle) || 0));
        var angularSpan = Number(style.angularSpan);
        var angleAxis = mode === 'radial' ? categoryAxis : valueAxis;
        angleAxis.startAngle = startAngle;
        angleAxis.clockwise = style.clockwise !== false;
        if (Number.isFinite(angularSpan) && angularSpan >= 30 && angularSpan < 360) {
            angleAxis.endAngle = startAngle + (angleAxis.clockwise ? -angularSpan : angularSpan);
            if (layout.geometry.radius[0] > 0) {
                angleAxis.axisLine.show = false;
            }
        }
        categoryAxis.axisLabel.width = layout.labelWidth;
        categoryAxis.axisLabel.margin = layout.labelMargin;
        var barThickness = (layout.geometry.radius[1] - layout.geometry.radius[0])
            / Math.max(1, items.length) * Math.max(20, Math.min(100, Number(style.barWidthPercent) || 60)) / 100;
        // ECharts adds half a rounded cap to the label anchor. Keep horizontal text
        // clear of that cap and inside the colored arc, especially on outer rings.
        function valueLabelDistance(index) {
            if (mode !== 'tangential' || style.roundCaps !== true
                || (valueLabelPosition !== 'insideStart' && valueLabelPosition !== 'insideEnd')) { return 5; }
            var middleRadius = layout.geometry.radius[0]
                + (index + 0.5) * (layout.geometry.radius[1] - layout.geometry.radius[0]) / Math.max(1, items.length);
            return barThickness / 2 + Math.max(30, valueFontSize * 3, middleRadius * 0.16);
        }

        return {
            backgroundColor: bootstrap.mode === 'ipsview' && bootstrap.options.adaptToBackground === true
                ? 'transparent' : colors.background,
            animation: true,
            animationDuration: 350,
            aria: {
                enabled: true,
                description: items.map(function (item) {
                    return item.label + ': ' + formatValue(item.value, itemDecimals(item), unit);
                }).join(', ')
            },
            title: {
                show: titleVisible, text: String(polar.title || ''), left: 'center', top: headerInset,
                textStyle: { color: colors.text, fontSize: 18 }
            },
            tooltip: {
                trigger: 'item',
                formatter: function (parameter) {
                    var item = items[parameter.dataIndex];
                    if (!item) { return ''; }
                    return escapeHtml(item.label) + '<br>'
                        + escapeHtml(formatValue(item.value, itemDecimals(item), unit));
                }
            },
            polar: layout.geometry,
            angleAxis: angleAxis,
            radiusAxis: mode === 'radial' ? valueAxis : categoryAxis,
            series: [{
                id: 'polar-values',
                type: 'bar',
                coordinateSystem: 'polar',
                barWidth: Math.max(20, Math.min(100, Number(style.barWidthPercent) || 60)) + '%',
                roundCap: style.roundCaps === true,
                showBackground: style.showBarBackground === true,
                backgroundStyle: {
                    color: style.barBackgroundColor || colors.border,
                    opacity: trackOpacity
                },
                emphasis: highlightMode === 'off' ? { disabled: true }
                    : highlightMode === 'standard' ? undefined : {
                        focus: highlightMode === 'focus' ? 'self' : 'none',
                        blurScope: 'series',
                        itemStyle: highlightStyle
                    },
                blur: highlightMode === 'focus' ? { itemStyle: { opacity: 0.25 } } : undefined,
                data: items.map(function (item, index) {
                    var itemColor = item.color || swatches[index % swatches.length] || colors.accent;
                    var gradient = style.barFillMode === 'gradient';
                    var fill = gradient ? {
                        type: 'linear', x: 0, y: 1, x2: 0, y2: 0,
                        colorStops: [
                            { offset: 0, color: itemColor },
                            { offset: 1, color: style.barGradientColor || colors.background }
                        ]
                    } : itemColor;
                    var textColor = valueLabelPosition === 'outside' ? colors.text
                        : typeof design.readableTextColor === 'function'
                            ? design.readableTextColor(itemColor, colors.text, colors.background)
                            : colors.text;
                    return {
                        id: item.id,
                        name: String(item.label || ''),
                        value: Number(item.value),
                        itemStyle: Object.assign(
                            { color: fill, opacity: barOpacity },
                            design.barOutlineStyle(style.barOutlineWidth, style.barOutlineColor, colors.border)
                        ),
                        label: {
                            distance: valueLabelDistance(index),
                            color: textColor,
                            textBorderColor: (gradient || barOpacity < 1) && valueLabelPosition !== 'outside'
                                ? (textColor === colors.text ? colors.background : colors.text) : undefined,
                            textBorderWidth: (gradient || barOpacity < 1) && valueLabelPosition !== 'outside' ? 2 : 0
                        }
                    };
                }),
                label: {
                    show: style.showValues === true,
                    position: valueLabelPosition,
                    color: colors.text,
                    fontSize: valueFontSize,
                    align: mode === 'tangential' && valueLabelPosition !== 'outside' ? 'center' : undefined,
                    rotate: 0,
                    formatter: function (parameter) {
                        return formatValue(parameter.value, itemDecimals(items[parameter.dataIndex]), unit);
                    }
                }
            }]
        };
    }

    function alignPartialBackgroundTracks(style) {
        if (!chart || !style || style.mode !== 'tangential' || style.showBarBackground !== true
            || !Number.isFinite(Number(style.angularSpan)) || Number(style.angularSpan) >= 360
            || typeof chart.getModel !== 'function' || typeof chart.getViewOfSeriesModel !== 'function') { return; }

        var model = chart.getModel();
        var series = model && model.getSeriesByIndex(0);
        var angle = model && model.getComponent('angleAxis');
        var view = series && chart.getViewOfSeriesModel(series);
        if (!angle || !view || !Array.isArray(view._backgroundEls)) { return; }

        // ECharts 6.1.0 hardcodes a full circle for tangential bar backgrounds.
        // Keep its own background sectors, but align them to the actual angle axis.
        var extent = angle.axis.getExtent();
        var start = -extent[0] * Math.PI / 180;
        var end = -extent[1] * Math.PI / 180;
        view._backgroundEls.forEach(function (track) {
            if (!track) { return; }
            track.stopAnimation();
            track.setShape({ startAngle: start, endAngle: end, clockwise: extent[0] >= extent[1] });
        });
    }

    function displayError(message) {
        if (chart) { chart.clear(); }
        chartElement.hidden = true;
        errorElement.textContent = translate(message || 'The Polar values could not be loaded.');
        errorElement.hidden = false;
    }

    function currentHighlightMode() {
        var style = currentState && currentState.chart && currentState.chart.polar
            && currentState.chart.polar.style;
        return style && style.barHighlightMode;
    }

    function pinnedDataIndex() {
        if (pinnedItemId === null || !currentState || !currentState.chart) { return -1; }
        var polar = currentState.chart.polar || {};
        var items = orderedItems(Array.isArray(currentState.chart.items) ? currentState.chart.items : [],
            (polar.style || {}).sortOrder);
        return items.findIndex(function (item) { return item.id === pinnedItemId; });
    }

    function togglePinnedBar(parameter) {
        if (!chart || !parameter || parameter.componentType !== 'series' || parameter.seriesIndex !== 0
            || !Number.isInteger(parameter.dataIndex)) { return; }
        var mode = currentHighlightMode();
        if (mode !== 'outline' && mode !== 'focus') { return; }
        var polar = currentState.chart.polar || {};
        var items = orderedItems(Array.isArray(currentState.chart.items) ? currentState.chart.items : [],
            (polar.style || {}).sortOrder);
        var item = items[parameter.dataIndex];
        if (!item || item.id == null) { return; }
        var previousIndex = pinnedDataIndex();
        if (previousIndex >= 0) {
            chart.dispatchAction({ type: 'downplay', seriesIndex: 0, dataIndex: previousIndex });
        }
        pinnedItemId = pinnedItemId === item.id ? null : item.id;
        if (pinnedItemId !== null) {
            chart.dispatchAction({ type: 'highlight', seriesIndex: 0, dataIndex: parameter.dataIndex });
        }
    }

    function restorePinnedBar() {
        var mode = currentHighlightMode();
        if (mode !== 'outline' && mode !== 'focus') {
            pinnedItemId = null;
            return;
        }
        var index = pinnedDataIndex();
        if (index < 0) {
            pinnedItemId = null;
            return;
        }
        chart.dispatchAction({ type: 'highlight', seriesIndex: 0, dataIndex: index });
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
            chart.on('click', { seriesIndex: 0 }, togglePinnedBar);
        }
        chart.setOption(buildOption(state.chart, theme), true);
        restorePinnedBar();
        alignPartialBackgroundTracks(state.chart.polar && state.chart.polar.style);
        lastWidth = chartElement.clientWidth;
        lastHeight = chartElement.clientHeight;
    }

    function resizeChart() {
        if (!chart) { return; }
        chart.resize();
        var width = chartElement.clientWidth;
        var height = chartElement.clientHeight;
        if (width === lastWidth && height === lastHeight) { return; }
        lastWidth = width;
        lastHeight = height;
        if (currentState && currentState.status === 'ready' && currentState.chart) {
            var polar = currentState.chart.polar || {};
            var layout = polarLayout(polar);
            var categoryAxisName = polar.style && polar.style.mode === 'tangential'
                ? 'radiusAxis' : 'angleAxis';
            var valueAxisName = categoryAxisName === 'angleAxis' ? 'radiusAxis' : 'angleAxis';
            var update = { polar: layout.geometry };
            update[categoryAxisName] = { axisLabel: { width: layout.labelWidth, margin: layout.labelMargin } };
            update[valueAxisName] = { axisLabel: { show: layout.showValueScale } };
            chart.setOption(update);
            alignPartialBackgroundTracks(polar.style);
        }
    }

    window.handleMessage = function (message) {
        var state = typeof message === 'string' ? JSON.parse(message) : message;
        if (state) { render(state); }
    };

    if (window.ResizeObserver) {
        new ResizeObserver(resizeChart).observe(chartElement);
    } else {
        window.addEventListener('resize', resizeChart);
    }
    window.addEventListener('beforeunload', function () { if (chart) { chart.dispose(); } });
    render(bootstrap.state || null);
}());
