(function (global) {
    'use strict';

    function parseColor(color) {
        var value = String(color || '').trim();
        var match = value.match(/^#([0-9a-f]{3}|[0-9a-f]{6})$/i);
        if (match) {
            var hex = match[1];
            if (hex.length === 3) {
                hex = hex.split('').map(function (channel) { return channel + channel; }).join('');
            }
            return [
                parseInt(hex.slice(0, 2), 16),
                parseInt(hex.slice(2, 4), 16),
                parseInt(hex.slice(4, 6), 16)
            ];
        }

        match = value.match(/^rgba?\(\s*([\d.]+)\s*,\s*([\d.]+)\s*,\s*([\d.]+)/i);
        if (!match) { return null; }
        return match.slice(1, 4).map(function (channel) {
            return Math.max(0, Math.min(255, Number(channel) || 0));
        });
    }

    function relativeLuminance(color) {
        var channels = parseColor(color);
        if (!channels) { return null; }
        var linear = channels.map(function (channel) {
            var normalized = channel / 255;
            return normalized <= 0.04045
                ? normalized / 12.92
                : Math.pow((normalized + 0.055) / 1.055, 2.4);
        });
        return (0.2126 * linear[0]) + (0.7152 * linear[1]) + (0.0722 * linear[2]);
    }

    function contrastRatio(first, second) {
        var firstLuminance = relativeLuminance(first);
        var secondLuminance = relativeLuminance(second);
        if (firstLuminance === null || secondLuminance === null) { return 0; }
        return (Math.max(firstLuminance, secondLuminance) + 0.05)
            / (Math.min(firstLuminance, secondLuminance) + 0.05);
    }

    function readableTextColor(background, firstCandidate, secondCandidate) {
        var first = firstCandidate || '#FFFFFF';
        var second = secondCandidate || '#111111';
        return contrastRatio(background, first) >= contrastRatio(background, second) ? first : second;
    }

    function opacityFromPercent(value, defaultPercent) {
        var fallback = defaultPercent == null ? 100 : Number(defaultPercent);
        if (!Number.isFinite(fallback)) { fallback = 100; }
        var percent = value == null ? fallback : Number(value);
        if (!Number.isFinite(percent)) { percent = fallback; }
        return Math.max(0, Math.min(100, percent)) / 100;
    }

    function prefersReducedMotion() {
        return typeof global.matchMedia === 'function'
            && global.matchMedia('(prefers-reduced-motion: reduce)').matches === true;
    }

    function animationOptions(style, defaults) {
        var settings = style || {};
        var fallback = defaults || {};
        var enabled = settings.animationEnabled == null
            ? fallback.enabled !== false : settings.animationEnabled === true;
        var animate = enabled && !prefersReducedMotion();
        function duration(value, defaultValue) {
            var number = Number(value);
            return Number.isInteger(number) && number >= 0 && number <= 3000 ? number : defaultValue;
        }
        return {
            animation: animate,
            animationDuration: animate
                ? duration(settings.animationDuration, fallback.initialDuration) : 0,
            animationDurationUpdate: animate
                ? duration(settings.animationDurationUpdate, fallback.updateDuration) : 0
        };
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

    function barOutlineStyle(width, color, themeBorder) {
        var pixels = Number(width);
        if (!Number.isFinite(pixels) || pixels <= 0) {
            return { borderWidth: 0 };
        }
        return { borderWidth: pixels, borderColor: color || themeBorder };
    }

    function resizeChartIfNeeded(chart, element) {
        if (!chart || (chart.getWidth() === element.clientWidth && chart.getHeight() === element.clientHeight)) {
            return false;
        }
        chart.resize();
        return true;
    }

    global.SYMC_ECHARTS_DESIGN = Object.freeze({
        contrastRatio: contrastRatio,
        readableTextColor: readableTextColor,
        relativeLuminance: relativeLuminance,
        opacityFromPercent: opacityFromPercent,
        prefersReducedMotion: prefersReducedMotion,
        animationOptions: animationOptions,
        colorWithAlpha: colorWithAlpha,
        barOutlineStyle: barOutlineStyle,
        resizeChartIfNeeded: resizeChartIfNeeded
    });
}(window));
