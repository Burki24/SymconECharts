(function () {
    'use strict';

    function signature(series) {
        return JSON.stringify([series.id, series.data[0].id, series.layout, series.orient,
            series.initialTreeDepth]);
    }

    function updateChart(chart, option) {
        var next = option.series[0];
        var model = chart.getModel && chart.getModel();
        var previous = model && model.getSeriesByIndex(0);
        var expanded = Object.create(null);
        var changedNodes = false;
        if (previous && previous.type === 'series.tree') {
            var previousRoot = previous.getData().tree.root;
            previousRoot.eachNode(function (node) {
                if (node !== previousRoot) { expanded[node.getId()] = node.isExpand; }
            });
            var remaining = Object.keys(expanded).length;
            function compare(node) {
                if (Object.prototype.hasOwnProperty.call(expanded, node.id)) { remaining--; }
                else { changedNodes = true; }
                (node.children || []).forEach(compare);
            }
            next.data.forEach(compare);
            changedNodes = changedNodes || remaining !== 0;
        }
        if (previous && previous.type === 'series.tree' && chart.__symcTreeSignature === signature(next)) {
            function restore(node) {
                if (Object.prototype.hasOwnProperty.call(expanded, node.id)) {
                    node.collapsed = expanded[node.id] === false;
                }
                (node.children || []).forEach(restore);
            }
            next.data.forEach(restore);
            if (next.roam) {
                next.center = previous.get('center');
                next.zoom = previous.get('zoom');
            }
        }
        if (previous && (changedNodes || chart.__symcTreeSignature !== signature(next))) { chart.clear(); }
        chart.setOption(option, true);
        chart.__symcTreeSignature = signature(next);
    }

    function depth(node) {
        return (node.children || []).reduce(function (maximum, child) {
            return Math.max(maximum, 1 + depth(child));
        }, 0);
    }

    function labelLayout(root, width, height, radial, orientation, compact, nodeSize, initialDepth, fontSize, values, chart) {
        var maximumDepth = depth(root);
        var labelWidth = compact ? 88 : 150;
        var stride = 1;
        var depths = Object.create(null);
        function remember(node, level) {
            depths[node.id] = level;
            (node.children || []).forEach(function (child) { remember(child, level + 1); });
        }
        remember(root, 0);
        if (maximumDepth > 6) {
            var visibleDepth = Math.min(maximumDepth, Math.max(0, initialDepth));
            var model = chart && chart.getModel();
            var previous = model && model.getSeriesByIndex(0);
            if (previous && previous.get('initialTreeDepth') === initialDepth
                && previous.get('layout') === (radial ? 'radial' : 'orthogonal')
                && previous.get('orient') === orientation) {
                var previousTree = previous.getData().tree;
                var previousRoot = previousTree.getNodeById && previousTree.getNodeById(root.id);
                function visible(node) {
                    return node.isExpand ? (node.children || []).reduce(function (maximum, child) {
                        return Math.max(maximum, 1 + visible(child));
                    }, 0) : 0;
                }
                if (previousRoot) { visibleDepth = visible(previousRoot); }
            }
            var horizontal = orientation === 'LR' || orientation === 'RL';
            var extent = radial ? Math.min(width * 0.82, height) / 2 : (horizontal ? width * 0.72 : height);
            var spacing = Math.max(1, extent / Math.max(1, visibleDepth));
            if (radial || horizontal) {
                labelWidth = Math.min(labelWidth, Math.max(fontSize * 4, spacing - nodeSize - 8));
            }
            var needed = radial || horizontal ? labelWidth : fontSize * (values ? 2.4 : 1.2);
            stride = Math.max(1, Math.ceil((needed + nodeSize + 8) / spacing));
        }
        return { width: labelWidth,
            outerPadding: maximumDepth > 6 && radial ? Math.min(labelWidth + fontSize + nodeSize,
                Math.max(0, (Math.min(width * 0.82, height) - 80) / 2)) : 0,
            topPadding: maximumDepth > 6 && !radial && (orientation === 'TB' || orientation === 'BT')
                ? fontSize * (values ? 2.4 : 1.2) + nodeSize / 2 + 8 : 0,
            show: function (node, collapsed) {
                if (maximumDepth > 6 && radial && height < 80 + fontSize * 4) { return false; }
                if (collapsed === true || (Array.isArray(node.children) && node.children.length === 0)) { return true; }
                if (stride === 1 || !Object.prototype.hasOwnProperty.call(depths, node.id)) { return true; }
                var level = depths[node.id];
                return level === 0 || (maximumDepth - level) % stride === 0;
            } };
    }

    window.SYMC_ECHARTS_TREE = Object.freeze({ updateChart: updateChart, labelLayout: labelLayout });
}());
