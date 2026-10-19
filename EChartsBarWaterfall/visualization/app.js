(function () {
    'use strict';

    var bootstrap = window.SYMC_VISUALIZATION || {};
    var chartElement = document.getElementById('echarts-bar-waterfall-chart');
    var errorElement = document.getElementById('echarts-bar-waterfall-error');
    var warningElement = document.getElementById('echarts-bar-waterfall-warning');
    var chart = null;
    var currentState = bootstrap.state || null;
    var currentTheme = null;
    var echartsDesign = window.SYMC_ECHARTS_DESIGN;
    var categoryNavigation = window.SymconEChartsCategoryNavigation.create();

    function translate(value) {
        return (bootstrap.translations || {})[value] || value;
    }
    var retainOnTransientError = echartsDesign.createTransientFailureRetainer(
        chartElement, warningElement, translate, 'The Waterfall values could not be loaded.'
    );

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
        var titleColor = style.titleColor || colors.text;
        var axisColor = style.axisColor || colors.text;
        var axisLineColor = style.axisColor || colors.border;
        var valueColor = style.valueColor || colors.text;
        var gridColor = style.gridColor || colors.border;
        var titleSize = echartsDesign.fontSizePercent(style.titleFontSizePercent, 18);
        var axisSize = echartsDesign.fontSizePercent(style.axisFontSizePercent, 12);
        var valueSize = echartsDesign.fontSizePercent(style.valueFontSizePercent, 12);
        var labelDistance = Math.max(0, Math.min(100, Number(style.valueLabelDistance === undefined ? 5 : style.valueLabelDistance) || 0));
        var categoryLabelMargin = style.showValues === true ? valueSize + labelDistance + 6 : 8;
        var top = headerInset + (titleVisible ? 55 : 20) + Math.max(0, titleSize - 18);
        var barWidth = Math.max(20, Math.min(100, Number(style.barWidthPercent) || 60));
        var valueSpan = steps.reduce(function (span, step) {
            return Math.max(span, Array.from(formatValue(step.kind === 'change' ? step.change : step.after, decimals, unit)).length * valueSize * 0.65);
        }, valueSize * 2);
        var minimumSpan = Math.max(80 * barWidth / 100, axisSize * 2.4 + 4,
            style.showValues === true ? valueSpan + 4 : 0);
        var availableWidth = Math.max(1, (chartElement.clientWidth || 800) - 76 - axisSize * 4);
        var visibleCount = Math.max(1, Math.min(steps.length, Math.floor(availableWidth / minimumSpan)));
        var scroll = steps.length > visibleCount;
        var keys = steps.map(function (step, index) { return String(step.id || 'step-' + index); });
        var viewport = categoryNavigation.window(keys, scroll ? visibleCount : steps.length);
        var first = viewport.first;
        var stepsByID = Object.create(null);
        steps.forEach(function (step, index) { stepsByID[keys[index]] = step; });
        var bottom = 48 + (scroll ? 28 : 0);
        var minimum = 0, maximum = 0;
        var helpersPositive = [];
        var helpersNegative = [];
        var barsPositive = [];
        var barsNegative = [];

        steps.forEach(function (step, index) {
            var before = Number(step.before);
            var after = Number(step.after);
            minimum = Math.min(minimum, before, after);
            maximum = Math.max(maximum, before, after);
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
                id: keys[index],
                value: positiveHeight,
                itemStyle: { color: fill },
                label: {
                    show: style.showValues === true && labelOnPositive && positiveHeight > 0,
                    ...echartsDesign.valueLabel(style, after >= before ? 'top' : 'bottom',
                        ['top', 'bottom', 'left', 'right', 'inside', 'insideTop', 'insideBottom',
                            'insideLeft', 'insideRight'], 5),
                    color: valueColor,
                    fontSize: valueSize,
                    formatter: labelText
                }
            };
            var negativeData = {
                id: keys[index],
                value: negativeHeight,
                itemStyle: { color: fill },
                label: {
                    show: style.showValues === true && !labelOnPositive && negativeHeight < 0,
                    ...echartsDesign.valueLabel(style, after >= before ? 'top' : 'bottom',
                        ['top', 'bottom', 'left', 'right', 'inside', 'insideTop', 'insideBottom',
                            'insideLeft', 'insideRight'], 5),
                    color: valueColor,
                    fontSize: valueSize,
                    formatter: labelText
                }
            };
            helpersPositive.push(positiveBase);
            helpersNegative.push(negativeBase);
            barsPositive.push(data);
            barsNegative.push(negativeData);
        });

        var width = barWidth + '%';
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
                textStyle: { color: titleColor, fontSize: titleSize }
            },
            grid: { top: top, right: 24, bottom: bottom, left: 52,
                ...(scroll ? { width: Math.min(minimumSpan, availableWidth) * visibleCount + axisSize * 4 } : {}),
                containLabel: true },
            dataZoom: scroll ? [
                { id: 'category-scroll', type: 'slider', xAxisIndex: 0, orient: 'horizontal',
                    bottom: 4, height: 12, left: 52, right: 24,
                    startValue: first, endValue: viewport.last, filterMode: 'filter', zoomLock: true,
                    showDetail: false, showDataShadow: false, brushSelect: false },
                { id: 'category-scroll-inside', type: 'inside', xAxisIndex: 0,
                    startValue: first, endValue: viewport.last, filterMode: 'filter', zoomLock: true,
                    zoomOnMouseWheel: false, moveOnMouseWheel: true, moveOnMouseMove: true }
            ] : [],
            legend: { show: false },
            tooltip: {
                trigger: 'axis', axisPointer: { type: 'shadow' },
                formatter: function (parameters) {
                    var values = Array.isArray(parameters) ? parameters : [parameters];
                    var indexed = values.find(function (parameter) { return parameter.data && parameter.data.id; });
                    var index = values.length ? values[0].dataIndex : -1;
                    var step = indexed ? stepsByID[indexed.data.id] : steps[index];
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
                type: 'category', data: scroll ? keys : steps.map(function (step) { return String(step.label || ''); }),
                axisLabel: { color: axisColor, fontSize: axisSize, margin: categoryLabelMargin, overflow: 'truncate',
                    width: scroll ? Math.max(8, Math.min(110, minimumSpan - 8, availableWidth - 8)) : 110,
                    ...(scroll ? { interval: 0, formatter: function (key) {
                        return stepsByID[key] ? String(stepsByID[key].label || '') : '';
                    } } : {}) },
                axisLine: { lineStyle: { color: axisLineColor } },
                axisTick: { alignWithLabel: true, lineStyle: { color: axisLineColor } }
            },
            yAxis: {
                type: 'value', name: unit, nameTextStyle: { color: style.axisColor || colors.muted, fontSize: axisSize },
                ...(scroll ? { min: minimum, max: maximum === minimum ? minimum + 1 : maximum } : {}),
                axisLabel: { color: axisColor, fontSize: axisSize },
                axisLine: { show: true, lineStyle: { color: axisLineColor } },
                axisTick: { show: true, lineStyle: { color: axisLineColor } },
                splitLine: { show: style.showGrid !== false,
                    lineStyle: { color: gridColor, opacity: style.gridColor ? 1 : 0.22 } }
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
        if (retainOnTransientError(state, currentState, chart)) { return; }
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
        categoryNavigation.remember(chart);
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
        var state = echartsDesign.parseVisualizationState(message);
        if (state) { render(state); }
    };

    if (window.ResizeObserver) {
        new ResizeObserver(function () {
            categoryNavigation.remember(chart);
            if (!echartsDesign.resizeChartIfNeeded(chart, chartElement)) { return; }
            if (currentState && currentState.status === 'ready') {
                chart.setOption(buildOption(currentState.chart, currentTheme || 'auto'), true);
            }
        }).observe(chartElement);
    } else {
        window.addEventListener('resize', function () {
            if (!chart) { return; }
            categoryNavigation.remember(chart);
            chart.resize();
            if (currentState && currentState.status === 'ready') {
                chart.setOption(buildOption(currentState.chart, currentTheme || 'auto'), true);
            }
        });
    }
    window.addEventListener('beforeunload', function () { if (chart) { chart.dispose(); } });
    render(currentState);
}());
