(function () {
    'use strict';

    var bootstrap = window.SYMC_VISUALIZATION || {};
    var design = window.SYMC_ECHARTS_DESIGN;
    var rootElement = document.getElementById('echarts-funnel-root');
    var chartElement = document.getElementById('echarts-funnel-chart');
    var emptyElement = document.getElementById('echarts-funnel-empty');
    var errorElement = document.getElementById('echarts-funnel-error');
    var warningElement = document.getElementById('echarts-funnel-warning');
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
        chartElement, warningElement, translate, 'The Funnel values could not be loaded.'
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
        var funnel = model.funnel || {};
        var style = funnel.style || {};
        var items = funnel.data || [];
        var width = chartElement.clientWidth || 1;
        var height = chartElement.clientHeight || 1;
        var horizontal = style.orientation === 'horizontal';
        var outside = style.labelPosition === 'outside';
        var font = design.fontSizePercent(style.labelFontSizePercent, width < 430 ? 11 : 13);
        var titleSize = design.fontSizePercent(style.titleFontSizePercent, 18);
        var titleLayout = design.chartTitleLayout(chartBootstrap, funnel.title, titleSize);
        var top = titleLayout.contentTop + 10;
        var left = 18;
        var right = outside && !horizontal ? Math.min(width * 0.42, Math.max(80, font * 13)) : 18;
        var bottom = outside && horizontal ? Math.min(height * 0.35, font * 3 + 26) : 18;
        top = Math.max(0, Math.min(top, height - 1));
        bottom = Math.min(bottom, Math.max(0, height - top - 1));
        // Clamp design gaps to leave every stage with a nonnegative extent in small tiles.
        var extent = Math.max(1, horizontal ? width - left - right : height - top - bottom);
        var gap = Math.min(style.gap == null ? 3 : style.gap, extent / Math.max(1, items.length * 2));
        var maximum = Math.max.apply(null, items.map(function (item) { return item.value; }));
        var seriesColors = colors.seriesColors || [colors.accent];
        var ordered = items.map(function (item, index) {
            return { ...item, originalIndex: index, stageColor: item.color || seriesColors[index % seriesColors.length] };
        });
        if (style.sortOrder === 'ascending' || style.sortOrder === 'descending') {
            ordered.sort(function (a, b) {
                var direction = style.sortOrder === 'ascending' ? 1 : -1;
                // Comparisons, not subtraction, keep very large finite values safe.
                return (a.value < b.value ? -direction : a.value > b.value ? direction : 0) || a.originalIndex - b.originalIndex;
            });
        }
        var align = style.alignment === 'start' ? (horizontal ? 'top' : 'left')
            : style.alignment === 'end' ? (horizontal ? 'bottom' : 'right') : 'center';
        function format(item) {
            return Number(item.value).toLocaleString(bootstrap.language || undefined, { maximumFractionDigits: item.decimals == null ? 2 : item.decimals })
                + (item.unit ? ' ' + item.unit : '');
        }
        var stageExtent = Math.max(1, (extent - gap * (items.length - 1)) / items.length);
        return {
            ...design.animationOptions(style, { enabled: true, initialDuration: 350, updateDuration: 500 }),
            backgroundColor: 'transparent', color: seriesColors,
            textStyle: { color: colors.text, fontFamily: 'Arial, sans-serif' }, aria: { enabled: true },
            title: { show: titleLayout.show, text: funnel.title || '', top: titleLayout.top, left: 'center',
                textStyle: { color: style.titleColor || colors.text, fontSize: titleSize, fontWeight: style.titleBold === false ? 'normal' : 'bold',
                    overflow: 'truncate', width: Math.max(1, width - 36) } },
            tooltip: { show: style.showTooltip !== false, trigger: 'item', confine: true,
                extraCssText: 'max-width:' + Math.max(120, width - 24) + 'px;white-space:normal;overflow-wrap:anywhere;',
                formatter: function (parameter) {
                    var item = parameter.data;
                    if (!item) { return ''; }
                    return escapeHTML(item.name) + '<br>' + escapeHTML(format(item));
                } },
            series: [{ type: 'funnel', orient: horizontal ? 'horizontal' : 'vertical', sort: 'none',
                min: 0, max: maximum, minSize: '0%', maxSize: '100%', funnelAlign: align,
                left: left, right: right, top: top, bottom: bottom, gap: gap,
                label: { show: style.showLabels !== false, position: outside ? (horizontal ? 'bottom' : 'right') : 'inside',
                    fontFamily: 'Arial, sans-serif', fontSize: font,
                    fontWeight: style.labelBold === true ? 'bold' : 'normal',
                    color: style.labelColor || (outside ? colors.text : 'inherit'), overflow: 'truncate',
                    formatter: function (parameter) {
                        return parameter.data.name + (style.showValues === false ? '' : '\n' + format(parameter.data));
                    } },
                labelLine: { show: outside && style.showLabels !== false, length: 10 },
                labelLayout: { hideOverlap: true },
                itemStyle: { borderWidth: style.borderWidth == null ? 1 : style.borderWidth,
                    borderColor: style.borderColor || colors.background,
                    opacity: style.opacityPercent == null ? 0.9 : style.opacityPercent / 100 },
                emphasis: { focus: 'self', label: { show: style.showLabels !== false } },
                data: ordered.map(function (item, index) {
                    var next = ordered[index + 1];
                    var meanWidth = (item.value / maximum + (next ? next.value / maximum : 0)) / 2;
                    var labelWidth = outside ? (horizontal ? stageExtent - 6 : right - 22)
                        : horizontal ? stageExtent - 8 : (width - left - right) * meanWidth - 8;
                    return { ...item, name: item.label, itemStyle: { color: item.stageColor },
                        label: { width: Math.max(1, labelWidth),
                            color: style.labelColor || (outside ? colors.text : design.readableTextColor(item.stageColor)),
                            show: style.showLabels !== false && (outside || horizontal || stageExtent >= font * (style.showValues === false ? 1 : 2)) } };
                }) }]
        };
    }
    function displayError(message) {
        if (chart) { chart.clear(); }
        chartElement.hidden = true;
        emptyElement.hidden = true;
        warningElement.hidden = true;
        errorElement.textContent = translate(message || 'The Funnel values could not be loaded.');
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
        if (!(model.funnel.data || []).some(function (item) { return item.value > 0; })) {
            if (chart) { chart.clear(); }
            chartElement.hidden = true;
            errorElement.hidden = true;
            warningElement.hidden = true;
            emptyElement.textContent = translate('All stage values are zero.');
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
