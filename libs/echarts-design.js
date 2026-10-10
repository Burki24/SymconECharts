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

    global.SYMC_ECHARTS_DESIGN = Object.freeze({
        contrastRatio: contrastRatio,
        readableTextColor: readableTextColor,
        relativeLuminance: relativeLuminance,
        opacityFromPercent: opacityFromPercent
    });
}(window));
