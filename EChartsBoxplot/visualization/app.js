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

    var tileHeaderInset = bootstrap.mode === 'symcon'
        && (!bootstrap.options || bootstrap.options.tileHeaderVisible !== false) ? 58 : 0;
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
    function buildOption(model, theme) {
        var colors = colorsFor(theme);
        var boxplot = model.boxplot || {};
        var style = boxplot.style || {};
        var items = boxplot.data || [];
        var width = chartElement.clientWidth || 1;
        var height = chartElement.clientHeight || 1;
        var horizontal = style.orientation === 'horizontal';
        var compact = width < 430;
        var font = design.fontSizePercent(style.axisFontSizePercent, compact ? 10 : 12);
        var titleSize = design.fontSizePercent(style.titleFontSizePercent, 18);
        var titleLayout = design.chartTitleLayout(chartBootstrap, boxplot.title, titleSize);
        var top = titleLayout.contentTop + (boxplot.unit ? font + 14 : 10);
        var left = horizontal ? Math.min(width * 0.35, Math.max(60, font * 12)) : Math.min(width * 0.25, Math.max(45, font * 5));
        var bottom = horizontal ? font * 3 + 24 : font * 2 + 22;
        var slot = (horizontal ? height - top - bottom : width - left - 22) / Math.max(1, items.length);
        var boxWidth = Math.max(1, Math.min(style.boxWidth == null ? 40 : style.boxWidth, slot * 0.65));
        var labels = Object.create(null);
        items.forEach(function (item) { labels[item.id] = item.label; });
        function format(value, item) {
            return Number(value).toLocaleString(bootstrap.language || undefined, { maximumFractionDigits: item.decimals == null ? 2 : item.decimals })
                + (item.unit ? ' ' + item.unit : '');
        }
        var category = { type: 'category', data: items.map(function (item) { return item.id; }),
            axisLabel: { show: style.showAxisLabels !== false, color: style.axisColor || colors.text,
                fontSize: font, interval: 0, hideOverlap: true, overflow: 'truncate',
                width: Math.max(12, horizontal ? left - 15 : slot - 8),
                formatter: function (id) { return labels[id] || ''; } },
            axisTick: { alignWithLabel: true }, axisLine: { lineStyle: { color: colors.border } } };
        var value = { type: 'value', scale: true, name: boxplot.unit || '', nameLocation: horizontal ? 'middle' : 'end',
            nameGap: horizontal ? font + 18 : 15,
            nameTextStyle: { color: style.axisColor || colors.text, fontSize: font },
            axisLabel: { show: style.showAxisLabels !== false, color: style.axisColor || colors.text, fontSize: font, hideOverlap: true },
            splitLine: { show: style.showGrid !== false, lineStyle: { color: colors.border, opacity: 0.25 } } };
        return {
            ...design.animationOptions(style, { enabled: true, initialDuration: 350, updateDuration: 500 }),
            backgroundColor: 'transparent', color: colors.seriesColors || [colors.accent],
            textStyle: { color: colors.text, fontFamily: 'Arial, sans-serif' }, aria: { enabled: true },
            title: { show: titleLayout.show, text: boxplot.title || '', top: titleLayout.top, left: 'center',
                textStyle: { color: style.titleColor || colors.text, fontSize: titleSize, overflow: 'truncate', width: Math.max(1, width - 36) } },
            grid: { top: top, left: left, right: horizontal ? Math.max(30, font * 3) : 22, bottom: bottom, containLabel: false },
            xAxis: horizontal ? value : category, yAxis: horizontal ? category : value,
            tooltip: { show: style.showTooltip !== false, trigger: 'item', confine: true,
                extraCssText: 'max-width:' + Math.max(120, width - 24) + 'px;white-space:normal;overflow-wrap:anywhere;',
                formatter: function (parameter) {
                    var item = items[parameter.dataIndex];
                    if (!item || !item.statistics) { return ''; }
                    var names = ['Minimum', 'Q1', 'Median', 'Q3', 'Maximum'];
                    return escapeHTML(item.label) + '<br>' + item.statistics.map(function (value, index) {
                        return escapeHTML(translate(names[index]) + ': ' + format(value, item));
                    }).join('<br>') + '<br>' + escapeHTML(translate('Hourly samples') + ': ' + item.count)
                        + '<br>' + escapeHTML(new Date(item.firstTimestamp * 1000).toLocaleString(bootstrap.language || undefined))
                        + ' – ' + escapeHTML(new Date(item.lastTimestamp * 1000).toLocaleString(bootstrap.language || undefined))
                        + '<br>' + escapeHTML(translate('Completed hourly means · whiskers: minimum and maximum'));
                } },
            series: [{ type: 'boxplot', boxWidth: [1, boxWidth],
                itemStyle: { borderWidth: style.borderWidth == null ? 2 : style.borderWidth,
                    borderColor: style.borderColor || colors.border, opacity: style.boxOpacityPercent == null ? 0.7 : style.boxOpacityPercent / 100 },
                emphasis: { focus: 'self' },
                data: items.map(function (item, index) {
                    return { id: item.id, name: item.label, value: item.statistics || [null, null, null, null, null],
                        itemStyle: { color: item.color || (colors.seriesColors || [colors.accent])[index % (colors.seriesColors || [colors.accent]).length] } };
                }) }]
        };
    }
    function displayError(message) {
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
