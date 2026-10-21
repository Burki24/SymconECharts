(function () {
    'use strict';
    var bootstrap = window.SYMC_VISUALIZATION || {};
    var options = bootstrap.options || {};
    var design = window.SYMC_ECHARTS_DESIGN;
    function element(name) { return document.getElementById('echarts-calendar-pie-' + name); }
    var root = element('root'), chartElement = element('chart'), warning = element('warning'), error = element('error');
    var period = element('period'), periodLabel = element('period-label');
    var previous = element('previous'), next = element('next'), selected = element('selected');
    var currentState = bootstrap.state || null, chart = null, theme = null, requestVersion = 0, navigating = false;
    var currentOffset = 0, desiredOffset = 0, disposed = false;
    var header = options.tileHeaderInset || 0;
    var chartBootstrap = {mode: bootstrap.mode, options: {tileHeaderVisible: false}};
    chartElement.style.top = header + 'px';
    chartElement.style.height = 'calc(100% - ' + header + 'px)';
    var loadError = 'The CalendarPie values could not be loaded.';
    function translate(text) { return (bootstrap.translations || {})[text] || text; }
    var retain = design.createTransientFailureRetainer(chartElement, warning, translate, loadError);
    function escapeHTML(value) {
        return String(value).replace(/[&<>"']/g, function (c) {
            return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c];
        });
    }
    function colorsFor(id) {
        var palettes = options.echartsThemes || {};
        var colors = Object.assign({background: '#151619', text: '#F4F5F7', muted: '#969AA2', border: '#34363B',
            surface: '#25272B', seriesColors: ['#5070DD', '#B6D634', '#FF994D', '#55CBB5']}, palettes[id] || palettes.auto || {});
        if (id === 'auto') {
            ['background', 'text', 'border', 'surface', 'muted'].forEach(function (name) {
                var probe = document.createElement('span');
                probe.style.color = 'var(--symc-' + (name === 'muted' ? 'text-muted' : name) + ', ' + colors[name] + ')';
                probe.style.display = 'none';
                document.body.appendChild(probe);
                colors[name] = window.getComputedStyle(probe).color || colors[name];
                probe.remove();
            });
        }
        return colors;
    }
    function bound(value, minimum, maximum, fallback) {
        var number = Number(value);
        return Number.isFinite(number) ? Math.min(maximum, Math.max(minimum, number)) : fallback;
    }
    function calendarData() { return currentState && currentState.chart && currentState.chart.calendarPie; }
    function monthName(month) {
        var names = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
        return translate(names[Number(month.slice(5)) - 1]) + ' ' + month.slice(0, 4);
    }
    function buildOption(model, id) {
        var calendar = model.calendarPie, style = calendar.style || {}, colors = colorsFor(id);
        var days = calendar.days || [], categories = calendar.categories || [];
        var colorByID = Object.create(null), labelByID = Object.create(null), dayByDate = Object.create(null);
        categories.forEach(function (category, index) {
            colorByID[category.id] = category.id === 'unknown' ? style.unknownColor || colors.muted
                : category.color || colors.seriesColors[index % colors.seriesColors.length];
            labelByID[category.id] = category.id === 'unknown' ? translate('Unknown') : category.label;
        });
        colorByID.unknown = style.unknownColor || colors.muted;
        labelByID.unknown = translate('Unknown');
        days.forEach(function (day) { dayByDate[day.date] = day; });
        var width = chartElement.clientWidth || 720, height = chartElement.clientHeight || 420;
        var titleSize = design.fontSizePercent(style.titleFontSizePercent, 18);
        var axisSize = design.fontSizePercent(style.axisFontSizePercent, width < 450 ? 10 : 12);
        var valueSize = design.fontSizePercent(style.valueFontSizePercent, 10);
        var title = design.chartTitleLayout(chartBootstrap, calendar.title, titleSize);
        period.style.top = (header + title.contentTop) + 'px';
        period.style.color = style.axisColor || colors.text;
        var firstDay = bound(style.firstWeekday, 0, 6, 1);
        var monthStart = new Date(calendar.month + '-01T12:00:00Z');
        var dayCount = new Date(Date.UTC(monthStart.getUTCFullYear(), monthStart.getUTCMonth() + 1, 0)).getUTCDate();
        var weekCount = Math.ceil((((monthStart.getUTCDay() - firstDay + 7) % 7) + dayCount) / 7);
        var top = title.contentTop + 36 + axisSize + 10;
        var bottom = style.showLegend === false ? 12 : axisSize + 32;
        top = Math.min(top, Math.max(1, height - bottom - 7));
        var left = 10, right = 10;
        var cellWidth = Math.max(1, width - left - right) / 7;
        var cellHeight = Math.max(1, height - top - bottom) / weekCount;
        var radius = Math.max(0.25, Math.min(cellWidth, cellHeight) / 2 - 3)
            * bound(style.pieRadiusPercent, 20, 90, 70) / 100;
        var showDays = style.showDayNumbers !== false && cellWidth >= valueSize * 2.2 && cellHeight >= valueSize * 3 + 12;
        // Native calendar pies stay centered on their date. Reserve a clear day-number strip above them.
        if (showDays) { radius = Math.min(radius, Math.max(0.25, cellHeight / 2 - valueSize - 6)); }
        var showValues = style.showValues === true && radius >= valueSize * 2.5;
        var border = style.cellBorderColor || colors.border;
        var statusText = {complete: '', zero: 'No positive values', partial: 'Incomplete data', missing: 'Missing data', pending: 'Not completed'};
        var modeText = {average: 'Daily means', counter: 'Daily consumption', states: 'State duration'};
        function label(category) { return labelByID[category.id] == null ? category.label : labelByID[category.id]; }
        function formatted(value, includeUnit) {
            return Number(calendar.mode === 'states' ? value / 3600 : value).toLocaleString(bootstrap.language || undefined,
                {minimumFractionDigits: calendar.decimals, maximumFractionDigits: calendar.decimals})
                + (includeUnit !== false && calendar.unit ? ' ' + calendar.unit : '');
        }
        function formattedSlice(slice, includeUnit) {
            if (!calendar.normalized || calendar.mode === 'states') { return formatted(slice.value, includeUnit); }
            return Number(slice.originalValue).toLocaleString(bootstrap.language || undefined,
                {minimumFractionDigits: slice.decimals, maximumFractionDigits: slice.decimals})
                + (includeUnit !== false && slice.unit ? ' ' + slice.unit : '');
        }
        function shortNumber(value) {
            return Number(value).toLocaleString(bootstrap.language || undefined, {maximumFractionDigits: 6});
        }
        function tooltip(p) {
            var raw = p.data || {}, day = dayByDate[raw.date || raw.value && raw.value[0]];
            if (!day) { return ''; }
            var result = escapeHTML(day.date) + '<br>' + escapeHTML(translate(modeText[calendar.mode]));
            if (calendar.normalized && calendar.mode !== 'states') { result += '<br>' + escapeHTML(translate('Normalized comparison (0–100)')); }
            if (statusText[day.status]) { result += '<br>' + escapeHTML(translate(statusText[day.status])); }
            var total = calendar.mode === 'states' ? day.daySeconds : (day.slices || []).reduce(function (sum, slice) { return sum + slice.value; }, 0);
            (day.slices || []).forEach(function (slice) {
                var percentage = total > 0 && (day.status === 'complete' || calendar.mode === 'states')
                    ? ' (' + (slice.value / total * 100).toLocaleString(bootstrap.language || undefined, {maximumFractionDigits: 1}) + ' %)' : '';
                result += '<br>' + escapeHTML(label(slice)) + ': ' + escapeHTML(formattedSlice(slice)
                    + (calendar.normalized && calendar.mode !== 'states' ? '' : percentage));
                if (calendar.normalized && calendar.mode !== 'states') {
                    result += '<br>' + escapeHTML(translate('Normalized') + ': ' + shortNumber(slice.value) + ' / 100'
                        + ' · ' + translate('Range') + ': ' + shortNumber(slice.minimum) + '–' + shortNumber(slice.maximum)
                        + (slice.unit ? ' ' + slice.unit : '')
                        + (percentage ? ' · ' + translate('Circle share') + ': ' + percentage.trim().slice(1, -1) : ''));
                }
            });
            if ((day.missing || []).length) {
                result += '<br>' + escapeHTML(translate('Missing data')) + ': '
                    + escapeHTML(day.missing.map(function (key) { return labelByID[key] || translate('Unknown'); }).join(', '));
            }
            return result;
        }
        var series = [];
        // A zero-radius provider keeps every category in the legend, even in an entirely missing month.
        if (style.showLegend !== false && categories.length) {
            series.push({id: 'legend-categories', type: 'pie', coordinateSystem: 'calendar', center: calendar.month + '-01',
                radius: 0, silent: true, tooltip: {show: false}, label: {show: false}, labelLine: {show: false},
                stillShowZeroSum: false, showEmptyCircle: false,
                data: categories.map(function (category) { return {name: category.id, value: 0, itemStyle: {color: colorByID[category.id]}}; })});
        }
        // Zero, absent, incomplete numeric and unfinished days never become synthetic equal slices.
        days.forEach(function (day) {
            var positive = (day.slices || []).filter(function (slice) { return Number.isFinite(slice.value) && slice.value > 0; });
            if (!positive.length || day.status === 'pending'
                || (calendar.mode !== 'states' && day.status !== 'complete')) { return; }
            var innerRadius = radius * bound(style.innerRadiusPercent, 0, 80, 0) / 100;
            var total = positive.reduce(function (sum, slice) { return sum + slice.value; }, 0);
            var previousValue = 0;
            var pieData = positive.map(function (slice) {
                var text = formattedSlice(slice, false);
                var measurement = window.echarts.format.getTextRect(text, valueSize + 'px sans-serif');
                var angle = -bound(style.startAngle, 0, 360, 90) / 180 * Math.PI
                    + (style.clockwise === false ? -1 : 1) * (previousValue + slice.value / 2) / total * Math.PI * 2;
                previousValue += slice.value;
                var labelRadius = (radius + innerRadius) / 2;
                var x = Math.cos(angle) * labelRadius, y = Math.sin(angle) * labelRadius;
                var halfWidth = measurement.width / 2 + 1, halfHeight = measurement.height / 2 + 1;
                var nearestX = Math.max(0, Math.abs(x) - halfWidth), nearestY = Math.max(0, Math.abs(y) - halfHeight);
                // Never abbreviate numeric values or let them escape a circle/ring. Full values remain in tooltips.
                var fits = measurement.width <= radius * 0.9
                    && Math.hypot(Math.abs(x) + halfWidth, Math.abs(y) + halfHeight) <= radius - 1
                    && (innerRadius === 0 || Math.hypot(nearestX, nearestY) >= innerRadius + 1);
                return {name: slice.id, value: slice.value, date: day.date, labelText: text,
                    label: {show: showValues && fits}, emphasis: {label: {show: false}},
                    itemStyle: {color: colorByID[slice.id] || slice.color || colors.muted}};
            });
            series.push({id: 'day-' + day.date, type: 'pie', coordinateSystem: 'calendar', center: day.date,
                radius: [innerRadius, radius],
                startAngle: bound(style.startAngle, 0, 360, 90), clockwise: style.clockwise !== false,
                stillShowZeroSum: false, showEmptyCircle: false, selectedMode: false,
                // Scaling on hover must not enter adjacent cells or day numbers.
                emphasis: {scale: false, focus: 'self'},
                labelLayout: {hideOverlap: true}, minShowLabelAngle: 15,
                labelLine: {show: false}, label: {show: showValues, position: 'inside', fontSize: valueSize,
                    fontFamily: 'sans-serif', align: 'center', verticalAlign: 'middle',
                    color: style.valueColor || undefined, formatter: function (p) { return p.data.labelText; }},
                itemStyle: {borderWidth: bound(style.sliceBorderWidth, 0, 5, 1), borderColor: style.sliceBorderColor || colors.surface},
                data: pieData});
        });
        var markers = days.filter(function (day) {
            return day.status !== 'complete' && !(calendar.mode === 'states' && day.status !== 'pending'
                && (day.slices || []).some(function (slice) { return slice.value > 0; }));
        });
        series.push({id: 'day-status', type: 'scatter', coordinateSystem: 'calendar', z: 3,
            symbolSize: Math.max(2, Math.min(10, Math.min(cellWidth, cellHeight) * 0.22)),
            data: markers.map(function (day) {
                return {value: [day.date, 0], date: day.date,
                    symbol: {zero: 'emptyCircle', partial: 'diamond', missing: 'rect', pending: 'triangle'}[day.status] || 'rect',
                    itemStyle: {color: day.status === 'partial' ? '#FF994D' : colors.muted, opacity: day.status === 'pending' ? 0.4 : 0.8}};
            })});
        series.push({id: 'day-numbers', type: 'scatter', coordinateSystem: 'calendar', z: 4, symbolSize: 0,
            label: {show: showDays, position: 'inside', align: 'left', verticalAlign: 'top',
                offset: [-cellWidth / 2 + 4, -cellHeight / 2 + 3], fontSize: valueSize, color: style.axisColor || colors.text,
                formatter: function (p) { return String(Number(p.value[0].slice(8))); }},
            data: days.map(function (day) { return {value: [day.date, 0], date: day.date}; })});
        return {backgroundColor: bootstrap.mode === 'ipsview' && options.adaptToBackground ? 'transparent' : colors.background,
            ...design.animationOptions(style, {enabled: true, initialDuration: 350, updateDuration: 500}),
            aria: {enabled: true, label: {description: calendar.title + ': ' + monthName(calendar.month) + ', ' + translate(modeText[calendar.mode])
                + (calendar.normalized && calendar.mode !== 'states' ? ', ' + translate('Normalized comparison (0–100)') : '')}},
            title: {show: title.show, text: calendar.title, left: 'center', top: title.top,
                textStyle: {color: style.titleColor || colors.text, fontSize: titleSize, width: Math.max(1, width - 24), overflow: 'truncate'}},
            tooltip: {show: style.showTooltip !== false, confine: true, formatter: tooltip,
                extraCssText: calendar.normalized && calendar.mode !== 'states'
                    ? 'max-width:' + Math.max(80, width - 16) + 'px;white-space:normal;overflow-wrap:anywhere;box-sizing:border-box;' : ''},
            legend: {show: style.showLegend !== false, type: 'scroll', bottom: 8, left: 10, right: 10,
                selectedMode: false, itemWidth: 12, itemHeight: 9, itemGap: 10,
                textStyle: {color: style.axisColor || colors.text, fontSize: axisSize,
                    width: Math.max(24, Math.min(220, width - 100)), overflow: 'truncate'},
                pageTextStyle: {color: style.axisColor || colors.text}, pageIconColor: colors.text, pageIconInactiveColor: colors.muted,
                data: categories.map(function (category) { return {name: category.id, itemStyle: {color: colorByID[category.id]}}; }),
                formatter: function (name) { return labelByID[name] || ''; }},
            calendar: {range: calendar.month, orient: 'vertical', left: left, right: right, top: top, bottom: bottom,
                cellSize: ['auto', 'auto'], yearLabel: {show: false}, monthLabel: {show: false}, splitLine: {show: false},
                dayLabel: {firstDay: firstDay, nameMap: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'].map(translate),
                    color: style.axisColor || colors.text, fontSize: axisSize, margin: 6},
                itemStyle: {color: style.emptyCellColor || colors.surface, borderColor: border,
                    borderWidth: bound(style.cellBorderWidth, 0, 4, 1), opacity: bound(style.cellOpacityPercent, 0, 100, 100) / 100}},
            series: series};
    }
    function updatePeriod(calendar) {
        period.hidden = false;
        periodLabel.textContent = monthName(calendar.month);
        previous.setAttribute('aria-label', translate('Previous month'));
        next.setAttribute('aria-label', translate('Next month'));
        selected.textContent = translate('Selected month');
        currentOffset = Number(calendar.windowOffset) || 0;
        previous.disabled = navigating || currentOffset >= calendar.maxOffset;
        next.disabled = navigating || currentOffset === 0;
        selected.disabled = navigating || currentOffset === 0;
    }
    function render(state) {
        if (disposed || retain(state, currentState, chart)) { return; }
        currentState = state;
        var calendar = calendarData();
        if (!state || state.status !== 'ready' || !calendar || !window.echarts) {
            if (chart) { chart.clear(); }
            chartElement.hidden = true;
            period.hidden = true;
            warning.hidden = true;
            error.textContent = translate(state && state.error || loadError);
            error.hidden = false;
            return;
        }
        var nextTheme = state.chart.theme || 'auto', wasHidden = chartElement.hidden;
        chartElement.hidden = false;
        error.hidden = true;
        warning.hidden = true;
        root.style.backgroundColor = bootstrap.mode === 'ipsview' && options.adaptToBackground ? 'transparent' : colorsFor(nextTheme).background;
        if (chart && theme !== nextTheme) { chart.dispose(); chart = null; }
        if (!chart) { chart = window.echarts.init(chartElement, nextTheme === 'auto' ? null : nextTheme, {renderer: 'canvas'}); theme = nextTheme; }
        else if (wasHidden) { chart.resize(); }
        updatePeriod(calendar);
        chart.setOption(buildOption(state.chart, nextTheme), true);
    }
    function readOrigin(reader) {
        try {
            var url = new URL(reader());
            if ((url.protocol === 'http:' || url.protocol === 'https:') && url.host && !url.username && !url.password) { return url.origin; }
        } catch (ignored) { /* Opaque frames and denied ancestor access are expected. */ }
        return null;
    }
    function address(target) { return target.location.href || target.location.protocol + '//' + target.location.host; }
    function stateURL(offset) {
        var path = options.calendarPieStatePath;
        if (typeof path !== 'string' || !/^\/hook\/SymconECharts\/state\/[0-9]+\/[a-zA-Z0-9]+\/?$/.test(path)) { throw new Error('Missing state path.'); }
        var origin = readOrigin(function () { return address(window); }) || readOrigin(function () { return document.baseURI; })
            || readOrigin(function () { return address(window.parent); }) || readOrigin(function () { return document.referrer; });
        if (!origin) {
            try {
                var ancestors = window.location.ancestorOrigins || [];
                for (var i = 0; !origin && i < ancestors.length; i++) { origin = readOrigin(function () { return ancestors[i]; }); }
            } catch (ignored) { /* Fall through to guarded same-origin top access. */ }
        }
        origin = origin || readOrigin(function () { return address(window.top); });
        if (!origin) { throw new Error('Missing state origin.'); }
        return origin + path + '?offset=' + offset + (bootstrap.mode === 'symcon' ? '&output=tile' : '');
    }
    function navigate(offset) {
        var calendar = calendarData();
        if (disposed || !calendar || !Number.isInteger(offset) || offset < 0 || offset > calendar.maxOffset) { return; }
        var version = ++requestVersion;
        desiredOffset = offset;
        navigating = true;
        updatePeriod(calendar);
        Promise.resolve().then(function () {
            return fetch(stateURL(offset), {method: 'GET', cache: 'no-store', credentials: 'same-origin', headers: {Accept: 'application/json'}});
        }).then(function (response) {
            if (!response.ok) { throw new Error('CalendarPie request failed.'); }
            return response.json();
        }).then(function (state) {
            var current = calendarData(), data = state && state.chart && state.chart.calendarPie;
            if (disposed || version !== requestVersion) { return; }
            if (!state || state.status !== 'ready' || state.chart.family !== 'calendarPie' || !data
                || data.baseMonth !== calendar.baseMonth || data.mode !== calendar.mode || data.windowOffset !== offset) { throw new Error('Invalid CalendarPie response.'); }
            if (current && current.baseMonth === calendar.baseMonth && current.mode === calendar.mode) { render(state); }
        }).catch(function () {
            if (!disposed && version === requestVersion) { warning.textContent = translate(loadError); warning.hidden = false; }
        }).finally(function () {
            if (!disposed && version === requestVersion) { navigating = false; if (calendarData()) { updatePeriod(calendarData()); } }
        });
    }
    previous.addEventListener('click', function () { if (!navigating) { navigate(currentOffset + 1); } });
    next.addEventListener('click', function () { if (!navigating) { navigate(currentOffset - 1); } });
    selected.addEventListener('click', function () { if (!navigating) { navigate(0); } });
    window.handleMessage = function (message) {
        var state = design.parseVisualizationState(message);
        if (!state) { return; }
        var data = state.chart && state.chart.calendarPie, current = calendarData();
        var keepOffset = navigating ? desiredOffset : currentOffset;
        if (data && current && keepOffset > 0 && data.windowOffset === 0 && data.baseMonth === current.baseMonth && data.mode === current.mode) {
            navigate(keepOffset); return;
        }
        requestVersion++;
        navigating = false;
        render(state);
    };
    function resize() { if (!disposed && design.resizeChartIfNeeded(chart, chartElement)) { render(currentState); } }
    var observer;
    if (window.ResizeObserver) { observer = new ResizeObserver(resize); observer.observe(chartElement); }
    else { window.addEventListener('resize', resize); }
    window.addEventListener('beforeunload', function () {
        disposed = true; requestVersion++;
        if (observer) { observer.disconnect(); }
        if (chart) { chart.dispose(); }
    });
    render(currentState);
}());
