'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = fs.readFileSync(path.join(__dirname, '..', 'libs', 'echarts-zoom.js'), 'utf8');
const window = {};
vm.runInNewContext(source, { window });
const zoom = window.SymconEChartsZoom;

assert.ok(zoom, 'The shared ECharts zoom API must be available.');
assert.equal(zoom.options(false, 'symcon', 12).length, 0);
assert.equal(zoom.options(true, 'symcon', 12)[0].zoomOnMouseWheel, true);
assert.equal(zoom.options(true, 'ipsview', 38)[0].zoomOnMouseWheel, false);
assert.equal(zoom.options(true, 'ipsview', 38)[1].bottom, 38);
assert.equal(zoom.sliderThickness(false), 12);
assert.equal(zoom.sliderThickness(true), 8);
assert.equal(zoom.timeAxisOptions(true, 'symcon', 22, false)[1].height, 12);
assert.equal(zoom.timeAxisOptions(true, 'ipsview', 38, true)[1].height, 8);
assert.equal(zoom.timeAxisOptions(true, 'ipsview', 38, true)[1].showDetail, false);

let wheelListener;
let action;
let prevented = false;
let stopped = false;
let enabled = true;
const element = {
    addEventListener: (name, listener) => { if (name === 'wheel') { wheelListener = listener; } },
    getBoundingClientRect: () => ({ left: 0, width: 800 })
};
const chart = {
    getOption: () => ({ dataZoom: [{ start: 20, end: 60 }] }),
    dispatchAction: value => { action = value; }
};
zoom.attachIPSViewWheel(element, () => chart, () => enabled);
assert.equal(typeof wheelListener, 'function');
wheelListener({
    deltaY: -100, clientX: 400,
    preventDefault: () => { prevented = true; },
    stopPropagation: () => { stopped = true; }
});
assert.equal(prevented, true);
assert.equal(stopped, true);
assert.equal(action.type, 'dataZoom');
assert.equal(action.start, 24);
assert.equal(action.end, 56);
enabled = false;
action = null;
wheelListener({ deltaY: -100, clientX: 400, preventDefault: () => {} });
assert.equal(action, null, 'Disabled zoom must not consume a wheel event.');

const range = { key: '24h', dataMode: 'raw', startTimestamp: 1000, endTimestamp: 2000 };
const refreshed = { key: '24h', dataMode: 'raw', startTimestamp: 1060, endTimestamp: 2060 };
const retained = zoom.capture(chart, range, refreshed);
assert.deepEqual([retained.start, retained.end], [20, 60]);
assert.equal(zoom.capture(chart, range, { ...refreshed, key: '7d' }), null);
assert.equal(zoom.capture(chart, range, { ...refreshed, dataMode: 'auto' }), null);
assert.equal(zoom.capture(chart, range, { ...refreshed, startTimestamp: 2100, endTimestamp: 3100 }), null);
assert.equal(zoom.capture(
    chart,
    { ...range, key: 'custom' },
    { ...refreshed, key: 'custom', endTimestamp: 3000 }
), null);
const nextOption = { dataZoom: zoom.options(true, 'symcon', 12) };
let applied;
zoom.apply({ setOption: (value, replace) => { applied = { value, replace }; } }, nextOption, retained);
assert.equal(applied.replace, true);
assert.equal(applied.value.dataZoom[0].start, 20);
assert.equal(applied.value.dataZoom[1].end, 60);

const cartesian = zoom.cartesianOptions(true, 'ipsview', true);
assert.equal(zoom.cartesianOptions(false, 'symcon', false).length, 0);
assert.equal(cartesian.length, 4);
assert.equal(cartesian[0].xAxisIndex, 0);
assert.equal(cartesian[1].yAxisIndex, 0);
assert.equal(cartesian[0].zoomOnMouseWheel, false);
assert.equal(zoom.cartesianOptions(true, 'symcon', false)[1].zoomOnMouseWheel, true);
assert.equal(cartesian[2].height, 8);
assert.equal(cartesian[3].width, 8);
assert.equal(zoom.cartesianOptions(true, 'symcon', false)[2].height, 12);
assert.equal(zoom.cartesianOptions(true, 'symcon', false)[3].width, 12);
assert.equal(cartesian[2].height, zoom.timeAxisOptions(true, 'ipsview', 38, true)[1].height);
assert.equal(cartesian[3].orient, 'vertical');
const cartesianChart = {
    getOption: () => ({ dataZoom: [{ start: 20, end: 60 }, { start: 30, end: 80 }] })
};
const retainedCartesian = zoom.captureCartesian(cartesianChart, range, refreshed);
assert.deepEqual([retainedCartesian.x.start, retainedCartesian.x.end], [20, 60]);
assert.deepEqual([retainedCartesian.y.start, retainedCartesian.y.end], [30, 80]);
assert.equal(zoom.captureCartesian(cartesianChart, range, { ...refreshed, key: '7d' }), null);
const cartesianOption = { dataZoom: zoom.cartesianOptions(true, 'symcon', false) };
zoom.applyCartesian({ setOption: (value, replace) => { applied = { value, replace }; } },
    cartesianOption, retainedCartesian);
assert.equal(applied.replace, true);
assert.deepEqual(Array.from(applied.value.dataZoom, entry => [entry.start, entry.end]),
    [[20, 60], [30, 80], [20, 60], [30, 80]]);
let wheelCartesian;
const actions = [];
zoom.attachIPSViewWheelCartesian({
    addEventListener: (name, listener) => { if (name === 'wheel') { wheelCartesian = listener; } },
    getBoundingClientRect: () => ({ left: 0, top: 0, width: 800, height: 400 })
}, () => ({ ...cartesianChart, dispatchAction: value => { actions.push(value); } }), () => true);
wheelCartesian({ deltaY: -100, clientX: 400, clientY: 200,
    preventDefault: () => {}, stopPropagation: () => {} });
assert.deepEqual(actions.map(value => [value.dataZoomId, value.start, value.end]),
    [['x-inside', 24, 56], ['y-inside', 35, 75]]);

console.log('Shared ECharts zoom behavior verified.');
