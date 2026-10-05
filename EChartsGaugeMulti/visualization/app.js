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

    function resolveGrid(count, width, height, hasTitle) {
        var top = hasTitle ? Math.max(32, height * 0.1) : 4;
        var usableHeight = Math.max(120, height - top);
        var best = null;
        for (var columns = 1; columns <= Math.min(count, 4); columns += 1) {
            var rows = Math.ceil(count / columns);
            var cellWidth = width / columns;
            var cellHeight = usableHeight / rows;
            var radius = Math.min(cellWidth * 0.38, cellHeight * 0.4);
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

    function buildSeries(item, index, grid, colors, style) {
        var column = index % grid.columns;
        var row = Math.floor(index / grid.columns);
        var centerX = column * grid.cellWidth + grid.cellWidth / 2;
        var centerY = grid.top + row * grid.cellHeight + grid.cellHeight * 0.48;
        var radius = Math.max(38, grid.radius);
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

    function buildOption(model, theme) {
        var items = Array.isArray(model.items) ? model.items : [];
        var gauge = model.gauge || {};
        var style = gauge.style || {};
        var colors = colorsFor(theme);
        var width = Math.max(chartElement.clientWidth, 240);
        var height = Math.max(chartElement.clientHeight, 180);
        var title = String(gauge.title || '');
        var grid = resolveGrid(items.length, width, height, title !== '');

        return {
            backgroundColor: colors.background,
            animation: !reduceMotion,
            animationDuration: reduceMotion ? 0 : 500,
            aria: { enabled: true, decal: { show: false } },
            title: {
                show: title !== '',
                text: title,
                left: 'center',
                top: 6,
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
            series: items.map(function (item, index) {
                return buildSeries(item, index, grid, colors, style);
            })
        };
    }

    function displayError(message) {
        if (chart) {
            chart.clear();
        }
        chartElement.hidden = true;
        errorElement.textContent = translate(message || 'The Multi Gauge values could not be loaded.');
        errorElement.hidden = false;
    }

    function render(state) {
        currentState = state;
        if (!state || state.status !== 'ready' || !state.chart) {
            displayError(state && state.error ? state.error : 'The Multi Gauge values could not be loaded.');
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
