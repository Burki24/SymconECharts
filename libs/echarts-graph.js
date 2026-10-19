(function () {
    'use strict';

    function signature(series) {
        return JSON.stringify([series.id, series.layout, series.roam]);
    }

    function finishLayout(chart) {
        if ((chart.isDisposed && chart.isDisposed()) || !chart.__symcGraphNeedsLabels
            || !chart.getViewOfSeriesModel) { return; }
        var model = chart.getModel();
        var series = model && model.getSeriesByIndex(0);
        if (!series) { return; }
        var view = chart.getViewOfSeriesModel(series);
        // Force ticks intentionally do not run ECharts' LabelLayout feature. Wait
        // for its pinned GraphView to finish rather than restarting the simulation.
        if (view && view._layouting) { return; }
        chart.__symcGraphNeedsLabels = false;
        var width = chart.getWidth();
        var height = chart.getHeight();
        var centerX = width / 2;
        var centerY = height / 2;
        var factor = 1;
        var dx = 0;
        var dy = 0;
        if (!chart.__symcGraphOverviewDone && !chart.__symcGraphUserRoamed
            && series.get('layout') === 'force' && series.getData().count() > 80) {
            var bounds = [Infinity, Infinity, -Infinity, -Infinity];
            var data = series.getData();
            data.each(function (index) {
                var position = series.coordinateSystem.dataToPoint(data.getItemLayout(index));
                if (!position || !Number.isFinite(position[0]) || !Number.isFinite(position[1])) { return; }
                bounds[0] = Math.min(bounds[0], position[0]);
                bounds[1] = Math.min(bounds[1], position[1]);
                bounds[2] = Math.max(bounds[2], position[0]);
                bounds[3] = Math.max(bounds[3], position[1]);
            });
            if (bounds.every(Number.isFinite)) {
                // Respect each family's own plot rectangle (notably the Z2M footer).
                var rect = series.coordinateSystem.getViewRect();
                centerX = rect.x + rect.width / 2;
                centerY = rect.y + rect.height / 2;
                factor = Math.max(0.001, Math.min(1, Math.max(1, rect.width - 48) / Math.max(1, bounds[2] - bounds[0]),
                    Math.max(1, rect.height - 48) / Math.max(1, bounds[3] - bounds[1])));
                dx = centerX - (bounds[0] + bounds[2]) / 2;
                dy = centerY - (bounds[1] + bounds[3]) / 2;
            }
        }
        chart.__symcGraphOverviewDone = true;
        chart.__symcGraphApplying = true;
        try {
            if (dx || dy) { chart.dispatchAction({ type: 'graphRoam', seriesIndex: 0, dx: dx, dy: dy }); }
            // Even zoom=1 deliberately reruns the bundled native collision pass.
            chart.dispatchAction({ type: 'graphRoam', seriesIndex: 0,
                zoom: factor, originX: centerX, originY: centerY });
        } finally { chart.__symcGraphApplying = false; }
    }

    function observeChart(chart) {
        if (chart.__symcGraphObserved || !chart.on) { return; }
        chart.__symcGraphObserved = true;
        chart.on('rendered', function () { finishLayout(chart); });
        function userRoamed() {
            if (!chart.__symcGraphApplying) { chart.__symcGraphUserRoamed = true; }
        }
        chart.on('graphRoam', userRoamed);
        chart.on('mousedown', userRoamed);
    }

    function updateChart(chart, option) {
        observeChart(chart);
        var next = option.series[0];
        var model = chart.getModel && chart.getModel();
        var previous = model && model.getSeriesByIndex(0);
        var compatible = previous && previous.type === 'series.graph'
            && chart.__symcGraphSignature === signature(next);
        if (compatible) {
            var data = previous.getData();
            var retained = Object.create(null);
            var points = Object.create(null);
            var overlap = 0;
            next.data.forEach(function (node) { retained[String(node.id)] = true; });
            data.each(function (index) {
                var id = data.getId(index);
                if (!Object.prototype.hasOwnProperty.call(retained, id)) { return; }
                overlap++;
                var point = data.getItemLayout(index);
                if (point && Number.isFinite(point[0]) && Number.isFinite(point[1])) {
                    points[id] = [point[0], point[1]];
                }
            });
            compatible = overlap > 0;
            if (compatible) {
                if (next.roam) {
                    next.center = previous.get('center');
                    next.zoom = previous.get('zoom');
                }
                // The pinned Graph engine continues the force simulation from these
                // ID-keyed points. Pruning removed IDs keeps this transient cache bounded.
                if (next.layout === 'force') { previous.preservedPoints = points; }
            }
        }
        if (!compatible) {
            chart.__symcGraphOverviewDone = false;
            chart.__symcGraphUserRoamed = false;
        }
        chart.__symcGraphNeedsLabels = true;
        chart.setOption(option, !compatible);
        chart.__symcGraphSignature = signature(next);
    }

    function labelOptions(style, width, height, fontSize) {
        var compact = width < 430 || height < 300;
        return {
            show: style.showLabels !== false,
            color: style.labelColor,
            fontSize: fontSize,
            position: 'right', distance: 4,
            width: compact ? 84 : 140,
            overflow: 'truncate'
        };
    }

    function tooltipOptions() {
        return { trigger: 'item', confine: true,
            extraCssText: 'max-width:calc(100% - 16px);white-space:normal;overflow-wrap:anywhere;' };
    }

    function chartFrame(bootstrap, chartElement) {
        var inset = bootstrap.mode === 'symcon'
            && (!bootstrap.options || bootstrap.options.tileHeaderVisible !== false) ? 58 : 0;
        chartElement.style.top = inset + 'px';
        chartElement.style.height = inset ? 'calc(100% - ' + inset + 'px)' : '100%';
        return inset ? { mode: 'symcon', options: { tileHeaderVisible: false } } : bootstrap;
    }

    function chartTitle(bootstrap, chartBootstrap, chartElement, rootElement, title, size, color, design) {
        var layout = design.chartTitleLayout(chartBootstrap, title, size);
        var header = bootstrap.mode === 'symcon'
            && (!bootstrap.options || bootstrap.options.tileHeaderVisible !== false) ? 58 : 0;
        if (!rootElement.__symcGraphTitle) {
            var element = document.createElement('div');
            element.setAttribute('class', 'echarts-graph-chart-title');
            Object.assign(element.style, { position: 'absolute', left: '8px', right: '8px',
                textAlign: 'center', whiteSpace: 'nowrap', overflow: 'hidden',
                textOverflow: 'ellipsis', pointerEvents: 'none', fontFamily: 'sans-serif',
                fontWeight: 'bold', zIndex: '2' });
            rootElement.appendChild(element);
            rootElement.__symcGraphTitle = element;
        }
        var titleElement = rootElement.__symcGraphTitle;
        titleElement.hidden = !layout.show;
        titleElement.textContent = String(title || '');
        titleElement.style.top = (header + layout.top) + 'px';
        titleElement.style.fontSize = size + 'px';
        titleElement.style.color = color;
        titleElement.style.lineHeight = '1.2';
        var inset = header + layout.contentTop;
        chartElement.style.top = inset + 'px';
        chartElement.style.height = 'calc(100% - ' + inset + 'px)';
        return layout;
    }

    window.SYMC_ECHARTS_GRAPH = Object.freeze({
        updateChart: updateChart, labelOptions: labelOptions, tooltipOptions: tooltipOptions,
        chartFrame: chartFrame, chartTitle: chartTitle, finishLayout: finishLayout, startup: window.SYMC_ECHARTS_STARTUP
    });
}());
