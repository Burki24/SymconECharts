(function () {
    'use strict';

    var bootstrap = window.SYMC_VISUALIZATION || {};
    var chartElement = document.getElementById('echarts-bar-waterfall-chart');
    var errorElement = document.getElementById('echarts-bar-waterfall-error');
    var chart = null;
    var currentState = bootstrap.state || null;
    var currentTheme = null;
    var echartsDesign = window.SYMC_ECHARTS_DESIGN;

    function translate(value) {
        return (bootstrap.translations || {})[value] || value;
    }

    function palette(theme) {
        var palettes = bootstrap.options && bootstrap.options.echartsThemes || {};
        return palettes[theme] || palettes.auto || {
            background: '#151619', text: '#F4F5F7', muted: '#969AA2', border: '#A5A9B0',
            accent: '#55CBB5', surface: '#25272B', seriesColors: ['#55CBB5']
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

    function normalizeTheme(theme) {
        var palettes = bootstrap.options && bootstrap.options.echartsThemes || {};
        return Object.prototype.hasOwnProperty.call(palettes, theme) ? theme : 'auto';
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
            surface: resolveColor('--symc-surface', colors.surface || colors.background),
            seriesColors: colors.seriesColors
        };
    }

    function formatValue(value, decimals, unit) {
        return Number(value).toLocaleString(undefined, {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        }) + (unit ? ' ' + unit : '');
    }

    function escapeHtml(value) {
        return String(value).replace(/[&<>"']/g, function (character) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[character];
        });
    }

    function buildOption(model, theme) {
        var colors = colorsFor(theme);
        var bar = model.bar || {};
        var style = bar.style || {};
        var configured = style.colors || {};
        var steps = Array.isArray(model.steps) ? model.steps : [];
        var decimals = Math.max(0, Math.min(6, Number(bar.decimals) || 0));
        var unit = String(bar.unit || '');
        var titleVisible = Boolean(bar.title);
        var headerInset = bootstrap.mode === 'symcon' && bootstrap.options.tileHeaderVisible !== false ? 58 : 8;
        var helpersPositive = [];
        var helpersNegative = [];
        var barsPositive = [];
        var barsNegative = [];

        steps.forEach(function (step) {
            var before = Number(step.before);
            var after = Number(step.after);
            var low = Math.min(before, after);
            var high = Math.max(before, after);
            var positiveBase = Math.max(0, low);
            var positiveHeight = Math.max(0, high) - positiveBase;
            var negativeBase = Math.min(0, high);
            var negativeHeight = Math.min(0, low) - negativeBase;
            var kind = step.kind === 'change' ? (Number(step.change) >= 0 ? 'increase' : 'decrease') : step.kind;
            var fallback = {
                start: colors.accent,
                increase: '#4CAF50',
                decrease: '#EF5350',
                total: (colors.seriesColors || [])[1] || colors.accent
            };
            var fill = configured[kind + 'Color'] || fallback[kind] || colors.accent;
            var labelOnPositive = after > 0 || (after === 0 && negativeHeight === 0);
            var labelText = formatValue(step.kind === 'change' ? step.change : after, decimals, unit);
            var data = {
                value: positiveHeight,
                itemStyle: { color: fill },
                label: {
                    show: style.showValues === true && labelOnPositive && positiveHeight > 0,
                    position: after >= before ? 'top' : 'bottom',
                    color: colors.text,
                    formatter: labelText
                }
            };
            var negativeData = {
                value: negativeHeight,
                itemStyle: { color: fill },
                label: {
                    show: style.showValues === true && !labelOnPositive && negativeHeight < 0,
                    position: after >= before ? 'top' : 'bottom',
                    color: colors.text,
                    formatter: labelText
                }
            };
            helpersPositive.push(positiveBase);
            helpersNegative.push(negativeBase);
            barsPositive.push(data);
            barsNegative.push(negativeData);
        });

        var width = Math.max(20, Math.min(100, Number(style.barWidthPercent) || 60)) + '%';
        function helper(id, data) {
            return {
                id: id, type: 'bar', stack: 'waterfall', silent: true, barWidth: width,
                itemStyle: { color: 'transparent', borderColor: 'transparent' },
                emphasis: { disabled: true }, tooltip: { show: false }, data: data
            };
        }
        function visible(id, data) {
            return { id: id, type: 'bar', stack: 'waterfall', barWidth: width, data: data };
        }

        return {
            backgroundColor: bootstrap.mode === 'ipsview' && bootstrap.options.adaptToBackground === true
                ? 'transparent' : colors.background,
            ...echartsDesign.animationOptions(style, {
                enabled: true, initialDuration: 350, updateDuration: 500
            }),
            aria: { enabled: true, description: steps.map(function (step) { return step.label; }).join(', ') },
            title: {
                show: titleVisible, text: String(bar.title || ''), left: 'center', top: headerInset,
                textStyle: { color: colors.text, fontSize: 18 }
            },
            grid: { top: headerInset + (titleVisible ? 55 : 20), right: 24, bottom: 48, left: 52, containLabel: true },
            legend: { show: false },
            tooltip: {
                trigger: 'axis', axisPointer: { type: 'shadow' },
                formatter: function (parameters) {
                    var values = Array.isArray(parameters) ? parameters : [parameters];
                    var index = values.length ? values[0].dataIndex : -1;
                    var step = steps[index];
                    if (!step) { return ''; }
                    var lines = [escapeHtml(step.label)];
                    if (step.kind === 'change') {
                        lines.push(escapeHtml(formatValue(step.change, decimals, unit)));
                        lines.push(escapeHtml(formatValue(step.before, decimals, unit)) + ' → '
                            + escapeHtml(formatValue(step.after, decimals, unit)));
                    } else {
                        lines.push(escapeHtml(formatValue(step.after, decimals, unit)));
                    }
                    return lines.join('<br>');
                }
            },
            xAxis: {
                type: 'category', data: steps.map(function (step) { return String(step.label || ''); }),
                axisLabel: { color: colors.text, overflow: 'truncate', width: 110 },
                axisLine: { lineStyle: { color: colors.border } },
                axisTick: { alignWithLabel: true, lineStyle: { color: colors.border } }
            },
            yAxis: {
                type: 'value', name: unit, nameTextStyle: { color: colors.muted },
                axisLabel: { color: colors.text },
                axisLine: { show: true, lineStyle: { color: colors.border } },
                axisTick: { show: true, lineStyle: { color: colors.border } },
                splitLine: { show: style.showGrid !== false, lineStyle: { color: colors.border, opacity: 0.22 } }
            },
            series: [
                helper('positive-base', helpersPositive),
                visible('positive-change', barsPositive),
                helper('negative-base', helpersNegative),
                visible('negative-change', barsNegative)
            ]
        };
    }

    function displayError(message) {
        if (chart) { chart.clear(); }
        chartElement.hidden = true;
        errorElement.textContent = translate(message || 'The Waterfall values could not be loaded.');
        errorElement.hidden = false;
    }

    function render(state) {
        currentState = state;
        if (!state || state.status !== 'ready' || !state.chart) {
            displayError(state && state.error);
            return;
        }
        if (!window.echarts || typeof window.echarts.init !== 'function') {
            displayError('Apache ECharts could not be initialized.');
            return;
        }
        errorElement.hidden = true;
        chartElement.hidden = false;
        var theme = normalizeTheme(state.chart.theme || 'auto');
        if (chart && currentTheme !== theme) {
            chart.dispose();
            chart = null;
        }
        if (!chart) {
            chart = window.echarts.init(chartElement, theme === 'auto' ? null : theme, { renderer: 'canvas' });
            currentTheme = theme;
        }
        chart.setOption(buildOption(state.chart, theme), true);
    }

    window.handleMessage = function (message) {
        var state = typeof message === 'string' ? JSON.parse(message) : message;
        if (state) { render(state); }
    };

    if (window.ResizeObserver) {
        new ResizeObserver(function () {
            echartsDesign.resizeChartIfNeeded(chart, chartElement);
        }).observe(chartElement);
    } else {
        window.addEventListener('resize', function () { if (chart) { chart.resize(); } });
    }
    window.addEventListener('beforeunload', function () { if (chart) { chart.dispose(); } });
    render(currentState);
}());
