(function () {
    'use strict';

    // On time axes ECharts stacks by row index. Align every member, including
    // hidden ones, before legend selection. Keep the source model untouched.
    function project(series, gapTimestamps) {
        var groups = new Map();
        var projected = series.slice();
        series.forEach(function (item, index) {
            if (!item.stackGroup) { return; }
            if (!groups.has(item.stackGroup)) { groups.set(item.stackGroup, []); }
            groups.get(item.stackGroup).push(index);
        });
        groups.forEach(function (indices, group) {
            var counts = new Map();
            if (gapTimestamps) {
                (gapTimestamps[group] || []).forEach(function (timestamp) { counts.set(timestamp, 0); });
            }
            var values = indices.map(function (index) {
                var byTime = new Map(series[index].points);
                byTime.forEach(function (_, timestamp) { counts.set(timestamp, (counts.get(timestamp) || 0) + 1); });
                return byTime;
            });
            // Bars can omit missing timestamps. Lines need one null per run
            // of missing intervals to break both the stroke and the filled band.
            var slots = [];
            var inGap = false;
            Array.from(counts.keys()).sort(function (a, b) { return a - b; }).forEach(function (timestamp) {
                var complete = counts.get(timestamp) === indices.length;
                if (complete || (gapTimestamps && !inGap)) { slots.push(timestamp); }
                inGap = !complete;
            });
            indices.forEach(function (index, member) {
                var points = slots.map(function (timestamp) {
                    return [timestamp, counts.get(timestamp) === indices.length ? values[member].get(timestamp) : null];
                });
                projected[index] = Object.assign({}, series[index], { points: points });
            });
        });
        return projected;
    }

    window.eChartsArchiveStack = { project: project };
}());
