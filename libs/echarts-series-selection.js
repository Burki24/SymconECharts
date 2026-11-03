(function () {
    'use strict';

    // View state only: never filter the source model or change its archive budget.
    function create() {
        var selected = Object.create(null);
        var initial = Object.create(null);
        var ids = [];
        var anchor = null;

        function capture(chart) {
            if (!chart || typeof chart.getOption !== 'function') { return; }
            var option = chart.getOption();
            var legend = option && (Array.isArray(option.legend) ? option.legend[0] : option.legend);
            if (!legend) { return; }
            ids.forEach(function (id) {
                if (legend.selected && typeof legend.selected[id] === 'boolean') {
                    selected[id] = legend.selected[id];
                }
            });
            var index = Number(legend.scrollDataIndex);
            if (Number.isInteger(index) && index >= 0 && index < ids.length) { anchor = ids[index]; }
        }

        function reconcile(series) {
            var nextSelected = Object.create(null);
            var nextInitial = Object.create(null);
            series.forEach(function (item) {
                var visible = item.initiallyVisible !== false;
                nextInitial[item.id] = visible;
                nextSelected[item.id] = Object.prototype.hasOwnProperty.call(selected, item.id)
                    && initial[item.id] === visible ? selected[item.id] : visible;
            });
            selected = nextSelected;
            initial = nextInitial;
            ids = series.map(function (item) { return item.id; });
            return selected;
        }

        return {
            capture: capture,
            reconcile: reconcile,
            select: function (id, visible) {
                if (ids.indexOf(id) >= 0 && typeof visible === 'boolean') { selected[id] = visible; }
            },
            selectAll: function (visible) {
                if (typeof visible === 'boolean') { ids.forEach(function (id) { selected[id] = visible; }); }
            },
            invert: function () { ids.forEach(function (id) { selected[id] = selected[id] === false; }); },
            scrollDataIndex: function () { return Math.max(0, ids.indexOf(anchor)); }
        };
    }

    window.SYMC_ECHARTS_SERIES_SELECTION = { create: create };
}());
