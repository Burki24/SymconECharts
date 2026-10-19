(function (global) {
    'use strict';
    // Transient category position only: family geometry and balances stay local.
    global.SymconEChartsCategoryNavigation = { create: function () {
        var anchor = null, start = 0;
        function reset() { anchor = null; start = 0; }
        return {
            reset: reset,
            remember: function (chart) {
                if (!chart || typeof chart.getOption !== 'function') { return; }
                var option = chart.getOption() || {};
                var zoom = (option.dataZoom || []).find(function (item) { return item.id === 'category-scroll'; });
                if (!zoom) { return; }
                var axes = zoom.orient === 'vertical' ? option.yAxis : option.xAxis;
                var axis = Array.isArray(axes) ? axes[0] : axes;
                var keys = axis && axis.data || [];
                var index = Number(zoom.startValue);
                if (!Number.isFinite(index)) {
                    index = keys.indexOf(zoom.startValue);
                    if (index < 0) { index = (Number(zoom.start) || 0) * Math.max(0, keys.length - 1) / 100; }
                }
                start = Math.max(0, Math.ceil(index - 1e-8));
                anchor = keys[start] || null;
            },
            window: function (keys, count) {
                if (!keys.length) { reset(); return { first: 0, last: 0, visibleCount: 0 }; }
                count = Math.max(1, Math.min(keys.length, Math.floor(count) || 1));
                var index = keys.indexOf(anchor);
                var first = Math.max(0, Math.min(keys.length - count, index >= 0 ? index : start));
                return { first: first, last: first + count - 1, visibleCount: count };
            }
        };
    } };
}(window));
