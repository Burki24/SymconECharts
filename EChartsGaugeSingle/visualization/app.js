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
    var currentTheme = null;
    var currentState = bootstrap.state || null;
    var reduceMotion = window.matchMedia
        && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var speedPointerIcon = 'path://M2090.36389,615.30999 L2090.36389,615.30999 C2091.48372,615.30999 2092.40383,616.194028 2092.44859,617.312956 L2096.90698,728.755929 C2097.05155,732.369577 2094.2393,735.416212 2090.62566,735.56078 C2090.53845,735.564269 2090.45117,735.566014 2090.36389,735.566014 L2090.36389,735.566014 C2086.74736,735.566014 2083.81557,732.63423 2083.81557,729.017692 C2083.81557,728.930412 2083.81732,728.84314 2083.82081,728.755929 L2088.2792,617.312956 C2088.32396,616.194028 2089.24407,615.30999 2090.36389,615.30999 Z';
    var pointerIcons = {
        needle: 'path://M0,-100 L7,10 L-7,10 Z',
        line: 'path://M-2,-100 L2,-100 L2,10 L-2,10 Z',
        arrow: 'path://M0,-100 L12,-72 L4,-72 L4,10 L-4,10 L-4,-72 L-12,-72 Z'
    };
    var gaugeLayoutDefinitions = {
        basic: {
            centerY: 196,
            radius: 132,
            lineWidth: 16,
            pointerLength: '73%',
            detailY: 294,
            titleTopY: 28,
            titleY: 352,
            splitLength: 10,
            labelGap: 13,
            detailFontSize: 38,
            unitFontSize: 38,
            pointerWidth: 8,
            pointerMinimum: 4,
            pointerMaximum: 10,
            valueMinimum: 20,
            unitMinimum: 20,
            unitMaximum: 50
        },
        simple: {
            centerY: 196,
            radius: 132,
            lineWidth: 18,
            pointerLength: '70%',
            detailY: 294,
            titleTopY: 28,
            titleY: 352,
            splitLength: 10,
            labelGap: 12,
            detailFontSize: 38,
            unitFontSize: 38,
            pointerWidth: 8,
            pointerMinimum: 4,
            pointerMaximum: 10,
            valueMinimum: 20,
            unitMinimum: 20,
            unitMaximum: 50
        },
        progress: {
            centerY: 190,
            radius: 132,
            lineWidth: 20,
            pointerLength: '66%',
            detailY: 306,
            titleTopY: 28,
            titleY: 360,
            splitLength: 14,
            labelGap: 10,
            detailFontSize: 46,
            unitFontSize: 46,
            pointerWidth: 8,
            pointerMinimum: 4,
            pointerMaximum: 10,
            valueMinimum: 20,
            unitMinimum: 20,
            unitMaximum: 50
        },
        speed: {
            centerY: 232,
            radius: 142,
            lineWidth: 18,
            pointerLength: '75%',
            detailY: 319,
            titleTopY: 28,
            titleY: 370,
            splitLength: 12,
            labelGap: 12,
            detailFontSize: 42,
            unitFontSize: 18,
            pointerWidth: 16,
            pointerMinimum: 10,
            pointerMaximum: 16,
            valueMinimum: 28,
            unitMinimum: 14,
            unitMaximum: 20
        }
    };

    function translate(text) {
        return typeof translations[text] === 'string' ? translations[text] : text;
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

    function displayError(message) {
        if (chart) {
            chart.clear();
        }
        chartElement.hidden = true;
        errorElement.textContent = translate(message || 'The Gauge value could not be loaded.');
        errorElement.hidden = false;
    }

    function formatNumber(value, decimals) {
        return Number(value).toLocaleString(undefined, {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        });
    }

    function formatValue(value, decimals, unit) {
        var formatted = formatNumber(value, decimals);

        return unit ? formatted + ' ' + unit : formatted;
    }

    function formatRichTextPart(value) {
        return String(value).replace(/[{}|]/g, '');
    }

    function formatSpeedValue(value, decimals, unit) {
        var formatted = '{value|' + formatRichTextPart(formatNumber(value, decimals)) + '}';

        return unit ? formatted + '{unit| ' + formatRichTextPart(unit) + '}' : formatted;
    }

    function colorWithAlpha(color, alpha) {
        var hex = /^#([0-9a-f]{6})$/i.exec(color);
        if (hex) {
            return 'rgba('
                + parseInt(hex[1].substring(0, 2), 16) + ','
                + parseInt(hex[1].substring(2, 4), 16) + ','
                + parseInt(hex[1].substring(4, 6), 16) + ','
                + alpha + ')';
        }

        var rgb = /^rgb\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*\)$/i.exec(color);
        if (rgb) {
            return 'rgba(' + rgb[1] + ',' + rgb[2] + ',' + rgb[3] + ',' + alpha + ')';
        }

        return color;
    }

    function resolveSpeedSplitNumber(minimum, maximum) {
        var range = Math.abs(maximum - minimum);
        var candidates = [12, 10, 8, 6, 5, 4];
        var niceSteps = [1, 2, 2.5, 3, 5, 10];

        for (var index = 0; index < candidates.length; index += 1) {
            var step = range / candidates[index];
            var magnitude = Math.pow(10, Math.floor(Math.log(step) / Math.LN10));
            var normalized = step / magnitude;
            for (var niceIndex = 0; niceIndex < niceSteps.length; niceIndex += 1) {
                if (Math.abs(normalized - niceSteps[niceIndex]) < 0.000000001) {
                    return candidates[index];
                }
            }
        }

        return 10;
    }

    function clamp(value, minimum, maximum) {
        return Math.max(minimum, Math.min(maximum, value));
    }

    function resolveStyleScale(style, name) {
        var percent = Number(style && style[name]);

        return Number.isFinite(percent) ? clamp(percent, 50, 150) / 100 : 1;
    }

    function resolveScaledMetric(value, factor, defaultMinimum, defaultMaximum, customMinimum, customMaximum) {
        return factor === 1
            ? clamp(Math.round(value), defaultMinimum, defaultMaximum)
            : clamp(Math.round(value * factor), customMinimum, customMaximum);
    }

    function gaugeLayoutFits(layout, style, width, height) {
        var margin = 8;
        var plateRadius = style.plateShape === 'circle'
            ? layout.circlePlateRadius * resolveStyleScale(style, 'plateSizePercent')
            : style.plateShape === 'arc'
                ? layout.arcPlateRadius * resolveStyleScale(style, 'plateSizePercent')
                : 0;
        var horizontalRadius = Math.max(layout.radius + layout.axisFontSize / 2,
            plateRadius, layout.detailWidth / 2);
        var verticalRadius = Math.max(layout.radius + layout.axisFontSize / 2, plateRadius);
        var detailX = layout.centerX + layout.detailOffset[0];
        var titleX = layout.centerX + layout.titleOffset[0];
        var detailY = layout.centerY + layout.detailOffset[1];
        var titleY = layout.centerY + layout.titleOffset[1];
        var minimumY = Math.min(layout.centerY - verticalRadius,
            detailY - layout.detailHeight / 2, titleY - layout.titleFontSize);
        var maximumY = Math.max(layout.centerY + verticalRadius,
            detailY + layout.detailHeight / 2, titleY + layout.titleFontSize);
        if (maximumY - minimumY > height - 2 * margin) {
            return false;
        }
        if (minimumY < margin) {
            layout.centerY += margin - minimumY;
        } else if (maximumY > height - margin) {
            layout.centerY -= maximumY - (height - margin);
        }

        return layout.centerX - horizontalRadius >= margin
            && layout.centerX + horizontalRadius <= width - margin
            && detailX - layout.detailWidth / 2 >= margin
            && detailX + layout.detailWidth / 2 <= width - margin
            && titleX >= margin
            && titleX <= width - margin
            && layout.centerY - verticalRadius >= margin
            && layout.centerY + verticalRadius <= height - margin;
    }

    function resolveGaugeLayout(preset, width, height, style, scaleMultiplier) {
        var definition = gaugeLayoutDefinitions[preset] || gaugeLayoutDefinitions.simple;
        var scale = Math.min(width / 440, height / 400) * (scaleMultiplier || 1);
        var contentOffsetY = (height - 400 * scale) / 2;
        var scaleFontSize = resolveStyleScale(style, 'scaleFontSizePercent');
        var valueFontSize = resolveStyleScale(style, 'valueFontSizePercent');
        var unitFontSize = resolveStyleScale(style, 'unitFontSizePercent');
        var titleFontSize = resolveStyleScale(style, 'titleFontSizePercent');
        var ringWidth = resolveStyleScale(style, 'ringWidthPercent');
        var pointerWidth = resolveStyleScale(style, 'pointerWidthPercent');
        var pointerLength = resolveStyleScale(style, 'pointerLengthPercent');
        var minorTickLength = resolveStyleScale(style, 'minorTickLengthPercent');
        var majorTickLength = resolveStyleScale(style, 'majorTickLengthPercent');
        var minorTickDistance = resolveStyleScale(style, 'minorTickDistancePercent');
        var majorTickDistance = resolveStyleScale(style, 'majorTickDistancePercent');
        var scaleLabelDistance = resolveStyleScale(style, 'scaleLabelDistancePercent');
        var radiusScale = resolveStyleScale(style, 'gaugeRadiusPercent');
        var gaugeOffsetX = clamp(Number(style.gaugeOffsetXPercent) || 0, -50, 50) * 2 * scale;
        var gaugeOffsetY = clamp(Number(style.gaugeOffsetYPercent) || 0, -50, 50) * 2 * scale;
        var valueOffsetX = clamp(Number(style.valueOffsetXPercent) || 0, -100, 100) * scale;
        var valueOffsetY = clamp(Number(style.valueOffsetYPercent) || 0, -100, 100) * scale;
        var titleOffsetX = clamp(Number(style.titleOffsetXPercent) || 0, -100, 100) * scale;
        var titleOffsetY = clamp(Number(style.titleOffsetYPercent) || 0, -100, 100) * scale;
        var titleY = style.titlePosition === 'top' ? definition.titleTopY : definition.titleY;
        var lineWidth = resolveScaledMetric(definition.lineWidth * scale, ringWidth, 8, 24, 4, 40);
        var detailTypographyScale = Math.max(valueFontSize, unitFontSize);
        var detailHeight = resolveScaledMetric(58 * scale, detailTypographyScale, 34, 64, 28, 88);
        var arcPlateRadius = definition.radius * scale * radiusScale + lineWidth / 2 + 10 * scale;
        var circlePlateRadius = Math.max(
            arcPlateRadius,
            Math.abs(definition.detailY - definition.centerY) * scale + detailHeight / 2 + 10 * scale,
            Math.abs(titleY - definition.centerY) * scale
                + resolveScaledMetric(18 * scale, titleFontSize, 11, 20, 7, 36)
                + 8 * scale
        );

        var layout = {
            centerX: Math.round(width / 2 + gaugeOffsetX),
            centerY: Math.round(contentOffsetY + definition.centerY * scale + gaugeOffsetY),
            radius: Math.round(definition.radius * scale * radiusScale + lineWidth / 2),
            arcPlateRadius: Math.round(arcPlateRadius),
            circlePlateRadius: Math.round(circlePlateRadius),
            lineWidth: lineWidth,
            tickDistance: resolveScaledMetric(4 * scale, minorTickDistance, 2, 8, 1, 16),
            tickLength: resolveScaledMetric(5 * scale, minorTickLength, 4, 10, 2, 20),
            splitDistance: resolveScaledMetric(4 * scale, majorTickDistance, 2, 8, 1, 16),
            splitLength: resolveScaledMetric(definition.splitLength * scale, majorTickLength, 8, 20, 4, 36),
            labelDistance: resolveScaledMetric(
                lineWidth + Math.round(definition.labelGap * scale),
                scaleLabelDistance,
                10,
                64,
                5,
                96
            ),
            axisFontSize: resolveScaledMetric(14 * scale, scaleFontSize, 9, 18, 6, 30),
            pointerLength: Math.round(parseFloat(definition.pointerLength) * pointerLength * 100) / 100 + '%',
            pointerWidth: resolveScaledMetric(
                definition.pointerWidth * scale,
                pointerWidth,
                definition.pointerMinimum,
                definition.pointerMaximum,
                2,
                24
            ),
            anchorSize: clamp(Math.round(18 * scale), 10, 24),
            detailOffset: [Math.round(valueOffsetX), Math.round((definition.detailY - definition.centerY) * scale + valueOffsetY)],
            titleOffset: [Math.round(titleOffsetX), Math.round((titleY - definition.centerY) * scale + titleOffsetY)],
            titleFontSize: resolveScaledMetric(18 * scale, titleFontSize, 11, 20, 7, 36),
            detailWidth: resolveScaledMetric(
                244 * scale,
                detailTypographyScale,
                120,
                280,
                100,
                Math.max(100, width - 24)
            ),
            detailHeight: detailHeight,
            valueFontSize: resolveScaledMetric(
                definition.detailFontSize * scale,
                valueFontSize,
                definition.valueMinimum,
                50,
                10,
                72
            ),
            unitFontSize: resolveScaledMetric(
                definition.unitFontSize * scale,
                unitFontSize,
                definition.unitMinimum,
                definition.unitMaximum,
                7,
                48
            )
        };

        if (scaleMultiplier === undefined) {
            for (var factor = 1.2; factor > 1; factor -= 0.05) {
                var expanded = resolveGaugeLayout(preset, width, height, style, factor);
                if (gaugeLayoutFits(expanded, style, width, height)) {
                    return expanded;
                }
            }
        }

        return layout;
    }

    function formatAxisValue(value) {
        var absolute = Math.abs(Number(value));
        if (absolute >= 1000) {
            return Number(value).toLocaleString(undefined, { maximumFractionDigits: 0 });
        }

        return Number(value).toLocaleString(undefined, { maximumFractionDigits: 1 });
    }

    function normalizePreset(value) {
        return ['basic', 'simple', 'progress', 'speed'].indexOf(value) >= 0 ? value : 'simple';
    }

    function normalizeTheme(value) {
        return ['auto', 'dark', 'vintage', 'macarons', 'infographic', 'shine', 'roma'].indexOf(value) >= 0
            ? value
            : 'auto';
    }

    function resolveThemeColors(theme) {
        if (theme !== 'auto' && themePalettes[theme]) {
            return themePalettes[theme];
        }

        return {
            background: resolveColor('--symc-background', '#333438'),
            text: resolveColor('--symc-text', '#f4f5f7'),
            muted: resolveColor('--symc-muted', '#a7a9ae'),
            subtle: resolveColor('--symc-subtle', '#777a80'),
            border: resolveColor('--symc-border-strong', '#606268'),
            track: resolveColor('--symc-surface-raised', '#45474c'),
            surface: resolveColor('--symc-surface-raised', '#45474c'),
            accent: resolveColor('--symc-accent', '#55cbb5')
        };
    }

    function applyThemeColors(series, colors) {
        series.progress.itemStyle = { color: colors.accent };
        series.axisLine.lineStyle.color = Array.isArray(colors.gaugeAxisLine)
            ? colors.gaugeAxisLine
            : [[1, colors.track]];
        series.pointer.itemStyle = { color: colors.accent };
        series.anchor.itemStyle.color = colors.accent;
        series.anchor.itemStyle.borderColor = colors.text;
        series.axisTick.lineStyle.color = colors.border;
        series.splitLine.lineStyle.color = colors.muted;
        series.axisLabel.color = colors.subtle || colors.muted;
        series.title.color = colors.muted;
        series.detail.color = colors.text;

        return series;
    }

    function applyPreset(series, preset, layout, colors, minimum, maximum) {
        switch (preset) {
            case 'basic':
                series.progress.show = false;
                series.axisLine.roundCap = false;
                break;

            case 'progress':
                series.axisTick.show = false;
                break;

            case 'speed':
                series.startAngle = 180;
                series.endAngle = 0;
                series.splitNumber = resolveSpeedSplitNumber(minimum, maximum);
                series.progress.itemStyle.shadowColor = colorWithAlpha(colors.accent, 0.45);
                series.progress.itemStyle.shadowBlur = 10;
                series.progress.itemStyle.shadowOffsetX = 2;
                series.progress.itemStyle.shadowOffsetY = 2;
                series.axisTick.splitNumber = 2;
                series.axisTick.lineStyle.width = 2;
                series.splitLine.lineStyle.width = 3;
                series.pointer.icon = speedPointerIcon;
                series.pointer.width = layout.pointerWidth;
                series.pointer.offsetCenter = [0, '5%'];
                series.pointer.itemStyle.shadowColor = colorWithAlpha(colors.accent, 0.45);
                series.pointer.itemStyle.shadowBlur = 10;
                series.pointer.itemStyle.shadowOffsetX = 2;
                series.pointer.itemStyle.shadowOffsetY = 2;
                series.anchor.show = false;
                series.detail.width = layout.detailWidth;
                series.detail.height = layout.detailHeight;
                series.detail.lineHeight = layout.detailHeight;
                series.detail.backgroundColor = colors.surface;
                series.detail.borderColor = colors.muted;
                series.detail.borderWidth = 2;
                series.detail.borderRadius = 8;
                series.detail.color = colors.text;
                series.detail.rich = {
                    value: {
                        fontSize: layout.valueFontSize,
                        fontWeight: 'bolder',
                        color: colors.text
                    },
                    unit: {
                        fontSize: layout.unitFontSize,
                        color: colors.muted,
                        padding: [0, 0, -12, 8]
                    }
                };
                break;

            default:
                break;
        }

        return series;
    }

    function normalizePosition(value, fallback) {
        var position = Number(value);
        if (!Number.isFinite(position)) {
            position = fallback;
        }

        return ((position % 360) + 360) % 360;
    }

    function applyArcDesign(series, style) {
        var mode = ['preset', 'full', 'three-quarter', 'half', 'quarter', 'custom'].indexOf(style.arcMode) >= 0
            ? style.arcMode
            : 'preset';
        if (mode === 'preset') {
            return series;
        }

        var startPosition = normalizePosition(style.startPosition, 0);
        var sweep = {
            full: 360,
            'three-quarter': 270,
            half: 180,
            quarter: 90
        }[mode];
        if (mode === 'custom') {
            sweep = (normalizePosition(style.endPosition, 90) - startPosition + 360) % 360;
        }
        if (!sweep) {
            return series;
        }

        series.startAngle = 90 - startPosition;
        series.endAngle = series.startAngle - sweep;

        return series;
    }

    function resolveCustomPointerGeometry(style, layout) {
        var parts = String(style.pointerViewBox || '').trim().split(/[\s,]+/).map(Number);
        if (parts.length !== 4 || parts.some(function (value) { return !Number.isFinite(value); })
            || parts[2] <= 0 || parts[3] <= 0) {
            return null;
        }

        var minimumX = parts[0];
        var minimumY = parts[1];
        var viewBoxWidth = parts[2];
        var viewBoxHeight = parts[3];
        var pointerLength = layout.radius * parseFloat(layout.pointerLength) / 100;
        var pointerWidth = Math.max(
            2,
            pointerLength * viewBoxWidth / viewBoxHeight * resolveStyleScale(style, 'pointerWidthPercent')
        );
        var pivotX = Number(style.pointerPivotX);
        var pivotY = Number(style.pointerPivotY);
        var hasPivot = Number.isFinite(pivotX) && Number.isFinite(pivotY);
        pivotX = hasPivot ? clamp(pivotX, minimumX, minimumX + viewBoxWidth) : minimumX + viewBoxWidth / 2;
        pivotY = hasPivot ? clamp(pivotY, minimumY, minimumY + viewBoxHeight) : minimumY + viewBoxHeight;

        return {
            width: pointerWidth,
            hasPivot: hasPivot,
            showAnchor: typeof style.pointerShowAnchor === 'boolean' ? style.pointerShowAnchor : !hasPivot,
            offsetCenter: [
                (0.5 - (pivotX - minimumX) / viewBoxWidth) * pointerWidth,
                (1 - (pivotY - minimumY) / viewBoxHeight) * pointerLength
            ]
        };
    }

    function applyPointerShape(series, style, layout) {
        var shape = ['preset', 'needle', 'line', 'arrow', 'custom'].indexOf(style.pointerShape) >= 0
            ? style.pointerShape
            : 'preset';
        if (shape === 'preset') {
            return series;
        }

        if (shape === 'custom') {
            var path = typeof style.pointerPath === 'string' ? style.pointerPath.trim() : '';
            var geometry = resolveCustomPointerGeometry(style, layout);
            if (!path || !geometry || !/^[MmZzLlHhVvCcSsQqTtAa0-9eE+.,\-\s]+$/.test(path)) {
                return series;
            }
            series.pointer.icon = 'path://' + path;
            series.pointer.width = geometry.width;
            series.pointer.offsetCenter = geometry.offsetCenter;
        } else {
            series.pointer.icon = pointerIcons[shape];
            series.pointer.width = layout.pointerWidth;
            series.pointer.offsetCenter = [0, 0];
        }
        series.pointer.length = layout.pointerLength;
        series.anchor.show = shape !== 'custom' || geometry.showAnchor;

        return series;
    }

    function applyAnchorDesign(series, style, layout, colors) {
        var shape = ['preset', 'circle', 'ring', 'custom', 'hidden'].indexOf(style.anchorShape) >= 0
            ? style.anchorShape
            : 'preset';
        series.anchor.size = Math.round(
            layout.anchorSize * resolveStyleScale(style, 'anchorSizePercent') * 100
        ) / 100;
        series.anchor.itemStyle.borderWidth = Math.round(
            2 * resolveStyleScale(style, 'anchorBorderWidthPercent') * 100
        ) / 100;

        if (style.anchorColorMode === 'custom') {
            series.anchor.itemStyle.color = normalizeStyleColor(style.anchorColor, colors.accent);
            series.anchor.itemStyle.borderColor = normalizeStyleColor(style.anchorBorderColor, colors.text);
        }
        if (shape === 'preset') {
            return series;
        }
        if (shape === 'hidden') {
            series.anchor.show = false;
            return series;
        }

        series.anchor.show = true;
        series.anchor.icon = 'circle';
        if (shape === 'ring') {
            series.anchor.itemStyle.color = 'transparent';
        } else if (shape === 'custom') {
            var path = typeof style.anchorPath === 'string' ? style.anchorPath.trim() : '';
            if (!path || !/^[MmZzLlHhVvCcSsQqTtAa0-9eE+.,\-\s]+$/.test(path)) {
                series.anchor.show = false;
                return series;
            }
            series.anchor.icon = 'path://' + path;
        }

        return series;
    }

    function buildPlateFill(style, colors) {
        var startColor = normalizeStyleColor(style.plateColor, colors.surface);
        if (style.plateColorMode !== 'custom'
            || ['linear', 'radial'].indexOf(style.plateFillMode) < 0) {
            return style.plateColorMode === 'custom' ? startColor : colors.surface;
        }

        var colorStops = [{ offset: 0, color: startColor }];
        if (style.plateGradientMiddleEnabled) {
            colorStops.push({
                offset: 0.5,
                color: normalizeStyleColor(style.plateGradientMiddleColor, startColor)
            });
        }
        colorStops.push({
            offset: 1,
            color: normalizeStyleColor(style.plateGradientEndColor, startColor)
        });

        if (style.plateFillMode === 'radial') {
            var centerX = Number(style.plateGradientCenterXPercent);
            var centerY = Number(style.plateGradientCenterYPercent);
            var radius = Number(style.plateGradientRadiusPercent);
            return {
                type: 'radial',
                x: clamp(Number.isFinite(centerX) ? centerX : 50, 0, 100) / 100,
                y: clamp(Number.isFinite(centerY) ? centerY : 50, 0, 100) / 100,
                r: clamp(Number.isFinite(radius) ? radius : 75, 25, 150) / 100,
                colorStops: colorStops,
                global: false
            };
        }

        var directions = {
            'left-right': [0, 0.5, 1, 0.5],
            'diagonal-down': [0, 0, 1, 1],
            'diagonal-up': [0, 1, 1, 0],
            'top-bottom': [0.5, 0, 0.5, 1]
        };
        var direction = directions[style.plateGradientDirection] || directions['top-bottom'];

        return {
            type: 'linear',
            x: direction[0],
            y: direction[1],
            x2: direction[2],
            y2: direction[3],
            colorStops: colorStops,
            global: false
        };
    }

    function buildPlateGraphic(style, layout, series, colors) {
        var shape = ['hidden', 'circle', 'arc'].indexOf(style.plateShape) >= 0
            ? style.plateShape
            : 'hidden';
        if (shape === 'hidden') {
            return [];
        }

        var sizeScale = resolveStyleScale(style, 'plateSizePercent');
        var customColors = style.plateColorMode === 'custom';
        var sweep = Math.abs(series.startAngle - series.endAngle);
        var renderCircle = shape === 'circle' || sweep >= 359.999;
        var fill = style.plateTransparent
            ? 'rgba(0,0,0,0)'
            : buildPlateFill(style, colors);
        var border = normalizeStyleColor(customColors ? style.plateBorderColor : colors.border, colors.border);
        var graphic = {
            id: 'gauge-plate',
            type: renderCircle ? 'circle' : 'polygon',
            silent: true,
            z: -10,
            style: {
                fill: fill,
                stroke: border,
                lineWidth: Math.round(2 * resolveStyleScale(style, 'plateBorderWidthPercent') * 100) / 100,
                lineJoin: 'round'
            }
        };
        if (style.plateShadow) {
            graphic.style.shadowColor = 'rgba(0,0,0,0.35)';
            graphic.style.shadowBlur = Math.max(6, Math.round(layout.lineWidth * 0.7));
            graphic.style.shadowOffsetY = Math.max(2, Math.round(layout.lineWidth * 0.2));
        }
        if (renderCircle) {
            graphic.shape = {
                cx: layout.centerX,
                cy: layout.centerY,
                r: (shape === 'circle' ? layout.circlePlateRadius : layout.arcPlateRadius) * sizeScale
            };
        } else {
            var radius = layout.arcPlateRadius * sizeScale;
            var segments = Math.max(12, Math.ceil(sweep / 5));
            var points = [[layout.centerX, layout.centerY]];
            for (var index = 0; index <= segments; index += 1) {
                var fraction = index / segments;
                var angle = series.startAngle + (series.endAngle - series.startAngle) * fraction;
                var radians = angle * Math.PI / 180;
                points.push([
                    layout.centerX + radius * Math.cos(radians),
                    layout.centerY - radius * Math.sin(radians)
                ]);
            }
            graphic.shape = { points: points };
        }

        if (!style.plateBackgroundEnabled
            || !/^data:image\/svg\+xml;base64,[a-z0-9+/=]+$/i.test(String(style.plateBackgroundImage || ''))) {
            return [graphic];
        }

        var bounds;
        if (renderCircle) {
            bounds = {
                x: graphic.shape.cx - graphic.shape.r,
                y: graphic.shape.cy - graphic.shape.r,
                width: graphic.shape.r * 2,
                height: graphic.shape.r * 2
            };
        } else {
            var xValues = graphic.shape.points.map(function (point) { return point[0]; });
            var yValues = graphic.shape.points.map(function (point) { return point[1]; });
            var minimumX = Math.min.apply(Math, xValues);
            var maximumX = Math.max.apply(Math, xValues);
            var minimumY = Math.min.apply(Math, yValues);
            var maximumY = Math.max.apply(Math, yValues);
            bounds = {
                x: minimumX,
                y: minimumY,
                width: maximumX - minimumX,
                height: maximumY - minimumY
            };
        }

        var aspectRatio = Number(style.plateBackgroundAspectRatio);
        aspectRatio = Number.isFinite(aspectRatio) && aspectRatio > 0 ? aspectRatio : 1;
        var fit = ['contain', 'cover', 'stretch'].indexOf(style.plateBackgroundFit) >= 0
            ? style.plateBackgroundFit
            : 'cover';
        var imageWidth = bounds.width;
        var imageHeight = bounds.height;
        var boundsAspectRatio = bounds.width / Math.max(1, bounds.height);
        if (fit === 'contain') {
            if (boundsAspectRatio > aspectRatio) {
                imageWidth = bounds.height * aspectRatio;
            } else {
                imageHeight = bounds.width / aspectRatio;
            }
        } else if (fit === 'cover') {
            if (boundsAspectRatio > aspectRatio) {
                imageHeight = bounds.width / aspectRatio;
            } else {
                imageWidth = bounds.height * aspectRatio;
            }
        }
        var backgroundScale = clamp(Number(style.plateBackgroundSizePercent) || 100, 25, 200) / 100;
        imageWidth *= backgroundScale;
        imageHeight *= backgroundScale;
        var centerX = bounds.x + bounds.width / 2
            + bounds.width * clamp(Number(style.plateBackgroundOffsetXPercent) || 0, -100, 100) / 100;
        var centerY = bounds.y + bounds.height / 2
            + bounds.height * clamp(Number(style.plateBackgroundOffsetYPercent) || 0, -100, 100) / 100;
        var opacity = clamp(Number(style.plateBackgroundOpacityPercent), 0, 100) / 100;
        if (!Number.isFinite(opacity)) {
            opacity = 1;
        }
        var rotation = clamp(Number(style.plateBackgroundRotation) || 0, -180, 180) * Math.PI / 180;
        var clipPath = {
            type: renderCircle ? 'circle' : 'polygon',
            shape: renderCircle
                ? { cx: graphic.shape.cx, cy: graphic.shape.cy, r: graphic.shape.r }
                : { points: graphic.shape.points.map(function (point) { return point.slice(); }) }
        };
        var imageGraphic = {
            id: 'gauge-plate-background',
            type: 'group',
            silent: true,
            z: -9,
            clipPath: clipPath,
            children: [{
                type: 'image',
                rotation: rotation,
                originX: centerX,
                originY: centerY,
                style: {
                    image: style.plateBackgroundImage,
                    x: centerX - imageWidth / 2,
                    y: centerY - imageHeight / 2,
                    width: imageWidth,
                    height: imageHeight,
                    opacity: opacity
                }
            }]
        };
        var outline = {
            id: 'gauge-plate-outline',
            type: renderCircle ? 'circle' : 'polygon',
            silent: true,
            z: -8,
            shape: clipPath.shape,
            style: {
                fill: 'rgba(0,0,0,0)',
                stroke: border,
                lineWidth: graphic.style.lineWidth,
                lineJoin: 'round'
            }
        };

        return [graphic, imageGraphic, outline];
    }

    function normalizeStyleColor(value, fallback) {
        return /^#[0-9a-f]{6}$/i.test(String(value || '')) ? value : fallback;
    }

    function applyCustomColors(series, style, colors) {
        if (style.colorMode !== 'custom') {
            return series;
        }

        var pointer = normalizeStyleColor(style.pointerColor, colors.accent);
        var progress = normalizeStyleColor(style.progressColor, colors.accent);
        var ring = normalizeStyleColor(style.ringColor, colors.track);
        var scale = normalizeStyleColor(style.scaleColor, colors.muted);
        var value = normalizeStyleColor(style.valueColor, colors.text);
        var title = normalizeStyleColor(style.titleColor, colors.muted);
        series.progress.itemStyle.color = progress;
        series.axisLine.lineStyle.color = [[1, ring]];
        series.pointer.itemStyle.color = pointer;
        series.anchor.itemStyle.color = pointer;
        series.anchor.itemStyle.borderColor = value;
        series.axisTick.lineStyle.color = scale;
        series.splitLine.lineStyle.color = scale;
        series.axisLabel.color = scale;
        series.title.color = title;
        series.detail.color = value;
        if (series.detail.rich) {
            series.detail.rich.value.color = value;
            series.detail.rich.unit.color = value;
        }
        if (series.detail.borderColor) {
            series.detail.borderColor = scale;
        }
        if (series.progress.itemStyle.shadowColor) {
            series.progress.itemStyle.shadowColor = colorWithAlpha(progress, 0.45);
        }
        if (series.pointer.itemStyle.shadowColor) {
            series.pointer.itemStyle.shadowColor = colorWithAlpha(pointer, 0.45);
        }

        return series;
    }

    function resolveVisibility(mode, presetValue) {
        if (mode === 'show') {
            return true;
        }
        if (mode === 'hide') {
            return false;
        }

        return presetValue;
    }

    function applyAdvancedDesign(series, style, layout, colors) {
        var majorSplitCount = Number(style.majorSplitCount);
        var minorSplitCount = Number(style.minorSplitCount);
        if (Number.isInteger(majorSplitCount) && majorSplitCount >= 2 && majorSplitCount <= 24) {
            series.splitNumber = majorSplitCount;
        }
        if (Number.isInteger(minorSplitCount) && minorSplitCount >= 1 && minorSplitCount <= 10) {
            series.axisTick.splitNumber = minorSplitCount;
        }

        series.clockwise = style.gaugeDirection !== 'counterclockwise';
        series.axisLabel.rotate = ['tangential', 'radial'].indexOf(style.scaleLabelRotation) >= 0
            ? style.scaleLabelRotation
            : 0;
        series.pointer.show = resolveVisibility(style.pointerVisibility, series.pointer.show !== false);
        series.progress.show = resolveVisibility(style.progressVisibility, series.progress.show !== false);
        series.axisLine.show = resolveVisibility(style.ringVisibility, series.axisLine.show !== false);
        series.axisTick.show = resolveVisibility(style.minorTicksVisibility, series.axisTick.show !== false);
        series.splitLine.show = resolveVisibility(style.majorTicksVisibility, series.splitLine.show !== false);
        series.axisLabel.show = resolveVisibility(style.scaleLabelsVisibility, series.axisLabel.show !== false);
        series.detail.show = resolveVisibility(style.valueVisibility, series.detail.show !== false);
        series.title.show = resolveVisibility(style.titleVisibility, series.title.show !== false);
        series.progress.width = resolveScaledMetric(
            layout.lineWidth,
            resolveStyleScale(style, 'progressWidthPercent'),
            8,
            24,
            4,
            40
        );

        var detailBox = resolveVisibility(style.detailBoxVisibility, Boolean(series.detail.backgroundColor));
        if (detailBox) {
            series.detail.width = layout.detailWidth;
            series.detail.height = layout.detailHeight;
            series.detail.lineHeight = layout.detailHeight;
            series.detail.backgroundColor = style.detailColorMode === 'custom'
                ? normalizeStyleColor(style.detailBackgroundColor, colors.surface)
                : colors.surface;
            series.detail.borderColor = style.detailColorMode === 'custom'
                ? normalizeStyleColor(style.detailBorderColor, colors.muted)
                : colors.muted;
            series.detail.borderWidth = Math.max(1, Math.round(2 * resolveStyleScale(style, 'detailBorderWidthPercent') * 100) / 100);
            series.detail.borderRadius = Math.max(1, Math.round(8 * resolveStyleScale(style, 'detailCornerRadiusPercent') * 100) / 100);
        } else {
            delete series.detail.backgroundColor;
            delete series.detail.borderColor;
            delete series.detail.borderWidth;
            delete series.detail.borderRadius;
        }

        var shadowColor = colorWithAlpha(colors.accent, 0.45);
        [
            [series.pointer.itemStyle, style.pointerShadow],
            [series.progress.itemStyle, style.progressShadow],
            [series.axisLine.lineStyle, style.ringShadow],
            [series.anchor.itemStyle, style.anchorShadow],
            [series.detail, style.detailShadow]
        ].forEach(function (entry) {
            if (entry[1]) {
                entry[0].shadowColor = shadowColor;
                entry[0].shadowBlur = 10;
                entry[0].shadowOffsetX = 2;
                entry[0].shadowOffsetY = 2;
            }
        });

        if (style.scaleZonesEnabled && Array.isArray(style.scaleZones) && style.scaleZones.length) {
            var zones = [];
            style.scaleZones.forEach(function (zone) {
                if (Array.isArray(zone) && Number.isFinite(Number(zone[0]))
                    && Number(zone[0]) > 0 && Number(zone[0]) <= 1
                    && /^#[0-9a-f]{6}$/i.test(String(zone[1] || ''))) {
                    zones.push([Number(zone[0]), zone[1]]);
                }
            });
            if (zones.length && zones[zones.length - 1][0] < 1) {
                var baseColor = series.axisLine.lineStyle.color;
                var fallback = Array.isArray(baseColor) && baseColor.length
                    ? baseColor[baseColor.length - 1][1]
                    : colors.track;
                zones.push([1, fallback]);
            }
            if (zones.length) {
                series.axisLine.lineStyle.color = zones;
            }
        }

        return series;
    }

    function buildOption(payload, theme) {
        var gauge = payload.gauge || {};
        var minimum = Number(gauge.minimum);
        var maximum = Number(gauge.maximum);
        var value = Number(payload.value);
        var decimals = Math.max(0, Math.min(6, Number(gauge.decimals) || 0));
        var title = typeof gauge.title === 'string' ? gauge.title : '';
        var unit = typeof gauge.unit === 'string' ? gauge.unit : '';
        var preset = normalizePreset(gauge.preset);
        var style = gauge.style && typeof gauge.style === 'object' ? gauge.style : {};
        var width = Math.max(chartElement.clientWidth, 1);
        var height = Math.max(chartElement.clientHeight, 160);
        var layout = resolveGaugeLayout(preset, width, height, style);
        var colors = resolveThemeColors(theme);

        chartElement.setAttribute(
            'aria-label',
            (title ? title + ': ' : '')
                + formatValue(value, decimals, unit)
                + ' (' + formatAxisValue(minimum) + '–' + formatAxisValue(maximum) + ')'
        );

        var series = {
            type: 'gauge',
            min: minimum,
            max: maximum,
            startAngle: 210,
            endAngle: -30,
            center: [layout.centerX, layout.centerY],
            radius: layout.radius,
            splitNumber: 10,
            progress: {
                show: true,
                roundCap: true,
                width: layout.lineWidth
            },
            axisLine: {
                roundCap: true,
                lineStyle: {
                    width: layout.lineWidth
                }
            },
            pointer: {
                show: true,
                length: layout.pointerLength,
                width: layout.pointerWidth
            },
            anchor: {
                show: true,
                showAbove: true,
                size: layout.anchorSize,
                itemStyle: {
                    borderWidth: 2
                }
            },
            axisTick: {
                show: true,
                distance: layout.tickDistance,
                splitNumber: 5,
                length: layout.tickLength,
                lineStyle: { width: 1 }
            },
            splitLine: {
                distance: layout.splitDistance,
                length: layout.splitLength,
                lineStyle: { width: 2 }
            },
            axisLabel: {
                distance: layout.labelDistance,
                fontSize: layout.axisFontSize,
                formatter: formatAxisValue
            },
            title: {
                show: title !== '',
                offsetCenter: layout.titleOffset,
                fontSize: layout.titleFontSize
            },
            detail: {
                valueAnimation: !reduceMotion,
                offsetCenter: layout.detailOffset,
                fontWeight: 600,
                rich: {
                    value: {
                        fontSize: layout.valueFontSize,
                        fontWeight: 600,
                        color: colors.text
                    },
                    unit: {
                        fontSize: layout.unitFontSize,
                        fontWeight: 600,
                        color: colors.text,
                        padding: [0, 0, 0, 8]
                    }
                },
                formatter: function () {
                    var shownUnit = style.unitVisibility === 'hide' ? '' : unit;
                    return formatSpeedValue(value, decimals, shownUnit);
                }
            },
            data: [{ value: value, name: title }]
        };

        series = applyThemeColors(series, colors);
        series = applyPreset(series, preset, layout, colors, minimum, maximum);
        series = applyArcDesign(series, style);
        series = applyPointerShape(series, style, layout);
        series = applyCustomColors(series, style, colors);
        series = applyAnchorDesign(series, style, layout, colors);
        series = applyAdvancedDesign(series, style, layout, colors);

        var option = {
            animation: !reduceMotion,
            animationDuration: reduceMotion ? 0 : 500,
            aria: {
                enabled: true,
                decal: { show: false }
            },
            tooltip: {
                trigger: 'item',
                formatter: function () {
                    return formatValue(value, decimals, unit);
                }
            },
            graphic: buildPlateGraphic(style, layout, series, colors),
            series: [series]
        };
        option.backgroundColor = colors.background;

        return option;
    }

    function render(state) {
        currentState = state;
        if (!state || state.status !== 'ready' || !state.chart) {
            displayError(state && state.error ? state.error : 'The Gauge value could not be loaded.');
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
            currentTheme = null;
        }
    });

    render(currentState);
}());
