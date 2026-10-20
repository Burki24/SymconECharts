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
    var selection = window.SYMC_ECHARTS_SERIES_SELECTION.create();
    var navigation = window.SymconEChartsCategoryNavigation.create();
    var selectedItems = [];
    var pageItems = [];
    var pageWindow = { first: 0, last: 0, visibleCount: 0 };
    var paletteIndices = Object.create(null);
    var nextPaletteIndex = 0;
    var lastWidth = 0;
    var lastHeight = 0;
    var controls = window.SYMC_ECHARTS_SOURCE_CONTROLS.create({
        prefix: 'echarts-funnel', translate: translate,
        change: function (id, visible) { selection.select(id, visible); render(currentState); },
        all: function () { selection.selectAll(true); render(currentState); },
        inverse: function () { selection.invert(); render(currentState); },
        move: function (direction) {
            navigation.rememberWindow(selectedItems.map(function (item) { return item.id; }),
                pageWindow.first + direction * pageWindow.visibleCount);
            render(currentState);
        }
    });

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
        // Width always follows the complete validated model, never the current view.
        var maximum = Math.max.apply(null, items.map(function (item) { return item.value; }));
        var enabled = funnel.enableSourceSelection === true;
        var projected = enabled || items.length > 16;
        var selected = selection.reconcile(items.map(function (item) {
            return { id: item.id, initiallyVisible: !enabled || item.initiallyVisible !== false };
        }));
        Object.keys(paletteIndices).forEach(function (id) {
            if (!items.some(function (item) { return item.id === id; })) { delete paletteIndices[id]; }
        });
        items.forEach(function (item) {
            if (!Object.prototype.hasOwnProperty.call(paletteIndices, item.id)) {
                paletteIndices[item.id] = nextPaletteIndex++;
            }
        });
        var width = chartElement.clientWidth || 1;
        var height = chartElement.clientHeight || 1;
        var horizontal = style.orientation === 'horizontal';
        var outside = style.labelPosition === 'outside';
        var outsideStart = outside && style.outsideLabelSide === 'start';
        var hideOverlap = style.hideOverlappingLabels !== false;
        var fontFamily = style.fontFamily || 'Arial, sans-serif';
        var font = design.fontSizePercent(style.labelFontSizePercent, width < 430 ? 11 : 13);
        var titleSize = design.fontSizePercent(style.titleFontSizePercent, 18);
        var titleLayout = design.chartTitleLayout(chartBootstrap, funnel.title, titleSize);
        var top = titleLayout.contentTop + 10;
        var lineLength = Math.min(style.labelLineLength == null ? 10 : style.labelLineLength, Math.min(width, height) * 0.2);
        var reserve = horizontal ? Math.min(height * 0.35, font * 3 + 26 + lineLength - 10)
            : Math.min(width * 0.42, Math.max(80, font * 13) + lineLength - 10);
        var left = outsideStart && !horizontal ? reserve : 18;
        var right = outside && !outsideStart && !horizontal ? reserve : 18;
        var bottom = outside && !outsideStart && horizontal ? reserve : 18;
        if (projected) { bottom += width < 260 ? 96 : 48; }
        if (outsideStart && horizontal) { top += reserve; }
        top = Math.max(0, Math.min(top, height - 1));
        bottom = Math.min(bottom, Math.max(0, height - top - 1));
        var extent = Math.max(1, horizontal ? width - left - right : height - top - bottom);
        var seriesColors = colors.seriesColors || [colors.accent];
        var ordered = items.map(function (item, index) {
            var paletteIndex = projected ? paletteIndices[item.id] : index;
            return { ...item, originalIndex: index, stageColor: item.color || seriesColors[paletteIndex % seriesColors.length] };
        });
        if (style.sortOrder === 'ascending' || style.sortOrder === 'descending') {
            ordered.sort(function (a, b) {
                var direction = style.sortOrder === 'ascending' ? 1 : -1;
                // Comparisons, not subtraction, keep very large finite values safe.
                return (a.value < b.value ? -direction : a.value > b.value ? direction : 0) || a.originalIndex - b.originalIndex;
            });
        }
        selectedItems = ordered.filter(function (item) { return !enabled || selected[item.id] !== false; });
        var requestedGap = style.gap == null ? 3 : style.gap;
        var minimumExtent = horizontal ? Math.max(50, font * 7 + requestedGap)
            : Math.max(18, font * (style.showValues === false ? 1.7 : 2.8) + requestedGap);
        var capacity = projected ? Math.max(1, Math.min(16, Math.floor(extent / minimumExtent))) : selectedItems.length;
        pageWindow = navigation.window(selectedItems.map(function (item) { return item.id; }), capacity);
        pageItems = selectedItems.slice(pageWindow.first, pageWindow.last + 1);
        navigation.rememberWindow(selectedItems.map(function (item) { return item.id; }), pageWindow.first);
        controls.update({ items: items, selected: selected, enabled: enabled, visible: projected,
            window: pageWindow, total: selectedItems.length });
        // Clamp design gaps to leave every visible stage with a nonnegative extent.
        var gap = Math.min(requestedGap, extent / Math.max(1, pageItems.length * 2));
        var align = style.alignment === 'start' ? (horizontal ? 'top' : 'left')
            : style.alignment === 'end' ? (horizontal ? 'bottom' : 'right') : 'center';
        function format(item) {
            return Number(item.value).toLocaleString(bootstrap.language || undefined, { maximumFractionDigits: item.decimals == null ? 2 : item.decimals })
                + (item.unit ? ' ' + item.unit : '');
        }
        var stageExtent = Math.max(1, (extent - gap * (pageItems.length - 1)) / Math.max(1, pageItems.length));
        var minimumSize = style.minSizePercent == null ? 0 : style.minSizePercent;
        var maximumSize = style.maxSizePercent == null ? 100 : style.maxSizePercent;
        return {
            ...design.animationOptions(style, { enabled: true, initialDuration: 350, updateDuration: 500 }),
            backgroundColor: 'transparent', color: seriesColors,
            textStyle: { color: colors.text, fontFamily: fontFamily }, aria: { enabled: true },
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
                min: 0, max: maximum, minSize: minimumSize + '%', maxSize: maximumSize + '%', funnelAlign: align,
                left: left, right: right, top: top, bottom: bottom, gap: gap,
                label: { show: style.showLabels !== false, position: outside ? (horizontal ? (outsideStart ? 'top' : 'bottom') : (outsideStart ? 'left' : 'right')) : 'inside',
                    fontFamily: fontFamily, fontSize: font,
                    fontWeight: style.labelBold === true ? 'bold' : 'normal',
                    color: style.labelColor || (outside ? colors.text : 'inherit'), overflow: 'truncate',
                    formatter: function (parameter) {
                        return parameter.data.name + (style.showValues === false ? '' : '\n' + format(parameter.data));
                    } },
                labelLine: { show: outside && style.showLabels !== false && style.showLabelLines !== false, length: lineLength,
                    lineStyle: { color: style.labelLineColor || undefined, width: style.labelLineWidth == null ? 1 : style.labelLineWidth,
                        type: style.labelLineType || 'solid', opacity: style.labelLineOpacityPercent == null ? 1 : style.labelLineOpacityPercent / 100 } },
                labelLayout: { hideOverlap: hideOverlap },
                itemStyle: { borderWidth: style.borderWidth == null ? 1 : style.borderWidth,
                    borderType: style.borderType || 'solid', shadowBlur: Number(style.shadowBlur) || 0,
                    shadowColor: style.shadowColor || 'rgba(0,0,0,0.35)',
                    shadowOffsetX: Number(style.shadowOffsetX) || 0, shadowOffsetY: Number(style.shadowOffsetY) || 0,
                    borderColor: style.borderColor || colors.background,
                    opacity: style.opacityPercent == null ? 0.9 : style.opacityPercent / 100 },
                emphasis: { focus: style.hoverFocus === 'none' ? 'none' : 'self', disabled: style.hoverFocus === 'none',
                    label: { show: style.showLabels !== false } },
                blur: { itemStyle: style.blurOpacityPercent == null || style.blurOpacityPercent < 0 ? {} : { opacity: style.blurOpacityPercent / 100 } },
                data: pageItems.map(function (item, index) {
                    var next = pageItems[index + 1];
                    var meanWidth = (minimumSize + (maximum > 0 ? item.value / maximum + (next ? next.value / maximum : 0) : 0) / 2 * (maximumSize - minimumSize)) / 100;
                    var labelWidth = outside ? (horizontal ? stageExtent - 6 : reserve - lineLength - 12)
                        : horizontal ? stageExtent - 8 : (width - left - right) * meanWidth - 8;
                    if (style.labelMaxWidth > 0) { labelWidth = Math.min(labelWidth, style.labelMaxWidth); }
                    return { ...item, name: item.label, itemStyle: { color: item.stageColor },
                        label: { width: Math.max(1, labelWidth),
                            color: style.labelColor || (outside ? colors.text : design.readableTextColor(item.stageColor)),
                            show: style.showLabels !== false && (!hideOverlap || outside || horizontal || stageExtent >= font * (style.showValues === false ? 1 : 2)) } };
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
        controls.hide();
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
        var theme = normalizeTheme(model.theme || 'auto');
        // Restore measurable geometry before projecting an empty/zero selection.
        // display:none otherwise reports 0px and would leave one tiny stage until a resize.
        var wasHidden = chartElement.hidden;
        chartElement.hidden = false;
        var option = buildOption(model, theme);
        rootElement.style.backgroundColor = bootstrap.mode === 'ipsview' && bootstrap.options.adaptToBackground === true
            ? 'transparent' : colorsFor(theme).background;
        if (!selectedItems.some(function (item) { return item.value > 0; })) {
            if (chart) { chart.clear(); }
            chartElement.hidden = true;
            errorElement.hidden = true;
            warningElement.hidden = true;
            emptyElement.textContent = translate(!selectedItems.length ? 'No sources selected'
                : (model.funnel.data || []).some(function (item) { return item.value > 0; })
                    ? 'All selected stage values are zero.' : 'All stage values are zero.');
            emptyElement.hidden = false;
            return;
        }
        errorElement.hidden = true;
        emptyElement.hidden = true;
        warningElement.hidden = true;
        if (chart && currentTheme !== theme) { chart.dispose(); chart = null; }
        if (!chart) {
            chart = window.echarts.init(chartElement, theme === 'auto' ? null : theme, { renderer: 'canvas' });
            currentTheme = theme;
        } else if (wasHidden) {
            chart.resize();
        }
        chart.setOption(option, true);

    }
    window.handleMessage = function (message) {
        var state = design.parseVisualizationState(message);
        if (state) { render(state); }
    };
    function resizeChart() {
        var width = chartElement.clientWidth;
        var height = chartElement.clientHeight;
        if (width === lastWidth && height === lastHeight) { return; }
        lastWidth = width; lastHeight = height;
        if (chart) { chart.resize(); }
        if (currentState && currentState.status === 'ready') { render(currentState); }
    }
    if (window.ResizeObserver) { new ResizeObserver(resizeChart).observe(chartElement); }
    else { window.addEventListener('resize', resizeChart); }
    window.addEventListener('beforeunload', function () { if (chart) { chart.dispose(); } });
    render(currentState);
}());
