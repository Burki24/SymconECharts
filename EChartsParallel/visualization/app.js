(function () {
    'use strict';

    var bootstrap = window.SYMC_VISUALIZATION || {};
    var design = window.SYMC_ECHARTS_DESIGN;
    var chartElement = document.getElementById('echarts-parallel-chart');
    var viewport = document.getElementById('echarts-parallel-viewport');
    var navigation = document.getElementById('echarts-parallel-navigation');
    var positionLabel = document.getElementById('echarts-parallel-position');
    var previousButton = document.getElementById('echarts-parallel-previous');
    var nextButton = document.getElementById('echarts-parallel-next');
    var axisPosition = window.SymconEChartsCategoryNavigation.create();
    var geometry = null;
    var anchorFraction = 0;
    var lastScrollLeft = 0;
    var emptyElement = document.getElementById('echarts-parallel-empty');
    var errorElement = document.getElementById('echarts-parallel-error');
    var warningElement = document.getElementById('echarts-parallel-warning');
    var chart = null;
    var currentState = bootstrap.state || null;
    var currentTheme = null;

    function translate(text) { return (bootstrap.translations || {})[text] || text; }
    var retainOnTransientError = design.createTransientFailureRetainer(
        chartElement, warningElement, translate, 'The Parallel values could not be loaded.'
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
    function formatTime(timestamp, aggregation) {
        var value = new Date(Number(timestamp) * 1000);
        return aggregation === 'day' ? value.toLocaleDateString() : value.toLocaleString();
    }
    function rememberPosition() {
        if (!geometry || !geometry.scrollable) { return; }
        var index = Math.max(0, viewport.scrollLeft / geometry.spacing);
        axisPosition.rememberWindow(geometry.keys, Math.floor(index));
        anchorFraction = index - Math.floor(index);
    }
    function updatePosition() {
        if (!geometry) { return; }
        var left = Math.max(0, Number(viewport.scrollLeft) || 0);
        var first = Math.max(0, Math.ceil((left - geometry.margin) / geometry.spacing));
        var last = Math.min(geometry.keys.length - 1,
            Math.floor((left + geometry.viewportWidth - geometry.margin) / geometry.spacing));
        positionLabel.textContent = translate('Axes {first}–{last} of {total}')
            .replace('{first}', first + 1).replace('{last}', Math.max(first, last) + 1).replace('{total}', geometry.keys.length);
        previousButton.disabled = left < 1;
        nextButton.disabled = left >= geometry.width - geometry.viewportWidth - 1;
    }
    function layoutViewport(model) {
        if (geometry && viewport.clientWidth === geometry.viewportWidth && viewport.scrollLeft !== lastScrollLeft) {
            rememberPosition();
        }
        var axes = model.parallel.axes;
        viewport.style.height = '100%';
        var width = Math.max(1, Number(viewport.clientWidth) || 1);
        var compact = width < 420 || viewport.clientHeight < 280;
        var size = design.fontSizePercent((model.parallel.style || {}).axisFontSizePercent, compact ? 10 : 12);
        var longest = axes.reduce(function (length, axis) {
            return Math.max(length, String(axis.label || '').length + (axis.unit ? String(axis.unit).length + 3 : 0));
        }, 0);
        var gap = Math.max(96, Math.min(280, Math.min(32, longest) * size * 0.6 + 24));
        var margin = Math.max(compact ? 28 : 64, Math.min(140, gap / 2));
        if (axes.length <= 2) { margin = Math.min(margin, width / 4); }
        var fullWidth = axes.length <= 2 ? width
            : Math.max(width, Math.ceil(2 * margin + Math.max(1, axes.length - 1) * gap));
        var scrolling = fullWidth > width;
        viewport.style.height = scrolling ? 'calc(100% - 32px)' : '100%';
        viewport.setAttribute('aria-label', translate('Parallel axes'));
        viewport.setAttribute('aria-keyshortcuts', 'ArrowLeft ArrowRight PageUp PageDown Home End');
        navigation.hidden = !scrolling;
        navigation.setAttribute('aria-label', translate('Parallel axes'));
        previousButton.setAttribute('aria-label', translate('Previous axis'));
        nextButton.setAttribute('aria-label', translate('Next axis'));
        var effective = model.range && model.range.effectiveLineBudget;
        positionLabel.setAttribute('title', effective
            ? translate('Effective line limit: {count}').replace('{count}', effective) : '');
        geometry = { keys: axes.map(function (axis, index) {
            return axis.variableID ? 'variable:' + axis.variableID : 'axis:' + index;
        }), viewportWidth: width, width: fullWidth, compact: compact, margin: margin,
        spacing: (fullWidth - 2 * margin) / Math.max(1, axes.length - 1), scrollable: scrolling };
        chartElement.style.width = fullWidth + 'px';
        var index = axisPosition.window(geometry.keys, 1).first;
        lastScrollLeft = scrolling
            ? Math.max(0, Math.min(fullWidth - width, (index + anchorFraction) * geometry.spacing)) : 0;
        viewport.scrollLeft = lastScrollLeft;
        updatePosition();
    }
    function pinTitle() {
        if (chart && geometry) {
            chart.setOption({ title: { left: viewport.scrollLeft + geometry.viewportWidth / 2 } });
        }
    }
    function moveTo(left) {
        if (!geometry || !geometry.scrollable) { return; }
        viewport.scrollLeft = Math.max(0, Math.min(geometry.width - geometry.viewportWidth, left));
        lastScrollLeft = viewport.scrollLeft;
        rememberPosition();
        updatePosition();
        pinTitle();
        if (chart && chart.dispatchAction) { chart.dispatchAction({ type: 'hideTip' }); }
    }
    function buildOption(model, theme) {
        var colors = colorsFor(theme);
        var parallel = model.parallel || {};
        var style = parallel.style || {};
        var axes = Array.isArray(parallel.axes) ? parallel.axes : [];
        var rows = Array.isArray(parallel.rows) ? parallel.rows : [];
        var compact = geometry.compact;
        var titleSize = design.fontSizePercent(style.titleFontSizePercent, 18);
        var titleLayout = design.chartTitleLayout(bootstrap, parallel.title, titleSize);
        var showTitle = titleLayout.show;
        var backgroundColor = bootstrap.mode === 'ipsview' && bootstrap.options.adaptToBackground === true
            ? 'transparent' : colors.background;
        var lineColor = style.lineColor || colors.accent;
        var axisColor = style.axisColor || colors.text;
        var axisTitleSize = design.fontSizePercent(style.axisFontSizePercent, compact ? 10 : 12);
        var axisNameGap = style.axisNameGap === undefined ? 20 : Number(style.axisNameGap);
        var axisTopExtra = Math.max(0, axisTitleSize - (compact ? 10 : 12)) + Math.max(0, axisNameGap - 20);
        return {
            backgroundColor: backgroundColor,
            ...design.animationOptions(style, { enabled: true, initialDuration: 350, updateDuration: 500 }),
            aria: { enabled: true, decal: { show: false } },
            title: {
                show: showTitle, text: String(parallel.title || ''), textAlign: 'center',
                left: viewport.scrollLeft + geometry.viewportWidth / 2, top: titleLayout.top,
                textStyle: { color: style.titleColor || colors.text,
                    fontSize: titleSize, width: Math.max(1, geometry.viewportWidth - 24), overflow: 'truncate' }
            },
            tooltip: {
                show: style.showTooltip !== false,
                trigger: 'item',
                confine: true,
                extraCssText: 'max-width:' + Math.max(120, geometry.viewportWidth - 16)
                    + 'px;max-height:' + Math.max(80, viewport.clientHeight - 16)
                    + 'px;overflow:auto;white-space:normal;overflow-wrap:anywhere;',
                position: function (point, params, dom, rect, sizes) {
                    return [Math.max(viewport.scrollLeft + 8, Math.min(point[0] + 12,
                        viewport.scrollLeft + geometry.viewportWidth - sizes.contentSize[0] - 8)),
                    Math.max(8, Math.min(point[1] + 12, viewport.clientHeight - sizes.contentSize[1] - 8))];
                },
                formatter: function (parameter) {
                    var values = Array.isArray(parameter.value) ? parameter.value : [];
                    var lines = [escapeHTML(parameter.name || '')];
                    axes.forEach(function (axis, index) {
                        lines.push(escapeHTML(axis.label) + ': ' + escapeHTML(Number(values[index]).toLocaleString())
                            + (axis.unit ? ' ' + escapeHTML(axis.unit) : ''));
                    });
                    return lines.join('<br>');
                }
            },
            parallel: {
                left: geometry.margin, right: geometry.margin,
                top: titleLayout.headerInset + (showTitle ? titleSize + 42 : 40) + axisTopExtra,
                bottom: parallel.truncated ? Math.max(68, (Number(warningElement.offsetHeight) || 0) + 32) : 38,
                parallelAxisDefault: {
                    type: 'value', nameLocation: 'end',
                    nameGap: axisNameGap,
                    nameTextStyle: { color: axisColor, fontSize: axisTitleSize },
                    axisLine: { lineStyle: { color: style.axisColor || colors.border,
                        width: Number(style.axisLineWidth) || 1 } },
                    axisTick: { show: style.showAxisTicks !== false },
                    axisLabel: { show: style.showAxisLabels !== false, color: axisColor,
                        fontSize: design.fontSizePercent(style.axisFontSizePercent, compact ? 9 : 11) }
                }
            },
            parallelAxis: axes.map(function (axis, index) {
                return { id: 'axis:' + (axis.variableID || index), dim: index,
                    name: String(axis.label || '') + (axis.unit ? ' (' + axis.unit + ')' : ''),
                    nameTruncate: { maxWidth: Math.min(geometry.spacing - 12, 2 * geometry.margin - 12), ellipsis: '…' },
                    nameTextStyle: { align: 'center' } };
            }),
            series: [{
                id: 'parallel', type: 'parallel',
                smooth: style.smooth === true,
                activeOpacity: style.activeLineOpacityPercent === undefined ? 1 : Number(style.activeLineOpacityPercent) / 100,
                inactiveOpacity: style.inactiveLineOpacityPercent === undefined ? 0.05 : Number(style.inactiveLineOpacityPercent) / 100,
                lineStyle: { color: lineColor, width: Number(style.lineWidth) || 1.5,
                    type: style.lineType || 'solid', opacity: Number(style.lineOpacityPercent) / 100 || 0.45 },
                emphasis: { lineStyle: { opacity: 1, width: Math.max(2, Number(style.lineWidth) || 1.5) } },
                data: rows.map(function (row) {
                    return { name: formatTime(row.timestamp, parallel.aggregation), value: row.values };
                })
            }]
        };
    }
    function displayError(message) {
        if (chart) { chart.clear(); }
        chartElement.hidden = true;
        viewport.hidden = true;
        navigation.hidden = true;
        emptyElement.hidden = true;
        warningElement.hidden = true;
        errorElement.textContent = translate(message || 'The Parallel values could not be loaded.');
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
        var parallel = model.parallel || {};
        if (!Array.isArray(parallel.rows) || parallel.rows.length === 0) {
            if (chart) { chart.clear(); }
            chartElement.hidden = true;
            viewport.hidden = true;
            navigation.hidden = true;
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
        chartElement.hidden = false;
        viewport.hidden = false;
        layoutViewport(model);
        errorElement.hidden = true;
        emptyElement.hidden = true;
        warningElement.textContent = translate('The selected range was truncated by the line budget.');
        if (parallel.truncated && model.range && model.range.effectiveLineBudget < model.range.lineBudget) {
            warningElement.textContent += ' ' + translate('Effective line limit: {count}').replace('{count}', model.range.effectiveLineBudget);
        }
        warningElement.style.bottom = geometry.scrollable ? '38px' : '0.65rem';
        warningElement.hidden = parallel.truncated !== true;
        var theme = normalizeTheme(model.theme || 'auto');
        if (chart && currentTheme !== theme) { chart.dispose(); chart = null; }
        if (!chart) {
            chart = window.echarts.init(chartElement, theme === 'auto' ? null : theme, { renderer: 'canvas' });
            currentTheme = theme;
        }
        chart.resize({ width: geometry.width, height: Math.max(1, viewport.clientHeight) });
        chart.setOption(buildOption(model, theme), true);
    }
    window.handleMessage = function (message) {
        var state = design.parseVisualizationState(message);
        if (state) { render(state); }
    };
    viewport.addEventListener('scroll', function () {
        if (!geometry || Math.abs(viewport.scrollLeft - lastScrollLeft) < 0.5) { return; }
        lastScrollLeft = viewport.scrollLeft;
        rememberPosition();
        updatePosition();
        pinTitle();
        if (chart && chart.dispatchAction) { chart.dispatchAction({ type: 'hideTip' }); }
    });
    previousButton.addEventListener('click', function () { if (geometry) { moveTo(viewport.scrollLeft - geometry.spacing); } });
    nextButton.addEventListener('click', function () { if (geometry) { moveTo(viewport.scrollLeft + geometry.spacing); } });
    viewport.addEventListener('keydown', function (event) {
        if (event.target !== viewport || !geometry || !geometry.scrollable) { return; }
        var targets = { ArrowLeft: viewport.scrollLeft - geometry.spacing, ArrowRight: viewport.scrollLeft + geometry.spacing,
            PageUp: viewport.scrollLeft - geometry.viewportWidth, PageDown: viewport.scrollLeft + geometry.viewportWidth,
            Home: 0, End: geometry.width };
        if (Object.prototype.hasOwnProperty.call(targets, event.key)) { event.preventDefault(); moveTo(targets[event.key]); }
    });
    function resize() {
        if (currentState && currentState.status === 'ready') { render(currentState); }
    }
    if (window.ResizeObserver) {
        new ResizeObserver(resize).observe(viewport.parentElement);
    } else {
        window.addEventListener('resize', resize);
    }
    window.addEventListener('beforeunload', function () { if (chart) { chart.dispose(); } });
    render(currentState);
}());
