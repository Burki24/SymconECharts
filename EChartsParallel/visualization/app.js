(function () {
    'use strict';

    var bootstrap = window.SYMC_VISUALIZATION || {};
    var design = window.SYMC_ECHARTS_DESIGN;
    var chartElement = document.getElementById('echarts-parallel-chart');
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
    function buildOption(model, theme) {
        var colors = colorsFor(theme);
        var parallel = model.parallel || {};
        var style = parallel.style || {};
        var axes = Array.isArray(parallel.axes) ? parallel.axes : [];
        var rows = Array.isArray(parallel.rows) ? parallel.rows : [];
        var width = Math.max(1, chartElement.clientWidth || 1);
        var height = Math.max(1, chartElement.clientHeight || 1);
        var compact = width < 420 || height < 280;
        var titleSize = design.fontSizePercent(style.titleFontSizePercent, 18);
        var titleLayout = design.chartTitleLayout(bootstrap, parallel.title, titleSize);
        var showTitle = titleLayout.show;
        var backgroundColor = bootstrap.mode === 'ipsview' && bootstrap.options.adaptToBackground === true
            ? 'transparent' : colors.background;
        var lineColor = style.lineColor || colors.accent;
        var axisColor = style.axisColor || colors.text;
        return {
            backgroundColor: backgroundColor,
            aria: { enabled: true, decal: { show: false } },
            title: {
                show: showTitle, text: String(parallel.title || ''), left: 'center', top: titleLayout.top,
                textStyle: { color: style.titleColor || colors.text,
                    fontSize: titleSize }
            },
            tooltip: {
                show: style.showTooltip !== false,
                trigger: 'item',
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
                left: compact ? 28 : 64, right: compact ? 28 : 64,
                top: titleLayout.headerInset + (showTitle ? titleSize + 42 : 40), bottom: 38,
                parallelAxisDefault: {
                    type: 'value', nameLocation: 'end', nameGap: 20,
                    nameTextStyle: { color: axisColor, fontSize: design.fontSizePercent(style.axisFontSizePercent, compact ? 10 : 12) },
                    axisLine: { lineStyle: { color: style.axisColor || colors.border } },
                    axisLabel: { color: axisColor, fontSize: design.fontSizePercent(style.axisFontSizePercent, compact ? 9 : 11) }
                }
            },
            parallelAxis: axes.map(function (axis, index) {
                return { dim: index, name: String(axis.label || '') + (axis.unit ? ' (' + axis.unit + ')' : '') };
            }),
            series: [{
                id: 'parallel', type: 'parallel',
                smooth: style.smooth === true,
                lineStyle: { color: lineColor, width: Number(style.lineWidth) || 1.5,
                    opacity: Number(style.lineOpacityPercent) / 100 || 0.45 },
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
        var wasHidden = chartElement.hidden;
        chartElement.hidden = false;
        errorElement.hidden = true;
        emptyElement.hidden = true;
        warningElement.textContent = translate('The selected range was truncated by the line budget.');
        warningElement.hidden = parallel.truncated !== true;
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
