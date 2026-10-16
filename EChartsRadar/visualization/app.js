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
        var compact = (chartElement.clientWidth || 1) < 430;
        var titleSize = design.fontSizePercent(style.titleFontSizePercent, 18);
        var titleLayout = design.chartTitleLayout(chartBootstrap, radar.title, titleSize);
        var axisSize = design.fontSizePercent(style.axisFontSizePercent, compact ? 10 : 12);
        var width = chartElement.clientWidth || 1;
        var height = chartElement.clientHeight || 1;
        var legendRows = style.showLegend === false ? 0 : Math.ceil((radar.data || []).length / (compact ? 2 : 4));
        var bottom = 22 + legendRows * 22 + (radar.clippedCount > 0 ? 26 : 0);
        var top = titleLayout.contentTop + axisSize * 2 + 20;
        var axisWidth = Math.min(compact ? 90 : 150, Math.max(30, width / 4));
        var nameGap = style.axisNameGap == null ? 15 : style.axisNameGap;
        var radius = Math.max(1, Math.min(width / 2 - axisWidth - nameGap - 12, (height - top - bottom) / 2 - axisSize * 2));
        function rawValue(value, axis) {
            return Number(value).toLocaleString(bootstrap.language || undefined, { maximumFractionDigits: axis.Decimals })
                + (axis.Unit ? ' ' + axis.Unit : '');
        }
        return {
            color: palette(theme).seriesColors || ['#5070DD', '#B6D634', '#505372', '#FF994D', '#0CA8DF', '#FFD10A', '#FB628B', '#785DB0'],
            backgroundColor: bootstrap.mode === 'ipsview' && bootstrap.options.adaptToBackground === true
                ? 'transparent' : colors.background,
            ...design.animationOptions(style, { enabled: true, initialDuration: 350, updateDuration: 500 }),
            aria: { enabled: true },
            title: { show: titleLayout.show, text: String(radar.title || ''), left: 'center', top: titleLayout.top,
                textStyle: { color: style.titleColor || colors.text, fontSize: titleSize } },
            legend: { show: style.showLegend !== false, type: 'scroll', bottom: radar.clippedCount > 0 ? 32 : 8,
                left: 12, right: 12, textStyle: { color: style.axisColor || colors.text, width: compact ? 100 : 160,
                    overflow: 'truncate' }, tooltip: { show: true } },
            tooltip: { show: style.showTooltip !== false, trigger: 'item', confine: true,
                formatter: function (parameter) {
                    var item = parameter.data || {};
                    return escapeHTML(item.name || '') + axes.map(function (axis, index) {
                        return '<br>' + escapeHTML(axis.Label) + ': ' + escapeHTML(rawValue((item.rawValues || [])[index], axis));
                    }).join('');
                } },
            radar: { center: [width / 2, top + (height - top - bottom) / 2], radius: radius,
                shape: style.shape || 'polygon', startAngle: style.startAngle == null ? 90 : style.startAngle,
                clockwise: style.clockwise === true, splitNumber: style.splitNumber || 5,
                axisNameGap: nameGap,
                axisName: { show: style.showAxisNames !== false, color: style.axisColor || colors.text, fontSize: axisSize,
                    width: axisWidth, overflow: 'truncate', lineHeight: axisSize * 1.25 },
                axisLabel: { show: style.showAxisValues === true, formatter: '{value}%', color: style.axisColor || colors.muted },
                axisLine: { lineStyle: { color: style.gridColor || colors.border, opacity: 0.5 } },
                splitLine: { lineStyle: { color: style.gridColor || colors.border, opacity: 0.4 } },
                splitArea: { show: style.showSplitArea !== false, areaStyle: { color: ['rgba(128,128,128,0.03)', 'rgba(128,128,128,0.09)'] } },
                indicator: axes.map(function (axis) {
                    return { name: axis.Label + '\n' + rawValue(axis.Minimum, axis) + ' … ' + rawValue(axis.Maximum, axis), min: 0, max: 100 };
                }) },
            series: [{ id: 'radar', type: 'radar', symbol: 'circle', symbolSize: style.symbolSize == null ? 4 : style.symbolSize,
                lineStyle: { width: style.lineWidth == null ? 2 : style.lineWidth,
                    opacity: style.lineOpacityPercent == null ? 1 : style.lineOpacityPercent / 100 },
                areaStyle: { opacity: style.areaOpacityPercent == null ? 0.25 : style.areaOpacityPercent / 100 },
                emphasis: { focus: 'self' },
                data: (radar.data || []).map(function (item) {
                    return { id: item.id, name: item.name, value: item.percentValues, rawValues: item.values };
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
        if ((model.radar || {}).clippedCount > 0) {
            warningElement.textContent = translate('Some values exceed their axis range and are visually limited.');
            warningElement.hidden = false;
        }
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


