(function () {
    'use strict';

    var bootstrap = window.SYMC_VISUALIZATION || {};
    var design = window.SYMC_ECHARTS_DESIGN;
    var zoom = window.SymconEChartsZoom;
    var chartElement = document.getElementById('echarts-scatter-chart');
    var emptyElement = document.getElementById('echarts-scatter-empty');
    var warningElement = document.getElementById('echarts-scatter-warning');
    var errorElement = document.getElementById('echarts-scatter-error');
    var chart = null;
    var currentState = bootstrap.state || null;
    var currentTheme = null;
    var refreshFailures = 0;
    var loadError = 'The Scatter values could not be loaded.';

    function translate(text) {
        return (bootstrap.translations || {})[text] || text;
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
            border: '#A5A9B0', track: '#34363B', accent: '#55CBB5', seriesColors: ['#55CBB5']
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
            track: colors.track,
            accent: resolveColor('--symc-accent', colors.accent),
            seriesColors: colors.seriesColors
        };
    }

    function formatValue(value, role) {
        var decimals = Math.max(0, Math.min(6, Number(role.decimals) || 0));
        return Number(value).toLocaleString(undefined, {
            minimumFractionDigits: decimals, maximumFractionDigits: decimals
        }) + (role.unit ? ' ' + role.unit : '');
    }

    function pointSize(value, minimum, maximum, base, large) {
        if (!Number.isFinite(value) || maximum <= minimum) { return base; }
        return base + (large - base) * Math.max(0, Math.min(1, (value - minimum) / (maximum - minimum)));
    }

    function colorMapOptions(style, roles, points, colors, top, compact, hasTitle) {
        if (style.colorMapEnabled !== true) { return null; }
        var dimension = { x: 0, y: 1, size: 2 }[style.colorMapDimension];
        if (dimension === undefined) { dimension = 1; }
        if (dimension === 2 && !roles.size) { return null; }
        var pointIndex = dimension + 1;
        var values = points.map(function (point) { return Number(point[pointIndex]); })
            .filter(Number.isFinite);
        var manual = style.colorMapRangeMode === 'manual';
        var minimum = manual ? Number(style.colorMapMinimum) : Math.min.apply(null, values);
        var maximum = manual ? Number(style.colorMapMaximum) : Math.max.apply(null, values);
        if (!Number.isFinite(minimum) || !Number.isFinite(maximum)) { return null; }
        if (maximum <= minimum) {
            var padding = Math.max(1, Math.abs(minimum) * 0.01);
            minimum -= padding;
            maximum += padding;
        }
        var role = [roles.x, roles.y, roles.size][dimension] || { unit: '', decimals: 1 };
        return {
            type: 'continuous', seriesIndex: 0, dimension: 4,
            min: minimum, max: maximum, show: style.colorMapShowLegend === true,
            calculable: false, orient: 'horizontal', left: 'center',
            top: top + (hasTitle ? 32 : 4), itemWidth: compact ? 8 : 12,
            itemHeight: Math.round(Math.min(420, Math.max(160, chartElement.clientWidth * 0.32))),
            text: [formatValue(maximum, role), formatValue(minimum, role)],
            textStyle: { color: style.axisColor || colors.muted,
                fontSize: compact ? 9 : 11 },
            inRange: { color: [style.colorMapLowColor || colors.seriesColors[0] || colors.accent,
                style.colorMapHighColor || colors.seriesColors[3] || colors.accent] }
        };
    }

    function sameZoomContext(previous, next) {
        if (!previous || !next) { return false; }
        var axisKeys = ['xAxisRangeMode', 'xAxisMinimum', 'xAxisMaximum',
            'yAxisRangeMode', 'yAxisMinimum', 'yAxisMaximum', 'enableZoom'];
        var previousStyle = previous.scatter && previous.scatter.style || {};
        var nextStyle = next.scatter && next.scatter.style || {};
        return axisKeys.every(function (key) { return previousStyle[key] === nextStyle[key]; })
            && JSON.stringify(previous.roles || {}) === JSON.stringify(next.roles || {})
            && (!previous.range || !next.range || (previous.range.aggregationLevel === next.range.aggregationLevel
                && previous.range.pointBudget === next.range.pointBudget));
    }

    function buildOption(model, theme) {
        var colors = colorsFor(theme);
        var scatter = model.scatter || {};
        var style = scatter.style || {};
        var roles = model.roles || {};
        var xRole = roles.x || { label: 'X', unit: '', decimals: 1 };
        var yRole = roles.y || { label: 'Y', unit: '', decimals: 1 };
        var sizeRole = roles.size || null;
        var points = Array.isArray(model.points) ? model.points : [];
        var hasTitle = Boolean(scatter.title) && (bootstrap.mode === 'ipsview'
            || bootstrap.options.tileHeaderVisible === false);
        var compact = chartElement.clientWidth < 420 || chartElement.clientHeight < 280;
        var zoomEnabled = style.enableZoom === true;
        var top = bootstrap.mode === 'symcon' && bootstrap.options.tileHeaderVisible !== false ? 58 : 8;
        var base = Math.max(4, Math.min(60, Number(style.pointSize) || 12));
        var large = Math.max(base, Math.min(100, Number(style.bubbleMaxSize) || 48));
        var sizeValues = sizeRole ? points.map(function (point) { return Number(point[3]); }) : [];
        var minimum = sizeValues.length ? Math.min.apply(null, sizeValues) : 0;
        var maximum = sizeValues.length ? Math.max.apply(null, sizeValues) : 0;
        var pointColor = style.pointColor || colors.seriesColors[0] || colors.accent;
        var axisColor = style.axisColor || colors.muted;
        var gridColor = style.gridColor || colors.track;
        var fontSize = design.fontSizePercent(style.axisFontSizePercent, compact ? 10 : 12);
        var visualMap = colorMapOptions(style, roles, points, colors, top, compact, hasTitle);
        var colorDimension = { x: 0, y: 1, size: 2 }[style.colorMapDimension];
        if (colorDimension === undefined) { colorDimension = 1; }
        var showColorLegend = visualMap && visualMap.show;

        return {
            backgroundColor: bootstrap.mode === 'ipsview' && bootstrap.options.adaptToBackground === true
                ? 'transparent' : colors.background,
            ...design.animationOptions(style, { enabled: true, initialDuration: 350, updateDuration: 500 }),
            aria: { enabled: true, decal: { show: false }, description: [xRole.label, yRole.label,
                sizeRole && sizeRole.label].filter(Boolean).join(', ') },
            title: {
                show: hasTitle, text: String(scatter.title || ''), left: 'center', top: top,
                textStyle: { color: style.titleColor || colors.text,
                    fontSize: design.fontSizePercent(style.titleFontSizePercent, 18) }
            },
            visualMap: visualMap || undefined,
            grid: { left: compact ? 65 : 86, right: zoomEnabled ? (compact ? 56 : 70) : (compact ? 20 : 38),
                top: top + (hasTitle ? 48 : 18) + (showColorLegend ? 30 : 0),
                bottom: zoomEnabled ? (compact ? 78 : 94) : (compact ? 54 : 70),
                containLabel: true },
            dataZoom: zoom.cartesianOptions(zoomEnabled, bootstrap.mode, compact),
            xAxis: {
                type: 'value', name: String(xRole.label || 'X') + (xRole.unit ? ' (' + xRole.unit + ')' : ''),
                scale: style.xAxisRangeMode !== 'zero',
                min: style.xAxisRangeMode === 'manual' ? Number(style.xAxisMinimum) : undefined,
                max: style.xAxisRangeMode === 'manual' ? Number(style.xAxisMaximum) : undefined,
                nameLocation: 'middle', nameGap: compact ? 25 : 34,
                nameTextStyle: { color: axisColor, fontSize: fontSize },
                axisLabel: { color: axisColor, fontSize: fontSize },
                axisLine: { lineStyle: { color: axisColor } },
                splitLine: { show: style.showGrid === true, lineStyle: { color: gridColor } }
            },
            yAxis: {
                type: 'value', name: String(yRole.label || 'Y') + (yRole.unit ? ' (' + yRole.unit + ')' : ''),
                scale: style.yAxisRangeMode !== 'zero',
                min: style.yAxisRangeMode === 'manual' ? Number(style.yAxisMinimum) : undefined,
                max: style.yAxisRangeMode === 'manual' ? Number(style.yAxisMaximum) : undefined,
                nameLocation: 'middle', nameGap: compact ? 38 : 52,
                nameTextStyle: { color: axisColor, fontSize: fontSize },
                axisLabel: { color: axisColor, fontSize: fontSize },
                axisLine: { lineStyle: { color: axisColor } },
                splitLine: { show: style.showGrid === true, lineStyle: { color: gridColor } }
            },
            tooltip: {
                trigger: 'item', formatter: function (parameters) {
                    var value = parameters.value || [];
                    var date = new Date(Number(value[3]) * 1000).toLocaleString();
                    var lines = [date, escapeHTML(xRole.label) + ': ' + escapeHTML(formatValue(value[0], xRole)),
                        escapeHTML(yRole.label) + ': ' + escapeHTML(formatValue(value[1], yRole))];
                    if (sizeRole) {
                        lines.push(escapeHTML(sizeRole.label) + ': ' + escapeHTML(formatValue(value[2], sizeRole)));
                    }
                    return lines.join('<br>');
                }
            },
            series: [{
                id: 'scatter', type: 'scatter', name: String(scatter.title || ''),
                symbol: 'circle',
                symbolSize: function (value) {
                    return sizeRole ? pointSize(Number(value[2]), minimum, maximum, base, large) : base;
                },
                itemStyle: visualMap ? { opacity: 0.8 } : { color: pointColor, opacity: 0.8 },
                labelLayout: { hideOverlap: true },
                label: {
                    show: style.showValues === true,
                    ...design.valueLabel(style, 'top', ['top', 'bottom', 'left', 'right', 'inside',
                        'insideTop', 'insideBottom', 'insideLeft', 'insideRight'], 5),
                    color: style.valueColor || colors.text,
                    fontSize: design.fontSizePercent(style.valueFontSizePercent, 11),
                    formatter: function (parameters) {
                        var value = parameters.value || [];
                        var content = style.valueLabelContent || 'y';
                        if (content === 'x') { return formatValue(value[0], xRole); }
                        if (content === 'both') {
                            return formatValue(value[0], xRole) + ' / ' + formatValue(value[1], yRole);
                        }
                        return formatValue(value[1], yRole);
                    }
                },
                data: points.map(function (point) {
                    var value = [Number(point[1]), Number(point[2]),
                        sizeRole ? Number(point[3]) : null, Number(point[0])];
                    if (visualMap) {
                        var rawColorValue = Number(value[colorDimension]);
                        value.push(Math.max(visualMap.min, Math.min(visualMap.max, rawColorValue)));
                    }
                    return { id: 'interval-' + point[0], value: value };
                })
            }]
        };
    }

    function displayError(message) {
        if (chart) { chart.clear(); }
        chartElement.hidden = true;
        emptyElement.hidden = true;
        warningElement.hidden = true;
        errorElement.textContent = translate(message || loadError);
        errorElement.hidden = false;
    }

    function render(state) {
        if (state && state.status === 'error' && state.error === loadError
            && chart && currentState && currentState.status === 'ready') {
            refreshFailures++;
            if (refreshFailures > 1) {
                warningElement.textContent = translate(
                    'Scatter data could not be refreshed. Showing the last available values.'
                );
                warningElement.hidden = false;
            }
            return;
        }
        refreshFailures = 0;
        var previousState = currentState;
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
        var points = Array.isArray(state.chart.points) ? state.chart.points : [];
        emptyElement.hidden = points.length > 0;
        emptyElement.textContent = translate('No common completed archive intervals.');
        var wasHidden = chartElement.hidden;
        chartElement.hidden = points.length === 0;
        warningElement.textContent = translate('The selected range was truncated by the point budget.');
        warningElement.hidden = state.chart.truncated !== true;
        if (points.length === 0) { return; }
        var palettes = bootstrap.options && bootstrap.options.echartsThemes || {};
        var theme = Object.prototype.hasOwnProperty.call(palettes, state.chart.theme) ? state.chart.theme : 'auto';
        var savedZoom = chart && currentTheme === theme && previousState
            && sameZoomContext(previousState.chart, state.chart)
            ? zoom.captureCartesian(chart, previousState.chart && previousState.chart.range, state.chart.range) : null;
        if (chart && currentTheme !== theme) { chart.dispose(); chart = null; }
        if (!chart) {
            chart = window.echarts.init(chartElement, theme === 'auto' ? null : theme, { renderer: 'canvas' });
            currentTheme = theme;
        }
        if (wasHidden) { chart.resize(); }
        zoom.applyCartesian(chart, buildOption(state.chart, theme), savedZoom);
    }

    window.handleMessage = function (message) {
        var state = typeof message === 'string' ? JSON.parse(message) : message;
        if (state) { render(state); }
    };
    if (bootstrap.mode === 'ipsview') {
        zoom.attachIPSViewWheelCartesian(chartElement, function () { return chart; }, function () {
            var style = currentState && currentState.chart && currentState.chart.scatter
                && currentState.chart.scatter.style;
            return style && style.enableZoom === true;
        });
    }
    if (window.ResizeObserver) {
        new ResizeObserver(function () {
            if (design.resizeChartIfNeeded(chart, chartElement)) { render(currentState); }
        }).observe(chartElement);
    } else {
        window.addEventListener('resize', function () {
            if (design.resizeChartIfNeeded(chart, chartElement)) { render(currentState); }
        });
    }
    window.addEventListener('beforeunload', function () { if (chart) { chart.dispose(); } });
    render(currentState);
}());
