(function () {
    'use strict';

    var bootstrap = window.SYMC_VISUALIZATION || {};
    var design = window.SYMC_ECHARTS_DESIGN;
    var rootElement = document.getElementById('echarts-radar-root');
    var chartElement = document.getElementById('echarts-radar-chart');
    var emptyElement = document.getElementById('echarts-radar-empty');
    var errorElement = document.getElementById('echarts-radar-error');
    var warningElement = document.getElementById('echarts-radar-warning');
    var chart = null;
    var currentState = bootstrap.state || null;
    var currentTheme = null;
    var groupSelection = window.SYMC_ECHARTS_SERIES_SELECTION.create();
    var selectionEnabled = null;
    var colorIndices = Object.create(null);
    var nextColorIndex = 0;
    var transientWarning = false;

    var tileHeaderInset = bootstrap.mode === 'symcon'
        && (!bootstrap.options || bootstrap.options.tileHeaderVisible !== false) ? 58 : 0;
    var chartBootstrap = tileHeaderInset ? { mode: 'symcon', options: { tileHeaderVisible: false } } : bootstrap;
    chartElement.style.top = tileHeaderInset + 'px';
    chartElement.style.height = tileHeaderInset ? 'calc(100% - ' + tileHeaderInset + 'px)' : '100%';

    function translate(text) { return (bootstrap.translations || {})[text] || text; }
    var retainOnTransientError = design.createTransientFailureRetainer(
        chartElement, warningElement, translate, 'The Radar values could not be loaded.'
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
    function buildOption(model, theme) {
        var colors = colorsFor(theme);
        var radar = model.radar || {};
        var style = radar.style || {};
        var axes = radar.axes || [];
        var groups = radar.data || [];
        var selectable = radar.enableGroupSelection === true;
        if (selectionEnabled !== selectable) {
            groupSelection = window.SYMC_ECHARTS_SERIES_SELECTION.create();
            selectionEnabled = selectable;
        }
        var selected = selectable ? groupSelection.reconcile(groups) : null;
        var showLegend = style.showLegend !== false || selectable;
        var expanded = selectable || groups.length > 8;
        var seriesColors = palette(theme).seriesColors || ['#5070DD', '#B6D634', '#505372', '#FF994D', '#0CA8DF', '#FFD10A', '#FB628B', '#785DB0'];
        groups.forEach(function (item) {
            if (!Object.prototype.hasOwnProperty.call(colorIndices, item.id)) { colorIndices[item.id] = nextColorIndex++; }
        });
        var compact = (chartElement.clientWidth || 1) < 430;
        var titleSize = design.fontSizePercent(style.titleFontSizePercent, 18);
        var titleLayout = design.chartTitleLayout(chartBootstrap, radar.title, titleSize);
        var axisSize = design.fontSizePercent(style.axisFontSizePercent, compact ? 10 : 12);
        var width = chartElement.clientWidth || 1;
        var height = chartElement.clientHeight || 1;
        var legendRows = !showLegend ? 0 : expanded ? 1 : Math.ceil(groups.length / (compact ? 2 : 4));
        var legendSize = design.fontSizePercent(style.legendFontSizePercent, 12);
        var legendTop = style.legendPosition === 'top';
        var legendSpace = legendRows * (legendSize + 10) + (selectable ? legendSize + 12 : 0);
        var warningVisible = radar.clippedCount > 0 || transientWarning;
        // Group controls must remain reachable even when a multi-line warning is displayed.
        warningElement.style.bottom = selectable && showLegend && !legendTop ? (legendSize + 26) + 'px' : '0.65rem';
        var warningSpace = selectable && warningVisible ? Math.max(32, warningElement.offsetHeight || 0) + 12 : radar.clippedCount > 0 ? 26 : 0;
        var bottom = 22 + (legendTop ? 0 : legendSpace) + warningSpace;
        var top = titleLayout.contentTop + axisSize * 2 + 20 + (legendTop ? legendSpace : 0);
        var axisWidth = Math.min(compact ? 90 : 150, Math.max(30, width / 4));
        var nameGap = style.axisNameGap == null ? 15 : style.axisNameGap;
        var radius = Math.max(1, Math.min(width / 2 - axisWidth - nameGap - 12, (height - top - bottom) / 2 - axisSize * 2));
        function rawValue(value, axis) {
            return Number(value).toLocaleString(bootstrap.language || undefined, { maximumFractionDigits: axis.Decimals })
                + (axis.Unit ? ' ' + axis.Unit : '');
        }
        return {
            color: seriesColors,
            backgroundColor: bootstrap.mode === 'ipsview' && bootstrap.options.adaptToBackground === true
                ? 'transparent' : colors.background,
            ...design.animationOptions(style, { enabled: true, initialDuration: 350, updateDuration: 500 }),
            aria: { enabled: true, ...(selectable ? { label: { description: String(radar.title || 'Radar') + '. '
                + (groups.some(function (item) { return selected[item.id] !== false; })
                    ? groups.filter(function (item) { return selected[item.id] !== false; }).map(function (item) {
                        return item.name + ': ' + axes.map(function (axis, index) {
                            return axis.Label + ': ' + rawValue((item.values || [])[index], axis);
                        }).join(', ');
                    }).join('; ')
                    : translate('No groups selected')) } } : {}) },
            title: { show: titleLayout.show, text: String(radar.title || ''), left: 'center', top: titleLayout.top,
                textStyle: { color: style.titleColor || colors.text, fontSize: titleSize, fontWeight: style.titleBold === false ? 'normal' : 'bold' } },
            legend: { show: showLegend, type: 'scroll',
                ...(selectable ? { selected: Object.assign({}, selected), scrollDataIndex: groupSelection.scrollDataIndex(),
                    selector: [{ type: 'all', title: translate('All') }, { type: 'inverse', title: translate('Inverse') }],
                    selectorPosition: 'start', selectorLabel: { color: style.legendColor || colors.text, fontSize: legendSize },
                    data: groups.map(function (item) {
                        return { name: item.id, tooltip: { formatter: function () { return escapeHTML(item.name); } } };
                    }),
                    formatter: function (id) { var item = groups.find(function (group) { return group.id === id; }); return item ? item.name : ''; }
                } : {}),
                ...(legendTop ? { top: titleLayout.contentTop + 4 } : { bottom: !selectable && radar.clippedCount > 0 ? 32 : 8 }),
                left: 12, right: 12, pageTextStyle: { color: style.legendColor || colors.text, fontSize: legendSize },
                pageIconColor: style.legendColor || colors.text,
                textStyle: { color: style.legendColor || style.axisColor || colors.text, fontSize: legendSize, width: compact ? 100 : 160,
                    overflow: 'truncate' }, tooltip: { show: true } },
            tooltip: { show: style.showTooltip !== false, trigger: 'item', confine: true,
                formatter: function (parameter) {
                    var item = parameter.data || {};
                    return escapeHTML(item.displayName || item.name || '') + axes.map(function (axis, index) {
                        return '<br>' + escapeHTML(axis.Label) + ': ' + escapeHTML(rawValue((item.rawValues || [])[index], axis));
                    }).join('');
                } },
            radar: { center: [width / 2, top + (height - top - bottom) / 2], radius: radius,
                shape: style.shape || 'polygon', startAngle: style.startAngle == null ? 90 : style.startAngle,
                clockwise: style.clockwise === true, splitNumber: style.splitNumber || 5,
                axisNameGap: nameGap,
                axisName: { show: style.showAxisNames !== false, color: style.axisColor || colors.text, fontSize: axisSize,
                    width: axisWidth, overflow: 'truncate', lineHeight: axisSize * 1.25, fontWeight: style.axisLabelBold === true ? 'bold' : 'normal' },
                axisLabel: { show: style.showAxisValues === true, formatter: '{value}%', color: style.axisColor || colors.muted },
                axisLine: { show: style.showAxisLines !== false, lineStyle: { color: style.gridColor || colors.border,
                    width: style.axisLineWidth == null ? 1 : style.axisLineWidth,
                    opacity: style.axisLineOpacityPercent == null ? 0.5 : style.axisLineOpacityPercent / 100 } },
                splitLine: { show: style.showSplitLines !== false, lineStyle: { color: style.gridColor || colors.border,
                    width: style.splitLineWidth == null ? 1 : style.splitLineWidth, type: style.splitLineType || 'solid',
                    opacity: style.splitLineOpacityPercent == null ? 0.4 : style.splitLineOpacityPercent / 100 } },
                splitArea: { show: style.showSplitArea !== false, areaStyle: {
                    color: [style.splitAreaColor1 || 'rgba(128,128,128,0.03)', style.splitAreaColor2 || 'rgba(128,128,128,0.09)'],
                    opacity: style.splitAreaOpacityPercent == null ? 1 : style.splitAreaOpacityPercent / 100 } },
                indicator: axes.map(function (axis) {
                    return { name: axis.Label + (style.showAxisRange === false ? '' : '\n' + rawValue(axis.Minimum, axis) + ' … ' + rawValue(axis.Maximum, axis)), min: 0, max: 100 };
                }) },
            series: [{ id: 'radar', type: 'radar', symbol: style.symbol || 'circle', symbolSize: style.symbolSize == null ? 4 : style.symbolSize,
                symbolRotate: Number(style.symbolRotate) || 0,
                labelLayout: { hideOverlap: style.hideOverlappingValueLabels !== false },
                itemStyle: { borderWidth: Number(style.symbolBorderWidth) || 0,
                    ...(style.symbolBorderColor ? { borderColor: style.symbolBorderColor } : {}),
                    opacity: style.symbolOpacityPercent == null ? 1 : style.symbolOpacityPercent / 100 },
                label: { show: style.showValues === true, opacity: 1, position: style.valueLabelPosition || 'top',
                    distance: style.valueLabelDistance == null ? 5 : style.valueLabelDistance,
                    color: style.valueColor || colors.text, fontSize: design.fontSizePercent(style.valueFontSizePercent, compact ? 10 : 12),
                    fontWeight: style.valueLabelBold === true ? 'bold' : 'normal',
                    formatter: function (parameter) {
                        var index = parameter.dimensionIndex;
                        return axes[index] ? rawValue((parameter.data.rawValues || [])[index], axes[index]) : '';
                    } },
                lineStyle: { width: style.lineWidth == null ? 2 : style.lineWidth,
                    type: style.lineType || 'solid',
                    opacity: style.lineOpacityPercent == null ? 1 : style.lineOpacityPercent / 100 },
                areaStyle: { opacity: style.areaOpacityPercent == null ? 0.25 : style.areaOpacityPercent / 100,
                    shadowBlur: Number(style.areaShadowBlur) || 0, shadowColor: style.areaShadowColor || 'rgba(0,0,0,0.35)' },
                emphasis: { focus: style.hoverFocus === 'none' ? 'none' : 'self', disabled: style.hoverFocus === 'none',
                    label: { show: style.showValues === true } },
                data: groups.map(function (item) {
                    var overrides = style.groupColors || {};
                    var color = Object.prototype.hasOwnProperty.call(overrides, item.name) ? overrides[item.name] : '';
                    if (!color && expanded) { color = seriesColors[colorIndices[item.id] % seriesColors.length]; }
                    return { id: item.id, name: selectable ? item.id : item.name, displayName: item.name, value: item.percentValues, rawValues: item.values,
                        ...(color ? { itemStyle: { color: color }, lineStyle: { color: color }, areaStyle: { color: color } } : {}) };
                }) }]
        };
    }
    function displayError(message) {
        if (chart) { chart.clear(); }
        chartElement.hidden = true;
        emptyElement.hidden = true;
        warningElement.hidden = true;
        errorElement.textContent = translate(message || 'The Radar values could not be loaded.');
        errorElement.hidden = false;
    }
    function render(state, keepWarning) {
        if (retainOnTransientError(state, currentState, chart)) {
            transientWarning = true;
            if (selectionEnabled && currentState && currentState.chart) {
                groupSelection.capture(chart);
                chart.setOption(buildOption(currentState.chart, currentTheme || 'auto'), true);
            }
            return;
        }
        if (!keepWarning) { transientWarning = false; }
        if (selectionEnabled) { groupSelection.capture(chart); }
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
        var wasHidden = chartElement.hidden;
        chartElement.hidden = false;
        errorElement.hidden = true;
        emptyElement.hidden = true;
        warningElement.hidden = !transientWarning;
        if ((model.radar || {}).clippedCount > 0 && !transientWarning) {
            warningElement.textContent = translate('Some values exceed their axis range and are visually limited.');
            warningElement.hidden = false;
        }
        rootElement.style.backgroundColor = bootstrap.mode === 'ipsview' && bootstrap.options.adaptToBackground === true
            ? 'transparent' : colorsFor(normalizeTheme(model.theme || 'auto')).background;
        var theme = normalizeTheme(model.theme || 'auto');
        if (chart && currentTheme !== theme) { chart.dispose(); chart = null; }
        if (!chart) {
            chart = window.echarts.init(chartElement, theme === 'auto' ? null : theme, { renderer: 'canvas' });
            currentTheme = theme;
            ['legendselectchanged', 'legendselectall', 'legendinverseselect'].forEach(function (eventName) {
                chart.on(eventName, function () {
                    if (selectionEnabled && currentState) { render(currentState, true); }
                });
            });
        } else if (wasHidden) {
            chart.resize();
        }
        chart.setOption(buildOption(model, theme), true);
        if ((model.radar || {}).enableGroupSelection === true) {
            var selected = chart.getOption().legend[0].selected || {};
            emptyElement.textContent = translate('No groups selected');
            emptyElement.hidden = (model.radar.data || []).some(function (item) { return selected[item.id] !== false; });
        }
    }
    window.handleMessage = function (message) {
        var state = design.parseVisualizationState(message);
        if (state) { render(state); }
    };
    function resize() {
        if (!design.resizeChartIfNeeded(chart, chartElement)) { return; }
        if (currentState && currentState.status === 'ready') {
            if (selectionEnabled) { groupSelection.capture(chart); }
            chart.setOption(buildOption(currentState.chart, currentTheme || 'auto'), true);
        }
    }
    if (window.ResizeObserver) {
        new ResizeObserver(resize).observe(chartElement);
    } else {
        window.addEventListener('resize', resize);
    }
    window.addEventListener('beforeunload', function () { if (chart) { chart.dispose(); } });
    render(currentState);
}());


