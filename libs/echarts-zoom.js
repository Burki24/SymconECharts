(function () {
    'use strict';

    function options(enabled, mode, sliderBottom) {
        if (!enabled) { return []; }
        return [
            {
                type: 'inside',
                xAxisIndex: 0,
                start: 0,
                end: 100,
                zoomOnMouseWheel: mode !== 'ipsview',
                moveOnMouseWheel: false
            },
            { type: 'slider', xAxisIndex: 0, bottom: sliderBottom }
        ];
    }

    function capture(chart, previousRange, nextRange) {
        if (!chart || typeof chart.getOption !== 'function') { return null; }
        if (previousRange || nextRange) {
            if (!previousRange || !nextRange
                || previousRange.key !== nextRange.key
                || previousRange.dataMode !== nextRange.dataMode) {
                return null;
            }
            var previousStart = Number(previousRange.startTimestamp);
            var previousEnd = Number(previousRange.endTimestamp);
            var nextStart = Number(nextRange.startTimestamp);
            var nextEnd = Number(nextRange.endTimestamp);
            if (Number.isFinite(previousStart) && Number.isFinite(previousEnd)
                && Number.isFinite(nextStart) && Number.isFinite(nextEnd)) {
                if (nextStart >= previousEnd || nextEnd <= previousStart) { return null; }
                if (previousRange.key === 'custom'
                    && Math.abs((previousEnd - previousStart) - (nextEnd - nextStart)) > 1) {
                    return null;
                }
            }
        }
        var option = chart.getOption();
        var current = option && Array.isArray(option.dataZoom) ? option.dataZoom[0] : null;
        if (!current) { return null; }
        var start = Number(current.start);
        var end = Number(current.end);
        if (!Number.isFinite(start) || !Number.isFinite(end)
            || start < 0 || end > 100 || end - start < 1) {
            return null;
        }
        return { start: start, end: end };
    }

    function apply(chart, option, saved) {
        if (saved && Array.isArray(option.dataZoom)) {
            option.dataZoom.forEach(function (entry) {
                entry.start = saved.start;
                entry.end = saved.end;
            });
        }
        chart.setOption(option, true);
    }

    function attachIPSViewWheel(element, getChart, isEnabled) {
        element.addEventListener('wheel', function (event) {
            var chart = getChart();
            if (!chart || !isEnabled()) { return; }
            var delta = Number(event.deltaY);
            if (!Number.isFinite(delta) || delta === 0) { return; }
            var current = capture(chart, null, null);
            if (!current) { return; }
            var span = current.end - current.start;
            var nextSpan = Math.max(1, Math.min(100, span * (delta < 0 ? 0.8 : 1.25)));
            var bounds = element.getBoundingClientRect();
            var anchor = bounds.width > 0 ? (Number(event.clientX) - bounds.left) / bounds.width : 0.5;
            anchor = Math.max(0, Math.min(1, Number.isFinite(anchor) ? anchor : 0.5));
            var anchorValue = current.start + span * anchor;
            var nextStart = anchorValue - nextSpan * anchor;
            var nextEnd = anchorValue + nextSpan * (1 - anchor);
            if (nextStart < 0) {
                nextEnd -= nextStart;
                nextStart = 0;
            }
            if (nextEnd > 100) {
                nextStart -= nextEnd - 100;
                nextEnd = 100;
            }
            event.preventDefault();
            event.stopPropagation();
            chart.dispatchAction({
                type: 'dataZoom',
                dataZoomIndex: 0,
                start: Math.max(0, nextStart),
                end: Math.min(100, nextEnd)
            });
        }, { passive: false });
    }

    window.SymconEChartsZoom = {
        options: options,
        capture: capture,
        apply: apply,
        attachIPSViewWheel: attachIPSViewWheel
    };
}());
