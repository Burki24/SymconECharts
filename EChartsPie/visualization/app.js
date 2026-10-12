(function () {
    'use strict';

    var bootstrap = window.SYMC_VISUALIZATION || {};
    var chartElement = document.getElementById('echarts-pie-chart');
    var errorElement = document.getElementById('echarts-pie-error');
    var emptyElement = document.getElementById('echarts-pie-empty');
    var warningElement = document.getElementById('echarts-pie-warning');
    var design = window.SYMC_ECHARTS_DESIGN;
    var chart = null;
    var currentTheme = null;
    var currentState = bootstrap.state || null;

    function translate(value) {
        return (bootstrap.translations || {})[value] || value;
    }

    var retainOnTransientError = design.createTransientFailureRetainer(
        chartElement, warningElement, translate, 'The Pie values could not be loaded.'
    );

    function palette(theme) {
        var palettes = bootstrap.options && bootstrap.options.echartsThemes || {};
        return palettes[theme] || palettes.auto || {
            background: '#151619', text: '#F4F5F7', muted: '#969AA2',
            accent: '#55CBB5', seriesColors: ['#55CBB5']
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
            accent: resolveColor('--symc-accent', colors.accent),
            seriesColors: colors.seriesColors
        };
    }

    function normalizeTheme(theme) {
        var palettes = bootstrap.options && bootstrap.options.echartsThemes || {};
        return Object.prototype.hasOwnProperty.call(palettes, theme) ? theme : 'auto';
    }

    function formatValue(item) {
        var decimals = Math.max(0, Math.min(6, Number(item.decimals) || 0));
        return Number(item.value).toLocaleString(undefined, {
            minimumFractionDigits: decimals, maximumFractionDigits: decimals
        }) + (item.unit ? ' ' + item.unit : '');
    }

    function escapeHTML(value) {
        return String(value).replace(/[&<>"']/g, function (character) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[character];
        });
    }

    function sortedItems(model) {
        var items = Array.isArray(model.items) ? model.items.slice() : [];
        var order = model.pie && model.pie.style && model.pie.style.sortOrder;
        if (order === 'ascending' || order === 'descending') {
            items.sort(function (left, right) {
                var difference = Number(left.value) - Number(right.value);
                if (difference === 0) { return Number(left.order) - Number(right.order); }
                return order === 'ascending' ? difference : -difference;
            });
        }
        return items;
    }

    function buildOption(model, theme) {
        var colors = colorsFor(theme);
        var pie = model.pie || {};
        var style = pie.style || {};
        var items = sortedItems(model);
        var titleSize = design.fontSizePercent(style.titleFontSizePercent, 18);
        var legendSize = design.fontSizePercent(style.legendFontSizePercent, 12);
        var labelSize = design.fontSizePercent(style.labelFontSizePercent, 12);
        var compact = chartElement.clientWidth < 420 || chartElement.clientHeight < 260;
        var showLegend = style.showLegend === true && !compact;
        var showTitle = Boolean(pie.title) && (bootstrap.mode === 'ipsview'
            || bootstrap.options.tileHeaderVisible === false);
        var top = (bootstrap.mode === 'tile' && bootstrap.options.tileHeaderVisible !== false ? 54 : 10)
            + (showTitle ? titleSize + 18 : 0);
        var outer = Math.max(40, Math.min(100, Number(style.outerRadiusPercent) || 90));
        var inner = style.mode === 'pie' ? 0
            : Math.round(outer * Math.max(0, Math.min(70, Number(style.innerRadiusPercent) || 0))) / 100;
        var labelMap = {};
        var itemMap = {};
        var data = items.map(function (item, index) {
            var key = String(item.id);
            labelMap[key] = String(item.name || '');
            itemMap[key] = item;
            var paletteColors = colors.seriesColors && colors.seriesColors.length
                ? colors.seriesColors : [colors.accent];
            return {
                id: key,
                name: key,
                value: Number(item.value),
                itemStyle: { color: item.color || paletteColors[index % paletteColors.length] },
                label: style.labelPosition === 'inside' && !style.labelColor ? {
                    color: design.readableTextColor(
                        item.color || paletteColors[index % paletteColors.length], colors.text, colors.background
                    )
                } : undefined
            };
        });
        return {
            backgroundColor: bootstrap.mode === 'ipsview' && bootstrap.options.adaptToBackground === true
                ? 'transparent' : colors.background,
            ...design.animationOptions(style, { enabled: true, initialDuration: 350, updateDuration: 500 }),
            aria: { enabled: true, decal: { show: false }, description: items.map(function (item) {
                return item.name + ': ' + formatValue(item);
            }).join(', ') },
            title: {
                show: showTitle, text: String(pie.title || ''), left: 'center', top: top - titleSize - 10,
                textStyle: { color: style.titleColor || colors.text, fontSize: titleSize }
            },
            legend: {
                show: showLegend, type: 'scroll', orient: 'vertical', right: 4, top: top,
                bottom: 10, width: '25%', data: data.map(function (item) { return item.name; }),
                textStyle: { color: style.legendColor || colors.text, fontSize: legendSize },
                formatter: function (key) { return labelMap[key] || ''; }
            },
            tooltip: {
                trigger: 'item',
                formatter: function (parameter) {
                    var item = itemMap[parameter.name];
                    if (!item) { return ''; }
                    return String(parameter.marker || '') + escapeHTML(labelMap[parameter.name]) + ': '
                        + escapeHTML(formatValue(item)) + ' (' + Number(parameter.percent).toLocaleString(undefined, {
                            maximumFractionDigits: 1
                        }) + '%)';
                }
            },
            series: [{
                id: 'pie', type: 'pie', name: String(pie.title || ''),
                radius: [inner + '%', outer + '%'],
                center: [showLegend ? '39%' : '50%', top < 55 ? '52%' : '56%'],
                roseType: style.mode === 'nightingale' ? 'area' : false,
                stillShowZeroSum: false,
                avoidLabelOverlap: true,
                label: {
                    show: style.showLabels === true,
                    position: style.labelPosition === 'inside' ? 'inside' : 'outside',
                    color: style.labelColor || colors.text,
                    fontSize: labelSize,
                    formatter: function (parameter) {
                        var name = labelMap[parameter.name] || '';
                        return name + (style.showPercent === true ? '\n' + Number(parameter.percent).toLocaleString(
                            undefined, { minimumFractionDigits: 1, maximumFractionDigits: 1 }
                        ) + '%' : '');
                    }
                },
                labelLine: { show: style.showLabels === true && style.labelPosition !== 'inside' },
                data: data
            }]
        };
    }

    function displayError(message) {
        if (chart) { chart.clear(); }
        chartElement.hidden = true;
        emptyElement.hidden = true;
        errorElement.textContent = translate(message || 'The Pie values could not be loaded.');
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
        var items = state.chart.items || [];
        if (!items.some(function (item) { return Number(item.value) > 0; })) {
            if (chart) { chart.clear(); }
            chartElement.hidden = true;
            errorElement.hidden = true;
            emptyElement.textContent = translate('No positive values');
            emptyElement.hidden = false;
            return;
        }
        emptyElement.hidden = true;
        errorElement.hidden = true;
        var wasHidden = chartElement.hidden;
        chartElement.hidden = false;
        var theme = normalizeTheme(state.chart.theme || 'auto');
        if (chart && currentTheme !== theme) {
            chart.dispose();
            chart = null;
        }
        if (!chart) {
            chart = window.echarts.init(chartElement, theme === 'auto' ? null : theme, { renderer: 'canvas' });
            currentTheme = theme;
        } else if (wasHidden) {
            chart.resize();
        }
        chart.setOption(buildOption(state.chart, theme), true);
    }

    window.handleMessage = function (message) {
        var state = typeof message === 'string' ? JSON.parse(message) : message;
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
