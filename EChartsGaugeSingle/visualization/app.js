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
    var gaugeLayoutDefinitions = {
        basic: {
            centerY: 196,
            radius: 132,
            lineWidth: 16,
            pointerLength: '73%',
            detailY: 294,
            titleY: 352,
            splitLength: 10,
            detailFontSize: 38
        },
        simple: {
            centerY: 196,
            radius: 132,
            lineWidth: 18,
            pointerLength: '70%',
            detailY: 294,
            titleY: 352,
            splitLength: 10,
            detailFontSize: 38
        },
        progress: {
            centerY: 190,
            radius: 132,
            lineWidth: 20,
            pointerLength: '66%',
            detailY: 306,
            titleY: 360,
            splitLength: 14,
            detailFontSize: 46
        },
        speed: {
            centerY: 232,
            radius: 142,
            lineWidth: 18,
            pointerLength: '75%',
            detailY: 319,
            titleY: 370,
            splitLength: 12,
            detailFontSize: 42
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

    function resolveGaugeLayout(preset, width, height) {
        var definition = gaugeLayoutDefinitions[preset] || gaugeLayoutDefinitions.simple;
        var scale = Math.min(width / 440, height / 400);
        var contentOffsetY = (height - 400 * scale) / 2;
        var lineWidth = clamp(Math.round(definition.lineWidth * scale), 8, 24);

        return {
            centerX: Math.round(width / 2),
            centerY: Math.round(contentOffsetY + definition.centerY * scale),
            radius: Math.round(definition.radius * scale + lineWidth / 2),
            lineWidth: lineWidth,
            tickDistance: clamp(Math.round(4 * scale), 2, 8),
            tickLength: clamp(Math.round(5 * scale), 4, 10),
            splitDistance: clamp(Math.round(4 * scale), 2, 8),
            splitLength: clamp(Math.round(definition.splitLength * scale), 8, 20),
            labelDistance: clamp(Math.round(30 * scale), 18, 42),
            axisFontSize: clamp(Math.round(14 * scale), 9, 18),
            pointerLength: definition.pointerLength,
            pointerWidth: clamp(Math.round(8 * scale), 4, 10),
            anchorSize: clamp(Math.round(18 * scale), 10, 24),
            detailOffset: Math.round((definition.detailY - definition.centerY) * scale),
            detailFontSize: clamp(Math.round(definition.detailFontSize * scale), 20, 50),
            titleOffset: Math.round((definition.titleY - definition.centerY) * scale),
            titleFontSize: clamp(Math.round(18 * scale), 11, 20),
            detailWidth: clamp(Math.round(244 * scale), 120, 280),
            detailHeight: clamp(Math.round(58 * scale), 34, 64),
            valueFontSize: clamp(Math.round(42 * scale), 28, 50),
            unitFontSize: clamp(Math.round(18 * scale), 14, 20)
        };
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
            background: 'transparent',
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
                series.pointer.width = clamp(Math.round(16 * Math.min(layout.lineWidth / 18, 1)), 10, 16);
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

    function buildOption(payload, theme) {
        var gauge = payload.gauge || {};
        var minimum = Number(gauge.minimum);
        var maximum = Number(gauge.maximum);
        var value = Number(payload.value);
        var decimals = Math.max(0, Math.min(6, Number(gauge.decimals) || 0));
        var title = typeof gauge.title === 'string' ? gauge.title : '';
        var unit = typeof gauge.unit === 'string' ? gauge.unit : '';
        var preset = normalizePreset(gauge.preset);
        var width = Math.max(chartElement.clientWidth, 1);
        var height = Math.max(chartElement.clientHeight, 160);
        var layout = resolveGaugeLayout(preset, width, height);
        var automaticTheme = theme === 'auto';
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
                offsetCenter: [0, layout.titleOffset],
                fontSize: layout.titleFontSize
            },
            detail: {
                valueAnimation: !reduceMotion,
                offsetCenter: [0, layout.detailOffset],
                fontSize: layout.detailFontSize,
                fontWeight: 600,
                formatter: function () {
                    return preset === 'speed'
                        ? formatSpeedValue(value, decimals, unit)
                        : formatValue(value, decimals, unit);
                }
            },
            data: [{ value: value, name: title }]
        };

        series = applyThemeColors(series, colors);
        series = applyPreset(series, preset, layout, colors, minimum, maximum);

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
            series: [series]
        };
        if (!automaticTheme) {
            option.backgroundColor = colors.background;
        }

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
