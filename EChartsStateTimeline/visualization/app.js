(function () {
    'use strict';

    var bootstrap = window.SYMC_VISUALIZATION || {};
    var design = window.SYMC_ECHARTS_DESIGN;
    var zoom = window.SymconEChartsZoom;
    var rootElement = document.getElementById('echarts-state-timeline-root');
    var chartElement = document.getElementById('echarts-state-timeline-chart');
    var emptyElement = document.getElementById('echarts-state-timeline-empty');
    var errorElement = document.getElementById('echarts-state-timeline-error');
    var warningElement = document.getElementById('echarts-state-timeline-warning');
    var chart = null, currentState = null, currentTheme = null;
    var tileHeaderInset = bootstrap.mode === 'symcon'
        && (!bootstrap.options || bootstrap.options.tileHeaderVisible !== false) ? 58 : 0;
    var chartBootstrap = tileHeaderInset ? {mode: 'symcon', options: {tileHeaderVisible: false}} : bootstrap;
    // A separate clipped viewport protects the native tile header even while panning.
    chartElement.style.top = tileHeaderInset + 'px';
    chartElement.style.height = tileHeaderInset ? 'calc(100% - ' + tileHeaderInset + 'px)' : '100%';

    function translate(text) { return (bootstrap.translations || {})[text] || text; }
    var retainOnTransientError = design.createTransientFailureRetainer(
        chartElement, warningElement, translate, 'The StateTimeline values could not be loaded.'
    );
    function escapeHTML(value) {
        return String(value).replace(/[&<>"']/g, function (character) {
            return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[character];
        });
    }
    function normalizeTheme(theme) {
        return Object.prototype.hasOwnProperty.call(bootstrap.options && bootstrap.options.echartsThemes || {}, theme) ? theme : 'auto';
    }
    function palette(theme) {
        var palettes = bootstrap.options && bootstrap.options.echartsThemes || {};
        return palettes[theme] || palettes.auto || {background: '#151619', text: '#F4F5F7', muted: '#969AA2',
            border: '#A5A9B0', accent: '#55CBB5', seriesColors: ['#5070DD', '#B6D634', '#FF994D', '#FB628B']};
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
        return {background: resolveColor('--symc-background', colors.background), text: resolveColor('--symc-text', colors.text),
            border: resolveColor('--symc-border', colors.border), muted: resolveColor('--symc-text-muted', colors.muted),
            accent: colors.accent, seriesColors: colors.seriesColors};
    }
    function dateText(timestamp, timezone, includeDate) {
        return new Date(timestamp * 1000).toLocaleString(bootstrap.language || undefined,
            includeDate ? {timeZone: timezone, year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', second: '2-digit', timeZoneName: 'shortOffset'}
                : {timeZone: timezone, hour: '2-digit', minute: '2-digit'});
    }
    function durationText(seconds) {
        var remaining = Math.max(0, Math.floor(seconds));
        var result = [];
        [[86400, 'd'], [3600, 'h'], [60, 'min'], [1, 's']].forEach(function (unit) {
            var amount = Math.floor(remaining / unit[0]);
            remaining %= unit[0];
            if (amount) { result.push(amount + ' ' + unit[1]); }
        });
        return result.join(' ') || '0 s';
    }
    function buildOption(model, theme) {
        var timeline = model.stateTimeline || {}, style = timeline.style || {}, rows = timeline.rows || [];
        var colors = colorsFor(theme), colorList = colors.seriesColors || [colors.accent];
        var width = Math.max(1, chartElement.clientWidth || 1), height = Math.max(1, chartElement.clientHeight || 1);
        var compact = width < 430;
        var fontFamilies = {Arial: 'Arial, sans-serif', Verdana: 'Verdana, sans-serif', Georgia: 'Georgia, serif', monospace: 'monospace'};
        var fontFamily = Object.prototype.hasOwnProperty.call(fontFamilies, style.fontFamily) ? fontFamilies[style.fontFamily] : fontFamilies.Arial;
        var axisFont = design.fontSizePercent(style.axisFontSizePercent, compact ? 11 : 12);
        var labelFont = design.fontSizePercent(style.labelFontSizePercent, compact ? 11 : 12);
        var titleFont = design.fontSizePercent(style.titleFontSizePercent, 18);
        var titleLayout = design.chartTitleLayout(chartBootstrap, timeline.title, titleFont);
        var top = titleLayout.contentTop + 14;
        var left = style.showSourceLabels === false ? (compact ? 14 : 24) : Math.min(width * 0.36, Math.max(60, axisFont * 13));
        var right = compact ? 14 : 24;
        var sliderBottom = 12;
        var timeFormat = style.timeLabelFormat || 'auto';
        var dateOnly = timeFormat === 'date' || (timeFormat === 'auto' && model.range.endTimestamp - model.range.startTimestamp > 172800);
        var axisMargin = style.axisLabelMargin === undefined ? 12 : Number(style.axisLabelMargin);
        var lineHeight = Math.ceil(axisFont * 1.3);
        var axisExtent = style.showTimeLabels !== false ? lineHeight * (timeFormat === 'dateTime' ? 2 : 1) + axisMargin : (style.showAxisTicks === true ? 5 : 0);
        var bottom = axisExtent + 10 + (style.enableZoom !== false ? zoom.sliderThickness(compact) + 24 : 0);
        var plotHeight = Math.max(1, height - top - bottom);
        var values = [];
        rows.forEach(function (row, rowIndex) {
            (row.segments || []).forEach(function (segment, segmentIndex) {
                var colorIndex = segment.value === null ? 0 : ((segment.value % colorList.length) + colorList.length) % colorList.length;
                values.push({id: row.id + '-' + segmentIndex, name: row.label,
                    value: [segment.startTimestamp * 1000, segment.endTimestamp * 1000, rowIndex],
                    segment: segment, rowLabel: row.label,
                    itemStyle: {color: segment.value === null ? style.unknownColor || colors.muted : segment.color || colorList[colorIndex]}});
            });
        });
        var dataZoom = zoom.timeAxisOptions(style.enableZoom !== false, bootstrap.mode, sliderBottom, compact);
        dataZoom.forEach(function (entry) {
            // Neither endpoint has to lie inside the zoom window for an interval to be visible.
            entry.filterMode = 'none';
            if (entry.type === 'slider') {entry.showDataShadow = false; entry.showDetail = false; entry.left = left; entry.right = right;}
        });
        return {
            backgroundColor: bootstrap.mode === 'ipsview' && bootstrap.options && bootstrap.options.adaptToBackground === true ? 'transparent' : colors.background,
            textStyle: {fontFamily: fontFamily},
            ...design.animationOptions(style, {enabled: true, initialDuration: 350, updateDuration: 500}),
            aria: {enabled: true, label: {description: String(timeline.title || 'StateTimeline') + '. ' + rows.map(function (row) {return row.label;}).join(', ')}},
            title: {show: titleLayout.show, text: String(timeline.title || ''), left: 'center', top: titleLayout.top,
                textStyle: {color: style.titleColor || colors.text, fontSize: titleFont, fontFamily: fontFamily,
                    fontWeight: style.titleBold === false ? 'normal' : 'bold', fontStyle: style.titleItalic ? 'italic' : 'normal',
                    width: Math.max(1, width - 24), overflow: 'truncate'}},
            grid: {left: left, right: right, top: top, height: plotHeight, containLabel: false},
            xAxis: {type: 'time', min: model.range.startTimestamp * 1000, max: model.range.endTimestamp * 1000,
                minInterval: dateOnly ? 86400000 : 60000,
                splitNumber: Math.max(2, Math.floor((width - left - right) / Math.max(70, axisFont * 5))),
                axisLine: {show: style.showAxisLine !== false, lineStyle: {color: style.axisColor || colors.border,
                    width: style.axisLineWidth === undefined ? 1 : Number(style.axisLineWidth), type: style.axisLineType || 'solid'}},
                axisTick: {show: style.showAxisTicks === true},
                axisLabel: {show: style.showTimeLabels !== false, color: style.axisColor || colors.text, fontSize: axisFont,
                    fontFamily: fontFamily, fontWeight: style.axisLabelBold ? 'bold' : 'normal', fontStyle: style.axisLabelItalic ? 'italic' : 'normal',
                    lineHeight: lineHeight, margin: axisMargin, hideOverlap: true,
                    showMinLabel: false, showMaxLabel: false,
                    formatter: function (value) {
                        var date = new Date(value).toLocaleDateString(bootstrap.language || undefined, {timeZone: model.range.timezone, day: '2-digit', month: '2-digit'});
                        if (timeFormat === 'dateTime') {return date + '\n' + dateText(value / 1000, model.range.timezone, false);}
                        if (dateOnly) {return date;}
                        return dateText(value / 1000, model.range.timezone, false);
                    }},
                splitLine: {show: style.showGrid === true, lineStyle: {color: style.gridColor || style.axisColor || colors.border,
                    opacity: Number(style.gridOpacityPercent === undefined ? 20 : style.gridOpacityPercent) / 100,
                    width: style.gridLineWidth === undefined ? 1 : Number(style.gridLineWidth), type: style.gridLineType || 'solid'}}},
            yAxis: {type: 'category', inverse: true, data: rows.map(function (row) {return row.id;}),
                axisLine: {show: false}, axisTick: {show: false},
                axisLabel: {show: style.showSourceLabels !== false, color: style.axisColor || colors.text, fontSize: axisFont, margin: 10,
                    fontFamily: fontFamily, fontWeight: style.axisLabelBold ? 'bold' : 'normal', fontStyle: style.axisLabelItalic ? 'italic' : 'normal',
                    width: Math.max(1, left - 18), overflow: 'truncate', hideOverlap: true,
                    formatter: function (id) { var row = rows.find(function (candidate) {return candidate.id === id;}); return row ? row.label : ''; }},
                splitLine: {show: false}},
            tooltip: {show: style.showTooltip !== false, trigger: 'item', confine: true,
                formatter: function (parameter) {
                    var item = parameter.data, segment = item && item.segment;
                    if (!segment) {return '';}
                    var lines = [item.rowLabel, segment.label];
                    if (segment.value !== null) {lines.push(translate('Value') + ': ' + segment.value);}
                    lines.push(translate('Start') + ': ' + dateText(segment.startTimestamp, model.range.timezone, true));
                    lines.push(translate('End') + ': ' + dateText(segment.endTimestamp, model.range.timezone, true));
                    lines.push(translate('Duration') + ': ' + durationText(segment.endTimestamp - segment.startTimestamp));
                    return lines.map(escapeHTML).join('<br>');
                }},
            dataZoom: dataZoom,
            series: [{id: 'state-intervals', type: 'custom', coordinateSystem: 'cartesian2d', clip: true,
                dimensions: ['start', 'end', 'row'], encode: {x: [0, 1], y: 2}, data: values,
                renderItem: function (parameters, api) {
                    var start = api.coord([api.value(0), api.value(2)]), end = api.coord([api.value(1), api.value(2)]);
                    var barHeight = Math.abs(api.size([0, 1])[1]) * Number(style.rowHeightPercent === undefined ? 65 : style.rowHeightPercent) / 100;
                    var rectangle = window.echarts.graphic.clipRectByRect({x: start[0], y: start[1] - barHeight / 2,
                        width: end[0] - start[0], height: barHeight}, parameters.coordSys);
                    if (!rectangle || rectangle.width <= 0 || rectangle.height <= 0) {return;}
                    var item = values[parameters.dataIndex];
                    var fill = item.itemStyle.color;
                    rectangle.r = Math.min(Number(style.borderRadius) || 0, rectangle.width / 2, rectangle.height / 2);
                    var opacity = Number(style.barOpacityPercent === undefined ? 90 : style.barOpacityPercent) / 100;
                    var focus = style.emphasisEnabled !== false && style.emphasisFocus === 'self' ? 'self' : 'none';
                    var blurOpacity = Number(style.blurOpacityPercent === undefined ? 20 : style.blurOpacityPercent) / 100;
                    // Native CustomView reads these flags from the root renderItem element, not series.emphasis.
                    var result = {type: 'group', focus: focus, blurScope: 'series', emphasisDisabled: style.emphasisEnabled === false,
                        children: [{type: 'rect', shape: rectangle, transition: ['shape'],
                            style: {fill: fill, opacity: opacity, stroke: style.borderColor || colors.border,
                                lineWidth: Number(style.borderWidth) || 0, lineDash: style.borderType === 'solid' || !style.borderType ? null : style.borderType},
                            blur: {style: {opacity: opacity * blurOpacity}}}]};
                    if (style.showLabels !== false && rectangle.width >= labelFont * 3 && rectangle.height >= labelFont + 4) {
                        var position = style.labelPosition || 'center';
                        result.children.push({type: 'text', silent: true,
                            x: position === 'left' ? rectangle.x + 4 : position === 'right' ? rectangle.x + rectangle.width - 4 : rectangle.x + rectangle.width / 2,
                            y: rectangle.y + rectangle.height / 2, style: {text: String(item.segment.label),
                                fill: style.labelColor || design.readableTextColor(fill), fontSize: labelFont, fontFamily: fontFamily,
                                fontWeight: style.labelBold ? 'bold' : 'normal', fontStyle: style.labelItalic ? 'italic' : 'normal',
                                align: position === 'left' ? 'left' : position === 'right' ? 'right' : 'center',
                                verticalAlign: 'middle', width: Math.max(1, rectangle.width - 8), overflow: 'truncate'},
                            blur: {style: {opacity: blurOpacity}}});
                    }
                    return result;
                }}]
        };
    }
    function render(state) {
        if (retainOnTransientError(state, currentState, chart)) {return;}
        var previousState = currentState;
        currentState = state;
        if (!state || state.status !== 'ready' || !state.chart || !state.chart.stateTimeline || !state.chart.range) {
            if (chart) {chart.clear();}
            chartElement.hidden = true; emptyElement.hidden = true; warningElement.hidden = true;
            errorElement.textContent = translate(state && (state.error || state.message) || 'The StateTimeline values could not be loaded.');
            errorElement.hidden = false;
            return;
        }
        if (!window.echarts || typeof window.echarts.init !== 'function') {
            chartElement.hidden = true; errorElement.hidden = false;
            errorElement.textContent = translate('Apache ECharts could not be initialized.'); return;
        }
        var model = state.chart, rows = model.stateTimeline.rows || [];
        if (!rows.length) {
            if (chart) {chart.clear();}
            chartElement.hidden = true; errorElement.hidden = true; warningElement.hidden = true;
            emptyElement.textContent = translate('No StateTimeline sources configured.'); emptyElement.hidden = false; return;
        }
        var theme = normalizeTheme(model.theme || 'auto');
        var savedZoom = chart && previousState && previousState.chart ? zoom.capture(chart, previousState.chart.range, model.range) : null;
        var wasHidden = chartElement.hidden;
        chartElement.hidden = false; errorElement.hidden = true; emptyElement.hidden = true; warningElement.hidden = true;
        rootElement.style.backgroundColor = bootstrap.mode === 'ipsview' && bootstrap.options && bootstrap.options.adaptToBackground === true ? 'transparent' : colorsFor(theme).background;
        if (chart && currentTheme !== theme) {chart.dispose(); chart = null;}
        if (!chart) {chart = window.echarts.init(chartElement, theme === 'auto' ? null : theme, {renderer: 'canvas'}); currentTheme = theme;}
        else if (wasHidden) {chart.resize();}
        zoom.apply(chart, buildOption(model, theme), savedZoom);
    }
    if (bootstrap.mode === 'ipsview') {
        zoom.attachIPSViewWheel(chartElement, function () {return chart;}, function () {
            return currentState && currentState.status === 'ready' && currentState.chart.stateTimeline.style.enableZoom !== false;
        });
    }
    window.handleMessage = function (message) {var state = design.parseVisualizationState(message); if (state) {render(state);}};
    if (window.ResizeObserver) {
        new ResizeObserver(function () {
            if (!design.resizeChartIfNeeded(chart, chartElement) || !currentState || currentState.status !== 'ready') {return;}
            zoom.apply(chart, buildOption(currentState.chart, currentTheme || 'auto'), zoom.capture(chart, null, null));
        }).observe(chartElement);
    } else {
        window.addEventListener('resize', function () {
            if (!chart || !currentState || currentState.status !== 'ready') {return;}
            chart.resize(); zoom.apply(chart, buildOption(currentState.chart, currentTheme || 'auto'), zoom.capture(chart, null, null));
        });
    }
    window.addEventListener('beforeunload', function () {if (chart) {chart.dispose();}});
    render(bootstrap.state || null);
}());
