'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = fs.readFileSync(path.join(__dirname, '..', 'libs', 'echarts-design.js'), 'utf8');
const context = { window: {} };
vm.runInNewContext(source, context);
const design = context.window.SYMC_ECHARTS_DESIGN;

let label = design.valueLabel({ valueLabelPosition: 'auto', valueLabelDistance: 5 },
    'inside', ['top', 'inside'], 5);
assert.equal(label.position, 'inside');
assert.equal(label.distance, 5);
label = design.valueLabel({ valueLabelPosition: 'bottom', valueLabelDistance: 18 },
    'top', ['top', 'bottom'], 5);
assert.equal(label.position, 'bottom');
assert.equal(label.distance, 18);
label = design.valueLabel({ valueLabelPosition: 'unsupported', valueLabelDistance: -1 },
    'top', ['top', 'bottom'], 5);
assert.equal(label.position, 'top');
assert.equal(label.distance, 5);

const series = {
    type: 'gauge',
    axisLabel: { distance: 12 },
    title: { offsetCenter: [0, '-18%'] },
    detail: { offsetCenter: [0, '82%'] }
};
design.gaugeLabelOffsets(series, {
    scaleLabelDistanceOffset: 4,
    titleOffsetXPercent: -3,
    titleOffsetYPercent: 5,
    valueOffsetXPercent: 6,
    valueOffsetYPercent: -8
});
assert.equal(series.axisLabel.distance, 16);
assert.deepEqual(Array.from(series.title.offsetCenter), ['-3%', '-13%']);
assert.deepEqual(Array.from(series.detail.offsetCenter), ['6%', '74%']);

const defaultSeries = { type: 'gauge', axisLabel: {}, title: { offsetCenter: [0, '-18%'] } };
design.gaugeLabelOffsets(defaultSeries, {});
assert.equal(defaultSeries.axisLabel.distance, undefined,
    'Zero offset must preserve the ECharts/preset distance.');
assert.deepEqual(Array.from(defaultSeries.title.offsetCenter), [0, '-18%']);

console.log('Shared ECharts label design verified.');
