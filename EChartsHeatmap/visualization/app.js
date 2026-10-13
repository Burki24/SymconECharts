(function () {
    'use strict';

    var bootstrap = window.SYMC_VISUALIZATION || {};
    var design = window.SYMC_ECHARTS_DESIGN;
    var chartElement = document.getElementById('echarts-heatmap-chart');
    var warningElement = document.getElementById('echarts-heatmap-warning');
    var emptyElement = document.getElementById('echarts-heatmap-empty');
    var errorElement = document.getElementById('echarts-heatmap-error');
    var classLegendElement = document.getElementById('echarts-heatmap-class-legend');
    var classMinElement = document.getElementById('echarts-heatmap-class-min');
    var classSegmentsElement = document.getElementById('echarts-heatmap-class-segments');
    var classMaxElement = document.getElementById('echarts-heatmap-class-max');
    var periodElement = document.getElementById('echarts-heatmap-period');
    var periodLabelElement = document.getElementById('echarts-heatmap-period-label');
    var previousElement = document.getElementById('echarts-heatmap-previous');
    var nextElement = document.getElementById('echarts-heatmap-next');
    var todayElement = document.getElementById('echarts-heatmap-today');
    var chart = null;
    var theme = null;
    var currentState = bootstrap.state || null;
    var currentOffset = 0;
    var navigating = false;
    var loadError = 'The Heatmap values could not be loaded.';
    var retainOnTransientError = design.createTransientFailureRetainer(
        chartElement, warningElement, translate, loadError
    );

    function translate(text) {
        return (bootstrap.translations || {})[text] || text;
    }

    function palette(themeId) {
        var palettes = bootstrap.options && bootstrap.options.echartsThemes || {};
        return palettes[themeId] || palettes.auto || {
            background: '#151619', text: '#F4F5F7', muted: '#969AA2', border: '#A5A9B0',
            track: '#34363B', accent: '#55CBB5'
        };
    }

    function resolveColor(variable, fallback) {
        var probe = document.createElement('span');
        probe.style.color = 'var(' + variable + ', ' + fallback + ')';
        probe.style.display = 'none';
        document.body.appendChild(probe);
        var result = window.getComputedStyle(probe).color;
        probe.remove();
        return result || fallback;
    }

    function colorsFor(themeId) {
        var colors = palette(themeId);
        if (themeId !== 'auto') { return colors; }
        return {
            background: resolveColor('--symc-background', colors.background),
            text: resolveColor('--symc-text', colors.text),
            muted: resolveColor('--symc-text-muted', colors.muted),
            border: resolveColor('--symc-border', colors.border),
            track: resolveColor('--symc-surface', colors.track),
            accent: resolveColor('--symc-accent', colors.accent)
        };
    }

    function formatValue(value, decimals, unit) {
        return Number(value).toLocaleString(undefined, {
            minimumFractionDigits: decimals, maximumFractionDigits: decimals
        }) + (unit ? ' ' + unit : '');
    }

    function escapeHTML(value) {
        return String(value).replace(/[&<>"']/g, function (character) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[character];
        });
    }

    function dateLabel(date) {
        return /^\d{4}-\d{2}-\d{2}$/.test(date || '')
            ? date.slice(8, 10) + '.' + date.slice(5, 7) + '.' + date.slice(0, 4) : '';
    }

    function updatePeriod(heatmap, headerInset) {
        periodElement.style.top = headerInset + 'px';
        periodElement.hidden = !bootstrap.options || !bootstrap.options.heatmapStatePath;
        periodElement.setAttribute('aria-label', translate('Time range'));
        previousElement.setAttribute('aria-label', translate('Previous period'));
        nextElement.setAttribute('aria-label', translate('Next period'));
        todayElement.textContent = translate('Today');
        currentOffset = Math.max(0, Number(heatmap.windowOffset) || 0);
        periodLabelElement.textContent = dateLabel(heatmap.startDate) + ' – ' + dateLabel(heatmap.endDate)
            + ' (' + translate(heatmap.range === '7d' ? '7 days' : '30-day average') + ')';
        previousElement.disabled = navigating || currentOffset >= (Number(bootstrap.options.heatmapMaxOffset) || 120);
        nextElement.disabled = navigating || currentOffset === 0;
        todayElement.disabled = navigating || currentOffset === 0;
    }

    function buildOption(model, themeId) {
        var colors = colorsFor(themeId);
        var heatmap = model.heatmap || {};
        var style = heatmap.style || {};
        var cells = Array.isArray(model.cells) ? model.cells : [];
        var values = cells.map(function (cell) { return Number(cell.value); })
            .filter(function (value) { return Number.isFinite(value); });
        var minimum = values.length ? Math.min.apply(null, values) : 0;
        var maximum = values.length ? Math.max.apply(null, values) : 1;
        var displayedMaximum = maximum;
        if (minimum === maximum) { maximum = minimum + 1; }
        var width = Math.max(240, Number(chartElement.clientWidth) || 720);
        var height = Math.max(190, Number(chartElement.clientHeight) || 420);
        var compact = width < 550 || height < 290;
        var headerInset = bootstrap.mode === 'symcon' && bootstrap.options.tileHeaderVisible !== false ? 58 : 8;
        var titleSize = design.fontSizePercent(style.titleFontSizePercent, compact ? 14 : 18);
        var axisSize = design.fontSizePercent(style.axisFontSizePercent, compact ? 10 : 12);
        var valueSize = design.fontSizePercent(style.valueFontSizePercent, compact ? 9 : 11);
        var top = headerInset + 26 + (heatmap.title ? (compact ? 32 : 42) : 14);
        var bottom = style.showColorScale === true ? (compact ? 62 : 76) : 34;
        var plotWidth = width - 60;
        var plotHeight = height - top - bottom;
        var showValues = style.showValues === true && plotWidth / 24 >= valueSize * 2.8
            && plotHeight / 7 >= valueSize * 1.5;
        var weekdays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'].map(translate);
        var datedDays = heatmap.range === '7d' && Array.isArray(heatmap.days) && heatmap.days.length === 7
            ? heatmap.days : null;
        var days = datedDays ? datedDays.map(function (day) {
            return weekdays[day.weekday] + ' ' + dateLabel(day.date).slice(0, 6);
        }) : weekdays;
        var weekdayRows = {};
        if (datedDays) {
            datedDays.forEach(function (day, index) { weekdayRows[day.weekday] = index; });
        }
        var low = style.lowColor || colors.track || colors.background;
        var high = style.highColor || colors.accent;
        var suppliedColors = Array.isArray(style.colorScaleColors)
            ? style.colorScaleColors.filter(function (color) { return /^#[0-9a-f]{6}$/i.test(color); }) : [];
        var customMiddleColors = Array.isArray(style.customMiddleColors)
            ? style.customMiddleColors.filter(function (color) { return /^#[0-9a-f]{6}$/i.test(color); }).slice(0, 3) : [];
        var presetRange = style.colorScaleRange || {};
        var presetMinimum = Number(presetRange.min);
        var presetMaximum = Number(presetRange.max);
        var classScale = style.colorScalePreset && style.colorScalePreset !== 'custom'
            && suppliedColors.length >= 2 && suppliedColors.length === style.colorScaleColors.length
            && presetRange.min !== undefined && presetRange.max !== undefined
            && Number.isFinite(presetMinimum) && Number.isFinite(presetMaximum)
            && presetMinimum < presetMaximum;
        var scaleColors = classScale ? suppliedColors
            : style.colorScalePreset && style.colorScalePreset !== 'custom'
                ? [low, high] : [low].concat(customMiddleColors, [high]);
        var decimals = Math.max(0, Math.min(6, Number(heatmap.decimals) || 0));
        var visualMap = {
            type: 'continuous', show: style.showColorScale === true && values.length > 0,
            min: minimum, max: maximum,
            text: [formatValue(displayedMaximum, decimals, heatmap.unit || ''),
                formatValue(minimum, decimals, heatmap.unit || '')],
            dimension: 2, calculable: false, orient: 'horizontal', left: 'center', bottom: compact ? 5 : 12,
            itemWidth: compact ? 10 : 12, itemHeight: Math.max(80, Math.min(220, width * 0.32)),
            textGap: compact ? 5 : 8,
            textStyle: { color: style.axisColor || colors.text, fontSize: axisSize },
            inRange: { color: scaleColors }
        };
        if (classScale) {
            // A preset's Celsius class boundaries must not move with the archive range.
            var classWidth = (presetMaximum - presetMinimum) / scaleColors.length;
            visualMap = {
                type: 'piecewise', show: false, dimension: 2,
                textStyle: { color: style.axisColor || colors.text, fontSize: axisSize },
                pieces: scaleColors.map(function (color, index) {
                    var lower = presetMinimum + classWidth * index;
                    var upper = presetMinimum + classWidth * (index + 1);
                    if (index === 0) { return { lt: upper, color: color }; }
                    if (index === scaleColors.length - 1) { return { gte: lower, color: color }; }
                    return { gte: lower, lt: upper, color: color };
                })
            };
        }

        function cellBackground(value) {
            if (classScale) {
                var classIndex = Math.max(0, Math.min(scaleColors.length - 1,
                    Math.floor((value - presetMinimum) / classWidth)));
                return scaleColors[classIndex];
            }
            return design.interpolateColorStops(scaleColors, (value - minimum) / (maximum - minimum)) || low;
        }

        return {
            backgroundColor: bootstrap.mode === 'ipsview' && bootstrap.options.adaptToBackground === true
                ? 'transparent' : colors.background,
            ...design.animationOptions(style, { enabled: true, initialDuration: 350, updateDuration: 500 }),
            aria: { enabled: true, description: String(heatmap.title || translate('Heatmap'))
                + ': ' + dateLabel(heatmap.startDate) + ' – ' + dateLabel(heatmap.endDate) },
            title: {
                show: Boolean(heatmap.title), text: String(heatmap.title || ''), left: 'center', top: headerInset + 26,
                textStyle: { color: style.titleColor || colors.text, fontSize: titleSize }
            },
            grid: { left: datedDays ? (compact ? 70 : 88) : (compact ? 38 : 48),
                right: compact ? 10 : 18, top: top, bottom: bottom },
            tooltip: {
                trigger: 'item',
                formatter: function (parameters) {
                    var data = parameters.data || {};
                    var value = data.value || [];
                    var dayLabel = datedDays && datedDays[value[1]]
                        ? weekdays[datedDays[value[1]].weekday] + ' ' + dateLabel(datedDays[value[1]].date)
                        : days[value[1]] || '';
                    var intervalCount = String(data.count || 1);
                    var intervalInfo = heatmap.range === '30d'
                        ? translate('Average of {count} hourly intervals').replace('{count}', intervalCount)
                        : intervalCount + ' ' + translate('Hourly intervals');
                    return escapeHTML(dayLabel) + ', ' + escapeHTML(String(value[0]).padStart(2, '0'))
                        + ':00<br>' + escapeHTML(formatValue(value[2], decimals, heatmap.unit || ''))
                        + '<br>' + escapeHTML(intervalInfo);
                }
            },
            xAxis: {
                type: 'category', data: Array.from({ length: 24 }, function (_, index) { return index; }),
                axisLabel: { color: style.axisColor || colors.text, fontSize: axisSize,
                    interval: compact ? 5 : 2 },
                axisLine: { lineStyle: { color: style.axisColor || colors.border } },
                splitArea: { show: true, areaStyle: { color: [colors.background] } },
                splitLine: { show: true, lineStyle: { color: style.gridColor || colors.border, opacity: 0.25 } }
            },
            yAxis: {
                type: 'category', data: days, inverse: true,
                axisLabel: { color: style.axisColor || colors.text, fontSize: axisSize },
                axisLine: { lineStyle: { color: style.axisColor || colors.border } },
                splitArea: { show: true, areaStyle: { color: [colors.background] } },
                splitLine: { show: true, lineStyle: { color: style.gridColor || colors.border, opacity: 0.25 } }
            },
            visualMap: visualMap,
            series: [{
                type: 'heatmap', data: cells.map(function (cell) {
                    var value = Number(cell.value);
                    var weekday = Number(cell.weekday);
                    return { value: [Number(cell.hour), datedDays ? weekdayRows[weekday] : weekday, value],
                        count: Number(cell.count),
                        label: { color: style.valueColor || design.readableTextColor(cellBackground(value)) } };
                }),
                label: { show: showValues, color: style.valueColor || colors.text,
                    fontSize: valueSize, formatter: function (parameters) {
                        return formatValue(parameters.value[2], decimals, '');
                    } },
                emphasis: { itemStyle: { borderColor: style.axisColor || colors.text, borderWidth: 1 } },
                itemStyle: { borderWidth: 1, borderColor: style.gridColor || colors.background }
            }]
        };
    }

    function displayError(message) {
        if (chart) { chart.clear(); }
        chartElement.hidden = true;
        periodElement.hidden = true;
        classLegendElement.hidden = true;
        warningElement.hidden = true;
        emptyElement.hidden = true;
        errorElement.textContent = translate(message || loadError);
        errorElement.hidden = false;
    }

    function updateClassLegend(option, model) {
        // ECharts' piecewise legend would allocate one label per class in a narrow tile.
        var heatmap = model.heatmap || {};
        var style = heatmap.style || {};
        var values = Array.isArray(model.cells) ? model.cells.map(function (cell) { return Number(cell.value); })
            .filter(function (value) { return Number.isFinite(value); }) : [];
        if (option.visualMap.type !== 'piecewise' || style.showColorScale !== true || values.length === 0) {
            classLegendElement.hidden = true;
            return;
        }
        var decimals = Math.max(0, Math.min(6, Number(heatmap.decimals) || 0));
        classMinElement.textContent = formatValue(style.colorScaleRange.min, decimals, '°C');
        classMaxElement.textContent = formatValue(style.colorScaleRange.max, decimals, '°C');
        classSegmentsElement.innerHTML = option.visualMap.pieces.map(function (piece) {
            return '<span style="background-color:' + piece.color + '"></span>';
        }).join('');
        classLegendElement.style.color = option.visualMap.textStyle.color;
        classLegendElement.style.fontSize = Math.min(option.visualMap.textStyle.fontSize,
            Math.max(10, (Number(chartElement.clientWidth) || 240) / 24)) + 'px';
        classLegendElement.setAttribute('aria-label', classMinElement.textContent + ' – '
            + classMaxElement.textContent);
        classLegendElement.hidden = false;
    }

    function render(state) {
        if (retainOnTransientError(state, currentState, chart)) {
            return;
        }
        currentState = state;
        if (!state || state.status !== 'ready' || !state.chart) {
            displayError(state && state.error);
            return;
        }
        if (!window.echarts || typeof window.echarts.init !== 'function') {
            displayError('Apache ECharts could not be initialized.');
            return;
        }
        chartElement.hidden = false;
        errorElement.hidden = true;
        emptyElement.textContent = translate('No completed archive hours in the selected range.');
        emptyElement.hidden = Array.isArray(state.chart.cells) && state.chart.cells.length > 0;
        warningElement.textContent = translate('The selected range was truncated.');
        warningElement.hidden = state.chart.truncated !== true;
        var nextTheme = state.chart.theme || 'auto';
        if (chart && nextTheme !== theme) { chart.dispose(); chart = null; }
        if (!chart) {
            chart = window.echarts.init(chartElement, nextTheme === 'auto' ? null : nextTheme,
                { renderer: 'canvas' });
            theme = nextTheme;
        }
        updatePeriod(state.chart.heatmap || {},
            bootstrap.mode === 'symcon' && bootstrap.options.tileHeaderVisible !== false ? 58 : 8);
        var option = buildOption(state.chart, nextTheme);
        chart.setOption(option, true);
        updateClassLegend(option, state.chart);
    }

    function stateURL(offset) {
        var path = bootstrap.options && bootstrap.options.heatmapStatePath;
        if (!path || !/^\/hook\/SymconECharts\/state\//.test(path)) { throw new Error('Missing state path.'); }
        var location = window.location || {};
        var protocol = location.protocol;
        var host = location.host;
        if (protocol !== 'http:' && protocol !== 'https:') {
            var source = String(location.href || '').startsWith('data:')
                ? decodeURIComponent(location.href) : ((document.head && document.head.innerHTML) || '');
            var base = source.match(/<base\s+href=["'](https?:)\/\/([^\/"']+)/i);
            if (base) {
                protocol = base[1];
                host = base[2];
            } else {
                protocol = window.top.location.protocol;
                host = window.top.location.host;
            }
        }
        return protocol + '//' + host + path + '?offset=' + offset
            + (bootstrap.mode === 'symcon' ? '&output=tile' : '');
    }

    function navigate(offset) {
        if (navigating || offset < 0 || offset > (Number(bootstrap.options.heatmapMaxOffset) || 120)
            || !currentState || !currentState.chart) { return; }
        navigating = true;
        updatePeriod(currentState.chart.heatmap || {},
            bootstrap.mode === 'symcon' && bootstrap.options.tileHeaderVisible !== false ? 58 : 8);
        var range = currentState.chart.heatmap.range;
        try {
            fetch(stateURL(offset), { method: 'GET', cache: 'no-store', credentials: 'same-origin',
                headers: { Accept: 'application/json' } })
                .then(function (response) {
                    if (!response.ok) { throw new Error('Heatmap state request failed.'); }
                    return response.json();
                })
                .then(function (state) {
                    if (!state || state.status !== 'ready' || !state.chart
                        || state.chart.heatmap.range !== range
                        || state.chart.heatmap.windowOffset !== offset) {
                        throw new Error('Heatmap state response is invalid.');
                    }
                    render(state);
                })
                .catch(function () {
                    warningElement.textContent = translate(loadError);
                    warningElement.hidden = false;
                })
                .finally(function () {
                    navigating = false;
                    if (currentState && currentState.chart) {
                        updatePeriod(currentState.chart.heatmap || {},
                            bootstrap.mode === 'symcon' && bootstrap.options.tileHeaderVisible !== false ? 58 : 8);
                    }
                });
        } catch (error) {
            navigating = false;
            warningElement.textContent = translate(loadError);
            warningElement.hidden = false;
            updatePeriod(currentState.chart.heatmap || {},
                bootstrap.mode === 'symcon' && bootstrap.options.tileHeaderVisible !== false ? 58 : 8);
        }
    }

    previousElement.addEventListener('click', function () { navigate(currentOffset + 1); });
    nextElement.addEventListener('click', function () { navigate(currentOffset - 1); });
    todayElement.addEventListener('click', function () { navigate(0); });

    window.handleMessage = function (message) {
        var state = design.parseVisualizationState(message);
        if (!state) { return; }
        if (currentOffset > 0 && state.chart && state.chart.heatmap
            && state.chart.heatmap.windowOffset === 0 && currentState && currentState.chart
            && state.chart.heatmap.range === currentState.chart.heatmap.range) {
            navigate(currentOffset);
            return;
        }
        render(state);
    };
    if (window.ResizeObserver) {
        new ResizeObserver(function () {
            if (design.resizeChartIfNeeded(chart, chartElement)) { render(currentState); }
        }).observe(chartElement);
    } else {
        window.addEventListener('resize', function () {
            if (design.resizeChartIfNeeded(chart, chartElement)) { render(currentState); }
        });
    }
    window.addEventListener('beforeunload', function () { if (chart) { chart.dispose(); } });
    render(currentState);
}());
