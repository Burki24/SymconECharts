(function () {
    'use strict';

    var bootstrap = window.SYMC_VISUALIZATION || {};
    var translations = bootstrap.translations || {};
    var chartElement = document.getElementById('echarts-gauge-chart');
    var errorElement = document.getElementById('echarts-gauge-error');
    var chart = null;
    var currentState = bootstrap.state || null;
    var reduceMotion = window.matchMedia
        && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

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

    function formatValue(value, decimals, unit) {
        var formatted = Number(value).toLocaleString(undefined, {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        });

        return unit ? formatted + ' ' + unit : formatted;
    }

    function formatAxisValue(value) {
        var absolute = Math.abs(Number(value));
        if (absolute >= 1000) {
            return Number(value).toLocaleString(undefined, { maximumFractionDigits: 0 });
        }

        return Number(value).toLocaleString(undefined, { maximumFractionDigits: 1 });
    }

    function buildOption(payload) {
        var gauge = payload.gauge || {};
        var minimum = Number(gauge.minimum);
        var maximum = Number(gauge.maximum);
        var value = Number(payload.value);
        var decimals = Math.max(0, Math.min(6, Number(gauge.decimals) || 0));
        var title = typeof gauge.title === 'string' ? gauge.title : '';
        var unit = typeof gauge.unit === 'string' ? gauge.unit : '';
        var width = Math.max(chartElement.clientWidth, 240);
        var lineWidth = Math.max(10, Math.min(22, Math.round(width * 0.055)));
        var pointerWidth = Math.max(4, Math.min(8, Math.round(width * 0.018)));
        var text = resolveColor('--symc-text', '#f4f5f7');
        var muted = resolveColor('--symc-muted', '#a7a9ae');
        var subtle = resolveColor('--symc-subtle', '#777a80');
        var border = resolveColor('--symc-border-strong', '#606268');
        var surface = resolveColor('--symc-surface-raised', '#45474c');
        var accent = resolveColor('--symc-accent', '#55cbb5');

        chartElement.setAttribute(
            'aria-label',
            (title ? title + ': ' : '')
                + formatValue(value, decimals, unit)
                + ' (' + formatAxisValue(minimum) + '–' + formatAxisValue(maximum) + ')'
        );

        return {
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
            series: [{
                type: 'gauge',
                min: minimum,
                max: maximum,
                startAngle: 210,
                endAngle: -30,
                center: ['50%', '54%'],
                radius: width < 320 ? '84%' : '88%',
                splitNumber: 10,
                progress: {
                    show: true,
                    roundCap: true,
                    width: lineWidth,
                    itemStyle: { color: accent }
                },
                axisLine: {
                    roundCap: true,
                    lineStyle: {
                        width: lineWidth,
                        color: [[1, surface]]
                    }
                },
                pointer: {
                    show: true,
                    length: '57%',
                    width: pointerWidth,
                    itemStyle: { color: accent }
                },
                anchor: {
                    show: true,
                    showAbove: true,
                    size: Math.max(10, Math.min(18, Math.round(width * 0.045))),
                    itemStyle: {
                        color: accent,
                        borderColor: text,
                        borderWidth: 2
                    }
                },
                axisTick: {
                    distance: -lineWidth - 7,
                    splitNumber: 5,
                    length: 5,
                    lineStyle: { color: border, width: 1 }
                },
                splitLine: {
                    distance: -lineWidth - 8,
                    length: 10,
                    lineStyle: { color: muted, width: 2 }
                },
                axisLabel: {
                    distance: lineWidth + 13,
                    color: subtle,
                    fontSize: Math.max(9, Math.min(13, Math.round(width * 0.034))),
                    formatter: formatAxisValue
                },
                title: {
                    show: title !== '',
                    offsetCenter: [0, '72%'],
                    color: muted,
                    fontSize: Math.max(11, Math.min(16, Math.round(width * 0.041)))
                },
                detail: {
                    valueAnimation: !reduceMotion,
                    offsetCenter: [0, '38%'],
                    color: text,
                    fontSize: Math.max(20, Math.min(38, Math.round(width * 0.095))),
                    fontWeight: 600,
                    formatter: function () {
                        return formatValue(value, decimals, unit);
                    }
                },
                data: [{ value: value, name: title }]
            }]
        };
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
        if (!chart) {
            chart = window.echarts.init(chartElement, null, { renderer: 'canvas' });
        }
        chart.setOption(buildOption(state.chart), true);
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
                    chart.setOption(buildOption(currentState.chart), true);
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
