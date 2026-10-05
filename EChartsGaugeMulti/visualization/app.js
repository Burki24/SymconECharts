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

    function resolveGrid(count, width, height, hasTitle, headerInset) {
        var top = headerInset + (hasTitle ? Math.max(32, height * 0.1) : 4);
        var usableHeight = Math.max(1, height - top);
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

    function itemColor(index, colors) {
        var palette = [
            colors.accent, '#5C83E9', '#DB7393', '#E6A547',
            '#8F6BD7', '#5BAE79', '#E5754F', '#3EA8C1',
            '#C482C7', '#86A646', '#D96C68', '#5B92B1',
            '#B98955', '#7493DD', '#A77DBC', '#64B8A4'
        ];
        return palette[index % palette.length];
    }

    function buildSeries(item, index, grid, colors, style) {
        var column = index % grid.columns;
        var row = Math.floor(index / grid.columns);
        var centerX = column * grid.cellWidth + grid.cellWidth / 2;
        var centerY = grid.top + row * grid.cellHeight + grid.cellHeight * 0.48;
        var radius = grid.radius;
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
        var radius = grid.radius * 0.9;
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

    function buildOption(model, theme) {
        var items = Array.isArray(model.items) ? model.items : [];
        var gauge = model.gauge || {};
        var style = gauge.style || {};
        var colors = colorsFor(theme);
        var width = Math.max(chartElement.clientWidth, 240);
        var height = Math.max(chartElement.clientHeight, 180);
        var title = String(gauge.title || '');
        var headerInset = bootstrap.mode === 'symcon' ? 64 : 0;
        var preset = String(gauge.preset || 'multi-title');
        var grid = preset === 'ring-concentric'
            ? null
            : resolveGrid(items.length, width, height, title !== '', headerInset);
        var concentric = preset === 'ring-concentric'
            ? buildConcentricLayout(items, width, height, headerInset, title !== '', colors, style)
            : null;

        return {
            backgroundColor: colors.background,
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
            graphic: concentric ? concentric.graphic : [],
            series: concentric ? concentric.series : items.map(function (item, index) {
                return preset === 'ring-grid'
                    ? buildRingGridSeries(item, index, grid, colors, style)
                    : buildSeries(item, index, grid, colors, style);
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
