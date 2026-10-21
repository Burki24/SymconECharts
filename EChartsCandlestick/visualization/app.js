(function () {
    'use strict';

    var bootstrap = window.SYMC_VISUALIZATION || {};
    var design = window.SYMC_ECHARTS_DESIGN;
    var rootElement = document.getElementById('echarts-candlestick-root');
    var chartElement = document.getElementById('echarts-candlestick-chart');
    var emptyElement = document.getElementById('echarts-candlestick-empty');
    var errorElement = document.getElementById('echarts-candlestick-error');
    var warningElement = document.getElementById('echarts-candlestick-warning');
    var chart = null;
    var currentState = bootstrap.state || null;
    var currentTheme = null;

    var tileHeaderInset = (bootstrap.options && bootstrap.options.tileHeaderInset) || 0;
    var chartBootstrap = tileHeaderInset ? { mode: 'symcon', options: { tileHeaderVisible: false } } : bootstrap;
    chartElement.style.top = tileHeaderInset + 'px';
    chartElement.style.height = tileHeaderInset ? 'calc(100% - ' + tileHeaderInset + 'px)' : '100%';

    function translate(text) { return (bootstrap.translations || {})[text] || text; }
    var retainOnTransientError = design.createTransientFailureRetainer(
        chartElement, warningElement, translate, 'The Candlestick values could not be loaded.'
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
    function buildOption(model, theme) {
        var colors = colorsFor(theme);
        var candlestick = model.candlestick || {};
        var style = candlestick.style || {};
        var items = candlestick.data || [];
        var width = chartElement.clientWidth || 1;
        var height = chartElement.clientHeight || 1;
        var horizontal = style.orientation === 'horizontal';
        var compact = width < 430;
        var font = design.fontSizePercent(style.axisFontSizePercent, compact ? 10 : 12);
        var titleSize = design.fontSizePercent(style.titleFontSizePercent, 18);
        var titleLayout = design.chartTitleLayout(chartBootstrap, candlestick.title, titleSize);
        var top = titleLayout.contentTop + (candlestick.unit ? font + 14 : 10);
        var margin = style.axisLabelMargin == null ? 8 : style.axisLabelMargin;
        var fontFamily = style.fontFamily || 'Arial, sans-serif';
        var fontStyle = style.axisLabelItalic === true ? 'italic' : 'normal';
        var left = (horizontal ? Math.min(width * 0.35, Math.max(60, font * 12)) : Math.min(width * 0.25, Math.max(45, font * 5))) + Math.max(0, margin - 8);
        var bottom = horizontal ? font * 3 + 24 : font * 2 + 22;
        bottom += Math.max(0, margin - 8);
        var rotation = Number(style.axisLabelRotate) || 0;
        var requestedLabelWidth = Number(style.axisLabelMaxWidth) || 0;
        var slot = (horizontal ? height - top - bottom : width - left - 22) / Math.max(1, items.length);
        var autoLabelWidth = Math.max(12, horizontal ? left - 15 : Math.max(60, slot - 8));
        var labelWidth = requestedLabelWidth > 0 ? Math.min(requestedLabelWidth, horizontal ? left - 15 : width - 30) : autoLabelWidth;
        if (rotation) {
            var sine = Math.abs(Math.sin(rotation * Math.PI / 180));
            labelWidth = Math.min(labelWidth, Math.max(12, (horizontal ? slot * 0.7 : height * 0.3) / sine));
            if (!horizontal && style.showAxisLabels !== false) { bottom += labelWidth * sine; }
        }
        var candleWidth = Math.max(1, Math.min(style.candleWidth == null ? 40 : style.candleWidth, slot * 0.65));
        var minimumCandleWidth = Math.min(style.minCandleWidth == null ? 1 : style.minCandleWidth, candleWidth);
        var fixedWidth = style.fixedCandleWidth > 0 ? Math.max(minimumCandleWidth, Math.min(style.fixedCandleWidth, candleWidth)) : undefined;
        var labels = Object.create(null);
        var rise = style.riseColor || colors.accent || '#3FBE95';
        var fall = style.fallColor || (colors.seriesColors || [])[3] || '#E66B6B';
        var dateOptions = model.range && model.range.timezone ? { timeZone: model.range.timezone } : undefined;
        items.forEach(function (item) { labels[item.id] = item.label; });
        function format(value, item) {
            return Number(value).toLocaleString(bootstrap.language || undefined, { maximumFractionDigits: item.decimals == null ? 2 : item.decimals })
                + (item.unit ? ' ' + item.unit : '');
        }
        var category = { type: 'category', inverse: style.reverseTimeAxis === true, data: items.map(function (item) { return item.id; }),
            axisLabel: { show: style.showAxisLabels !== false, color: style.axisColor || colors.text,
                fontFamily: fontFamily, fontStyle: fontStyle, margin: margin,
                fontSize: font, interval: 'auto', hideOverlap: true, overflow: 'truncate',
                rotate: rotation, fontWeight: style.axisLabelBold === true ? 'bold' : 'normal', width: labelWidth,
                formatter: function (id) { return labels[id] || ''; } },
            axisTick: { show: style.showAxisTicks !== false, alignWithLabel: true },
            axisLine: { lineStyle: { color: style.axisLineColor || colors.border, type: style.axisLineType || 'solid', width: style.axisLineWidth == null ? 1 : style.axisLineWidth } } };
        var value = { type: 'value', scale: style.includeZero !== true, name: candlestick.unit || '', nameLocation: horizontal ? 'middle' : 'end',
            nameGap: horizontal ? font + 18 : 15,
            nameTextStyle: { color: style.axisColor || colors.text, fontSize: font, fontFamily: fontFamily, fontStyle: fontStyle },
            splitNumber: style.splitNumber == null ? 5 : style.splitNumber,
            axisLabel: { show: style.showAxisLabels !== false, color: style.axisColor || colors.text, fontSize: font, hideOverlap: true,
                fontFamily: fontFamily, fontStyle: fontStyle, margin: margin,
                fontWeight: style.axisLabelBold === true ? 'bold' : 'normal' },
            splitLine: { show: style.showGrid !== false, lineStyle: { color: style.gridColor || colors.border,
                type: style.gridLineType || 'solid', width: style.gridLineWidth == null ? 1 : style.gridLineWidth,
                opacity: style.gridOpacityPercent == null ? 0.25 : style.gridOpacityPercent / 100 } } };
        return {
            ...design.animationOptions(style, { enabled: true, initialDuration: 350, updateDuration: 500 }),
            backgroundColor: 'transparent', color: colors.seriesColors || [colors.accent],
            textStyle: { color: colors.text, fontFamily: fontFamily }, aria: { enabled: true },
            title: { show: titleLayout.show, text: candlestick.title || '', top: titleLayout.top, left: 'center',
                textStyle: { color: style.titleColor || colors.text, fontSize: titleSize, fontFamily: fontFamily,
                    fontStyle: style.titleItalic === true ? 'italic' : 'normal', fontWeight: style.titleBold === false ? 'normal' : 'bold',
                    overflow: 'truncate', width: Math.max(1, width - 36) } },
            grid: { top: top, left: left, right: horizontal ? Math.max(30, font * 3) : 22, bottom: bottom, containLabel: false },
            xAxis: horizontal ? value : category, yAxis: horizontal ? category : value,
            tooltip: { show: style.showTooltip !== false, trigger: 'item', confine: true,
                textStyle: { fontFamily: fontFamily },
                extraCssText: 'max-width:' + Math.max(120, width - 24) + 'px;white-space:normal;overflow-wrap:anywhere;',
                formatter: function (parameter) {
                    var item = items[parameter.dataIndex];
                    if (!item || !item.statistics) { return ''; }
                    var names = ['First value', 'Last value', 'Minimum', 'Maximum'];
                    return escapeHTML(candlestick.sourceLabel || '') + '<br>' + escapeHTML(item.label) + '<br>' + item.statistics.map(function (value, index) {
                        return escapeHTML(translate(names[index]) + ': ' + format(value, item));
                    }).join('<br>') + '<br>' + escapeHTML(translate('Logged samples') + ': ' + item.count)
                        + '<br>' + escapeHTML(new Date(item.firstTimestamp * 1000).toLocaleString(bootstrap.language || undefined, dateOptions))
                        + ' – ' + escapeHTML(new Date(item.lastTimestamp * 1000).toLocaleString(bootstrap.language || undefined, dateOptions))
                        + '<br>' + escapeHTML(translate('Actual logged samples in completed intervals'));
                } },
            series: [{ type: 'candlestick', large: false, clip: true, barWidth: fixedWidth, barMinWidth: minimumCandleWidth, barMaxWidth: candleWidth,
                itemStyle: { borderWidth: style.borderWidth == null ? 2 : style.borderWidth,
                    borderType: style.borderType || 'solid', shadowBlur: Number(style.shadowBlur) || 0,
                    shadowColor: style.shadowColor || 'rgba(0,0,0,0.35)',
                    shadowOffsetX: Number(style.shadowOffsetX) || 0, shadowOffsetY: Number(style.shadowOffsetY) || 0,
                    color: style.riseHollow === true ? 'transparent' : rise, color0: style.fallHollow === true ? 'transparent' : fall,
                    borderColor: style.riseBorderColor || style.borderColor || rise,
                    borderColor0: style.fallBorderColor || style.borderColor || fall, borderColorDoji: style.dojiColor || colors.text, opacity: style.candleOpacityPercent == null ? 0.7 : style.candleOpacityPercent / 100 },
                emphasis: { focus: style.hoverFocus === 'none' ? 'none' : 'self', disabled: style.hoverFocus === 'none',
                    itemStyle: { borderWidth: style.hoverBorderWidth == null ? 2 : style.hoverBorderWidth,
                        ...(style.hoverBorderColor ? {borderColor:style.hoverBorderColor,borderColor0:style.hoverBorderColor,borderColorDoji:style.hoverBorderColor} : {}),
                        ...(style.hoverOpacityPercent >= 0 ? {opacity:style.hoverOpacityPercent / 100} : {}) } },
                blur: { itemStyle: { opacity: style.blurOpacityPercent == null ? 0.1 : style.blurOpacityPercent / 100 } },
                data: items.map(function (item, index) {
                    return { id: item.id, name: item.label, value: item.statistics || [null, null, null, null] };
                }) }]
        };
    }
    function displayError(message) {
        if (chart) { chart.clear(); }
        chartElement.hidden = true;
        emptyElement.hidden = true;
        warningElement.hidden = true;
        errorElement.textContent = translate(message || 'The Candlestick values could not be loaded.');
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
        if (!(model.candlestick.data || []).some(function (item) { return item.statistics !== null; })) {
            if (chart) { chart.clear(); }
            chartElement.hidden = true;
            errorElement.hidden = true;
            warningElement.hidden = true;
            emptyElement.textContent = translate('No logged values in completed intervals.');
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
            if (!design.resizeChartIfNeeded(chart, chartElement)) { return; }
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
