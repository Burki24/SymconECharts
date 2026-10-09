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

console.log('Shared ECharts zoom behavior verified.');
