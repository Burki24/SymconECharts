'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = fs.readFileSync(path.join(__dirname, '..', 'libs', 'echarts-design.js'), 'utf8');
const window = {};
vm.runInNewContext(source, { window });

const opacityFromPercent = window.SYMC_ECHARTS_DESIGN.opacityFromPercent;
assert.equal(opacityFromPercent(undefined, 100), 1, 'Missing bar opacity must remain fully opaque.');
assert.equal(opacityFromPercent(undefined, 22), 0.22, 'Area opacity keeps its own default.');
assert.equal(opacityFromPercent(0, 100), 0, 'Zero percent must remain transparent.');
assert.equal(opacityFromPercent(35, 100), 0.35, 'Configured percentages map to ECharts fractions.');
assert.equal(opacityFromPercent(100, 22), 1, 'Full opacity must remain one.');
assert.equal(opacityFromPercent(-1, 100), 0, 'Opacity is bounded below.');
assert.equal(opacityFromPercent(101, 100), 1, 'Opacity is bounded above.');
assert.equal(opacityFromPercent('invalid', 25), 0.25, 'Invalid opacity uses the caller default.');
assert.equal(opacityFromPercent(Infinity, 25), 0.25, 'Non-finite opacity uses the caller default.');

console.log('Shared ECharts design opacity verified.');
