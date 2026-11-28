(function () {
    'use strict';

    var bootstrap = window.SYMC_VISUALIZATION;
    var design = window.SYMC_ECHARTS_DESIGN;
    var chartElement = document.getElementById('echarts-gauge-chart');
    var errorElement = document.getElementById('echarts-gauge-error');
    var chart = null;
    var theme;
    var currentState;
    var rotorSignature = null;
    var hasCompassImage = false;
    var previousDirection = null;
    var dial = {bearing: 0};
    var dialTarget = null;
    var dialAnimator = null;
    var motion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)');
    var readError = 'The wind values could not be loaded.';
    var retain = design.createTransientFailureRetainer(
        chartElement, document.getElementById('echarts-gauge-warning'), translate, readError
    );

    function translate(text) {
        return (bootstrap.translations || {})[text] || text;
    }

    function clamp(value, min, max) {
        return Math.min(max, Math.max(min, value));
    }

    function bezel() {
        return {type: 'linear', x: 0, y: 0, x2: 1, y2: 1, colorStops: [
            {offset: 0, color: '#EEF3F7'}, {offset: 0.32, color: '#A6ACB3'},
            {offset: 0.62, color: '#555D64'}, {offset: 1, color: '#B6BDC4'}
        ]};
    }

    function reading(model) {
        var locale = model.language || document.documentElement.lang || 'en';
        var speed = '';
        if (model.speed != null) {
            var decimals = model.speedDecimals;
            speed = model.windLabel + ': ' + model.speed.toLocaleString(locale, {
                minimumFractionDigits: decimals, maximumFractionDigits: decimals
            }) + (model.speedUnit ? ' ' + model.speedUnit : '');
        }
        var degrees = model.direction.toLocaleString(locale, {maximumFractionDigits: 1}) + '°';
        var bearing = model.labels[Math.round(model.direction / 45) % 8] + ' ' + degrees;
        return {
            header: [speed, model.style.showDegreeValue ? bearing : ''].filter(Boolean).join(' · '),
            accessible: [model.title, bearing, speed].filter(Boolean).join(', ')
        };
    }

    function text(id, value, x, y, color, size, width) {
        return {id: id, type: 'text', silent: true, z: 6, x: x, y: y, invisible: value === '',
            style: {text: value, fill: color, align: 'center', verticalAlign: 'middle',
                font: '600 ' + size + 'px sans-serif', width: width, overflow: 'truncate'}};
    }

    // Updating a graphic element restarts its ECharts keyframes. Send the leaf only on a real change.
    function updateRotor(graphic, model, x, y, radius, animate) {
        var style = model.style;
        if (!style.showRotor) {
            if (rotorSignature !== null) { graphic.push({id: 'wind-leaf', $action: 'remove'}); }
            rotorSignature = null;
            return;
        }
        var duration = animate && model.speed > 0
            ? Math.round(clamp(4500 / (1 + model.speed / 5), 320, 4500)) : 0;
        var color = style.rotorColor || '#70CBAF';
        var signature = [x, y, radius, duration, color].join('|');
        if (signature === rotorSignature) { return; }
        rotorSignature = signature;
        var leaf = {id: 'wind-leaf', type: 'polygon', $action: 'replace', silent: true, z: 2,
            x: x, y: y, shape: {smooth: 0.3, points: [
                [0, -radius], [radius * 0.46, -radius * 0.25], [radius * 0.28, radius * 0.34],
                [-radius * 0.15, radius * 0.62], [-radius * 0.32, radius * 0.94],
                [-radius * 0.27, radius * 0.47], [-radius * 0.48, -radius * 0.13]
            ]}, style: {fill: color}};
        if (duration) {
            leaf.keyframeAnimation = {duration: duration, loop: true,
                keyframes: [{percent: 0, rotation: 0}, {percent: 1, rotation: -2 * Math.PI, easing: 'linear'}]};
        }
        graphic.push(leaf);
    }

    function stopDial() {
        if (dialAnimator) { dialAnimator.stop(); }
        dialAnimator = null;
        dialTarget = null;
    }

    // ECharts measures gauge angles counterclockwise from east. Move the scale and its
    // data angle together so that the needle stays at twelve o'clock throughout the turn.
    function dialPosition(bearing) {
        var angle = (bearing % 360 + 360) % 360;
        return {startAngle: 90 + angle, endAngle: -270 + angle, data: [{value: angle}]};
    }

    function rotateDial(direction, animation) {
        if (dialTarget === direction && (animation.animationDurationUpdate || !dialAnimator)) { return; }
        var first = dialTarget === null;
        stopDial();
        dialTarget = direction;
        if (first || !animation.animationDurationUpdate) {
            dial.bearing = direction;
            return;
        }
        var distance = ((direction - dial.bearing) % 360 + 540) % 360 - 180;
        if (!distance) { return; }
        dialAnimator = chart.getZr().animation.animate(dial)
            .when(animation.animationDurationUpdate, {bearing: dial.bearing + distance})
            .delay(animation.animationDelayUpdate)
            .during(function () {
                var frame = {series: [Object.assign({id: 'wind-bearing'}, dialPosition(dial.bearing))]};
                if (currentState.chart.style.compassImage) {
                    frame.graphic = [{id: 'wind-compass', rotation: dial.bearing * Math.PI / 180}];
                }
                chart.setOption(frame);
            })
            .done(function () { dialAnimator = null; dial.bearing = direction; })
            .start(animation.animationEasingUpdate);
    }

    function buildOption(model) {
        var style = model.style;
        var palette = (bootstrap.options.echartsThemes || {})[model.theme] || {};
        var css = window.getComputedStyle(chartElement);
        var textColor = (model.theme === 'auto' && css.color) || palette.text || '#F4F5F7';
        var background = model.theme === 'auto' ? 'transparent' : palette.background;
        var width = Math.max(1, chartElement.clientWidth);
        var height = Math.max(1, chartElement.clientHeight);
        var inset = Math.min(bootstrap.options.tileHeaderInset || 0, height);
        var font = clamp(Math.min(width, height - inset) * 0.067, 10, 26);
        var top = inset + 4;
        var graphic = [];
        var value = reading(model);
        chartElement.setAttribute('aria-label', value.accessible);
        graphic.push(text('wind-title', model.title, width / 2, top + font / 2,
            style.titleColor || textColor, font, width - 8));
        if (model.title) { top += font + 4; }
        graphic.push(text('wind-speed', value.header, width / 2, top + font / 2,
            style.valueColor || textColor, font, width - 8));
        if (value.header) { top += font + 4; }
        var cx = width / 2;
        var cy = (top + height) / 2;
        var radius = Math.max(1, Math.min(width, height - top) * 0.48);
        graphic.push(
            {id: 'wind-bezel', type: 'circle', z: 0, silent: true, invisible: !!style.compassImage,
                shape: {cx: cx, cy: cy, r: radius}, style: {fill: bezel()}},
            {id: 'wind-dial', type: 'circle', z: 1, silent: true, invisible: !!style.compassImage,
                shape: {cx: cx, cy: cy, r: radius * 0.91}, style: {fill: style.dialColor || '#616871'}}
        );
        var r = radius * 0.90;
        var animation = design.animationOptions(style, {enabled: true, initialDuration: 500, updateDuration: 500});
        // Gauge interpolation follows its linear 0–360 scale. Across north, snap to the
        // reported bearing so a 359° to 1° change does not sweep across south.
        if (style.rotateCompass) {
            rotateDial(model.direction, animation);
        } else {
            stopDial();
        }
        if (style.compassImage) {
            graphic.push({id: 'wind-compass', type: 'image', silent: true, z: 1, x: cx, y: cy,
                rotation: style.rotateCompass ? dial.bearing * Math.PI / 180 : 0,
                style: {image: style.compassImage, x: -radius, y: -radius, width: 2 * radius, height: 2 * radius}});
        } else if (hasCompassImage) {
            graphic.push({id: 'wind-compass', $action: 'remove'});
        }
        hasCompassImage = !!style.compassImage;
        if (!style.rotateCompass && previousDirection !== null && Math.abs(model.direction - previousDirection) > 180) {
            animation.animationDurationUpdate = 0;
            animation.animationDelayUpdate = 0;
        }
        previousDirection = model.direction;
        updateRotor(graphic, model, cx, cy + 0.38 * r, r * 0.15, animation.animation);
        return Object.assign({}, animation, {
            backgroundColor: design.chartBackground(bootstrap, model, background),
            graphic: graphic,
            tooltip: {trigger: 'item', renderMode: 'richText', formatter: function () { return value.accessible; }},
            series: [Object.assign({id: 'wind-bearing', type: 'gauge', z: 3, center: [cx, cy], radius: r,
                startAngle: 90, endAngle: -270, clockwise: true, min: 0, max: 360, splitNumber: 8,
                axisLine: {show: !style.compassImage, lineStyle: {width: r * 0.105, color: [[1, style.ringColor || '#1678BD']]}},
                axisTick: {show: !style.compassImage, distance: -r * 0.10, splitNumber: 6, length: r * 0.06,
                    lineStyle: {color: style.tickColor || '#EEF4FA', width: Math.max(1, r * 0.009)}},
                splitLine: {show: !style.compassImage, distance: -r * 0.10, length: r * 0.11,
                    lineStyle: {color: style.tickColor || '#F47B60', width: Math.max(1, r * 0.018)}},
                axisLabel: {show: !style.compassImage, distance: r * 0.16, color: style.cardinalColor || '#FFFFFF',
                    fontSize: clamp(r * 0.14, 7, 30), fontWeight: 'bold',
                    formatter: function (angle) { return angle === 360 ? '' : model.labels[Math.round(angle / 45)]; }},
                pointer: {width: r * 0.145, length: '73%', itemStyle: {color: style.pointerColor || '#42B7FF'},
                    icon: 'path://M 0 -100 L 11 -5 L 0 0 L -11 -5 Z'},
                anchor: {show: true, showAbove: true, size: r * 0.155, itemStyle: {
                    color: style.hubColor || '#F1F5F8', borderColor: '#858E97', borderWidth: Math.max(1, r * 0.012)}},
                title: {show: false}, detail: {show: false}, data: [{value: model.direction}],
                animation: animation.animation && !style.rotateCompass
            }, style.rotateCompass ? dialPosition(dial.bearing) : {})]
        });
    }

    function render(state) {
        if (retain(state, currentState, chart)) { return; }
        currentState = state;
        if (!state || state.status !== 'ready' || !window.echarts) {
            stopDial();
            if (chart) { chart.clear(); }
            hasCompassImage = false;
            rotorSignature = null;
            previousDirection = null;
            chartElement.hidden = true;
            errorElement.textContent = translate((state && state.error) ||
                (!window.echarts ? 'Apache ECharts could not be initialized.' : readError));
            errorElement.hidden = false;
            return;
        }
        chartElement.hidden = false;
        errorElement.hidden = true;
        if (!chart || theme !== state.chart.theme) {
            stopDial();
            if (chart) { chart.dispose(); }
            theme = state.chart.theme;
            chart = window.echarts.init(chartElement, theme === 'auto' ? null : theme, {renderer: 'canvas'});
            hasCompassImage = false;
            rotorSignature = null;
            previousDirection = null;
        }
        chart.setOption(buildOption(state.chart));
    }

    window.handleMessage = function (message) {
        var state = design.parseVisualizationState(message);
        if (state) { render(state); }
    };
    function resize() {
        if (design.resizeChartIfNeeded(chart, chartElement) && currentState.status === 'ready') {
            render(currentState);
        }
    }
    if (window.ResizeObserver) { new window.ResizeObserver(resize).observe(chartElement); }
    else { window.addEventListener('resize', resize); }
    if (motion && motion.addEventListener) { motion.addEventListener('change', function () { render(currentState); }); }
    window.addEventListener('beforeunload', function () { stopDial(); if (chart) { chart.dispose(); } });
    render(bootstrap.state);
}());
