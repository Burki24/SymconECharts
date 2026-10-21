(function () {
    'use strict';
    var bootstrap = window.SYMC_VISUALIZATION || {};
    var design = window.SYMC_ECHARTS_DESIGN;
    var root = document.getElementById('echarts-calendar-root');
    var chartElement = document.getElementById('echarts-calendar-chart');
    var empty = document.getElementById('echarts-calendar-empty');
    var error = document.getElementById('echarts-calendar-error');
    var warning = document.getElementById('echarts-calendar-warning');
    var period = document.getElementById('echarts-calendar-period');
    var periodLabel = document.getElementById('echarts-calendar-period-label');
    var previous = document.getElementById('echarts-calendar-previous');
    var next = document.getElementById('echarts-calendar-next');
    var selected = document.getElementById('echarts-calendar-selected');
    var chart = null;
    var theme = null;
    var currentState = bootstrap.state || null;
    var navigating = false;
    var currentOffset = 0;
    var header = ((bootstrap.options && bootstrap.options.tileHeaderInset) || 0);
    var chartBootstrap = { mode: bootstrap.mode, options: { tileHeaderVisible: false } };
    chartElement.style.top = (header + 30) + 'px';
    chartElement.style.height = 'calc(100% - ' + (header + 30) + 'px)';
    period.style.top = header + 'px';
    var loadError = 'The Calendar values could not be loaded.';
    function translate(text) { return (bootstrap.translations || {})[text] || text; }
    function metricLabel(metric) {
        return translate({ minimum: 'Daily minimum', maximum: 'Daily maximum', consumption: 'Daily consumption' }[metric] || 'Daily mean');
    }
    function emptyLabel(metric, period) {
        if (period && period !== 'year') {
            return translate('No completed archive days in this period.');
        }
        return translate({ minimum: 'No completed daily minima in this year.', maximum: 'No completed daily maxima in this year.',
            consumption: 'No completed daily consumption in this year.' }[metric] || 'No completed daily means in this year.');
    }
    var retain = design.createTransientFailureRetainer(chartElement, warning, translate, loadError);
    function escapeHTML(value) {
        return String(value).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }
    function colorsFor(id) {
        var palettes = bootstrap.options.echartsThemes || {};
        var colors = palettes[id] || palettes.auto || {
            background: '#151619', text: '#F4F5F7', border: '#34363B', surface: '#25272B'
        };
        if (id !== 'auto') { return colors; }
        var resolved = Object.assign({}, colors);
        ['background', 'text', 'border', 'surface'].forEach(function (name) {
            var probe = document.createElement('span');
            probe.style.color = 'var(--symc-' + name + ', ' + colors[name] + ')';
            probe.style.display = 'none';
            document.body.appendChild(probe);
            resolved[name] = window.getComputedStyle(probe).color || colors[name];
            probe.remove();
        });
        return resolved;
    }
    function buildOption(model, id) {
        var calendar = model.calendar;
        var style = calendar.style;
        var colors = colorsFor(id);
        var data = calendar.data || [];
        var values = data.map(function (cell) { return cell.value; });
        var min = values.length ? Math.min.apply(null, values) : 0;
        var max = values.length ? Math.max.apply(null, values) : 1;
        if (style.useFixedColorRange === true) {
            min = Number(style.colorScaleMinimum);
            max = Number(style.colorScaleMaximum);
        }
        // A constant series still has a finite, non-degenerate visual range, including extreme finite means.
        if (min === max) {
            if (min > 0) { min = min / 2; } else if (max < 0) { max = max / 2; } else { max = 1; }
        }
        var width = chartElement.clientWidth || 720;
        var height = chartElement.clientHeight || 360;
        var vertical = style.orientation === 'vertical' || (style.orientation === 'auto' && width < height * 1.6);
        var axisSize = design.fontSizePercent(style.axisFontSizePercent, width < 450 ? 10 : 12);
        var valueSize = design.fontSizePercent(style.valueFontSizePercent, 10);
        var titleSize = design.fontSizePercent(style.titleFontSizePercent, 18);
        var title = design.chartTitleLayout(chartBootstrap, calendar.title, titleSize);
        var top = title.contentTop + (vertical && style.showWeekdayLabels ? axisSize + 12 : 12);
        var bottom = style.showColorScale ? 55 : 16;
        var left = style.showWeekdayLabels && !vertical ? axisSize * 3 + 8
            : (style.showMonthLabels && vertical ? axisSize * 3 + 8 : 18);
        var right = 22;
        var monthPosition = style.monthLabelPosition || 'start';
        var weekdayPosition = style.weekdayLabelPosition || 'start';
        var monthMargin = style.monthLabelMargin == null ? 8 : style.monthLabelMargin;
        var weekdayMargin = style.weekdayLabelMargin == null ? 8 : style.weekdayLabelMargin;
        var monthFormat = style.monthLabelFormat || 'name';
        var monthTextWidth = axisSize * (monthFormat === 'year-month' ? 4.8 : 2.2);
        if (style.showWeekdayLabels) {
            if (vertical) {
                if (weekdayPosition === 'end') { top -= axisSize; bottom += axisSize + weekdayMargin + 6; }
                else { top += Math.max(0, weekdayMargin - 8); }
            } else if (weekdayPosition === 'end') { left = 18; right += axisSize * 2.2 + weekdayMargin; }
            else { left += Math.max(0, weekdayMargin - 8); }
        }
        if (style.showMonthLabels) {
            if (vertical) {
                if (monthPosition === 'end') { left = 18; right += monthTextWidth + monthMargin; }
                else { left = Math.max(left, monthTextWidth + monthMargin + 8); }
            } else if (monthPosition === 'end') { bottom += axisSize + monthMargin + 6; }
            else { top += Math.max(0, monthMargin - 8); }
        }
        var yearSize = design.fontSizePercent(style.yearFontSizePercent, 18);
        var yearPosition = style.yearLabelPosition && style.yearLabelPosition !== 'auto' ? style.yearLabelPosition : (vertical ? 'top' : 'left');
        var yearMargin = style.yearLabelMargin == null ? 30 : style.yearLabelMargin;
        if (style.showYearLabel === true) {
            // The year sits outside the other calendar labels on the chosen side.
            var occupied = { top: top - title.contentTop, bottom: bottom - (style.showColorScale ? 55 : 16), left: left - 18, right: right - 22 };
            yearMargin += Math.max(0, occupied[yearPosition]);
            var yearSpace = yearMargin + yearSize + 4;
            if (yearPosition === 'top') { top += yearSpace; }
            else if (yearPosition === 'bottom') { bottom += yearSpace; }
            else if (yearPosition === 'left') { left += yearSpace; }
            else { right += yearSpace; }
        }
        var scaleColors = [style.lowColor || '#5070DD', style.highColor || '#FB628B'];
        if (style.reverseColorScale === true) { scaleColors.reverse(); }
        // Even very large custom text/margins must leave a positive calendar viewport.
        var horizontalFit = Math.min(1, Math.max(1, width - 28) / Math.max(1, left + right));
        left *= horizontalFit;
        right *= horizontalFit;
        var verticalFit = Math.min(1, Math.max(1, height - title.contentTop - 28)
            / Math.max(1, top - title.contentTop + bottom));
        top = title.contentTop + (top - title.contentTop) * verticalFit;
        bottom *= verticalFit;
        var weeks = calendar.dayCount ? Math.ceil((calendar.dayCount + ((calendar.startWeekday - style.firstWeekday + 7) % 7)) / 7) : 54;
        var cellWidth = Math.max(1, width - left - right) / (vertical ? 7 : weeks);
        var cellHeight = Math.max(1, height - top - bottom) / (vertical ? weeks : 7);
        var monthStep = Math.max(1, Math.ceil(axisSize * (vertical ? 1.2 : (monthFormat === 'year-month' ? 7 : 3) * 0.65)
            / Math.max(1, (vertical ? height - top - bottom : width - left - right) / (calendar.monthCount || 12))));
        var monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'].map(translate);
        var weekdayStep = Math.max(1, Math.ceil(axisSize * (vertical ? 2 : 1.2)
            / Math.max(1, (vertical ? width - left - right : height - top - bottom) / 7)));
        var weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'].map(function (name, index) {
            return ((index - style.firstWeekday + 7) % 7) % weekdayStep ? '' : translate(name);
        });
        var monthFormatter = monthFormat === 'number' ? '{MM}' : (monthFormat === 'year-month' ? '{yyyy}-{MM}' : '{nameMap}');
        if (monthStep > 1) {
            monthFormatter = function (p) {
                if ((Number(p.M) - 1) % monthStep) { return ''; }
                return monthFormat === 'year-month' ? p.yyyy + '-' + p.MM
                    : (monthFormat === 'number' ? p.MM : monthNames[Number(p.M) - 1]);
            };
        }
        function format(value, unit) {
            return Number(value).toLocaleString(undefined, { minimumFractionDigits: calendar.decimals,
                maximumFractionDigits: calendar.decimals }) + (unit && calendar.unit ? ' ' + calendar.unit : '');
        }
        var showValues = style.showValues === true && cellWidth >= valueSize * (calendar.decimals + 4)
            && cellHeight >= valueSize + 4;
        var showDays = !showValues && style.showDayNumbers === true
            && cellWidth >= valueSize * 1.8 && cellHeight >= valueSize + 4;
        var border = style.cellBorderColor || colors.background;
        return {
            backgroundColor: bootstrap.mode === 'ipsview' && bootstrap.options.adaptToBackground ? 'transparent' : colors.background,
            ...design.animationOptions(style, { enabled: true, initialDuration: 350, updateDuration: 500 }),
            aria: { enabled: true, description: calendar.sourceLabel + ': ' + (calendar.periodLabel || calendar.year) + ', ' + metricLabel(calendar.metric) },
            title: { show: title.show, text: calendar.title, left: 'center', top: title.top,
                textStyle: { color: style.titleColor || colors.text, fontSize: titleSize } },
            tooltip: { show: style.showTooltip !== false, formatter: function (p) {
                var value = p.value || [];
                return escapeHTML(calendar.sourceLabel) + '<br>' + escapeHTML(value[0]) + '<br>'
                    + escapeHTML(metricLabel(calendar.metric)) + ': ' + escapeHTML(format(value[1], true));
            } },
            calendar: { range: calendar.calendarRange || String(calendar.year), orient: vertical ? 'vertical' : 'horizontal',
                left: left, right: right, top: top, bottom: bottom, cellSize: ['auto', 'auto'],
                yearLabel: { show: style.showYearLabel === true, position: yearPosition, margin: yearMargin,
                    color: style.yearColor || style.axisColor || colors.text, fontSize: yearSize,
                    fontWeight: style.labelBold ? 'bold' : 'normal' },
                monthLabel: { show: style.showMonthLabels, color: style.axisColor || colors.text, fontSize: axisSize,
                    position: monthPosition, margin: monthMargin, align: style.monthLabelAlign || 'center',
                    formatter: monthFormatter,
                    fontWeight: style.labelBold ? 'bold' : 'normal',
                    nameMap: monthNames },
                dayLabel: { show: style.showWeekdayLabels, firstDay: style.firstWeekday,
                    color: style.axisColor || colors.text, fontSize: axisSize, margin: weekdayMargin, position: weekdayPosition,
                    fontWeight: style.labelBold ? 'bold' : 'normal',
                    nameMap: weekdays },
                splitLine: { show: style.showMonthSeparators !== false, lineStyle: {
                    color: style.monthLineColor || style.cellBorderColor || colors.border,
                    width: style.monthLineWidth || style.cellBorderWidth, type: style.monthLineType || 'solid' } },
                itemStyle: { color: style.emptyCellColor || colors.surface, borderWidth: style.cellBorderWidth,
                    borderType: style.cellBorderType || 'solid', borderColor: border,
                    opacity: style.cellOpacityPercent == null ? 1 : style.cellOpacityPercent / 100 } },
            visualMap: { show: style.showColorScale === true && data.length > 0, type: 'continuous',
                min: min, max: max, dimension: 1, seriesIndex: 0, calculable: false, orient: 'horizontal',
                left: 'center', bottom: 8, itemWidth: 10, itemHeight: Math.max(40, Math.min(180, width - 170)),
                textStyle: { color: style.axisColor || colors.text, fontSize: axisSize },
                text: [format(max, true), format(min, true)],
                formatter: function (value) { return format(value, true); },
                inRange: { color: scaleColors } },
            series: [{ type: 'heatmap', coordinateSystem: 'calendar',
                data: data.map(function (cell) { return [cell.date, cell.value]; }),
                itemStyle: { borderWidth: style.cellBorderWidth, borderColor: border, borderType: style.cellBorderType || 'solid',
                    opacity: style.cellOpacityPercent == null ? 1 : style.cellOpacityPercent / 100 },
                label: { show: showValues || showDays, fontSize: valueSize, color: style.valueColor || colors.text,
                    fontWeight: style.labelBold ? 'bold' : 'normal',
                    formatter: function (p) { return showValues ? format(p.value[1], false) : String(Number(p.value[0].slice(8))); } },
                emphasis: { disabled: style.hoverHighlight === false,
                    itemStyle: { borderWidth: Math.max(1, style.cellBorderWidth), borderColor: style.hoverBorderColor || colors.text } } }]
        };
    }
    function updatePeriod(calendar) {
        period.hidden = false;
        periodLabel.textContent = String(calendar.periodLabel || calendar.year);
        var periodNames = { year: ['Previous year', 'Next year', 'Selected year'], month: ['Previous month', 'Next month', 'Selected month'],
            quarter: ['Previous quarter', 'Next quarter', 'Selected quarter'], absolute: ['Previous period', 'Next period', 'Selected period'] };
        var names = periodNames[calendar.period || 'year'] || periodNames.year;
        previous.setAttribute('aria-label', translate(names[0]));
        next.setAttribute('aria-label', translate(names[1]));
        selected.textContent = translate(names[2]);
        currentOffset = Number(calendar.windowOffset) || 0;
        previous.disabled = navigating || currentOffset >= calendar.maxOffset;
        next.disabled = navigating || currentOffset === 0;
        selected.disabled = navigating || currentOffset === 0;
    }
    function render(state) {
        if (retain(state, currentState, chart)) { return; }
        currentState = state;
        if (!state || state.status !== 'ready' || !state.chart || !state.chart.calendar || !window.echarts) {
            if (chart) { chart.clear(); }
            chartElement.hidden = true;
            period.hidden = true;
            empty.hidden = true;
            warning.hidden = true;
            error.textContent = translate(state && state.error || loadError);
            error.hidden = false;
            return;
        }
        var nextTheme = state.chart.theme || 'auto';
        var wasHidden = chartElement.hidden;
        chartElement.hidden = false;
        error.hidden = true;
        warning.hidden = true;
        empty.textContent = emptyLabel(state.chart.calendar.metric, state.chart.calendar.period);
        empty.hidden = state.chart.calendar.data.length > 0;
        root.style.backgroundColor = bootstrap.mode === 'ipsview' && bootstrap.options.adaptToBackground
            ? 'transparent' : colorsFor(nextTheme).background;
        if (chart && theme !== nextTheme) { chart.dispose(); chart = null; }
        if (!chart) {
            chart = window.echarts.init(chartElement, nextTheme === 'auto' ? null : nextTheme, { renderer: 'canvas' });
            theme = nextTheme;
        } else if (wasHidden) { chart.resize(); }
        updatePeriod(state.chart.calendar);
        chart.setOption(buildOption(state.chart, nextTheme), true);
    }
    function stateURL(offset) {
        var path = bootstrap.options.calendarStatePath;
        if (!path || !/^\/hook\/SymconECharts\/state\//.test(path)) { throw new Error('Missing state path.'); }
        var location = window.location || {};
        var protocol = location.protocol;
        var host = location.host;
        if (protocol !== 'http:' && protocol !== 'https:') {
            var source = String(location.href || '').startsWith('data:')
                ? decodeURIComponent(location.href) : ((document.head && document.head.innerHTML) || '');
            var base = source.match(/<base\s+href=["'](https?:)\/\/([^\/"']+)/i);
            if (base) { protocol = base[1]; host = base[2]; }
            else { protocol = window.top.location.protocol; host = window.top.location.host; }
        }
        return protocol + '//' + host + path + '?offset=' + offset
            + (bootstrap.mode === 'symcon' ? '&output=tile' : '');
    }
    function sameView(a, b) {
        if (!a || !b) { return false; }
        if (a.viewKey || b.viewKey) { return a.viewKey === b.viewKey; }
        return a.baseYear === b.baseYear && (a.metric || 'average') === (b.metric || 'average');
    }
    function navigate(offset) {
        var calendar = currentState && currentState.chart && currentState.chart.calendar;
        if (navigating || !calendar || offset < 0 || offset > calendar.maxOffset) { return; }
        navigating = true;
        updatePeriod(calendar);
        Promise.resolve().then(function () {
            return fetch(stateURL(offset), { method: 'GET', cache: 'no-store', credentials: 'same-origin',
                headers: { Accept: 'application/json' } });
        }).then(function (response) {
            if (!response.ok) { throw new Error('Calendar request failed.'); }
            return response.json();
        }).then(function (state) {
            if (!state || state.status !== 'ready' || !state.chart || state.chart.family !== 'calendar'
                || !sameView(state.chart.calendar, calendar) || state.chart.calendar.windowOffset !== offset) {
                throw new Error('Invalid Calendar response.');
            }
            // A configuration update may have arrived while the request was in flight.
            if (sameView(currentState.chart.calendar, calendar)) { render(state); }
        }).catch(function () {
            warning.textContent = translate(loadError);
            warning.hidden = false;
        }).finally(function () {
            navigating = false;
            if (currentState && currentState.chart && currentState.chart.calendar) { updatePeriod(currentState.chart.calendar); }
        });
    }
    previous.addEventListener('click', function () { navigate(currentOffset + 1); });
    next.addEventListener('click', function () { navigate(currentOffset - 1); });
    selected.addEventListener('click', function () { navigate(0); });
    window.handleMessage = function (message) {
        var state = design.parseVisualizationState(message);
        if (!state) { return; }
        var calendar = state.chart && state.chart.calendar;
        if (currentOffset > 0 && calendar && calendar.windowOffset === 0 && currentState && currentState.chart
            && sameView(currentState.chart.calendar, calendar)) { navigate(currentOffset); return; }
        render(state);
    };
    function resize() {
        if (design.resizeChartIfNeeded(chart, chartElement)) { render(currentState); }
    }
    if (window.ResizeObserver) { new ResizeObserver(resize).observe(chartElement); }
    else { window.addEventListener('resize', resize); }
    window.addEventListener('beforeunload', function () { if (chart) { chart.dispose(); } });
    render(currentState);
}());
