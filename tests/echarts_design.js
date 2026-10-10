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

const barOutlineStyle = window.SYMC_ECHARTS_DESIGN.barOutlineStyle;
assert.equal(barOutlineStyle(0, '#112233', '#778899').borderWidth, 0,
    'A zero-width outline must leave existing bars unframed.');
assert.equal(barOutlineStyle(2, '#112233', '#778899').borderColor, '#112233',
    'A configured outline color must take priority.');
assert.equal(barOutlineStyle(2, '', '#778899').borderColor, '#778899',
    'Automatic outline color must follow the active theme.');
assert.equal(barOutlineStyle(2, '', '#778899').borderWidth, 2,
    'The configured width must reach ECharts unchanged.');
assert.equal(barOutlineStyle(-1, '', '#778899').borderWidth, 0,
    'Negative widths must not create unexpected outlines.');
assert.equal(barOutlineStyle(Infinity, '', '#778899').borderWidth, 0,
    'Non-finite widths must not create unexpected outlines.');
assert.equal(barOutlineStyle(10, '', '#778899').borderWidth, 10,
    'The shared helper must not impose the Polar module’s width limit on future charts.');

const colorWithAlpha = window.SYMC_ECHARTS_DESIGN.colorWithAlpha;
assert.equal(colorWithAlpha('#223344', 0.35), 'rgba(34,51,68,0.35)',
    'Bar shadows and Gauge effects must share the same hex color transparency.');
assert.equal(colorWithAlpha('rgb(10, 20, 30)', 0.45), 'rgba(10,20,30,0.45)',
    'The shared helper must preserve the existing Gauge RGB behavior.');
assert.equal(colorWithAlpha('rgba(10,20,30,0.5)', 0.35), 'rgba(10,20,30,0.5)',
    'Unsupported color syntax must remain unchanged for existing Gauge designs.');

const prefersReducedMotion = window.SYMC_ECHARTS_DESIGN.prefersReducedMotion;
assert.equal(prefersReducedMotion(), false, 'Missing media-query support must preserve motion.');
window.matchMedia = query => ({ matches: query === '(prefers-reduced-motion: reduce)' });
assert.equal(prefersReducedMotion(), true, 'The shared helper must honor reduced-motion preferences.');
window.matchMedia = () => ({ matches: false });
assert.equal(prefersReducedMotion(), false, 'Normal motion preferences must retain chart animations.');

console.log('Shared ECharts design utilities verified.');
