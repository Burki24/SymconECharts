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
    var sourceSelection = window.SYMC_ECHARTS_SERIES_SELECTION.create();
    var selectionEnabled = null;

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

    function pieLayout(style, showLegend, top, legendSize, selectable) {
        var width = Math.max(1, chartElement.clientWidth || 1);
        var height = Math.max(1, chartElement.clientHeight || 1);
        var legendAtRight = showLegend && width >= 760 && width >= height * 1.2;
        var legendWidth = legendAtRight ? Math.min(width * 0.4, Math.max(
            Math.min(230, Math.max(170, Math.round(width * 0.24))),
            selectable ? legendSize * 12 + 24 : 0
        )) : 0;
        var plotWidth = width - legendWidth - (legendAtRight ? 16 : 0);
        var legendHeight = Math.max(28, Math.ceil(legendSize * 1.5) + 10) + (selectable ? Math.ceil(legendSize * 1.5) + 8 : 0);
        var bottom = 16 + (showLegend && !legendAtRight ? legendHeight + 8 : 0);
        var sideInset = style.showLabels === true && style.labelPosition !== 'inside'
            ? Math.min(100, Math.max(60, Math.round(width * 0.12))) : 16;
        var centerX = plotWidth / 2;
        var centerY = Math.max(1, Math.min(height - 1, (top + height - bottom) / 2));
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
            legendHeight: legendHeight,
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
        var selectable = pie.enableSourceSelection === true;
        if (selectionEnabled !== selectable) {
            sourceSelection = window.SYMC_ECHARTS_SERIES_SELECTION.create();
            selectionEnabled = selectable;
        }
        var selected = sourceSelection.reconcile(items.map(function (item) {
            return selectable ? item : Object.assign({}, item, {initiallyVisible: true});
        }));
        // Ratios use scaled denominators: a finite set of finite weights may overflow when summed directly.
        var maximum = items.reduce(function (value, item) { return Math.max(value, Number(item.value)); }, 0);
        var visibleMaximum = items.reduce(function (value, item) {
            return selected[item.id] !== false ? Math.max(value, Number(item.value)) : value;
        }, 0);
        var fullWeight = 0, visibleWeight = 0, visibleCount = 0;
        items.forEach(function (item) {
            var weight = maximum > 0 ? Number(item.value) / maximum : 0;
            fullWeight += weight;
            if (selected[item.id] !== false) {
                visibleWeight += visibleMaximum > 0 ? Number(item.value) / visibleMaximum : 0;
                visibleCount += 1;
            }
        });
        // Native angle multiplication also needs a finite reciprocal for subnormal weights.
        var scaleValues = visibleMaximum > 0 && (visibleMaximum > Number.MAX_VALUE / Math.max(1, visibleWeight)
            || !Number.isFinite(Math.PI * 2 / visibleMaximum));
        function percent(item, weight, scale) {
            return scale > 0 && weight > 0 ? (Number(item.value) / scale) / weight * 100 : 0;
        }
        function formatPercent(value, decimals) {
            return Number(value).toLocaleString(undefined, {minimumFractionDigits: decimals || 0, maximumFractionDigits: 1}) + '%';
        }
        var titleSize = design.fontSizePercent(style.titleFontSizePercent, 18);
        var legendSize = design.fontSizePercent(style.legendFontSizePercent, 12);
        var labelSize = design.fontSizePercent(style.labelFontSizePercent, 12);
        var compact = chartElement.clientWidth < 420 || chartElement.clientHeight < 260;
        var showLegend = selectable || style.showLegend === true && !compact;
        var titleLayout = design.chartTitleLayout(bootstrap, pie.title, titleSize, 18);
        if (pie.normalized === true) { titleLayout.contentTop += 20; }
        var showTitle = titleLayout.show;
        var layout = pieLayout(style, showLegend, titleLayout.contentTop, legendSize, selectable);
        var positiveIds = items.filter(function (item) {
            return selected[item.id] !== false && Number(item.value) > 0;
        }).map(function (item) { return String(item.id); });
        // The shipped lean runtime has no global LabelLayout component. Bound labels explicitly,
        // sampling around the visible circle instead of drawing one dense contiguous block.
        var labelLines = style.showPercent === true ? 2 : 1;
        var labelCapacity = Math.max(0, Math.min(24,
            Math.floor(layout.radius[1] * 2 / (labelSize * labelLines * 1.3 + 8)) * 2));
        var limitedLabels = positiveIds.length > labelCapacity;
        var labeledIds = Object.create(null);
        if (limitedLabels) {
            for (var labelIndex = 0; labelIndex < labelCapacity; labelIndex += 1) {
                labeledIds[positiveIds[Math.floor(labelIndex * positiveIds.length / labelCapacity)]] = true;
            }
        }
        var labelMap = {};
        var itemMap = {};
        var data = items.map(function (item) {
            var key = String(item.id);
            labelMap[key] = String(item.name || '');
            itemMap[key] = item;
            var paletteColors = colors.seriesColors && colors.seriesColors.length
                ? colors.seriesColors : [colors.accent];
            var order = Math.max(0, Math.floor(Number(item.order) || 0));
            var color = item.color || paletteColors[order % paletteColors.length];
            var itemLabel = style.labelPosition === 'inside' && !style.labelColor ? {
                color: design.readableTextColor(color, colors.text, colors.background)
            } : undefined;
            var hideLabel = limitedLabels && !labeledIds[key];
            if (hideLabel) { itemLabel = Object.assign({}, itemLabel, {show: false}); }
            return {
                id: key,
                name: key,
                value: scaleValues ? Math.min(Number.MAX_VALUE, Number(item.value) / visibleMaximum) : Number(item.value),
                itemStyle: { color: color },
                label: itemLabel,
                labelLine: hideLabel ? {show: false} : undefined
            };
        });
        emptyElement.textContent = translate(visibleCount === 0 ? 'No sources selected.'
            : visibleWeight === 0 && fullWeight > 0 ? 'No positive values in the selected sources.' : 'No positive values');
        emptyElement.hidden = visibleWeight > 0;
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
                selectedMode: true, selected: Object.assign({}, selected),
                scrollDataIndex: sourceSelection.scrollDataIndex(),
                selector: selectable ? [{type: 'all', title: translate('All')}, {type: 'inverse', title: translate('Invert')}] : [],
                selectorPosition: 'start', selectorLabel: {color: style.legendColor || colors.text, fontSize: legendSize},
                left: layout.legendAtRight ? layout.width - layout.legendWidth - 8 : 8,
                top: layout.legendAtRight ? layout.top : undefined,
                bottom: layout.legendAtRight ? 12 : 6,
                width: layout.legendAtRight ? layout.legendWidth : layout.width - 16,
                height: layout.legendAtRight ? Math.max(1, layout.height - layout.top - 24) : layout.legendHeight,
                data: data.map(function (item) { return item.name; }),
                textStyle: { color: style.legendColor || colors.text, fontSize: legendSize },
                formatter: function (key) { return labelMap[key] || ''; }
            },
            tooltip: {
                confine: true,
                extraCssText: 'max-width:' + Math.max(80, chartElement.clientWidth - 24)
                    + 'px;white-space:normal;overflow-wrap:anywhere;box-sizing:border-box;',
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
                            : '')
                        + '<br>' + escapeHTML(translate(pie.normalized === true
                            ? 'Share of selected normalized weights' : 'Share of visible selection')) + ': '
                        + escapeHTML(formatPercent(percent(item, visibleWeight, visibleMaximum)))
                        + '<br>' + escapeHTML(translate(pie.normalized === true
                            ? 'Share of all normalized weights' : 'Share of full model')) + ': '
                        + escapeHTML(formatPercent(percent(item, fullWeight, maximum)));
                }
            },
            series: [{
                id: 'pie', type: 'pie', name: String(pie.title || ''),
                left: 0, right: layout.width - layout.plotWidth,
                radius: layout.radius,
                center: layout.center,
                roseType: style.mode === 'nightingale' ? 'area' : false,
                stillShowZeroSum: false,
                showEmptyCircle: false,
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
                        var item = itemMap[parameter.name];
                        return name + (style.showPercent === true && item ? '\n' + formatPercent(percent(item, visibleWeight, visibleMaximum), 1)
                            + (pie.normalized === true ? ' ' + translate('Share of normalized weights') : '') : '');
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
        sourceSelection.capture(chart);
        currentState = state;
        if (!state || state.status !== 'ready' || !state.chart) {
            displayError(state && state.error);
            return;
        }
        if (!window.echarts || typeof window.echarts.init !== 'function') {
            displayError('Apache ECharts could not be initialized.');
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
            if (typeof chart.on === 'function') {
                ['legendselectchanged', 'legendselectall', 'legendinverseselect'].forEach(function (eventName) {
                    chart.on(eventName, function () { if (currentState) { render(currentState); } });
                });
            }
        } else if (wasHidden) {
            chart.resize();
        }
        chart.setOption(buildOption(state.chart, theme), true);
    }

    window.handleMessage = function (message) {
        var state = design.parseVisualizationState(message);
        if (state) { render(state); }
    };

    function redrawAfterResize() {
        if (currentState && currentState.status === 'ready') {
            sourceSelection.capture(chart);
            chart.setOption(buildOption(currentState.chart, currentTheme || 'auto'), true);
        }
    }
    if (window.ResizeObserver) {
        new ResizeObserver(function () {
            if (!design.resizeChartIfNeeded(chart, chartElement)) { return; }
            redrawAfterResize();
        }).observe(chartElement);
    } else {
        window.addEventListener('resize', function () {
            if (!design.resizeChartIfNeeded(chart, chartElement)) { return; }
            redrawAfterResize();
        });
    }
    window.addEventListener('beforeunload', function () { if (chart) { chart.dispose(); } });
    render(currentState);
}());
