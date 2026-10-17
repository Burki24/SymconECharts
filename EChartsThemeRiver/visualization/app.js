(function () {
    'use strict';

    var bootstrap = window.SYMC_VISUALIZATION || {};
    var design = window.SYMC_ECHARTS_DESIGN;
    var chartElement = document.getElementById('echarts-theme-river-chart');
    var emptyElement = document.getElementById('echarts-theme-river-empty');
    var errorElement = document.getElementById('echarts-theme-river-error');
    var warningElement = document.getElementById('echarts-theme-river-warning');
    var chart = null;
    var currentState = bootstrap.state || null;
    var currentTheme = null;

    function translate(text) { return (bootstrap.translations || {})[text] || text; }
    var retainOnTransientError = design.createTransientFailureRetainer(
        chartElement, warningElement, translate, 'The ThemeRiver values could not be loaded.'
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
            accent: resolveColor('--symc-accent', colors.accent)
        };
    }
    var pointerRow = null;
    var plotBounds = null;
    var hasBands = false;
    function intervalTooltip(river, row) {
        if (!row || !Array.isArray(row.values)) { return ''; }
        var lines = [escapeHTML(row.label || '')];
        (river.sources || []).forEach(function (source, index) {
            lines.push(escapeHTML(source.label) + ': ' + escapeHTML(Number(row.values[index]).toLocaleString(
                bootstrap.language || undefined, { maximumFractionDigits: Math.max(0, Math.min(6, Number(source.decimals) || 0)) }
            )) + (source.unit ? ' ' + escapeHTML(source.unit) : ''));
        });
        return lines.join('<br>');
    }
    function bindPointer() {
        if (!chart.getZr) { return; }
        var zr = chart.getZr();
        zr.on('mousemove', function (event) {
            var river = currentState && currentState.chart && currentState.chart.themeRiver;
            if (!river || river.style.showTooltip === false || !plotBounds || !hasBands) { return; }
            var x = Number(event.offsetX), y = Number(event.offsetY);
            if (x < plotBounds.left || x > plotBounds.right || y < plotBounds.top || y > plotBounds.bottom) {
                chart.dispatchAction({type: 'hideTip'}); return;
            }
            var coordinate = chart.convertFromPixel({singleAxisIndex: 0}, [x, y]);
            var timestamp = Number(Array.isArray(coordinate) ? coordinate[0] : coordinate) / 1000;
            pointerRow = river.rows.find(function (row) { return timestamp >= row.startTimestamp && timestamp < row.endTimestamp + 1; });
            if (!pointerRow || !Array.isArray(pointerRow.values)) {
                chart.dispatchAction({type: 'hideTip'}); return;
            }
            chart.dispatchAction({type: 'showTip', seriesIndex: 0, dataIndex: 0,
                position: [Math.min(x + 12, plotBounds.right - 20), y + 12]});
        });
        zr.on('globalout', function () { pointerRow = null; chart.dispatchAction({type: 'hideTip'}); });
    }
    function buildOption(model, theme) {
        var colors = colorsFor(theme);
        var river = model.themeRiver || {};
        var style = river.style || {};
        var sources = river.sources || [];
        var rows = river.rows || [];
        pointerRow = null;
        var width = Math.max(1, chartElement.clientWidth || 1);
        var height = Math.max(1, chartElement.clientHeight || 1);
        var compact = width < 420 || height < 280;
        var titleSize = design.fontSizePercent(style.titleFontSizePercent, compact ? 15 : 18);
        var titleLayout = design.chartTitleLayout(bootstrap, river.title, titleSize);
        var titleBottom = titleLayout.headerInset + (titleLayout.show ? titleSize + 28 : 16);
        var bottom = (style.showLegend !== false ? 62 : 30) + (river.missingIntervals > 0 ? 36 : 0);
        var top = Math.min(titleBottom, Math.max(12, height - bottom - 20));
        var actualBottom = Math.max(top + 8, height - bottom);
        var left = compact ? 22 : 46, right = compact ? 22 : 46;
        plotBounds = {left: left, right: width - right, top: top, bottom: actualBottom};
        var colorList = palette(theme).seriesColors || ['#5070DD', '#B6D634', '#FF994D', '#FB628B', '#55CBB5', '#B693E8', '#D0A662', '#6C9EA8'];
        var sourceColors = sources.map(function (source, index) { return source.color || colorList[index % colorList.length]; });
        // Normalize before summing: even eight finite values near Number.MAX_VALUE must remain drawable.
        var maximum = 0;
        rows.forEach(function (row) { (row.values || []).forEach(function (value) { maximum = Math.max(maximum, value); }); });
        var runs = [], run = [];
        rows.forEach(function (row) {
            if (!Array.isArray(row.values)) { if (run.length) { runs.push(run); run = []; } return; }
            run.push(row);
        });
        if (run.length) { runs.push(run); }
        var globalTotal = 0;
        runs.forEach(function (segment) {
            segment.forEach(function (row) {
                globalTotal = Math.max(globalTotal, row.values.reduce(function (sum, value) { return sum + (maximum ? value / maximum : 0); }, 0));
            });
        });
        var series = [], labels = [];
        runs.forEach(function (segment, index) {
            var localTotal = 0;
            segment.forEach(function (row) {
                localTotal = Math.max(localTotal, row.values.reduce(function (sum, value) { return sum + (maximum ? value / maximum : 0); }, 0));
            });
            // Native ThemeRiver divides by the local maximum: never hand it an all-zero run.
            if (!(localTotal > 0)) { return; }
            var padding = 50 * (1 - 0.84 * localTotal / globalTotal);
            if (style.showLabels === true && series.length === 0) {
                // ECharts 6.1 ThemeRiver omits labelFetcher, so its label.formatter cannot replace internal IDs.
                // Draw bounded source names as public Graphic elements; never change the upstream renderer.
                var first = segment[0], total = first.values.reduce(function (sum, value) {return sum + value / maximum;}, 0);
                var scale = 0.84 * (actualBottom - top) / globalTotal;
                var y = top + padding / 100 * (actualBottom - top) + (localTotal - total) / 2 * scale;
                var labelFont = design.fontSizePercent(style.labelFontSizePercent, compact ? 10 : 12);
                var duration = model.range.endTimestamp + 1 - model.range.startTimestamp;
                var x = left + (first.startTimestamp - model.range.startTimestamp) / duration * (width - left - right) + 6;
                var labelWidth = Math.min(160, (segment[segment.length - 1].endTimestamp + 1 - first.startTimestamp) / duration * (width - left - right) - 12);
                sources.forEach(function (source, sourceIndex) {
                    var bandHeight = first.values[sourceIndex] / maximum * scale;
                    if (bandHeight >= labelFont + 4 && labelWidth >= 24) {
                        labels.push({type: 'text', silent: true, z: 20, x: x, y: y + bandHeight / 2,
                            style: {text: source.label, fill: style.labelColor || colors.text, fontSize: labelFont,
                                fontFamily: 'Arial, sans-serif', verticalAlign: 'middle', width: labelWidth, overflow: 'truncate'}});
                    }
                    y += bandHeight;
                });
            }
            var data = [];
            sources.forEach(function (source, sourceIndex) {
                function point(time, row) {
                    data.push([time, row.values[sourceIndex] / maximum, source.id, rows.indexOf(row)]);
                }
                point(segment[0].startTimestamp * 1000, segment[0]);
                segment.forEach(function (row) {
                    point((row.startTimestamp + (row.endTimestamp + 1 - row.startTimestamp) / 2) * 1000, row);
                });
                point((segment[segment.length - 1].endTimestamp + 1) * 1000 - 1, segment[segment.length - 1]);
            });
            series.push({
                id: 'river-run-' + index, type: 'themeRiver', singleAxisIndex: 0,
                color: sourceColors, colorBy: 'data', boundaryGap: [padding + '%', padding + '%'],
                label: {show: false},
                emphasis: {focus: 'self', label: {show: false}},
                itemStyle: {opacity: Number(style.bandOpacityPercent === undefined ? 85 : style.bandOpacityPercent) / 100,
                    borderWidth: Number(style.borderWidth) || 0, borderColor: style.borderColor || colors.border},
                data: data
            });
        });
        hasBands = series.length > 0;
        if (!hasBands) {
            // Empty named series preserve the legend without invented epsilon values or division by zero.
            series = sources.map(function (source, index) {
                return {id: 'zero-source-' + index, name: source.id, type: 'themeRiver', singleAxisIndex: 0,
                    data: [], itemStyle: {color: sourceColors[index]}, label: {show: false}};
            });
        }
        return {
            backgroundColor: bootstrap.mode === 'ipsview' && bootstrap.options.adaptToBackground === true ? 'transparent' : colors.background,
            ...design.animationOptions(style, {enabled: true, initialDuration: 350, updateDuration: 500}),
            aria: {enabled: true, label: {description: String(river.title || 'ThemeRiver') + '. '
                + translate('Completed interval means') + ': ' + sources.map(function (s) { return s.label; }).join(', ')
                + '. ' + String(rows.length - (river.missingIntervals || 0)) + '/' + rows.length}},
            color: sourceColors,
            graphic: labels,
            title: {show: titleLayout.show, text: String(river.title || ''), left: 'center', top: titleLayout.top,
                textStyle: {color: style.titleColor || colors.text, fontSize: titleSize}},
            legend: {show: style.showLegend !== false, type: 'scroll', selectedMode: false,
                left: 12, right: 12, bottom: river.missingIntervals > 0 ? 44 : 10,
                data: sources.map(function (s) { return {name: s.id, itemStyle: {color: sourceColors[sources.indexOf(s)]}}; }),
                formatter: function (id) { var source = sources.find(function (s) { return s.id === id; }); return source ? source.label : ''; },
                textStyle: {color: style.legendColor || colors.text, fontSize: design.fontSizePercent(style.legendFontSizePercent, compact ? 10 : 12)},
                pageTextStyle: {color: style.legendColor || colors.text}},
            singleAxis: {type: 'time', orient: 'horizontal', left: left, right: right, top: top, height: actualBottom - top,
                min: model.range.startTimestamp * 1000, max: (model.range.endTimestamp + 1) * 1000,
                axisLine: {lineStyle: {color: style.axisColor || colors.border}},
                axisTick: {show: style.showAxisTicks !== false},
                axisLabel: {show: style.showAxisLabels !== false, hideOverlap: true, color: style.axisColor || colors.text,
                    fontSize: design.fontSizePercent(style.axisFontSizePercent, compact ? 10 : 12),
                    formatter: function (value) {
                        var date = new Date(value);
                        if (river.aggregation !== 'day' && model.range.endTimestamp - model.range.startTimestamp > 172800) {
                            return date.toLocaleDateString(bootstrap.language || undefined, {timeZone: model.range.timezone, day: '2-digit', month: '2-digit'})
                                + '\n' + date.toLocaleTimeString(bootstrap.language || undefined, {timeZone: model.range.timezone, hour: '2-digit', minute: '2-digit'});
                        }
                        return date.toLocaleString(bootstrap.language || undefined, river.aggregation === 'day'
                            ? {timeZone: model.range.timezone, day: '2-digit', month: '2-digit'}
                            : {timeZone: model.range.timezone, hour: '2-digit', minute: '2-digit'});
                    }},
                splitLine: {show: false}},
            tooltip: {show: style.showTooltip !== false, trigger: 'item', triggerOn: 'none', confine: true,
                formatter: function (parameter) {
                    var row = pointerRow || rows[parameter.data && parameter.data[3]];
                    return intervalTooltip(river, row);
                }},
            series: series
        };
    }
    function displayError(message) {
        if (chart) { chart.clear(); }
        chartElement.hidden = true;
        emptyElement.hidden = true;
        warningElement.hidden = true;
        errorElement.textContent = translate(message || 'The ThemeRiver values could not be loaded.');
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
        var themeRiver = model.themeRiver || {};
        if (!Array.isArray(themeRiver.rows) || !themeRiver.rows.some(function (row) { return Array.isArray(row.values); })) {
            if (chart) { chart.clear(); }
            chartElement.hidden = true;
            errorElement.hidden = true;
            warningElement.hidden = true;
            emptyElement.textContent = translate('No common completed archive intervals.');
            emptyElement.hidden = false;
            return;
        }
        if (!window.echarts || typeof window.echarts.init !== 'function') {
            displayError('Apache ECharts could not be initialized.');
            return;
        }
        var wasHidden = chartElement.hidden;
        chartElement.hidden = false;
        errorElement.hidden = true;
        emptyElement.hidden = true;
        warningElement.textContent = translate('Intervals with missing archive values are left blank.');
        warningElement.hidden = !(themeRiver.missingIntervals > 0);
        var theme = normalizeTheme(model.theme || 'auto');
        if (chart && currentTheme !== theme) { chart.dispose(); chart = null; }
        if (!chart) {
            chart = window.echarts.init(chartElement, theme === 'auto' ? null : theme, { renderer: 'canvas' });
            currentTheme = theme;
            bindPointer();
        } else if (wasHidden) {
            chart.resize();
        }
        chart.setOption(buildOption(model, theme), true);
        chart.dispatchAction({type: 'hideTip'});
        emptyElement.textContent = translate('All completed interval means are zero.');
        emptyElement.hidden = themeRiver.rows.some(function (row) { return Array.isArray(row.values) && row.values.some(function (v) { return v > 0; }); });
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
                chart.dispatchAction({type: 'hideTip'});
            }
        }).observe(chartElement);
    } else {
        window.addEventListener('resize', function () { if (chart) { chart.resize(); } });
    }
    window.addEventListener('beforeunload', function () { if (chart) { chart.dispose(); } });
    render(currentState);
}());
