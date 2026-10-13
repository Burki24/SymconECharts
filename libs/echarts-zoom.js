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

    function timeAxisOptions(enabled, mode, sliderBottom, compact) {
        var result = options(enabled, mode, sliderBottom);
        if (result.length > 1) {
            result[1].height = compact ? 8 : 12;
            if (compact) { result[1].showDetail = false; }
        }
        return result;
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

    function cartesianOptions(enabled, mode, compact) {
        if (!enabled) { return []; }
        var inside = { type: 'inside', start: 0, end: 100, filterMode: 'none',
            zoomOnMouseWheel: mode !== 'ipsview', moveOnMouseWheel: false };
        return [
            Object.assign({ id: 'x-inside', xAxisIndex: 0 }, inside),
            Object.assign({ id: 'y-inside', yAxisIndex: 0 }, inside),
            { id: 'x-slider', type: 'slider', xAxisIndex: 0, start: 0, end: 100,
                filterMode: 'none', bottom: compact ? 10 : 14, height: compact ? 7 : 10,
                showDataShadow: false, showDetail: false, handleSize: 12 },
            { id: 'y-slider', type: 'slider', yAxisIndex: 0, orient: 'vertical', start: 0, end: 100,
                filterMode: 'none', right: compact ? 8 : 12, width: compact ? 7 : 10,
                showDataShadow: false, showDetail: false, handleSize: 12 }
        ];
    }

    function validWindow(entry) {
        if (!entry) { return null; }
        var start = Number(entry.start);
        var end = Number(entry.end);
        return Number.isFinite(start) && Number.isFinite(end)
            && start >= 0 && end <= 100 && end - start >= 1 ? { start: start, end: end } : null;
    }

    function captureCartesian(chart, previousRange, nextRange) {
        var x = capture(chart, previousRange, nextRange);
        if (!x) { return null; }
        var option = chart.getOption();
        var entries = option && Array.isArray(option.dataZoom) ? option.dataZoom : [];
        var y = validWindow(entries[1]);
        return y ? { x: x, y: y } : null;
    }

    function applyCartesian(chart, option, saved) {
        if (saved && Array.isArray(option.dataZoom)) {
            option.dataZoom.forEach(function (entry) {
                var range = entry.xAxisIndex !== undefined ? saved.x : saved.y;
                entry.start = range.start;
                entry.end = range.end;
            });
        }
        chart.setOption(option, true);
    }

    function zoomWindow(current, delta, anchor) {
        var span = current.end - current.start;
        var nextSpan = Math.max(1, Math.min(100, span * (delta < 0 ? 0.8 : 1.25)));
        anchor = Math.max(0, Math.min(1, Number.isFinite(anchor) ? anchor : 0.5));
        var anchorValue = current.start + span * anchor;
        var start = anchorValue - nextSpan * anchor;
        var end = anchorValue + nextSpan * (1 - anchor);
        if (start < 0) { end -= start; start = 0; }
        if (end > 100) { start -= end - 100; end = 100; }
        return { start: Math.max(0, start), end: Math.min(100, end) };
    }

    function attachIPSViewWheelCartesian(element, getChart, isEnabled) {
        element.addEventListener('wheel', function (event) {
            var chart = getChart();
            if (!chart || !isEnabled()) { return; }
            var delta = Number(event.deltaY);
            if (!Number.isFinite(delta) || delta === 0) { return; }
            var current = captureCartesian(chart, null, null);
            if (!current) { return; }
            var bounds = element.getBoundingClientRect();
            var xAnchor = bounds.width > 0 ? (Number(event.clientX) - bounds.left) / bounds.width : 0.5;
            var yAnchor = bounds.height > 0 ? 1 - (Number(event.clientY) - bounds.top) / bounds.height : 0.5;
            var x = zoomWindow(current.x, delta, xAnchor);
            var y = zoomWindow(current.y, delta, yAnchor);
            event.preventDefault();
            event.stopPropagation();
            chart.dispatchAction({ type: 'dataZoom', dataZoomId: 'x-inside', start: x.start, end: x.end });
            chart.dispatchAction({ type: 'dataZoom', dataZoomId: 'y-inside', start: y.start, end: y.end });
        }, { passive: false });
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
        timeAxisOptions: timeAxisOptions,
        capture: capture,
        apply: apply,
        attachIPSViewWheel: attachIPSViewWheel,
        cartesianOptions: cartesianOptions,
        captureCartesian: captureCartesian,
        applyCartesian: applyCartesian,
        attachIPSViewWheelCartesian: attachIPSViewWheelCartesian
    };
}());
