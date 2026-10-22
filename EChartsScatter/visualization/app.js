(function () {
    'use strict';

    var bootstrap = window.SYMC_VISUALIZATION || {};
    var design = window.SYMC_ECHARTS_DESIGN;
    var zoom = window.SymconEChartsZoom;
    var chartElement = document.getElementById('echarts-scatter-chart');
    var emptyElement = document.getElementById('echarts-scatter-empty');
    var warningElement = document.getElementById('echarts-scatter-warning');
    var selectionElement = document.getElementById('echarts-scatter-selection');
    var errorElement = document.getElementById('echarts-scatter-error');
    var chart = null;
    var currentState = bootstrap.state || null;
    var currentTheme = null;
    var refreshFailures = 0;
    var groupSelection = window.SYMC_ECHARTS_SERIES_SELECTION.create();
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
        value = Math.max(minimum, Math.min(maximum, value));
        var span = maximum - minimum;
        var ratio = Number.isFinite(span) ? (value - minimum) / span
            : (value / 2 - minimum / 2) / (maximum / 2 - minimum / 2);
        return base + (large - base) * Math.max(0, Math.min(1, ratio));
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
            && (Array.isArray(previous.groups) && Array.isArray(next.groups)
                ? JSON.stringify(previous.groups.map(function (g) { return g.id; }).sort())
                    === JSON.stringify(next.groups.map(function (g) { return g.id; }).sort())
                : JSON.stringify(previous.roles || {}) === JSON.stringify(next.roles || {}))
            && (!previous.range || !next.range || (previous.range.aggregationLevel === next.range.aggregationLevel
                && previous.range.pointBudget === next.range.pointBudget));
    }

    function pointLabelText(value, content, xRole, yRole) {
        if (content === 'x') { return formatValue(value[0], xRole); }
        if (content === 'both') {
            return formatValue(value[0], xRole) + ' / ' + formatValue(value[1], yRole);
        }
        return formatValue(value[1], yRole);
    }

    function adaptiveLabelVisibility(points, style, xRole, yRole, grid, label, fontSize, pointSize) {
        var xValues = points.map(function (point) { return Number(point[1]); });
        var yValues = points.map(function (point) { return Number(point[2]); });
        var xMinimum = style.xAxisRangeMode === 'manual' ? Number(style.xAxisMinimum)
            : Math.min.apply(null, xValues);
        var xMaximum = style.xAxisRangeMode === 'manual' ? Number(style.xAxisMaximum)
            : Math.max.apply(null, xValues);
        var yMinimum = style.yAxisRangeMode === 'manual' ? Number(style.yAxisMinimum)
            : Math.min.apply(null, yValues);
        var yMaximum = style.yAxisRangeMode === 'manual' ? Number(style.yAxisMaximum)
            : Math.max.apply(null, yValues);
        if (style.xAxisRangeMode === 'zero') {
            xMinimum = Math.min(0, xMinimum); xMaximum = Math.max(0, xMaximum);
        }
        if (style.yAxisRangeMode === 'zero') {
            yMinimum = Math.min(0, yMinimum); yMaximum = Math.max(0, yMaximum);
        }
        if (xMinimum === xMaximum) { xMinimum -= 1; xMaximum += 1; }
        if (yMinimum === yMaximum) { yMinimum -= 1; yMaximum += 1; }
        var width = Math.max(1, chartElement.clientWidth - grid.left - grid.right);
        var height = Math.max(1, chartElement.clientHeight - grid.top - grid.bottom);
        var distance = Math.max(0, Number(label.distance) || 0) + pointSize / 2;
        var occupied = [];
        return points.map(function (point) {
            var xValue = Number(point[1]);
            var yValue = Number(point[2]);
            if (xValue < xMinimum || xValue > xMaximum || yValue < yMinimum || yValue > yMaximum) {
                return false;
            }
            var x = grid.left + (xValue - xMinimum) / (xMaximum - xMinimum) * width;
            var y = grid.top + (yMaximum - yValue) / (yMaximum - yMinimum) * height;
            var text = pointLabelText([point[1], point[2]], style.valueLabelContent, xRole, yRole);
            var labelWidth = text.length * fontSize * 0.65 + 8;
            var labelHeight = fontSize * 1.5 + 4;
            var left = x - labelWidth / 2;
            var top = y - distance - labelHeight;
            if (label.position === 'bottom' || label.position === 'insideBottom') {
                top = y + distance;
            } else if (label.position === 'left' || label.position === 'insideLeft') {
                left = x - distance - labelWidth; top = y - labelHeight / 2;
            } else if (label.position === 'right' || label.position === 'insideRight') {
                left = x + distance; top = y - labelHeight / 2;
            } else if (label.position === 'inside') {
                top = y - labelHeight / 2;
            }
            var rect = { left: left - 4, right: left + labelWidth + 4,
                top: top - 4, bottom: top + labelHeight + 4 };
            var overlaps = occupied.some(function (other) {
                return rect.left < other.right && rect.right > other.left
                    && rect.top < other.bottom && rect.bottom > other.top;
            });
            if (!overlaps) { occupied.push(rect); }
            return !overlaps;
        });
    }

    function buildSingleOption(model, theme) {
        var colors = colorsFor(theme);
        var scatter = model.scatter || {};
        var style = scatter.style || {};
        var roles = model.roles || {};
        var xRole = roles.x || { label: 'X', unit: '', decimals: 1 };
        var yRole = roles.y || { label: 'Y', unit: '', decimals: 1 };
        var sizeRole = roles.size || null;
        var points = Array.isArray(model.points) ? model.points : [];
        var titleSize = design.fontSizePercent(style.titleFontSizePercent, 18);
        var titleLayout = design.chartTitleLayout(bootstrap, scatter.title, titleSize);
        var hasTitle = titleLayout.show;
        var compact = chartElement.clientWidth < 420 || chartElement.clientHeight < 280;
        var zoomEnabled = style.enableZoom === true;
        var brushEnabled = style.enableBrush === true;
        var base = Math.max(4, Math.min(60, Number(style.pointSize) || 12));
        var large = Math.max(base, Math.min(100, Number(style.bubbleMaxSize) || 48));
        var sizeValues = sizeRole ? points.map(function (point) { return Number(point[3]); }).filter(Number.isFinite) : [];
        var minimum = sizeValues.length ? Math.min.apply(null, sizeValues) : 0;
        var maximum = sizeValues.length ? Math.max.apply(null, sizeValues) : 0;
        if (style.bubbleRangeMode === 'manual') {
            minimum = Number(style.bubbleMinimum);
            maximum = Number(style.bubbleMaximum);
        }
        var pointColor = style.pointColor || colors.seriesColors[0] || colors.accent;
        var axisColor = style.axisColor || colors.muted;
        var gridColor = style.gridColor || colors.track;
        var fontSize = design.fontSizePercent(style.axisFontSizePercent, compact ? 10 : 12);
        var visualMap = colorMapOptions(style, roles, points, colors, titleLayout.contentTop, compact, hasTitle);
        var colorDimension = { x: 0, y: 1, size: 2 }[style.colorMapDimension];
        if (colorDimension === undefined) { colorDimension = 1; }
        var showColorLegend = visualMap && visualMap.show;
        var brushToolSize = Math.max(18, Math.min(28,
            Math.round(Math.min(chartElement.clientWidth, chartElement.clientHeight) / 20)));
        var toolboxTop = titleLayout.contentTop + (hasTitle ? 12 : 0) + (showColorLegend ? 30 : 0);
        var gridTop = titleLayout.contentTop + (hasTitle ? 26 : 18) + (showColorLegend ? 30 : 0);
        if (brushEnabled) { gridTop = Math.max(gridTop, toolboxTop + brushToolSize + 8); }
        var grid = { left: compact ? 65 : 86,
            right: zoomEnabled ? (compact ? 56 : 70) : (compact ? 20 : 38),
            top: gridTop, bottom: zoomEnabled ? (compact ? 78 : 94) : (compact ? 54 : 70),
            containLabel: true };
        var valueLabel = design.valueLabel(style, 'top', ['top', 'bottom', 'left', 'right', 'inside',
            'insideTop', 'insideBottom', 'insideLeft', 'insideRight'], 5);
        var valueFontSize = design.fontSizePercent(style.valueFontSizePercent, 11);
        var visibleLabels = style.showValues === true && style.adaptiveValueLabels === true
            ? adaptiveLabelVisibility(points, style, xRole, yRole, grid, valueLabel, valueFontSize, base) : null;

        return {
            backgroundColor: bootstrap.mode === 'ipsview' && bootstrap.options.adaptToBackground === true
                ? 'transparent' : colors.background,
            ...design.animationOptions(style, { enabled: true, initialDuration: 350, updateDuration: 500 }),
            aria: { enabled: true, decal: { show: false }, description: [xRole.label, yRole.label,
                sizeRole && sizeRole.label].filter(Boolean).join(', ') },
            title: {
                show: hasTitle, text: String(scatter.title || ''), left: 'center', top: titleLayout.top,
                textStyle: { color: style.titleColor || colors.text, fontSize: titleSize }
            },
            visualMap: visualMap || undefined,
            toolbox: brushEnabled ? {
                show: true, right: zoomEnabled ? (compact ? 56 : 70) : (compact ? 20 : 38),
                top: toolboxTop, padding: 2,
                itemSize: brushToolSize, itemGap: 8,
                iconStyle: { borderColor: colors.text, borderWidth: 2 },
                emphasis: { iconStyle: { borderColor: colors.accent, borderWidth: 2 } },
                feature: { brush: { type: ['rect', 'clear'], title: {
                    rect: translate('Select points'), clear: translate('Clear selection')
                } } }
            } : undefined,
            brush: brushEnabled ? {
                toolbox: ['rect', 'clear'], brushMode: 'single', transformable: false,
                xAxisIndex: 0, yAxisIndex: 0,
                brushStyle: { borderWidth: 1, borderColor: colors.accent,
                    color: 'rgba(85, 203, 181, 0.12)' },
                outOfBrush: { opacity: 0.18 }
            } : undefined,
            grid: grid,
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
                    ...valueLabel,
                    color: style.valueColor || colors.text,
                    fontSize: valueFontSize,
                    formatter: function (parameters) {
                        var value = parameters.value || [];
                        return pointLabelText(value, style.valueLabelContent, xRole, yRole);
                    }
                },
                data: points.map(function (point, index) {
                    var value = [Number(point[1]), Number(point[2]),
                        sizeRole ? Number(point[3]) : null, Number(point[0])];
                    if (visualMap) {
                        var rawColorValue = Number(value[colorDimension]);
                        value.push(Math.max(visualMap.min, Math.min(visualMap.max, rawColorValue)));
                    }
                    var item = { id: 'interval-' + point[0], value: value };
                    if (visibleLabels) { item.label = { show: visibleLabels[index] }; }
                    return item;
                })
            }]
        };
    }

    function buildOption(model, theme) {
        if (!Array.isArray(model.groups)) { return buildSingleOption(model, theme); }
        var groups = model.groups;
        var points = groups.reduce(function (all, g) { return all.concat(g.points); }, []);
        var roles = Object.assign({}, model.roles);
        var sized = groups.find(function (g) { return g.roles.size; });
        if (sized) { roles.size = sized.roles.size; }
        var common = buildSingleOption(Object.assign({}, model, { points: points, roles: roles }), theme);
        var style = model.scatter.style || {};
        var colors = colorsFor(theme);
        var selected = groupSelection.reconcile(groups);
        var fontSize = design.fontSizePercent(style.axisFontSizePercent, 12);
        var reserve = Math.ceil(fontSize * 1.5 + 16);
        common.grid.bottom += reserve;
        common.dataZoom.forEach(function (entry) {
            if (entry.id === 'x-slider') { entry.bottom += reserve; }
        });
        var options = groups.map(function (g) {
            return buildSingleOption(Object.assign({}, model, { points: g.points, roles: g.roles }), theme);
        });
        common.aria.description += '. ' + groups.map(function (g) { return g.label || translate('Comparison'); }).join(', ');
        var bubbleSize = common.series[0].symbolSize;
        var visibility = style.showValues === true && style.adaptiveValueLabels === true
            ? adaptiveLabelVisibility(points, style, roles.x, roles.y, common.grid, common.series[0].label,
                design.fontSizePercent(style.valueFontSizePercent, 11), Math.max(4, Math.min(60, Number(style.pointSize) || 12))) : null;
        var offset = 0;
        common.series = options.map(function (option, index) {
            var group = groups[index];
            var series = option.series[0];
            series.id = group.id;
            series.name = group.id;
            if (group.roles.size) { series.symbolSize = bubbleSize; }
            if (visibility) {
                series.data.forEach(function (item, i) { item.label = { show: visibility[offset + i] }; });
            }
            offset += group.points.length;
            if (!common.visualMap) {
                series.itemStyle.color = style.pointColor || colors.seriesColors[group.colorIndex % colors.seriesColors.length] || colors.accent;
            }
            return series;
        });
        common.tooltip.formatter = function (parameters) {
            var index = Number.isInteger(parameters.seriesIndex) ? parameters.seriesIndex : 0;
            var group = groups[index];
            return group ? escapeHTML(group.label || translate('Comparison')) + '<br>'
                + options[index].tooltip.formatter(parameters) : '';
        };
        if (common.visualMap) { common.visualMap.seriesIndex = groups.map(function (_, i) { return i; }); }
        common.legend = { type: 'scroll', bottom: 4, left: 12, right: 12,
            selected: Object.assign({}, selected), scrollDataIndex: groupSelection.scrollDataIndex(),
            data: groups.map(function (g) { return g.id; }),
            formatter: function (id) {
                var group = groups.find(function (g) { return g.id === id; });
                return group ? group.label || translate('Comparison') : '';
            },
            textStyle: { color: style.axisColor || colors.text, fontSize: fontSize,
                width: Math.max(50, Math.min(180, chartElement.clientWidth / 3)), overflow: 'truncate' },
            pageTextStyle: { color: colors.text, fontSize: fontSize }
        };
        return common;
    }

    function displayError(message) {
        if (chart) { chart.clear(); }
        selectionElement.hidden = true;
        chartElement.hidden = true;
        emptyElement.hidden = true;
        warningElement.hidden = true;
        errorElement.textContent = translate(message || loadError);
        errorElement.hidden = false;
    }

    function updateSelection(event) {
        var batch = event && event.batch && event.batch[0];
        var model = currentState && currentState.chart;
        var groups = model && (model.groups || [{ points: model.points }]);
        if (!batch || !Array.isArray(batch.areas) || batch.areas.length === 0
            || !Array.isArray(batch.selected) || !groups) {
            selectionElement.hidden = true;
            return;
        }
        var matching = [];
        batch.selected.forEach(function (selected) {
            var group = groups[selected.seriesIndex || 0];
            if (!group || !Array.isArray(selected.dataIndex) || !Array.isArray(group.points)) { return; }
            selected.dataIndex.forEach(function (index) {
                if (Array.isArray(group.points[index])) { matching.push(group.points[index]); }
            });
        });
        if (matching.length === 0) {
            selectionElement.hidden = true;
            return;
        }
        var roles = currentState.chart.roles || {};
        var xRole = roles.x || { label: 'X', unit: '', decimals: 1 };
        var yRole = roles.y || { label: 'Y', unit: '', decimals: 1 };
        var xs = matching.map(function (point) { return Number(point[1]); });
        var ys = matching.map(function (point) { return Number(point[2]); });
        selectionElement.textContent = translate('Selected points') + ': ' + matching.length + ' · '
            + xRole.label + ': ' + formatValue(Math.min.apply(null, xs), xRole) + '–'
            + formatValue(Math.max.apply(null, xs), xRole) + ' · '
            + yRole.label + ': ' + formatValue(Math.min.apply(null, ys), yRole) + '–'
            + formatValue(Math.max.apply(null, ys), yRole);
        selectionElement.hidden = false;
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
        var points = Array.isArray(state.chart.groups)
            ? state.chart.groups.reduce(function (all, g) { return all.concat(g.points); }, [])
            : (Array.isArray(state.chart.points) ? state.chart.points : []);
        selectionElement.hidden = true;
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
        if (chart) { groupSelection.capture(chart); }
        if (chart && currentTheme !== theme) { chart.dispose(); chart = null; }
        if (!chart) {
            chart = window.echarts.init(chartElement, theme === 'auto' ? null : theme, { renderer: 'canvas' });
            chart.on('brushselected', updateSelection);
            currentTheme = theme;
        }
        if (wasHidden) { chart.resize(); }
        var option = buildOption(state.chart, theme);
        selectionElement.style.setProperty('--scatter-selection-top', (option.grid.top + 8) + 'px');
        selectionElement.style.setProperty('--scatter-selection-left', (option.grid.left + 8) + 'px');
        zoom.applyCartesian(chart, option, savedZoom);
    }

    window.handleMessage = function (message) {
        var state = design.parseVisualizationState(message);
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
