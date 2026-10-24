(function () {
    'use strict';

    var bootstrap = window.SYMC_VISUALIZATION || {};
    var design = window.SYMC_ECHARTS_DESIGN;
    var rootElement = document.getElementById('echarts-radar-root');
    var chartElement = document.getElementById('echarts-radar-chart');
    var emptyElement = document.getElementById('echarts-radar-empty');
    var errorElement = document.getElementById('echarts-radar-error');
    var warningElement = document.getElementById('echarts-radar-warning');
    var chart = null;
    var currentState = bootstrap.state || null;
    var currentTheme = null;
    var groupSelection = window.SYMC_ECHARTS_SERIES_SELECTION.create();
    var selectionEnabled = null;
    var colorIndices = Object.create(null);
    var nextColorIndex = 0;
    var transientWarning = false;

    var tileHeaderInset = (bootstrap.options && bootstrap.options.tileHeaderInset) || 0;
    var chartBootstrap = tileHeaderInset ? { mode: 'symcon', options: { tileHeaderVisible: false } } : bootstrap;
    chartElement.style.top = tileHeaderInset + 'px';
    chartElement.style.height = tileHeaderInset ? 'calc(100% - ' + tileHeaderInset + 'px)' : '100%';

    function translate(text) { return (bootstrap.translations || {})[text] || text; }
    var retainOnTransientError = design.createTransientFailureRetainer(
        chartElement, warningElement, translate, 'The Radar values could not be loaded.'
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
    function buildOption(model, theme) {
        var colors = colorsFor(theme);
        var radar = model.radar || {};
        var style = radar.style || {};
        var axes = radar.axes || [];
        var groups = radar.data || [];
        var incomplete = groups.some(function (item) { return (item.missingAxes || []).length > 0; });
        var selectable = radar.enableGroupSelection === true;
        if (selectionEnabled !== selectable) {
            groupSelection = window.SYMC_ECHARTS_SERIES_SELECTION.create();
            selectionEnabled = selectable;
        }
        var selected = selectable ? groupSelection.reconcile(groups) : null;
        var showLegend = style.showLegend !== false || selectable;
        var expanded = selectable || groups.length > 8;
        var seriesColors = palette(theme).seriesColors || ['#5070DD', '#B6D634', '#505372', '#FF994D', '#0CA8DF', '#FFD10A', '#FB628B', '#785DB0'];
        groups.forEach(function (item) {
            if (!Object.prototype.hasOwnProperty.call(colorIndices, item.id)) { colorIndices[item.id] = nextColorIndex++; }
        });
        var compact = (chartElement.clientWidth || 1) < 430;
        var titleSize = design.fontSizePercent(style.titleFontSizePercent, 18);
        var titleLayout = design.chartTitleLayout(chartBootstrap, radar.title, titleSize);
        var axisSize = design.fontSizePercent(style.axisFontSizePercent, compact ? 10 : 12);
        var width = chartElement.clientWidth || 1;
        var height = chartElement.clientHeight || 1;
        var legendRows = !showLegend ? 0 : expanded ? 1 : Math.ceil(groups.length / (compact ? 2 : 4));
        var legendSize = design.fontSizePercent(style.legendFontSizePercent, 12);
        var legendTop = style.legendPosition === 'top';
        var legendSpace = legendRows * (legendSize + 10) + (selectable ? legendSize + 12 : 0);
        var warningVisible = radar.clippedCount > 0 || incomplete || transientWarning;
        // Group controls must remain reachable even when a multi-line warning is displayed.
        warningElement.style.bottom = selectable && showLegend && !legendTop ? (legendSize + 26) + 'px' : '0.65rem';
        var warningSpace = (selectable || incomplete) && warningVisible ? Math.max(32, warningElement.offsetHeight || 0) + 12 : radar.clippedCount > 0 ? 26 : 0;
        var bottom = 22 + (legendTop ? 0 : legendSpace) + warningSpace;
        var top = titleLayout.contentTop + axisSize * 2 + 20 + (legendTop ? legendSpace : 0);
        var axisWidth = Math.min(compact ? 90 : 150, Math.max(30, width / 4));
        var nameGap = style.axisNameGap == null ? 15 : style.axisNameGap;
        var radius = Math.max(1, Math.min(width / 2 - axisWidth - nameGap - 12, (height - top - bottom) / 2 - axisSize * 2));
        function rawValue(value, axis) {
            if (value === null || value === undefined) { return translate('Not configured'); }
            return Number(value).toLocaleString(bootstrap.language || undefined, { maximumFractionDigits: axis.Decimals })
                + (axis.Unit ? ' ' + axis.Unit : '');
        }
        function axisName(axis) {
            // Put the direction first so truncated names still disclose inversion.
            return (axis.Inverse === true ? translate('(inverse)') + ' ' : '') + axis.Label;
        }
        function partial(item) { return (item.missingAxes || []).length > 0; }
        function displayName(item) { return item.name + (partial(item) ? ' ' + translate('(incomplete)') : ''); }
        function groupColor(item) {
            var overrides = style.groupColors || {};
            return (Object.prototype.hasOwnProperty.call(overrides, item.name) ? overrides[item.name] : '')
                || seriesColors[colorIndices[item.id] % seriesColors.length];
        }
        function legendName(item) { return selectable ? item.id : item.name; }
        var option = {
            color: seriesColors,
            backgroundColor: bootstrap.mode === 'ipsview' && bootstrap.options.adaptToBackground === true
                ? 'transparent' : colors.background,
            ...design.animationOptions(style, { enabled: true, initialDuration: 350, updateDuration: 500 }),
            aria: { enabled: true, ...(incomplete || selectable || axes.some(function (axis) { return axis.Inverse === true; }) ? { label: { description: String(radar.title || 'Radar') + '. '
                + (groups.some(function (item) { return !selectable || selected[item.id] !== false; })
                    ? groups.filter(function (item) { return !selectable || selected[item.id] !== false; }).map(function (item) {
                        return displayName(item) + ': ' + axes.map(function (axis, index) {
                            return axisName(axis) + ': ' + rawValue((item.values || [])[index], axis);
                        }).join(', ');
                    }).join('; ')
                    : translate('No groups selected')) } } : {}) },
            title: { show: titleLayout.show, text: String(radar.title || ''), left: 'center', top: titleLayout.top,
                textStyle: { color: style.titleColor || colors.text, fontSize: titleSize, fontWeight: style.titleBold === false ? 'normal' : 'bold' } },
            legend: { show: showLegend, type: 'scroll',
                ...(selectable ? { selected: Object.assign({}, selected), scrollDataIndex: groupSelection.scrollDataIndex(),
                    selector: [{ type: 'all', title: translate('All') }, { type: 'inverse', title: translate('Inverse') }],
                    selectorPosition: 'start', selectorLabel: { color: style.legendColor || colors.text, fontSize: legendSize },
                    data: groups.map(function (item) {
                        return { name: item.id, tooltip: { formatter: function () { return escapeHTML(displayName(item)); } } };
                    }),
                    formatter: function (id) { var item = groups.find(function (group) { return group.id === id; }); return item ? displayName(item) : ''; }
                } : {}),
                ...(legendTop ? { top: titleLayout.contentTop + 4 } : { bottom: !selectable && radar.clippedCount > 0 ? 32 : 8 }),
                left: 12, right: 12, pageTextStyle: { color: style.legendColor || colors.text, fontSize: legendSize },
                pageIconColor: style.legendColor || colors.text,
                textStyle: { color: style.legendColor || style.axisColor || colors.text, fontSize: legendSize, width: compact ? 100 : 160,
                    overflow: 'truncate' }, tooltip: { show: true } },
            tooltip: { show: style.showTooltip !== false, trigger: 'item', confine: true,
                formatter: function (parameter) {
                    var item = parameter.data || {};
                    return escapeHTML(item.displayName || item.name || '') + axes.map(function (axis, index) {
                        return '<br>' + escapeHTML(axisName(axis)) + ': ' + escapeHTML(rawValue((item.rawValues || [])[index], axis));
                    }).join('');
                } },
            radar: { center: [width / 2, top + (height - top - bottom) / 2], radius: radius,
                shape: style.shape || 'polygon', startAngle: style.startAngle == null ? 90 : style.startAngle,
                clockwise: style.clockwise === true, splitNumber: style.splitNumber || 5,
                axisNameGap: nameGap,
                axisName: { show: style.showAxisNames !== false, color: style.axisColor || colors.text, fontSize: axisSize,
                    width: axisWidth, overflow: 'truncate', lineHeight: axisSize * 1.25, fontWeight: style.axisLabelBold === true ? 'bold' : 'normal' },
                axisLabel: { show: style.showAxisValues === true, formatter: '{value}%', color: style.axisColor || colors.muted },
                axisLine: { show: style.showAxisLines !== false, lineStyle: { color: style.gridColor || colors.border,
                    width: style.axisLineWidth == null ? 1 : style.axisLineWidth,
                    opacity: style.axisLineOpacityPercent == null ? 0.5 : style.axisLineOpacityPercent / 100 } },
                splitLine: { show: style.showSplitLines !== false, lineStyle: { color: style.gridColor || colors.border,
                    width: style.splitLineWidth == null ? 1 : style.splitLineWidth, type: style.splitLineType || 'solid',
                    opacity: style.splitLineOpacityPercent == null ? 0.4 : style.splitLineOpacityPercent / 100 } },
                splitArea: { show: style.showSplitArea !== false, areaStyle: {
                    color: [style.splitAreaColor1 || 'rgba(128,128,128,0.03)', style.splitAreaColor2 || 'rgba(128,128,128,0.09)'],
                    opacity: style.splitAreaOpacityPercent == null ? 1 : style.splitAreaOpacityPercent / 100 } },
                indicator: axes.map(function (axis) {
                    var inner = axis.Inverse === true ? axis.Maximum : axis.Minimum;
                    var outer = axis.Inverse === true ? axis.Minimum : axis.Maximum;
                    return { name: axisName(axis) + (style.showAxisRange === false ? '' : '\n' + rawValue(inner, axis) + ' … ' + rawValue(outer, axis)), min: 0, max: 100 };
                }) },
            series: [{ id: 'radar', type: 'radar', symbol: style.symbol || 'circle', symbolSize: style.symbolSize == null ? 4 : style.symbolSize,
                symbolRotate: Number(style.symbolRotate) || 0,
                labelLayout: { hideOverlap: style.hideOverlappingValueLabels !== false },
                itemStyle: { borderWidth: Number(style.symbolBorderWidth) || 0,
                    ...(style.symbolBorderColor ? { borderColor: style.symbolBorderColor } : {}),
                    opacity: style.symbolOpacityPercent == null ? 1 : style.symbolOpacityPercent / 100 },
                label: { show: style.showValues === true, opacity: 1, position: style.valueLabelPosition || 'top',
                    distance: style.valueLabelDistance == null ? 5 : style.valueLabelDistance,
                    color: style.valueColor || colors.text, fontSize: design.fontSizePercent(style.valueFontSizePercent, compact ? 10 : 12),
                    fontWeight: style.valueLabelBold === true ? 'bold' : 'normal',
                    formatter: function (parameter) {
                        var index = parameter.dimensionIndex;
                        return axes[index] ? rawValue((parameter.data.rawValues || [])[index], axes[index]) : '';
                    } },
                lineStyle: { width: style.lineWidth == null ? 2 : style.lineWidth,
                    type: style.lineType || 'solid',
                    opacity: style.lineOpacityPercent == null ? 1 : style.lineOpacityPercent / 100 },
                areaStyle: { opacity: style.areaOpacityPercent == null ? 0.25 : style.areaOpacityPercent / 100,
                    shadowBlur: Number(style.areaShadowBlur) || 0, shadowColor: style.areaShadowColor || 'rgba(0,0,0,0.35)' },
                emphasis: { focus: style.hoverFocus === 'none' ? 'none' : 'self', disabled: style.hoverFocus === 'none',
                    label: { show: style.showValues === true } },
                data: groups.filter(function (item) { return !partial(item); }).map(function (item) {
                    var overrides = style.groupColors || {};
                    var color = Object.prototype.hasOwnProperty.call(overrides, item.name) ? overrides[item.name] : '';
                    if (!color && (expanded || incomplete)) { color = seriesColors[colorIndices[item.id] % seriesColors.length]; }
                    return { id: item.id, name: selectable ? item.id : item.name, displayName: item.name, value: item.percentValues, rawValues: item.values,
                        ...(color ? { itemStyle: { color: color }, lineStyle: { color: color }, areaStyle: { color: color } } : {}) };
                }) }]
        };
        if (incomplete) {
            option.legend.data = groups.map(function (item) {
                return {name: legendName(item), itemStyle: {color: groupColor(item)},
                    tooltip: {formatter: function () { return escapeHTML(displayName(item)); }}};
            });
            option.legend.formatter = function (name) {
                var item = groups.find(function (group) { return legendName(group) === name; });
                return item ? displayName(item) : '';
            };
            var occupied = [];
            groups.filter(partial).forEach(function (item) {
                var color = groupColor(item);
                option.series.push({id: 'partial-' + item.id, name: legendName(item), type: 'custom', coordinateSystem: 'none',
                    itemStyle: {color: color}, animation: false,
                    data: [{id: item.id, name: legendName(item), displayName: displayName(item), value: [1], rawValues: item.values}],
                    renderItem: function () {
                        function point(index, position) {
                            var angle = (style.startAngle == null ? 90 : style.startAngle) * Math.PI / 180
                                + (style.clockwise === true ? -1 : 1) * index * 2 * Math.PI / axes.length;
                            return [option.radar.center[0] + radius * position * Math.cos(angle), option.radar.center[1] - radius * position * Math.sin(angle)];
                        }
                        var children = [];
                        (item.segments || []).forEach(function (edge) {
                            var from = point(edge[0], item.percentValues[edge[0]] / 100), to = point(edge[1], item.percentValues[edge[1]] / 100);
                            children.push({type: 'line', name: 'edge-' + edge.join('-'), shape: {x1: from[0], y1: from[1], x2: to[0], y2: to[1]},
                                style: {stroke: color, lineWidth: style.lineWidth == null ? 2 : style.lineWidth,
                                    lineDash: style.lineType === 'dashed' ? [6, 4] : style.lineType === 'dotted' ? [2, 3] : null,
                                    opacity: style.lineOpacityPercent == null ? 1 : style.lineOpacityPercent / 100}});
                        });
                        item.percentValues.forEach(function (value, index) {
                            var p = point(index, value === null ? 1 : value / 100);
                            if (value === null) {
                                children.push({type: 'text', name: 'missing-' + index, x: p[0], y: p[1], style: {
                                    text: '?', fill: color, fontSize: 14, fontWeight: 'bold', align: 'center', verticalAlign: 'middle'}});
                                return;
                            }
                            var size = style.symbolSize == null ? 4 : style.symbolSize;
                            var symbol = style.symbol || 'circle';
                            if (symbol === 'none' || size === 0) { symbol = 'emptyCircle'; size = 4; }
                            var r = size / 2, shape;
                            if (symbol === 'rect' || symbol === 'roundRect') { shape = {type: 'rect', shape: {x: -r, y: -r, width: size, height: size, r: symbol === 'roundRect' ? r * 0.45 : 0}}; }
                            else if (symbol === 'triangle' || symbol === 'diamond') {
                                shape = {type: 'polygon', shape: {points: symbol === 'triangle' ? [[0, -r], [r, r], [-r, r]] : [[0, -r], [r, 0], [0, r], [-r, 0]]}};
                            } else { shape = {type: 'circle', shape: {cx: 0, cy: 0, r: r}}; }
                            shape.name = 'value-' + index;
                            shape.style = {fill: symbol === 'emptyCircle' ? '#ffffff' : color, stroke: style.symbolBorderColor || color,
                                lineWidth: symbol === 'emptyCircle' ? Math.max(2, style.symbolBorderWidth || 0) : style.symbolBorderWidth || 0,
                                opacity: style.symbolOpacityPercent == null ? 1 : style.symbolOpacityPercent / 100};
                            children.push({type: 'group', x: p[0], y: p[1], rotation: (style.symbolRotate || 0) * Math.PI / 180, children: [shape]});
                            if (style.showValues === true && style.symbol !== 'none' && style.symbolSize !== 0) {
                                var text = rawValue(item.values[index], axes[index]);
                                var font = design.fontSizePercent(style.valueFontSizePercent, compact ? 10 : 12);
                                var distance = r + (style.valueLabelDistance == null ? 5 : style.valueLabelDistance);
                                var position = style.valueLabelPosition || 'top';
                                var x = p[0] + (position === 'left' ? -distance : position === 'right' ? distance : 0);
                                var y = p[1] + (position === 'top' ? -distance : position === 'bottom' ? distance : 0);
                                var align = position === 'left' ? 'right' : position === 'right' ? 'left' : 'center';
                                var verticalAlign = position === 'top' ? 'bottom' : position === 'bottom' ? 'top' : 'middle';
                                var textWidth = text.length * font * 0.65;
                                var left = x - (align === 'right' ? textWidth : align === 'center' ? textWidth / 2 : 0);
                                var top = y - (verticalAlign === 'bottom' ? font : verticalAlign === 'middle' ? font / 2 : 0);
                                var box = [left, top, left + textWidth, top + font];
                                if (style.hideOverlappingValueLabels !== false && occupied.some(function (b) { return box[0] < b[2] && box[2] > b[0] && box[1] < b[3] && box[3] > b[1]; })) { return; }
                                occupied.push(box);
                                children.push({type: 'text', name: 'label-' + index, silent: true, x: x, y: y, style: {text: text,
                                    fill: style.valueColor || colors.text, fontSize: font, fontWeight: style.valueLabelBold === true ? 'bold' : 'normal', align: align, verticalAlign: verticalAlign}});
                            }
                        });
                        return {type: 'group', emphasisDisabled: style.hoverFocus === 'none', focus: style.hoverFocus === 'none' ? 'none' : 'self', children: children};
                    }});
            });
        }
        return option;
    }
    function displayError(message) {
        if (chart) { chart.clear(); }
        chartElement.hidden = true;
        emptyElement.hidden = true;
        warningElement.hidden = true;
        errorElement.textContent = translate(message || 'The Radar values could not be loaded.');
        errorElement.hidden = false;
    }
    function render(state, keepWarning) {
        if (retainOnTransientError(state, currentState, chart)) {
            transientWarning = true;
            var hasIncomplete = currentState && currentState.chart && (currentState.chart.radar || {}).incompleteGroupCount > 0;
            if (hasIncomplete) {
                // Preserve the disclosure during the shared first-error grace period too.
                warningElement.textContent = (warningElement.hidden ? '' : warningElement.textContent + ' ')
                    + translate('Incomplete groups: ? marks unassigned axes. No area is drawn.');
                warningElement.hidden = false;
            }
            if ((selectionEnabled || hasIncomplete) && currentState && currentState.chart) {
                if (selectionEnabled) { groupSelection.capture(chart); }
                chart.setOption(buildOption(currentState.chart, currentTheme || 'auto'), true);
            }
            return;
        }
        if (!keepWarning) { transientWarning = false; }
        if (selectionEnabled) { groupSelection.capture(chart); }
        currentState = state;
        if (!state || state.status !== 'ready' || !state.chart) {
            displayError(state && state.error);
            return;
        }
        var model = state.chart;
        if (!window.echarts || typeof window.echarts.init !== 'function') {
            displayError('Apache ECharts could not be initialized.');
            return;
        }
        var wasHidden = chartElement.hidden;
        chartElement.hidden = false;
        errorElement.hidden = true;
        emptyElement.hidden = true;
        warningElement.hidden = !transientWarning;
        var warnings = [];
        if ((model.radar || {}).clippedCount > 0) { warnings.push(translate('Some values exceed their axis range and are visually limited.')); }
        if ((model.radar || {}).incompleteGroupCount > 0) { warnings.push(translate('Incomplete groups: ? marks unassigned axes. No area is drawn.')); }
        if (warnings.length > 0 && !transientWarning) {
            warningElement.textContent = warnings.join(' ');
            warningElement.hidden = false;
        }
        rootElement.style.backgroundColor = bootstrap.mode === 'ipsview' && bootstrap.options.adaptToBackground === true
            ? 'transparent' : colorsFor(normalizeTheme(model.theme || 'auto')).background;
        var theme = normalizeTheme(model.theme || 'auto');
        if (chart && currentTheme !== theme) { chart.dispose(); chart = null; }
        if (!chart) {
            chart = window.echarts.init(chartElement, theme === 'auto' ? null : theme, { renderer: 'canvas' });
            currentTheme = theme;
            ['legendselectchanged', 'legendselectall', 'legendinverseselect'].forEach(function (eventName) {
                chart.on(eventName, function () {
                    if (selectionEnabled && currentState) { render(currentState, true); }
                });
            });
        } else if (wasHidden) {
            chart.resize();
        }
        chart.setOption(buildOption(model, theme), true);
        if ((model.radar || {}).enableGroupSelection === true) {
            var selected = chart.getOption().legend[0].selected || {};
            emptyElement.textContent = translate('No groups selected');
            emptyElement.hidden = (model.radar.data || []).some(function (item) { return selected[item.id] !== false; });
        }
    }
    window.handleMessage = function (message) {
        var state = design.parseVisualizationState(message);
        if (state) { render(state); }
    };
    function resize() {
        if (!design.resizeChartIfNeeded(chart, chartElement)) { return; }
        if (currentState && currentState.status === 'ready') {
            if (selectionEnabled) { groupSelection.capture(chart); }
            chart.setOption(buildOption(currentState.chart, currentTheme || 'auto'), true);
        }
    }
    if (window.ResizeObserver) {
        new ResizeObserver(resize).observe(chartElement);
    } else {
        window.addEventListener('resize', resize);
    }
    window.addEventListener('beforeunload', function () { if (chart) { chart.dispose(); } });
    render(currentState);
}());


