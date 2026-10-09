(function () {
    'use strict';

    var bootstrap = window.SYMC_VISUALIZATION || {};
    var chartElement = document.getElementById('echarts-bar-polar-chart');
    var errorElement = document.getElementById('echarts-bar-polar-error');
    var chart = null;
    var currentState = bootstrap.state || null;
    var currentTheme = null;
    var lastWidth = 0;
    var lastHeight = 0;

    function translate(value) {
        return (bootstrap.translations || {})[value] || value;
    }

    function palette(theme) {
        var palettes = bootstrap.options && bootstrap.options.echartsThemes || {};
        return palettes[theme] || palettes.auto || {
            background: '#151619', text: '#F4F5F7', muted: '#969AA2', border: '#A5A9B0',
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

    function orderedItems(items, order) {
        var result = items.slice();
        if (order === 'ascending' || order === 'descending') {
            result.sort(function (left, right) {
                var difference = Number(left.value) - Number(right.value);
                return (order === 'ascending' ? difference : -difference)
                    || Number(left.order) - Number(right.order);
            });
        }
        return result;
    }

    function polarLayout(polar) {
        var style = polar.style || {};
        var width = Math.max(1, chartElement.clientWidth || 1);
        var height = Math.max(1, chartElement.clientHeight || 1);
        var headerInset = bootstrap.mode === 'symcon' && bootstrap.options.tileHeaderVisible !== false ? 58 : 8;
        var labelWidth = Math.min(90, Math.max(36, Math.round(width * 0.13)));
        var labelMargin = Math.min(24, Math.max(16, Math.round(width * 0.05)));
        var top = headerInset + (polar.title ? 50 : 12);
        var bottom = style.showCategoryLabels !== false ? Math.min(45, height * 0.14) : 24;
        var side = style.showCategoryLabels !== false ? labelWidth + labelMargin + 8 : 36;
        var outerPercent = Math.max(20, Math.min(95, Number(style.outerRadiusPercent) || 76));
        var innerPercent = Math.max(0, Math.min(75, Number(style.innerRadiusPercent) || 0));
        var requestedRadius = Math.min(width, height) * outerPercent / 200;
        var availableRadius = Math.max(1, Math.min(width / 2 - side, (height - top - bottom) / 2));
        var outerRadius = Math.min(requestedRadius, availableRadius);
        return {
            geometry: {
                center: [width / 2, (top + height - bottom) / 2],
                radius: [outerRadius * innerPercent / outerPercent, outerRadius]
            },
            labelWidth: labelWidth,
            labelMargin: labelMargin,
            showValueScale: style.showGrid !== false && (outerRadius >= 110 || style.showValues !== true)
        };
    }

    function buildOption(model, theme) {
        var colors = colorsFor(theme);
        var design = window.SYMC_ECHARTS_DESIGN || {};
        var polar = model.polar || {};
        var style = polar.style || {};
        var mode = style.mode === 'tangential' ? 'tangential' : 'radial';
        var items = orderedItems(Array.isArray(model.items) ? model.items : [], style.sortOrder);
        var labels = items.map(function (item) { return String(item.label || ''); });
        var defaultDecimals = Math.max(0, Math.min(6, Number(polar.decimals) || 0));
        var unit = String(polar.unit || '');
        function itemDecimals(item) {
            return item && Number.isInteger(item.decimals)
                ? Math.max(0, Math.min(6, item.decimals)) : defaultDecimals;
        }
        var swatches = colors.seriesColors || [colors.accent];
        var titleVisible = Boolean(polar.title);
        var headerInset = bootstrap.mode === 'symcon' && bootstrap.options.tileHeaderVisible !== false ? 58 : 8;
        var layout = polarLayout(polar);
        var axisStyle = { color: colors.border, opacity: 0.7 };
        var splitStyle = { color: colors.border, opacity: 0.2 };
        var categoryAxis = {
            type: 'category',
            data: labels,
            axisLabel: {
                show: style.showCategoryLabels !== false, color: colors.text,
                overflow: 'truncate', width: 90, fontSize: 11, margin: 24
            },
            axisLine: { show: style.showGrid !== false, lineStyle: axisStyle },
            axisTick: { show: style.showGrid !== false, lineStyle: axisStyle },
            splitLine: { show: style.showGrid !== false, lineStyle: splitStyle }
        };
        var valueAxis = {
            type: 'value',
            axisLabel: { show: layout.showValueScale, color: colors.muted },
            axisLine: { show: style.showGrid !== false, lineStyle: axisStyle },
            axisTick: { show: style.showGrid !== false, lineStyle: axisStyle },
            splitLine: { show: style.showGrid !== false, lineStyle: splitStyle }
        };
        var startAngle = Math.max(0, Math.min(360, Number(style.startAngle) || 0));
        var angleAxis = mode === 'radial' ? categoryAxis : valueAxis;
        angleAxis.startAngle = startAngle;
        angleAxis.clockwise = style.clockwise !== false;
        categoryAxis.axisLabel.width = layout.labelWidth;
        categoryAxis.axisLabel.margin = layout.labelMargin;

        return {
            backgroundColor: bootstrap.mode === 'ipsview' && bootstrap.options.adaptToBackground === true
                ? 'transparent' : colors.background,
            animation: true,
            animationDuration: 350,
            aria: {
                enabled: true,
                description: items.map(function (item) {
                    return item.label + ': ' + formatValue(item.value, itemDecimals(item), unit);
                }).join(', ')
            },
            title: {
                show: titleVisible, text: String(polar.title || ''), left: 'center', top: headerInset,
                textStyle: { color: colors.text, fontSize: 18 }
            },
            tooltip: {
                trigger: 'item',
                formatter: function (parameter) {
                    var item = items[parameter.dataIndex];
                    if (!item) { return ''; }
                    return escapeHtml(item.label) + '<br>'
                        + escapeHtml(formatValue(item.value, itemDecimals(item), unit));
                }
            },
            polar: layout.geometry,
            angleAxis: angleAxis,
            radiusAxis: mode === 'radial' ? valueAxis : categoryAxis,
            series: [{
                id: 'polar-values',
                type: 'bar',
                coordinateSystem: 'polar',
                barWidth: Math.max(20, Math.min(100, Number(style.barWidthPercent) || 60)) + '%',
                roundCap: style.roundCaps === true,
                data: items.map(function (item, index) {
                    var itemColor = item.color || swatches[index % swatches.length] || colors.accent;
                    return {
                        id: item.id,
                        name: String(item.label || ''),
                        value: Number(item.value),
                        itemStyle: { color: itemColor },
                        label: {
                            color: typeof design.readableTextColor === 'function'
                                ? design.readableTextColor(itemColor, colors.text, colors.background)
                                : colors.text
                        }
                    };
                }),
                label: {
                    show: style.showValues === true,
                    position: 'middle',
                    color: colors.text,
                    rotate: 0,
                    formatter: function (parameter) {
                        return formatValue(parameter.value, itemDecimals(items[parameter.dataIndex]), unit);
                    }
                }
            }]
        };
    }

    function displayError(message) {
        if (chart) { chart.clear(); }
        chartElement.hidden = true;
        errorElement.textContent = translate(message || 'The Polar values could not be loaded.');
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
        lastWidth = chartElement.clientWidth;
        lastHeight = chartElement.clientHeight;
    }

    function resizeChart() {
        if (!chart) { return; }
        chart.resize();
        var width = chartElement.clientWidth;
        var height = chartElement.clientHeight;
        if (width === lastWidth && height === lastHeight) { return; }
        lastWidth = width;
        lastHeight = height;
        if (currentState && currentState.status === 'ready' && currentState.chart) {
            var polar = currentState.chart.polar || {};
            var layout = polarLayout(polar);
            var categoryAxisName = polar.style && polar.style.mode === 'tangential'
                ? 'radiusAxis' : 'angleAxis';
            var valueAxisName = categoryAxisName === 'angleAxis' ? 'radiusAxis' : 'angleAxis';
            var update = { polar: layout.geometry };
            update[categoryAxisName] = { axisLabel: { width: layout.labelWidth, margin: layout.labelMargin } };
            update[valueAxisName] = { axisLabel: { show: layout.showValueScale } };
            chart.setOption(update);
        }
    }

    window.handleMessage = function (message) {
        var state = typeof message === 'string' ? JSON.parse(message) : message;
        if (state) { render(state); }
    };

    if (window.ResizeObserver) {
        new ResizeObserver(resizeChart).observe(chartElement);
    } else {
        window.addEventListener('resize', resizeChart);
    }
    window.addEventListener('beforeunload', function () { if (chart) { chart.dispose(); } });
    render(bootstrap.state || null);
}());
