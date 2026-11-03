(function () {
    'use strict';

    var bootstrap = window.SYMC_VISUALIZATION || {};
    var design = window.SYMC_ECHARTS_DESIGN;
    var rootElement = document.getElementById('echarts-boxplot-root');
    var chartElement = document.getElementById('echarts-boxplot-chart');
    var emptyElement = document.getElementById('echarts-boxplot-empty');
    var errorElement = document.getElementById('echarts-boxplot-error');
    var warningElement = document.getElementById('echarts-boxplot-warning');
    var chart = null;
    var currentState = bootstrap.state || null;
    var currentTheme = null;
    var categoryAnchor = null;
    var categoryStart = 0;

    var tileHeaderInset = (bootstrap.options && bootstrap.options.tileHeaderInset) || 0;
    var chartBootstrap = tileHeaderInset ? { mode: 'symcon', options: { tileHeaderVisible: false } } : bootstrap;
    chartElement.style.top = tileHeaderInset + 'px';
    chartElement.style.height = tileHeaderInset ? 'calc(100% - ' + tileHeaderInset + 'px)' : '100%';

    function translate(text) { return (bootstrap.translations || {})[text] || text; }
    var retainOnTransientError = design.createTransientFailureRetainer(
        chartElement, warningElement, translate, 'The Boxplot values could not be loaded.'
    );
    function normalizeTheme(theme) {
        var palettes = bootstrap.options && bootstrap.options.echartsThemes || {};
        return Object.prototype.hasOwnProperty.call(palettes, theme) ? theme : 'auto';
    }
    function escapeHTML(value) {
        return String(value).replace(/[&<>"']/g, function (character) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[character];
        });
    }
    function palette(theme) {
        var palettes = bootstrap.options && bootstrap.options.echartsThemes || {};
        return palettes[theme] || palettes.auto || {
            background: '#151619', text: '#F4F5F7', muted: '#969AA2',
            border: '#A5A9B0', accent: '#55CBB5'
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
    function buildOption(model, theme) {
        var colors = colorsFor(theme);
        var boxplot = model.boxplot || {};
        var style = boxplot.style || {};
        var items = boxplot.data || [];
        var iqr = boxplot.whiskers === 'iqr';
        var width = chartElement.clientWidth || 1;
        var height = chartElement.clientHeight || 1;
        var horizontal = style.orientation === 'horizontal';
        var compact = width < 430;
        var font = design.fontSizePercent(style.axisFontSizePercent, compact ? 10 : 12);
        var titleSize = design.fontSizePercent(style.titleFontSizePercent, 18);
        var titleLayout = design.chartTitleLayout(chartBootstrap, boxplot.title, titleSize);
        var top = titleLayout.contentTop + (boxplot.unit ? font + 14 : 10);
        var left = horizontal ? Math.min(width * 0.35, Math.max(60, font * 12)) : Math.min(width * 0.25, Math.max(45, font * 5));
        var right = horizontal ? Math.max(30, font * 3) : 22;
        var bottom = horizontal ? font * 3 + 24 : font * 2 + 22;
        var rotation = Number(style.axisLabelRotate) || 0;
        var requestedLabelWidth = Number(style.axisLabelMaxWidth) || 0;
        var requestedBoxWidth = Number(style.boxWidth == null ? 40 : style.boxWidth);
        var minimumSpan = Math.max(requestedBoxWidth / 0.65, font * (horizontal ? 1.75 : 3) + (horizontal ? 6 : 12));
        var crossSpace = Math.max(1, horizontal ? height - top - bottom : width - left - right);
        var visibleCount = Math.max(1, Math.min(items.length, Math.floor(crossSpace / minimumSpan)));
        var slot = crossSpace / visibleCount;
        var autoLabelWidth = Math.max(12, horizontal ? left - 15 : slot - 8);
        var labelWidth = requestedLabelWidth > 0 ? Math.min(requestedLabelWidth, horizontal ? left - 15 : width - 30) : autoLabelWidth;
        if (rotation) {
            var sine = Math.abs(Math.sin(rotation * Math.PI / 180));
            labelWidth = Math.min(labelWidth, Math.max(12, (horizontal ? slot * 0.7 : height * 0.3) / sine));
            if (!horizontal && style.showAxisLabels !== false) { bottom += labelWidth * sine; }
        }
        var scroll = visibleCount < items.length;
        if (scroll && horizontal) { right += 28; }
        if (scroll && !horizontal) { bottom += 28; }
        var anchorIndex = items.findIndex(function (item) { return item.id === categoryAnchor; });
        var first = Math.max(0, Math.min(items.length - visibleCount, anchorIndex >= 0 ? anchorIndex : categoryStart));
        var boxWidth = Math.max(1, Math.min(requestedBoxWidth, slot * 0.65));
        var minimumBoxWidth = Math.min(style.minBoxWidth == null ? 1 : style.minBoxWidth, boxWidth);
        var labels = Object.create(null);
        var sources = Object.create(null);
        var minimum = Infinity, maximum = -Infinity;
        var axisDecimals = 0;
        items.forEach(function (item) {
            labels[item.id] = item.label;
            sources[item.id] = item;
            if (item.statistics) {
                minimum = Math.min(minimum, iqr ? item.sampleMinimum : item.statistics[0]);
                maximum = Math.max(maximum, iqr ? item.sampleMaximum : item.statistics[4]);
                axisDecimals = Math.max(axisDecimals, item.decimals == null ? 2 : item.decimals);
            }
        });
        var splitNumber = style.splitNumber == null ? 5 : style.splitNumber;
        var step = (maximum > minimum ? maximum - minimum : Math.max(1, Math.abs(maximum)) * 0.1) / splitNumber;
        if (Number.isFinite(step) && step > 0) {
            // Preserve distinguishable ticks for narrow distributions of hourly means.
            axisDecimals = Math.max(axisDecimals, 1 - Math.floor(Math.log10(step)));
        }
        var scientificAxis = axisDecimals > 20;
        axisDecimals = Math.max(0, Math.min(20, axisDecimals));
        var axisNumberFormat = new Intl.NumberFormat(bootstrap.language || undefined, scientificAxis
            ? { notation: 'scientific', maximumSignificantDigits: 15 }
            : { maximumFractionDigits: axisDecimals });
        function format(value, item) {
            return Number(value).toLocaleString(bootstrap.language || undefined, { maximumFractionDigits: item.decimals == null ? 2 : item.decimals })
                + (item.unit ? ' ' + item.unit : '');
        }
        var category = { type: 'category', data: items.map(function (item) { return item.id; }),
            axisLabel: { show: style.showAxisLabels !== false, color: style.axisColor || colors.text,
                fontSize: font, interval: 0, hideOverlap: true, overflow: 'truncate',
                rotate: rotation, fontWeight: style.axisLabelBold === true ? 'bold' : 'normal', width: labelWidth,
                formatter: function (id) { return labels[id] || ''; } },
            axisTick: { show: style.showAxisTicks !== false, alignWithLabel: true },
            axisLine: { lineStyle: { color: colors.border, width: style.axisLineWidth == null ? 1 : style.axisLineWidth } } };
        var value = { type: 'value', scale: true, name: boxplot.unit || '', nameLocation: horizontal ? 'middle' : 'end',
            nameGap: horizontal ? font + 18 : 15,
            nameTextStyle: { color: style.axisColor || colors.text, fontSize: font },
            splitNumber: splitNumber,
            axisLabel: { show: style.showAxisLabels !== false, color: style.axisColor || colors.text, fontSize: font, hideOverlap: true,
                fontWeight: style.axisLabelBold === true ? 'bold' : 'normal',
                formatter: function (value) {
                    var number = Number(value);
                    if (number === 0 || (!scientificAxis && Math.abs(number) < Math.pow(10, -axisDecimals) / 2)) { number = 0; }
                    return axisNumberFormat.format(number);
                } },
            splitLine: { show: style.showGrid !== false, lineStyle: { color: style.gridColor || colors.border,
                type: style.gridLineType || 'solid', width: style.gridLineWidth == null ? 1 : style.gridLineWidth,
                opacity: style.gridOpacityPercent == null ? 0.25 : style.gridOpacityPercent / 100 } } };
        if (scroll && Number.isFinite(minimum) && Number.isFinite(maximum)) {
            var padding = minimum === maximum ? Math.max(1, Math.abs(minimum) * 0.05) : (maximum - minimum) * 0.05;
            value.min = minimum - padding;
            value.max = maximum + padding;
        }
        return {
            ...design.animationOptions(style, { enabled: true, initialDuration: 350, updateDuration: 500 }),
            backgroundColor: 'transparent', color: colors.seriesColors || [colors.accent],
            textStyle: { color: colors.text, fontFamily: 'Arial, sans-serif' }, aria: { enabled: true },
            title: { show: titleLayout.show, text: boxplot.title || '', top: titleLayout.top, left: 'center',
                textStyle: { color: style.titleColor || colors.text, fontSize: titleSize, fontWeight: style.titleBold === false ? 'normal' : 'bold',
                    overflow: 'truncate', width: Math.max(1, width - 36) } },
            grid: { top: top, left: left, right: right, bottom: bottom, containLabel: false },
            xAxis: horizontal ? value : category, yAxis: horizontal ? category : value,
            dataZoom: scroll ? [
                { id: 'category-scroll', type: 'slider',
                    ...(horizontal ? { yAxisIndex: 0, orient: 'vertical', right: 6, width: 12, top: top, bottom: bottom }
                        : { xAxisIndex: 0, orient: 'horizontal', bottom: 4, height: 12, left: left, right: right }),
                    startValue: first, endValue: first + visibleCount - 1, filterMode: 'filter',
                    zoomLock: true,
                    showDetail: false, showDataShadow: false, brushSelect: false },
                { id: 'category-scroll-inside', type: 'inside',
                    ...(horizontal ? { yAxisIndex: 0 } : { xAxisIndex: 0 }),
                    startValue: first, endValue: first + visibleCount - 1, filterMode: 'filter',
                    // ECharts 6.1 disables wheel listeners with zoomLock. Lock the span instead.
                    zoomLock: false, minValueSpan: visibleCount - 1, maxValueSpan: visibleCount - 1,
                    zoomOnMouseWheel: false, moveOnMouseWheel: true, moveOnMouseMove: true }
            ] : [],
            tooltip: { show: style.showTooltip !== false, trigger: 'item', confine: true,
                extraCssText: 'max-width:' + Math.max(120, width - 24) + 'px;white-space:normal;overflow-wrap:anywhere;',
                formatter: function (parameter) {
                    var point = parameter.data || {};
                    var item = sources[point.sourceID || point.id];
                    if (!item || !item.statistics) { return ''; }
                    if (point.sourceID) {
                        return escapeHTML(item.label) + '<br>' + escapeHTML(translate('Outlier') + ': ' + format(point.value[1], item))
                            + '<br>' + escapeHTML(new Date(point.timestamp * 1000).toLocaleString(bootstrap.language || undefined))
                            + '<br>' + escapeHTML(translate('Hourly samples') + ': ' + item.count)
                            + '<br>' + escapeHTML(translate('Outliers') + ': ' + item.outlierCount)
                            + '<br>' + escapeHTML(translate('Completed hourly means · whiskers: 1.5 IQR'));
                    }
                    var names = iqr ? ['Lower whisker', 'Q1', 'Median', 'Q3', 'Upper whisker'] : ['Minimum', 'Q1', 'Median', 'Q3', 'Maximum'];
                    return escapeHTML(item.label) + '<br>' + item.statistics.map(function (value, index) {
                        return escapeHTML(translate(names[index]) + ': ' + format(value, item));
                    }).join('<br>') + (iqr ? '<br>' + escapeHTML(translate('Minimum') + ': ' + format(item.sampleMinimum, item))
                        + '<br>' + escapeHTML(translate('Maximum') + ': ' + format(item.sampleMaximum, item))
                        + '<br>' + escapeHTML(translate('Outliers') + ': ' + item.outlierCount) : '')
                        + '<br>' + escapeHTML(translate('Hourly samples') + ': ' + item.count)
                        + '<br>' + escapeHTML(new Date(item.firstTimestamp * 1000).toLocaleString(bootstrap.language || undefined))
                        + ' – ' + escapeHTML(new Date(item.lastTimestamp * 1000).toLocaleString(bootstrap.language || undefined))
                        + '<br>' + escapeHTML(translate(iqr ? 'Completed hourly means · whiskers: 1.5 IQR'
                            : 'Completed hourly means · whiskers: minimum and maximum'));
                } },
            series: [{ type: 'boxplot', boxWidth: [minimumBoxWidth, boxWidth],
                itemStyle: { borderWidth: style.borderWidth == null ? 2 : style.borderWidth,
                    borderType: style.borderType || 'solid', shadowBlur: Number(style.shadowBlur) || 0,
                    shadowColor: style.shadowColor || 'rgba(0,0,0,0.35)',
                    shadowOffsetX: Number(style.shadowOffsetX) || 0, shadowOffsetY: Number(style.shadowOffsetY) || 0,
                    borderColor: style.borderColor || colors.border, opacity: style.boxOpacityPercent == null ? 0.7 : style.boxOpacityPercent / 100 },
                emphasis: { focus: style.hoverFocus === 'none' ? 'none' : 'self', disabled: style.hoverFocus === 'none' },
                blur: { itemStyle: { opacity: style.blurOpacityPercent == null ? 0.1 : style.blurOpacityPercent / 100 } },
                data: items.map(function (item, index) {
                    return { id: item.id, name: item.label, value: item.statistics || [null, null, null, null, null],
                        itemStyle: { color: item.color || (colors.seriesColors || [colors.accent])[index % (colors.seriesColors || [colors.accent]).length] } };
                }) }, ...(iqr ? [{ id: 'boxplot-outliers', type: 'scatter',
                    dimensions: ['category', 'value'], encode: horizontal ? { x: 1, y: 0 } : { x: 0, y: 1 },
                    symbolSize: Math.max(4, Math.min(10, font * 0.55)), clip: true,
                    emphasis: { disabled: style.hoverFocus === 'none', scale: false },
                    data: items.flatMap(function (item, index) {
                        var color = item.color || (colors.seriesColors || [colors.accent])[index % (colors.seriesColors || [colors.accent]).length];
                        return (item.outliers || []).map(function (point) {
                            return { id: item.id + '-outlier-' + point[0], sourceID: item.id, timestamp: point[0],
                                name: item.label, value: [item.id, point[1]], itemStyle: { color: color } };
                        });
                    })
                }] : [])]
        };
    }
    function displayError(message) {
        rememberCategoryWindow();
        if (chart) { chart.clear(); }
        chartElement.hidden = true;
        emptyElement.hidden = true;
        warningElement.hidden = true;
        errorElement.textContent = translate(message || 'The Boxplot values could not be loaded.');
        errorElement.hidden = false;
    }
    function render(state) {
        if (retainOnTransientError(state, currentState, chart)) { return; }
        currentState = state;
        if (!state || state.status !== 'ready' || !state.chart) {
            displayError(state && state.error);
            return;
        }
        var model = state.chart;
        if (!window.echarts || typeof window.echarts.init !== 'function') {
            displayError('Apache ECharts could not be initialized.');
            return;
        }
        if (!(model.boxplot.data || []).some(function (item) { return item.statistics !== null; })) {
            rememberCategoryWindow();
            if (chart) { chart.clear(); }
            chartElement.hidden = true;
            errorElement.hidden = true;
            warningElement.hidden = true;
            emptyElement.textContent = translate('No completed hourly archive values.');
            emptyElement.hidden = false;
            return;
        }
        var wasHidden = chartElement.hidden;
        chartElement.hidden = false;
        errorElement.hidden = true;
        emptyElement.hidden = true;
        warningElement.hidden = true;
        rootElement.style.backgroundColor = bootstrap.mode === 'ipsview' && bootstrap.options.adaptToBackground === true
            ? 'transparent' : colorsFor(normalizeTheme(model.theme || 'auto')).background;
        var theme = normalizeTheme(model.theme || 'auto');
        rememberCategoryWindow();
        if (chart && currentTheme !== theme) { chart.dispose(); chart = null; }
        if (!chart) {
            chart = window.echarts.init(chartElement, theme === 'auto' ? null : theme, { renderer: 'canvas' });
            currentTheme = theme;
        } else if (wasHidden) {
            chart.resize();
        }
        chart.setOption(buildOption(model, theme), true);

    }
    window.handleMessage = function (message) {
        var state = design.parseVisualizationState(message);
        if (state) { render(state); }
    };
    if (window.ResizeObserver) {
        new ResizeObserver(function () {
            rememberCategoryWindow();
            if (!design.resizeChartIfNeeded(chart, chartElement)) { return; }
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
