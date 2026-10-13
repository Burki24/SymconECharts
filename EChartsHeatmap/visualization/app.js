(function () {
    'use strict';

    var bootstrap = window.SYMC_VISUALIZATION || {};
    var design = window.SYMC_ECHARTS_DESIGN;
    var chartElement = document.getElementById('echarts-heatmap-chart');
    var warningElement = document.getElementById('echarts-heatmap-warning');
    var emptyElement = document.getElementById('echarts-heatmap-empty');
    var errorElement = document.getElementById('echarts-heatmap-error');
    var chart = null;
    var theme = null;
    var currentState = bootstrap.state || null;
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
        var top = headerInset + (heatmap.title ? (compact ? 32 : 42) : 14);
        var bottom = style.showColorScale === true ? (compact ? 62 : 76) : 34;
        var plotWidth = width - 60;
        var plotHeight = height - top - bottom;
        var showValues = style.showValues === true && plotWidth / 24 >= valueSize * 2.8
            && plotHeight / 7 >= valueSize * 1.5;
        var days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'].map(translate);
        var low = style.lowColor || colors.track || colors.background;
        var high = style.highColor || colors.accent;
        var decimals = Math.max(0, Math.min(6, Number(heatmap.decimals) || 0));

        return {
            backgroundColor: bootstrap.mode === 'ipsview' && bootstrap.options.adaptToBackground === true
                ? 'transparent' : colors.background,
            ...design.animationOptions(style, { enabled: true, initialDuration: 350, updateDuration: 500 }),
            aria: { enabled: true, description: String(heatmap.title || translate('Heatmap'))
                + ': ' + translate('24 hours by seven weekdays.') },
            title: {
                show: Boolean(heatmap.title), text: String(heatmap.title || ''), left: 'center', top: headerInset,
                textStyle: { color: style.titleColor || colors.text, fontSize: titleSize }
            },
            grid: { left: compact ? 38 : 48, right: compact ? 10 : 18, top: top, bottom: bottom },
            tooltip: {
                trigger: 'item',
                formatter: function (parameters) {
                    var data = parameters.data || {};
                    var value = data.value || [];
                    return escapeHTML(days[value[1]] || '') + ', ' + escapeHTML(String(value[0]).padStart(2, '0'))
                        + ':00<br>' + escapeHTML(formatValue(value[2], decimals, heatmap.unit || ''))
                        + '<br>' + escapeHTML(String(data.count || 1)) + ' ' + escapeHTML(translate('Hourly intervals'));
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
            visualMap: {
                type: 'continuous', show: style.showColorScale === true && values.length > 0,
                min: minimum, max: maximum,
                text: [formatValue(displayedMaximum, decimals, heatmap.unit || ''),
                    formatValue(minimum, decimals, heatmap.unit || '')],
                dimension: 2, calculable: false, orient: 'horizontal', left: 'center', bottom: compact ? 5 : 12,
                itemWidth: compact ? 10 : 12, itemHeight: Math.max(80, Math.min(220, width * 0.32)),
                textGap: compact ? 5 : 8,
                textStyle: { color: style.axisColor || colors.text, fontSize: axisSize },
                inRange: { color: [low, high] }
            },
            series: [{
                type: 'heatmap', data: cells.map(function (cell) {
                    return { value: [Number(cell.hour), Number(cell.weekday), Number(cell.value)],
                        count: Number(cell.count) };
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
        warningElement.hidden = true;
        emptyElement.hidden = true;
        errorElement.textContent = translate(message || loadError);
        errorElement.hidden = false;
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
        chart.setOption(buildOption(state.chart, nextTheme), true);
    }

    window.handleMessage = function (message) {
        var state = design.parseVisualizationState(message);
        if (state) { render(state); }
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
