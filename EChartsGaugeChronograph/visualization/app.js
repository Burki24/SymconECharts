(function () {
    'use strict';

    var bootstrap = window.SYMC_VISUALIZATION || {};
    var translations = bootstrap.translations || {};
    var themePalettes = bootstrap.options && bootstrap.options.echartsThemes
        ? bootstrap.options.echartsThemes
        : {};
    var chartElement = document.getElementById('echarts-gauge-chart');
    var errorElement = document.getElementById('echarts-gauge-error');
    var chart = null;
    var currentState = bootstrap.state || null;
    var currentTheme = null;
    var reduceMotion = window.matchMedia
        && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var pointerIcons = {
        needle: 'path://M0,-100 L7,10 L-7,10 Z',
        line: 'path://M-2,-100 L2,-100 L2,10 L-2,10 Z',
        arrow: 'path://M0,-100 L12,-72 L4,-72 L4,10 L-4,10 L-4,-72 L-12,-72 Z'
    };

    function translate(text) {
        return typeof translations[text] === 'string' ? translations[text] : text;
    }

    function parseState(message) {
        if (typeof message === 'string') {
            try {
                return JSON.parse(message);
            } catch (error) {
                return null;
            }
        }

        return message && typeof message === 'object' ? message : null;
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
        return Object.prototype.hasOwnProperty.call(themePalettes, theme) ? theme : 'auto';
    }

    function colorsFor(theme) {
        var palette = themePalettes[theme] || themePalettes.auto || {};
        if (theme !== 'auto') {
            return palette;
        }

        return {
            background: resolveColor('--symc-background', palette.background || '#151619'),
            text: resolveColor('--symc-text', palette.text || '#F4F5F7'),
            muted: resolveColor('--symc-muted', palette.muted || '#969AA2'),
            border: resolveColor('--symc-border', palette.border || '#A5A9B0'),
            track: resolveColor('--symc-surface', palette.track || '#34363B'),
            accent: resolveColor('--symc-accent', palette.accent || '#55CBB5')
        };
    }

    function clamp(value, minimum, maximum) {
        return Math.max(minimum, Math.min(maximum, value));
    }

    function formatValue(value, decimals, unit) {
        var formatted = Number(value).toLocaleString(undefined, {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        });

        return unit ? formatted + ' ' + unit : formatted;
    }

    function resolveGrid(count, width, height, hasTitle, headerInset) {
        var top = headerInset + (hasTitle ? Math.max(32, height * 0.1) : 4);
        var usableHeight = Math.max(1, height - top);
        var best = null;
        for (var columns = 1; columns <= Math.min(count, 4); columns += 1) {
            var rows = Math.ceil(count / columns);
            var cellWidth = width / columns;
            var cellHeight = usableHeight / rows;
            var radius = Math.min(cellWidth * 0.43, cellHeight * 0.44);
            if (!best || radius > best.radius) {
                best = {
                    columns: columns,
                    rows: rows,
                    cellWidth: cellWidth,
                    cellHeight: cellHeight,
                    radius: radius,
                    top: top
                };
            }
        }

        return best;
    }

    function itemColor(index, colors) {
        var palette = [
            colors.accent, '#5C83E9', '#DB7393', '#E6A547',
            '#8F6BD7', '#5BAE79', '#E5754F', '#3EA8C1',
            '#C482C7', '#86A646', '#D96C68', '#5B92B1',
            '#B98955', '#7493DD', '#A77DBC', '#64B8A4'
        ];
        return palette[index % palette.length];
    }

    function styleColor(style, name, fallback) {
        var value = String(style[name] || '');
        return /^#[0-9a-f]{6}$/i.test(value) ? value : fallback;
    }

    function resolveCustomPointerGeometry(style, radius, pointerLength) {
        var parts = String(style.pointerViewBox || '').trim().split(/[\s,]+/).map(Number);
        if (parts.length !== 4 || parts.some(function (value) { return !Number.isFinite(value); })
            || parts[2] <= 0 || parts[3] <= 0) {
            return null;
        }
        var length = radius * pointerLength / 100;
        var width = Math.max(2, length * parts[2] / parts[3]
            * clamp(Number(style.pointerWidthPercent) || 100, 50, 150) / 100);
        var pivotX = Number(style.pointerPivotX);
        var pivotY = Number(style.pointerPivotY);
        var hasPivot = Number.isFinite(pivotX) && Number.isFinite(pivotY);
        pivotX = hasPivot ? clamp(pivotX, parts[0], parts[0] + parts[2]) : parts[0] + parts[2] / 2;
        pivotY = hasPivot ? clamp(pivotY, parts[1], parts[1] + parts[3]) : parts[1] + parts[3];

        return {
            width: width,
            offsetCenter: [
                (0.5 - (pivotX - parts[0]) / parts[2]) * width,
                (1 - (pivotY - parts[1]) / parts[3]) * length
            ],
            showAnchor: typeof style.pointerShowAnchor === 'boolean' ? style.pointerShowAnchor : !hasPivot
        };
    }

    function applySeriesDesign(series, style, colors) {
        var pointerScale = clamp(Number(style.pointerWidthPercent) || 100, 50, 150) / 100;
        var pointerLengthScale = clamp(Number(style.pointerLengthPercent) || 100, 50, 150) / 100;
        var anchorScale = clamp(Number(style.anchorSizePercent) || 100, 50, 150) / 100;
        var anchorBorderScale = clamp(Number(style.anchorBorderWidthPercent) || 100, 50, 150) / 100;
        if (series.pointer.show !== false) {
            var pointerLength = parseFloat(series.pointer.length);
            series.pointer.width = Math.max(1, (Number(series.pointer.width) || 1) * pointerScale);
            if (Number.isFinite(pointerLength)) {
                series.pointer.length = clamp(pointerLength * pointerLengthScale, 10, 100) + '%';
            }
            if (style.pointerShape === 'custom') {
                var path = typeof style.pointerPath === 'string' ? style.pointerPath.trim() : '';
                var geometry = resolveCustomPointerGeometry(
                    style,
                    Number(series.radius) || 1,
                    Number.isFinite(pointerLength) ? clamp(pointerLength * pointerLengthScale, 10, 100) : 65
                );
                if (path && geometry && /^[MmZzLlHhVvCcSsQqTtAa0-9eE+.,\-\s]+$/.test(path)) {
                    series.pointer.icon = 'path://' + path;
                    series.pointer.width = geometry.width;
                    series.pointer.offsetCenter = geometry.offsetCenter;
                    series.anchor.show = geometry.showAnchor;
                }
            } else if (Object.prototype.hasOwnProperty.call(pointerIcons, style.pointerShape)) {
                series.pointer.icon = pointerIcons[style.pointerShape];
            }
            series.anchor.itemStyle = series.anchor.itemStyle || {};
            series.anchor.size = Math.max(1, (Number(series.anchor.size) || 1) * anchorScale);
            series.anchor.itemStyle.borderWidth = Math.max(0,
                (series.anchor.itemStyle.borderWidth || 1) * anchorBorderScale);
            if (style.anchorShape === 'none') {
                series.anchor.show = false;
            } else if (style.anchorShape === 'circle' || style.anchorShape === 'ring') {
                series.anchor.show = true;
                if (style.anchorShape === 'ring') {
                    series.anchor.itemStyle.color = colors.background;
                    series.anchor.itemStyle.borderWidth = Math.max(2, series.anchor.itemStyle.borderWidth);
                }
            } else if (style.anchorShape === 'custom') {
                var anchorPath = typeof style.anchorPath === 'string' ? style.anchorPath.trim() : '';
                series.anchor.show = !!anchorPath && /^[MmZzLlHhVvCcSsQqTtAa0-9eE+.,\-\s]+$/.test(anchorPath);
                if (series.anchor.show) {
                    series.anchor.icon = 'path://' + anchorPath;
                }
            }
        }

        var majorSplitCount = Number(style.majorSplitCount);
        var minorSplitCount = Number(style.minorSplitCount);
        if (Number.isInteger(majorSplitCount) && majorSplitCount >= 2 && majorSplitCount <= 24) {
            series.splitNumber = majorSplitCount;
        }
        if (Number.isInteger(minorSplitCount) && minorSplitCount >= 1 && minorSplitCount <= 10) {
            series.axisTick.splitNumber = minorSplitCount;
        }
        if (style.colorMode === 'custom') {
            var pointer = styleColor(style, 'pointerColor', colors.accent);
            var progress = styleColor(style, 'progressColor', colors.accent);
            var ring = styleColor(style, 'ringColor', colors.track);
            var scale = styleColor(style, 'scaleColor', colors.muted);
            var value = styleColor(style, 'valueColor', colors.text);
            var title = styleColor(style, 'titleColor', colors.muted);
            var anchor = styleColor(style, 'anchorColor', pointer);
            var anchorBorder = styleColor(style, 'anchorBorderColor', value);
            series.pointer.itemStyle = series.pointer.itemStyle || {};
            series.pointer.itemStyle.color = pointer;
            series.progress.itemStyle = series.progress.itemStyle || {};
            series.progress.itemStyle.color = progress;
            series.axisLine.lineStyle.color = [[1, ring]];
            if (series.axisTick.lineStyle) {
                series.axisTick.lineStyle.color = scale;
            }
            if (series.splitLine.lineStyle) {
                series.splitLine.lineStyle.color = scale;
            }
            series.axisLabel.color = scale;
            series.detail.color = value;
            series.title.color = title;
            if (series.anchor.itemStyle) {
                series.anchor.itemStyle.color = style.anchorShape === 'ring' ? colors.background : anchor;
                series.anchor.itemStyle.borderColor = anchorBorder;
            }
            series.itemStyle = { color: pointer };
        }
        return series;
    }

    function applyPlateDesign(graphic, series, preset, style, colors, width, height, headerInset, items) {
        var hasIndividualPlate = items.some(function (item) {
            return item.style && item.style.plateMode && item.style.plateMode !== 'preset';
        });
        if (preset === 'ring-grid' || preset === 'ring-concentric'
            || ((style.plateMode || 'preset') === 'preset' && !hasIndividualPlate)) {
            return graphic;
        }
        var retained = graphic.slice();
        series.forEach(function (gauge, index) {
            var plateStyle = Object.assign({}, style, (items[index] || {}).style || {});
            var plateMode = plateStyle.plateMode || 'preset';
            if (plateMode === 'preset') {
                return;
            }
            retained = retained.filter(function (element) {
                var id = String(element.id || '');
                return id !== 'tacho-bezel-' + gauge.id
                    && id !== 'tacho-inner-' + gauge.id
                    && id !== 'weather-bezel-' + gauge.id
                    && id !== 'weather-inner-' + gauge.id
                    && id !== 'chronograph-sub-bezel-' + gauge.id
                    && !(index === 0 && id === 'chronograph-main-bezel');
            });
            if (plateMode === 'hidden') {
                return;
            }
            var sizeScale = clamp(Number(plateStyle.plateSizePercent) || 100, 50, 150) / 100;
            var borderScale = clamp(Number(plateStyle.plateBorderWidthPercent) || 100, 50, 150) / 100;
            var fill = styleColor(plateStyle, 'plateColor', colors.background);
            var border = styleColor(plateStyle, 'plateBorderColor', colors.border);
            var maximumRadius = Math.max(1, Math.min(
                gauge.center[0], width - gauge.center[0],
                gauge.center[1] - headerInset, height - gauge.center[1]
            ));
            var plateRadius = Math.min(gauge.radius * 1.08 * sizeScale, maximumRadius);
            retained.push({
                id: 'custom-plate-' + gauge.id,
                type: 'circle',
                z: preset === 'chronograph' && index > 0 ? 3 : 0,
                shape: { cx: gauge.center[0], cy: gauge.center[1], r: plateRadius },
                style: {
                    fill: fill,
                    stroke: border,
                    lineWidth: clamp(gauge.radius * 0.02 * borderScale, 1, 12)
                },
                silent: true
            });
            if (plateStyle.plateBackgroundEnabled
                && /^data:image\/svg\+xml;base64,[a-z0-9+/=]+$/i.test(String(plateStyle.plateBackgroundImage || ''))) {
                var backgroundZ = preset === 'chronograph' && index > 0 ? 4 : 1;
                var diameter = plateRadius * 2;
                var aspectRatio = Number(plateStyle.plateBackgroundAspectRatio);
                aspectRatio = Number.isFinite(aspectRatio) && aspectRatio > 0 ? aspectRatio : 1;
                var fit = ['contain', 'cover', 'stretch'].indexOf(plateStyle.plateBackgroundFit) >= 0
                    ? plateStyle.plateBackgroundFit : 'cover';
                var imageWidth = diameter;
                var imageHeight = diameter;
                if (fit === 'contain') {
                    if (aspectRatio > 1) {
                        imageHeight = diameter / aspectRatio;
                    } else {
                        imageWidth = diameter * aspectRatio;
                    }
                } else if (fit === 'cover') {
                    if (aspectRatio > 1) {
                        imageWidth = diameter * aspectRatio;
                    } else {
                        imageHeight = diameter / aspectRatio;
                    }
                }
                var backgroundScale = clamp(Number(plateStyle.plateBackgroundSizePercent) || 100, 25, 200) / 100;
                imageWidth *= backgroundScale;
                imageHeight *= backgroundScale;
                var centerX = gauge.center[0] + diameter
                    * clamp(Number(plateStyle.plateBackgroundOffsetXPercent) || 0, -100, 100) / 100;
                var centerY = gauge.center[1] + diameter
                    * clamp(Number(plateStyle.plateBackgroundOffsetYPercent) || 0, -100, 100) / 100;
                var opacity = clamp(Number(plateStyle.plateBackgroundOpacityPercent), 0, 100) / 100;
                if (!Number.isFinite(opacity)) {
                    opacity = 1;
                }
                retained.push({
                    id: 'custom-plate-background-' + gauge.id,
                    type: 'group',
                    z: backgroundZ,
                    silent: true,
                    clipPath: {
                        type: 'circle',
                        shape: { cx: gauge.center[0], cy: gauge.center[1], r: plateRadius }
                    },
                    children: [{
                        type: 'image',
                        z: backgroundZ,
                        rotation: clamp(Number(plateStyle.plateBackgroundRotation) || 0, -180, 180) * Math.PI / 180,
                        originX: centerX,
                        originY: centerY,
                        style: {
                            image: plateStyle.plateBackgroundImage,
                            x: centerX - imageWidth / 2,
                            y: centerY - imageHeight / 2,
                            width: imageWidth,
                            height: imageHeight,
                            opacity: opacity
                        }
                    }]
                });
                retained.push({
                    id: 'custom-plate-outline-' + gauge.id,
                    type: 'circle',
                    z: backgroundZ,
                    silent: true,
                    shape: { cx: gauge.center[0], cy: gauge.center[1], r: plateRadius },
                    style: {
                        fill: 'rgba(0,0,0,0)',
                        stroke: border,
                        lineWidth: clamp(gauge.radius * 0.02 * borderScale, 1, 12)
                    }
                });
            }
        });
        return retained;
    }

    function buildSeries(item, index, grid, colors, style, position) {
        var column = grid ? index % grid.columns : 0;
        var row = grid ? Math.floor(index / grid.columns) : 0;
        var centerX = position ? position.center[0] : column * grid.cellWidth + grid.cellWidth / 2;
        var centerY = position ? position.center[1] : grid.top + row * grid.cellHeight + grid.cellHeight * 0.48;
        var radius = position ? position.radius : grid.radius;
        var scale = clamp(Number(style.scaleFontSizePercent) || 100, 50, 150) / 100;
        var valueScale = clamp(Number(style.valueFontSizePercent) || 100, 50, 150) / 100;
        var titleScale = clamp(Number(style.titleFontSizePercent) || 100, 50, 150) / 100;
        var ringScale = clamp(Number(style.ringWidthPercent) || 100, 50, 150) / 100;
        var gauge = item.gauge || {};
        var value = Number(item.value);
        var decimals = clamp(Number(gauge.decimals) || 0, 0, 6);
        var unit = String(gauge.unit || '');

        return {
            id: item.id,
            type: 'gauge',
            min: Number(gauge.minimum),
            max: Number(gauge.maximum),
            center: [centerX, centerY],
            radius: radius,
            startAngle: 225,
            endAngle: -45,
            splitNumber: 5,
            progress: { show: false },
            axisLine: {
                lineStyle: {
                    width: clamp(radius * 0.1 * ringScale, 6, 24),
                    color: [[1, colors.track]]
                }
            },
            pointer: {
                show: true,
                length: '62%',
                width: clamp(radius * 0.045, 3, 8),
                itemStyle: { color: colors.accent }
            },
            anchor: {
                show: true,
                showAbove: true,
                size: clamp(radius * 0.12, 7, 15),
                itemStyle: { color: colors.accent, borderColor: colors.text, borderWidth: 1 }
            },
            axisTick: {
                distance: -clamp(radius * 0.1 * ringScale, 6, 24),
                splitNumber: 4,
                length: clamp(radius * 0.05, 3, 8),
                lineStyle: { color: colors.muted, width: 1 }
            },
            splitLine: {
                distance: -clamp(radius * 0.1 * ringScale, 6, 24),
                length: clamp(radius * 0.09, 5, 12),
                lineStyle: { color: colors.border, width: 2 }
            },
            axisLabel: {
                distance: clamp(radius * 0.14, 8, 20),
                color: colors.muted,
                fontSize: clamp(radius * 0.105 * scale, 8, 16),
                formatter: function (axisValue) {
                    return Number(axisValue).toLocaleString(undefined, { maximumFractionDigits: 2 });
                }
            },
            title: {
                show: true,
                offsetCenter: [0, '-18%'],
                color: colors.muted,
                fontSize: clamp(radius * 0.14 * titleScale, 10, 20),
                overflow: 'truncate',
                width: radius * 1.3
            },
            detail: {
                valueAnimation: !reduceMotion,
                offsetCenter: [0, '82%'],
                color: colors.text,
                fontWeight: 700,
                fontSize: clamp(radius * 0.17 * valueScale, 12, 28),
                formatter: function () {
                    return formatValue(value, decimals, unit);
                }
            },
            data: [{ value: value, name: String(gauge.label || '') }]
        };
    }

    function buildRingGridSeries(item, index, grid, colors, style) {
        var series = buildSeries(item, index, grid, colors, style);
        var radius = grid.radius * 0.95;
        var ringScale = clamp(Number(style.ringWidthPercent) || 100, 50, 150) / 100;
        var ringWidth = clamp(radius * 0.13 * ringScale, 2, 24);
        var color = itemColor(index, colors);
        series.radius = radius;
        series.startAngle = 90;
        series.endAngle = -270;
        series.progress = { show: true, roundCap: true, width: ringWidth, itemStyle: { color: color } };
        series.axisLine = {
            roundCap: true,
            lineStyle: { width: ringWidth, color: [[1, colors.track]] }
        };
        series.pointer = { show: false };
        series.anchor = { show: false };
        series.axisTick = { show: false };
        series.splitLine = { show: false };
        series.axisLabel = { show: false };
        series.title.offsetCenter = [0, '-18%'];
        series.detail.offsetCenter = [0, '20%'];
        series.itemStyle = { color: color };
        return series;
    }

    function buildConcentricLayout(items, width, height, headerInset, hasTitle, colors, style) {
        var top = headerInset + (hasTitle ? Math.max(32, height * 0.1) : 4);
        var availableHeight = Math.max(1, height - top);
        var beside = width >= 560 && width >= availableHeight * 1.15;
        var ringAreaWidth = beside ? width * 0.53 : width;
        var ringAreaHeight = beside ? availableHeight : availableHeight * 0.58;
        var center = [ringAreaWidth * 0.5, top + ringAreaHeight * 0.5];
        var outerRadius = Math.max(1, Math.min(ringAreaWidth * 0.42, ringAreaHeight * 0.43));
        var spacing = outerRadius / (items.length + 0.5);
        var ringScale = clamp(Number(style.ringWidthPercent) || 100, 50, 150) / 100;
        var ringWidth = spacing * clamp(0.65 * ringScale, 0.3, 0.85);
        var legendColumns = beside || items.length <= 8 ? 1 : 2;
        var legendRows = Math.ceil(items.length / legendColumns);
        var legendLeft = beside ? ringAreaWidth + 12 : 10;
        var legendTop = beside ? top : top + ringAreaHeight + 6;
        var legendWidth = beside ? width - ringAreaWidth - 22 : width - 20;
        var legendHeight = beside ? availableHeight : availableHeight - ringAreaHeight - 6;
        var columnWidth = legendWidth / legendColumns;
        var rowHeight = Math.max(1, Math.min(28, legendHeight / legendRows));
        var labelScale = clamp(Number(style.titleFontSizePercent) || 100, 50, 150) / 100;
        var valueScale = clamp(Number(style.valueFontSizePercent) || 100, 50, 150) / 100;
        var series = [];
        var graphic = [];

        items.forEach(function (item, index) {
            var gauge = item.gauge || {};
            var color = itemColor(index, colors);
            var legendX = legendLeft + (index % legendColumns) * columnWidth;
            var legendY = legendTop + Math.floor(index / legendColumns) * rowHeight;
            var valueText = formatValue(Number(item.value), clamp(Number(gauge.decimals) || 0, 0, 6), String(gauge.unit || ''));
            var labelWidth = Math.max(1, columnWidth * 0.52 - 16);
            var valueWidth = Math.max(1, columnWidth - labelWidth - 24);
            series.push({
                id: item.id,
                type: 'gauge',
                min: Number(gauge.minimum),
                max: Number(gauge.maximum),
                center: center,
                radius: outerRadius - index * spacing,
                startAngle: 90,
                endAngle: -270,
                progress: { show: true, roundCap: true, width: ringWidth, itemStyle: { color: color } },
                axisLine: { roundCap: true, lineStyle: { width: ringWidth, color: [[1, colors.track]] } },
                pointer: { show: false },
                anchor: { show: false },
                axisTick: { show: false },
                splitLine: { show: false },
                axisLabel: { show: false },
                title: { show: false },
                detail: { show: false },
                itemStyle: { color: color },
                data: [{ value: Number(item.value), name: String(gauge.label || '') }]
            });
            graphic.push({
                id: 'legend-swatch-' + item.id,
                type: 'rect',
                left: legendX,
                top: legendY + Math.max(0, (rowHeight - 7) / 2),
                shape: { x: 0, y: 0, width: 7, height: 7 },
                style: { fill: color },
                silent: true
            });
            graphic.push({
                id: 'legend-label-' + item.id,
                type: 'text',
                left: legendX + 13,
                top: legendY,
                style: {
                    text: String(gauge.label || ''), fill: colors.muted,
                    fontSize: clamp(rowHeight * 0.52 * labelScale, 5, 15),
                    width: labelWidth, overflow: 'truncate'
                },
                silent: true
            });
            graphic.push({
                id: 'legend-value-' + item.id,
                type: 'text',
                left: legendX + 17 + labelWidth,
                top: legendY,
                style: {
                    text: valueText, fill: colors.text,
                    fontSize: clamp(rowHeight * 0.54 * valueScale, 5, 16),
                    width: valueWidth, overflow: 'truncate'
                },
                silent: true
            });
        });

        return { series: series, graphic: graphic };
    }

    function resolveWeatherLayout(count, width, height, headerInset, hasTitle) {
        var top = headerInset + (hasTitle ? Math.max(32, height * 0.1) : 4);
        var availableHeight = Math.max(1, height - top);
        var beside = width >= 620 && width >= availableHeight * 1.2;
        var mainWidth = beside ? width * 0.52 : width;
        var mainHeight = beside ? availableHeight : availableHeight * 0.52;
        var main = {
            center: [mainWidth * 0.5, top + mainHeight * 0.5],
            radius: Math.max(1, Math.min(mainWidth * 0.34, mainHeight * 0.39))
        };
        var otherCount = count - 1;
        var area = {
            left: beside ? mainWidth : 0,
            top: beside ? top : top + mainHeight,
            width: beside ? width - mainWidth : width,
            height: beside ? availableHeight : availableHeight - mainHeight
        };
        var best = null;
        for (var columns = 1; columns <= Math.min(otherCount, 4); columns += 1) {
            var rows = Math.ceil(otherCount / columns);
            var cellWidth = area.width / columns;
            var cellHeight = area.height / rows;
            var radius = Math.min(cellWidth * 0.34, cellHeight * 0.36, main.radius * 0.75);
            if (!best || radius > best.radius) {
                best = { columns: columns, cellWidth: cellWidth, cellHeight: cellHeight, radius: radius };
            }
        }
        var positions = [main];
        for (var index = 0; index < otherCount; index += 1) {
            positions.push({
                center: [
                    area.left + (index % best.columns + 0.5) * best.cellWidth,
                    area.top + (Math.floor(index / best.columns) + 0.5) * best.cellHeight
                ],
                radius: Math.max(1, best.radius)
            });
        }
        return positions;
    }

    function buildWeatherLayout(items, width, height, headerInset, hasTitle, colors, style) {
        var positions = resolveWeatherLayout(items.length, width, height, headerInset, hasTitle);
        var series = [];
        var graphic = [];
        items.forEach(function (item, index) {
            var position = positions[index];
            var color = itemColor(index, colors);
            var gauge = buildSeries(item, index, null, colors, style, position);
            gauge.itemStyle = { color: color };
            gauge.pointer.itemStyle.color = color;
            gauge.anchor.itemStyle.color = color;
            var ringScale = clamp(Number(style.ringWidthPercent) || 100, 50, 150) / 100;
            var ringWidth = clamp(position.radius * 0.1 * ringScale, 1, 24);
            gauge.axisLine.lineStyle.width = ringWidth;
            gauge.axisTick.distance = -ringWidth;
            gauge.splitLine.distance = -ringWidth;
            gauge.pointer.width = clamp(position.radius * 0.045, 1, 8);
            gauge.anchor.size = clamp(position.radius * 0.12, 2, 15);
            gauge.axisLabel.fontSize = clamp(position.radius * 0.105
                * clamp(Number(style.scaleFontSizePercent) || 100, 50, 150) / 100, 4, 16);
            gauge.title.offsetCenter = [0, '-27%'];
            gauge.title.fontSize = clamp(position.radius * 0.14
                * clamp(Number(style.titleFontSizePercent) || 100, 50, 150) / 100, 5, 20);
            gauge.detail.offsetCenter = [0, '44%'];
            gauge.detail.fontSize = clamp(position.radius * 0.16
                * clamp(Number(style.valueFontSizePercent) || 100, 50, 150) / 100, 6, 28);
            series.push(gauge);
            graphic.push({
                id: 'weather-bezel-' + item.id,
                type: 'circle',
                z: 0,
                shape: { cx: position.center[0], cy: position.center[1], r: position.radius * 1.14 },
                style: { fill: colors.background, stroke: colors.border, lineWidth: Math.max(1, position.radius * 0.018) },
                silent: true
            });
            graphic.push({
                id: 'weather-inner-' + item.id,
                type: 'circle',
                z: 0,
                shape: { cx: position.center[0], cy: position.center[1], r: position.radius * 1.04 },
                style: { fill: 'none', stroke: colors.track, lineWidth: Math.max(1, position.radius * 0.025) },
                silent: true
            });
        });
        return { series: series, graphic: graphic };
    }

    function resolveTachoLayout(count, width, height, headerInset, hasTitle) {
        var top = headerInset + (hasTitle ? Math.max(32, height * 0.1) : 4);
        var availableHeight = Math.max(1, height - top);
        var primaryHeight = count > 3 ? availableHeight * 0.68 : availableHeight;
        var wide = width >= 650 && width >= primaryHeight * 1.4;
        var mainRadius = Math.max(1, Math.min(width * (wide ? 0.19 : 0.32), primaryHeight * (wide ? 0.39 : 0.27)));
        var sideRadius = Math.max(1, Math.min(width * (wide ? 0.115 : 0.21),
            primaryHeight * (wide ? 0.28 : 0.17), mainRadius * 0.7));
        var positions = [
            { center: [width * 0.5, top + primaryHeight * (wide ? 0.5 : 0.32)], radius: mainRadius },
            { center: [width * (wide ? 0.17 : 0.25), top + primaryHeight * (wide ? 0.55 : 0.77)], radius: sideRadius },
            { center: [width * (wide ? 0.83 : 0.75), top + primaryHeight * (wide ? 0.55 : 0.77)], radius: sideRadius }
        ].slice(0, count);
        var extraCount = count - 3;
        if (extraCount <= 0) {
            return positions;
        }

        var extraHeight = availableHeight - primaryHeight;
        var best = null;
        for (var columns = 1; columns <= Math.min(extraCount, 6); columns += 1) {
            var rows = Math.ceil(extraCount / columns);
            var cellWidth = width / columns;
            var cellHeight = extraHeight / rows;
            var radius = Math.min(cellWidth * 0.34, cellHeight * 0.36, mainRadius * 0.5);
            if (!best || radius > best.radius) {
                best = { columns: columns, cellWidth: cellWidth, cellHeight: cellHeight, radius: radius };
            }
        }
        for (var index = 0; index < extraCount; index += 1) {
            positions.push({
                center: [
                    (index % best.columns + 0.5) * best.cellWidth,
                    top + primaryHeight + (Math.floor(index / best.columns) + 0.5) * best.cellHeight
                ],
                radius: Math.max(1, best.radius)
            });
        }
        return positions;
    }

    function buildTachoLayout(items, width, height, headerInset, hasTitle, colors, style) {
        var positions = resolveTachoLayout(items.length, width, height, headerInset, hasTitle);
        var needle = '#F0442D';
        var series = [];
        var graphic = [];
        items.forEach(function (item, index) {
            var position = positions[index];
            var radius = position.radius;
            var itemStyle = Object.assign({}, style, item.style || {});
            var gauge = buildSeries(item, index, null, colors, itemStyle, position);
            var ringScale = clamp(Number(itemStyle.ringWidthPercent) || 100, 50, 150) / 100;
            var axisWidth = clamp(radius * 0.05 * ringScale, 1, 14);
            gauge.startAngle = index === 0 ? 210 : 225;
            gauge.endAngle = index === 0 ? -30 : -45;
            gauge.splitNumber = index === 0 ? 8 : 5;
            gauge.axisLine.lineStyle.width = axisWidth;
            gauge.axisLine.lineStyle.color = [[1, colors.border]];
            gauge.axisTick.distance = -axisWidth;
            gauge.axisTick.length = clamp(radius * 0.065, 1, 9);
            gauge.axisTick.lineStyle.color = colors.text;
            gauge.splitLine.distance = -axisWidth;
            gauge.splitLine.length = clamp(radius * 0.1, 2, 16);
            gauge.splitLine.lineStyle.color = colors.text;
            gauge.axisLabel.color = colors.text;
            gauge.axisLabel.fontWeight = 700;
            gauge.axisLabel.fontSize = clamp(radius * 0.12
                * clamp(Number(itemStyle.scaleFontSizePercent) || 100, 50, 150) / 100, 4, 24);
            gauge.pointer.length = '65%';
            gauge.pointer.width = clamp(radius * 0.05, 1, 10);
            gauge.pointer.itemStyle = { color: needle, shadowColor: needle, shadowBlur: Math.min(8, radius * 0.04) };
            gauge.anchor.size = clamp(radius * 0.1, 2, 16);
            gauge.anchor.itemStyle = { color: needle, borderColor: colors.text, borderWidth: 1 };
            gauge.itemStyle = { color: needle };
            gauge.title.offsetCenter = [0, '-36%'];
            gauge.title.color = colors.text;
            gauge.title.fontSize = clamp(radius * 0.12
                * clamp(Number(itemStyle.titleFontSizePercent) || 100, 50, 150) / 100, 5, 18);
            gauge.detail.offsetCenter = [0, '49%'];
            gauge.detail.fontSize = clamp(radius * 0.2
                * clamp(Number(itemStyle.valueFontSizePercent) || 100, 50, 150) / 100, 6, 38);
            series.push(gauge);
            graphic.push({
                id: 'tacho-bezel-' + item.id,
                type: 'circle',
                z: 0,
                shape: { cx: position.center[0], cy: position.center[1], r: radius * 1.08 },
                style: { fill: colors.background, stroke: colors.track, lineWidth: Math.max(1, radius * 0.02) },
                silent: true
            });
            graphic.push({
                id: 'tacho-inner-' + item.id,
                type: 'circle',
                z: 0,
                shape: { cx: position.center[0], cy: position.center[1], r: radius * 0.96 },
                style: { fill: 'none', stroke: colors.border, lineWidth: Math.max(1, radius * 0.012) },
                silent: true
            });
        });
        return { series: series, graphic: graphic };
    }

    function buildChronographLayout(items, width, height, headerInset, hasTitle, colors, style) {
        var top = headerInset + (hasTitle ? Math.max(32, height * 0.1) : 4);
        var availableHeight = Math.max(1, height - top);
        var radius = Math.max(1, Math.min(width * 0.43, availableHeight * 0.44));
        var center = [width * 0.5, top + availableHeight * 0.5];
        var subCount = items.length - 1;
        var offsets = {
            1: [[0, 0.52]],
            2: [[-0.48, 0.28], [0.48, 0.28]],
            3: [[-0.48, -0.08], [0.48, -0.08], [0, 0.53]],
            4: [[-0.43, -0.32], [0.43, -0.32], [-0.43, 0.36], [0.43, 0.36]],
            5: [[-0.48, -0.18], [0.48, -0.18], [-0.46, 0.43], [0.46, 0.43], [0, 0.55]]
        }[subCount] || [];
        var subRadius = radius * (subCount <= 3 ? 0.23 : 0.19);
        var graphic = [{
            id: 'chronograph-main-bezel', type: 'circle', z: 0,
            shape: { cx: center[0], cy: center[1], r: radius * 1.06 },
            style: { fill: colors.background, stroke: colors.border, lineWidth: Math.max(1, radius * 0.022) },
            silent: true
        }];
        var series = items.map(function (item, index) {
            var itemStyle = Object.assign({}, style, item.style || {});
            var ringScale = clamp(Number(itemStyle.ringWidthPercent) || 100, 50, 150) / 100;
            var scale = clamp(Number(itemStyle.scaleFontSizePercent) || 100, 50, 150) / 100;
            var titleScale = clamp(Number(itemStyle.titleFontSizePercent) || 100, 50, 150) / 100;
            var valueScale = clamp(Number(itemStyle.valueFontSizePercent) || 100, 50, 150) / 100;
            var position = index === 0
                ? { center: center, radius: radius }
                : {
                    center: [center[0] + offsets[index - 1][0] * radius,
                        center[1] + offsets[index - 1][1] * radius],
                    radius: subRadius
                };
            var gauge = buildSeries(item, index, null, colors, itemStyle, position);
            var color = itemColor(index, colors);
            var dialRadius = position.radius;
            var axisWidth = clamp(dialRadius * (index === 0 ? 0.055 : 0.09) * ringScale, 1, 18);
            gauge.z = index === 0 ? 6 : 5;
            gauge.splitNumber = index === 0 ? 8 : 2;
            gauge.axisLine.lineStyle.width = axisWidth;
            gauge.axisLine.lineStyle.color = [[1, colors.track]];
            gauge.axisTick.distance = -axisWidth;
            gauge.axisTick.splitNumber = index === 0 ? 4 : 2;
            gauge.axisTick.length = clamp(dialRadius * 0.05, 1, 9);
            gauge.splitLine.distance = -axisWidth;
            gauge.splitLine.length = clamp(dialRadius * 0.1, 2, 16);
            gauge.axisLabel.fontSize = clamp(dialRadius * 0.105 * scale, index === 0 ? 6 : 4, 18);
            gauge.axisLabel.distance = clamp(dialRadius * 0.13, 2, 20);
            gauge.pointer.length = index === 0 ? '69%' : '58%';
            gauge.pointer.width = clamp(dialRadius * 0.045, 1, 10);
            gauge.pointer.itemStyle.color = color;
            gauge.anchor.size = clamp(dialRadius * 0.11, 2, 16);
            gauge.anchor.itemStyle.color = color;
            gauge.itemStyle = { color: color };
            gauge.title.offsetCenter = [0, index === 0 ? '-16%' : '-24%'];
            gauge.title.width = dialRadius * (index === 0 ? 0.65 : 1.3);
            gauge.title.fontSize = clamp(dialRadius * 0.12 * titleScale, index === 0 ? 7 : 4, 20);
            gauge.detail.offsetCenter = [0, index === 0 ? '74%' : '40%'];
            gauge.detail.fontSize = clamp(dialRadius * 0.16 * valueScale, index === 0 ? 8 : 4, 30);
            if (index > 0) {
                graphic.push({
                    id: 'chronograph-sub-bezel-' + item.id, type: 'circle', z: 4,
                    shape: { cx: position.center[0], cy: position.center[1], r: subRadius * 1.08 },
                    style: { fill: colors.background, stroke: color, lineWidth: Math.max(1, subRadius * 0.045) },
                    silent: true
                });
            }
            return gauge;
        });
        return { series: series, graphic: graphic };
    }

    function buildOption(model, theme) {
        var items = Array.isArray(model.items) ? model.items : [];
        var gauge = model.gauge || {};
        var style = gauge.style || {};
        var colors = colorsFor(theme);
        var width = Math.max(chartElement.clientWidth, 240);
        var height = Math.max(chartElement.clientHeight, 180);
        var title = String(gauge.title || '');
        var headerInset = bootstrap.mode === 'symcon' && bootstrap.options.tileHeaderVisible !== false ? 64 : 0;
        var preset = String(gauge.preset || 'multi-title');
        var grid = preset === 'ring-concentric' || preset === 'weather-station' || preset === 'tacho'
            || preset === 'chronograph'
            ? null
            : resolveGrid(items.length, width, height, title !== '', headerInset);
        var concentric = preset === 'ring-concentric'
            ? buildConcentricLayout(items, width, height, headerInset, title !== '', colors, style)
            : null;
        var weather = preset === 'weather-station'
            ? buildWeatherLayout(items, width, height, headerInset, title !== '', colors, style)
            : null;
        var tacho = preset === 'tacho'
            ? buildTachoLayout(items, width, height, headerInset, title !== '', colors, style)
            : null;
        var chronograph = preset === 'chronograph'
            ? buildChronographLayout(items, width, height, headerInset, title !== '', colors, style)
            : null;
        var graphic = concentric ? concentric.graphic : weather ? weather.graphic : tacho ? tacho.graphic
            : chronograph ? chronograph.graphic : [];
        var series = concentric ? concentric.series : weather ? weather.series : tacho ? tacho.series
            : chronograph ? chronograph.series : items.map(function (item, index) {
                return preset === 'ring-grid'
                    ? buildRingGridSeries(item, index, grid, colors, style)
                    : buildSeries(item, index, grid, colors, style);
            });
        series = series.map(function (gaugeSeries, index) {
            return applySeriesDesign(gaugeSeries, Object.assign({}, style, items[index].style || {}), colors);
        });
        graphic = applyPlateDesign(graphic, series, preset, style, colors, width, height, headerInset, items);

        return {
            backgroundColor: bootstrap.mode === 'ipsview' && bootstrap.options.adaptToBackground === true
                ? 'transparent'
                : colors.background,
            animation: !reduceMotion,
            animationDuration: reduceMotion ? 0 : 500,
            aria: { enabled: true, decal: { show: false } },
            title: {
                show: title !== '',
                text: title,
                left: 'center',
                top: headerInset + 6,
                textStyle: { color: colors.text, fontSize: clamp(height * 0.045, 14, 24) }
            },
            tooltip: {
                trigger: 'item',
                formatter: function (parameters) {
                    var item = items[parameters.seriesIndex] || {};
                    var itemGauge = item.gauge || {};
                    return String(itemGauge.label || '') + '<br>'
                        + formatValue(item.value, Number(itemGauge.decimals) || 0, String(itemGauge.unit || ''));
                }
            },
            graphic: graphic,
            series: series
        };
    }

    function displayError(message) {
        if (chart) {
            chart.clear();
        }
        chartElement.hidden = true;
        errorElement.textContent = translate(message || 'The Chronograph Gauge values could not be loaded.');
        errorElement.hidden = false;
    }

    function render(state) {
        currentState = state;
        if (!state || state.status !== 'ready' || !state.chart) {
            displayError(state && state.error ? state.error : 'The Chronograph Gauge values could not be loaded.');
            return;
        }
        if (!window.echarts || typeof window.echarts.init !== 'function') {
            displayError('Apache ECharts could not be initialized.');
            return;
        }
        errorElement.hidden = true;
        chartElement.hidden = false;
        var theme = normalizeTheme(state.chart.theme);
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
        var state = parseState(message);
        if (state) {
            render(state);
        }
    };

    if (window.ResizeObserver) {
        new ResizeObserver(function () {
            if (chart) {
                chart.resize();
                if (currentState && currentState.status === 'ready') {
                    chart.setOption(buildOption(currentState.chart, currentTheme || 'auto'), true);
                }
            }
        }).observe(chartElement);
    } else {
        window.addEventListener('resize', function () {
            if (chart) {
                chart.resize();
            }
        });
    }

    window.addEventListener('beforeunload', function () {
        if (chart) {
            chart.dispose();
            chart = null;
        }
    });

    render(currentState);
}());
