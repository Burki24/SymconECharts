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
        return Number(item.originalValue ?? item.value).toLocaleString(undefined, {
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

    function pieLayout(style, showLegend, top) {
        var width = Math.max(1, chartElement.clientWidth || 1);
        var height = Math.max(1, chartElement.clientHeight || 1);
        var legendAtRight = showLegend && width >= 760 && width >= height * 1.2;
        var legendWidth = legendAtRight ? Math.min(230, Math.max(170, Math.round(width * 0.24))) : 0;
        var plotWidth = width - legendWidth - (legendAtRight ? 16 : 0);
        var bottom = 16 + (showLegend && !legendAtRight ? 36 : 0);
        var sideInset = style.showLabels === true && style.labelPosition !== 'inside'
            ? Math.min(100, Math.max(60, Math.round(width * 0.12))) : 16;
        var centerX = plotWidth / 2;
        var centerY = (top + height - bottom) / 2;
        var outerPercent = Math.max(40, Math.min(100, Number(style.outerRadiusPercent) || 70));
        var requestedRadius = Math.min(width, height) * outerPercent / 200;
        var availableRadius = Math.max(1, Math.min(
            centerX - sideInset,
            plotWidth - centerX - sideInset,
            (height - top - bottom) / 2
        ));
        var outerRadius = Math.max(1, Math.min(requestedRadius, availableRadius));
        var innerPercent = style.mode === 'pie' ? 0
            : Math.max(0, Math.min(70, Number(style.innerRadiusPercent) || 0));
        return {
            center: [centerX, centerY],
            radius: [outerRadius * innerPercent / 100, outerRadius],
            legendAtRight: legendAtRight,
            legendWidth: legendWidth,
            plotWidth: plotWidth,
            top: top,
            width: width,
            height: height
        };
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
        var titleLayout = design.chartTitleLayout(bootstrap, pie.title, titleSize, 18);
        if (pie.normalized === true) { titleLayout.contentTop += 20; }
        var showTitle = titleLayout.show;
        var layout = pieLayout(style, showLegend, titleLayout.contentTop);
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
            }).join(', ') + (pie.normalized === true ? '; ' + translate('Share of normalized weights') : '') },
            title: {
                show: showTitle || pie.normalized === true, text: String(pie.title || ''), left: layout.plotWidth / 2,
                textAlign: 'center', top: titleLayout.top,
                textStyle: { color: style.titleColor || colors.text, fontSize: titleSize },
                subtext: pie.normalized === true ? translate('Share of normalized weights') : '',
                subtextStyle: { color: colors.muted, fontSize: 11 }
            },
            legend: {
                show: showLegend, type: 'scroll', orient: layout.legendAtRight ? 'vertical' : 'horizontal',
                right: layout.legendAtRight ? 8 : undefined,
                left: layout.legendAtRight ? undefined : 8,
                top: layout.legendAtRight ? layout.top : undefined,
                bottom: layout.legendAtRight ? 12 : 6,
                width: layout.legendAtRight ? layout.legendWidth : layout.width - 16,
                height: layout.legendAtRight ? Math.max(1, layout.height - layout.top - 24) : 28,
                data: data.map(function (item) { return item.name; }),
                textStyle: { color: style.legendColor || colors.text, fontSize: legendSize },
                formatter: function (key) { return labelMap[key] || ''; }
            },
            tooltip: {
                confine: pie.normalized === true,
                extraCssText: pie.normalized === true ? 'max-width:' + Math.max(80, chartElement.clientWidth - 24)
                    + 'px;white-space:normal;overflow-wrap:anywhere;box-sizing:border-box;' : '',
                trigger: 'item',
                formatter: function (parameter) {
                    var item = itemMap[parameter.name];
                    if (!item) { return ''; }
                    return String(parameter.marker || '') + escapeHTML(labelMap[parameter.name]) + ': '
                        + escapeHTML(formatValue(item)) + (pie.normalized === true
                            ? '<br>' + escapeHTML(translate('Normalized')) + ': ' + escapeHTML(Number(item.value).toLocaleString(undefined, { maximumFractionDigits: 2 }))
                                + ' / 100<br>' + escapeHTML(translate('Range')) + ': '
                                + escapeHTML(formatValue({ ...item, value: item.minimum, originalValue: item.minimum })) + ' – '
                                + escapeHTML(formatValue({ ...item, value: item.maximum, originalValue: item.maximum }))
                                + '<br>' + escapeHTML(translate('Share of normalized weights')) + ': '
                            : ' (') + Number(parameter.percent).toLocaleString(undefined, {
                            maximumFractionDigits: 1
                        }) + (pie.normalized === true ? '%' : '%)');
                }
            },
            series: [{
                id: 'pie', type: 'pie', name: String(pie.title || ''),
                radius: layout.radius,
                center: layout.center,
                roseType: style.mode === 'nightingale' ? 'area' : false,
                stillShowZeroSum: false,
                avoidLabelOverlap: true,
                labelLayout: { hideOverlap: true },
                label: {
                    show: style.showLabels === true,
                    position: style.labelPosition === 'inside' ? 'inside' : 'outside',
                    color: style.labelColor || colors.text,
                    fontSize: labelSize,
                    alignTo: style.labelPosition === 'inside' ? undefined : (style.outsideLabelAlignment || 'edge'),
                    edgeDistance: style.labelPosition === 'inside' ? undefined : Number(style.labelEdgeDistance ?? 8),
                    distanceToLabelLine: Number(style.labelLineGap ?? 5),
                    bleedMargin: 4,
                    width: Math.min(130, Math.max(72, Math.round(layout.width * 0.2))),
                    overflow: 'truncate',
                    formatter: function (parameter) {
                        var name = labelMap[parameter.name] || '';
                        return name + (style.showPercent === true ? '\n' + Number(parameter.percent).toLocaleString(
                            undefined, { minimumFractionDigits: 1, maximumFractionDigits: 1 }
                        ) + '%' + (pie.normalized === true ? ' ' + translate('Share of normalized weights') : '') : '');
                    }
                },
                labelLine: {
                    show: style.showLabels === true && style.labelPosition !== 'inside',
                    length: Number(style.leaderLineLength ?? 15),
                    length2: Number(style.leaderLineLength2 ?? 15)
                },
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
