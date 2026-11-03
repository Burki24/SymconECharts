'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = fs.readFileSync(path.join(__dirname, '..', 'libs', 'echarts-design.js'), 'utf8');
const window = {};
vm.runInNewContext(source, { window });

const updateHierarchyChart = window.SYMC_ECHARTS_DESIGN.updateHierarchyChart;
assert.equal(typeof updateHierarchyChart, 'function', 'Both hierarchy renderers need one shared stable-root update contract.');
for (const [seriesId, actionName] of [['treemap', 'treemapRootToNode'], ['sunburst', 'sunburstRootToNode']]) {
    const calls = [];
    const hierarchyChart = {
        getModel: () => ({getSeriesByIndex: () => ({id: seriesId, getViewRoot: () => ({getId: () => 'deep-group'})})}),
        setOption: (option, replace) => calls.push(['set', replace]),
        dispatchAction: action => calls.push(['restore', action.seriesId, action.type, action.targetNodeId])
    };
    const option = {series: [{id: seriesId, data: [{id: 'root-group', children: [
        {id: 'deep-group', children: [{id: 'variable-1', value: 1}]}]}]}]};
    updateHierarchyChart(hierarchyChart, option, actionName, true);
    assert.deepEqual(calls, [['set', true],
        ...(seriesId === 'treemap' ? [['restore', seriesId, actionName, 'root-group']] : []),
        ['restore', seriesId, actionName, 'deep-group']],
    'Treemap must rebuild ancestor view children before restoring a leaf-depth-pruned group.');
    calls.length = 0;
    updateHierarchyChart(hierarchyChart, {...option, series: [{id: seriesId, data: [{id: 'variable-1', value: 1}]}]}, actionName, true);
    assert.deepEqual(calls, [['set', true]], 'Removed groups must return to the new full tree.');
    calls.length = 0;
    updateHierarchyChart(hierarchyChart, option, actionName, false);
    assert.deepEqual(calls, [['set', true]], 'Disabling navigation must not restore a previous root.');
}

const parseVisualizationState = window.SYMC_ECHARTS_DESIGN.parseVisualizationState;
const validMessage = { schemaVersion: 1, family: 'scatter', status: 'ready', chart: {} };
assert.deepEqual(parseVisualizationState(validMessage), validMessage,
    'A complete chart update must reach the renderer.');
assert.equal(parseVisualizationState(JSON.stringify(validMessage)).status, 'ready',
    'A JSON chart update must reach the renderer.');
assert.equal(parseVisualizationState({ schemaVersion: 1, family: 'scatter', status: 'error', error: 'Failure' }).status,
    'error', 'A real error state must remain visible.');
assert.equal(parseVisualizationState({ status: 'error', error: 'Invalid configuration' }).status,
    'error', 'Existing error updates without protocol metadata must remain visible.');
for (const message of [{}, { type: 'click' }, { status: 'error' }, { schemaVersion: 1, status: 'ready' },
    { schemaVersion: 1, family: 'scatter', status: 'ready' }, 'not JSON', null]) {
    assert.equal(parseVisualizationState(message), null,
        'An unrelated or incomplete browser message must not replace a chart.');
}

const opacityFromPercent = window.SYMC_ECHARTS_DESIGN.opacityFromPercent;
const recoveryChartElement = { hidden: false };
const recoveryWarningElement = { hidden: true, textContent: '' };
const retainFailure = window.SYMC_ECHARTS_DESIGN.createTransientFailureRetainer(
    recoveryChartElement, recoveryWarningElement, text => text, 'Temporary failure'
);
const readyState = { status: 'ready', chart: {} };
assert.equal(retainFailure({ status: 'error', error: 'Temporary failure' }, readyState, {}), true);
assert.equal(recoveryWarningElement.hidden, true, 'One transient failure must not flash a warning.');
assert.equal(retainFailure({ status: 'error', error: 'Temporary failure' }, readyState, {}), true);
assert.equal(recoveryWarningElement.hidden, false, 'Repeated failures must mark retained data as stale.');
assert.equal(retainFailure(readyState, readyState, {}), false);
assert.equal(recoveryWarningElement.hidden, false, 'Repainting the same cached state must not clear the warning.');
assert.equal(retainFailure({ ...readyState }, readyState, {}), false);
assert.equal(recoveryWarningElement.hidden, true, 'Fresh data must clear the warning.');
assert.equal(retainFailure({ status: 'error', error: 'Invalid configuration' }, readyState, {}), false,
    'Configuration failures must not retain a stale chart.');
const fontSizePercent = window.SYMC_ECHARTS_DESIGN.fontSizePercent;
const chartTitleLayout = window.SYMC_ECHARTS_DESIGN.chartTitleLayout;
assert.deepEqual(JSON.parse(JSON.stringify(chartTitleLayout(
    { mode: 'symcon', options: { tileHeaderVisible: true, tileHeaderInset: 58 } }, 'Chart', 18
))), { show: true, top: 58, headerInset: 58, contentTop: 88 },
'A visible tile header must leave room for its name, maximize button and chart title.');
assert.deepEqual(JSON.parse(JSON.stringify(chartTitleLayout(
    { mode: 'symcon', options: { tileHeaderVisible: false, tileHeaderInset: 0 } }, 'Chart', 18
))), { show: true, top: 8, headerInset: 8, contentTop: 38 },
'A hidden tile header must release its reserved space without hiding the chart title.');
assert.deepEqual(JSON.parse(JSON.stringify(chartTitleLayout(
    { mode: 'ipsview', options: { tileHeaderVisible: false, tileHeaderInset: 0 } }, 'Chart', 18
))), { show: true, top: 4, headerInset: 8, contentTop: 38 },
'IPSView title placement must remain independent from Symcon tile headers.');
assert.equal(chartTitleLayout({ mode: 'symcon', options: { tileHeaderVisible: true, tileHeaderInset: 58 } }, '', 18).contentTop,
    58, 'Empty chart titles must not reserve a second title row.');
assert.equal(chartTitleLayout(
    { mode: 'symcon', options: { tileHeaderVisible: true, tileHeaderInset: 82 } }, 'Chart', 18
).top, 82, 'Renderers must consume the supplied inset instead of assuming a local header height.');
assert.equal(fontSizePercent(undefined, 12), 12, 'Missing font size must retain the chart default.');
assert.equal(fontSizePercent(150, 12), 18, 'Shared percent scaling must use the chart-specific base size.');
assert.equal(fontSizePercent(300, 12), 12, 'Invalid font size must not make a chart unreadable.');
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
const interpolateColorStops = window.SYMC_ECHARTS_DESIGN.interpolateColorStops;
assert.equal(interpolateColorStops(['#000000', '#ffffff'], 0.5), '#808080',
    'Two-stop gradients must preserve their existing midpoint.');
assert.equal(interpolateColorStops(['#000000', '#ff0000', '#ffffff'], 0.5), '#ff0000',
    'An intermediate user color must land at its evenly spaced stop.');
assert.equal(interpolateColorStops(['rgb(0, 0, 0)', '#ffffff'], 1), '#ffffff',
    'Automatic theme RGB colors must also interpolate.');
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

const animationOptions = window.SYMC_ECHARTS_DESIGN.animationOptions;
const animationDefaults = { enabled: true, initialDuration: 350, updateDuration: 500 };
assert.deepEqual(JSON.parse(JSON.stringify(animationOptions({}, animationDefaults))), {
    animation: true, animationDuration: 350, animationDurationUpdate: 500,
    animationEasing: 'cubicInOut', animationEasingUpdate: 'cubicInOut',
    animationDelay: 0, animationDelayUpdate: 0
});
assert.deepEqual(JSON.parse(JSON.stringify(animationOptions({
    animationEnabled: true, animationDuration: 0, animationDurationUpdate: 3000,
    animationEasing: 'linear', animationEasingUpdate: 'bounceOut',
    animationDelay: 400, animationDelayUpdate: 250
}, animationDefaults))), {
    animation: true, animationDuration: 0, animationDurationUpdate: 3000,
    animationEasing: 'linear', animationEasingUpdate: 'bounceOut',
    animationDelay: 0, animationDelayUpdate: 250
});
assert.equal(animationOptions({
    animationDuration: 350, animationDelay: 400
}, animationDefaults).animationDelay, 400);
assert.equal(animationOptions({ animationDuration: 3001 }, animationDefaults).animationDuration, 350);
assert.equal(animationOptions({ animationEnabled: false }, animationDefaults).animationDurationUpdate, 0);
assert.equal(animationOptions({ animationEnabled: false, animationDelay: 400 }, animationDefaults).animationDelay, 0);
assert.equal(animationOptions({ animationEasing: 'not-an-easing' }, animationDefaults).animationEasing, 'cubicInOut');
assert.equal(animationOptions({ animationDelayUpdate: 3001 }, animationDefaults).animationDelayUpdate, 0);
assert.equal(animationOptions({}, { enabled: false, initialDuration: 350, updateDuration: 300 }).animation, false);
window.matchMedia = query => ({ matches: query === '(prefers-reduced-motion: reduce)' });
assert.equal(animationOptions({}, animationDefaults).animation, false);
window.matchMedia = () => ({ matches: false });

const resizeChartIfNeeded = window.SYMC_ECHARTS_DESIGN.resizeChartIfNeeded;
const chartElement = { clientWidth: 640, clientHeight: 360 };
let chartWidth = 640;
let chartHeight = 360;
let resizeCount = 0;
const chart = {
    getWidth: () => chartWidth,
    getHeight: () => chartHeight,
    resize: () => {
        resizeCount++;
        chartWidth = chartElement.clientWidth;
        chartHeight = chartElement.clientHeight;
    }
};
assert.equal(resizeChartIfNeeded(null, chartElement), false, 'A missing chart cannot be resized.');
assert.equal(resizeChartIfNeeded({ isDisposed: () => true, getWidth: () => {
    throw new Error('Disposed chart must not be measured.');
} }, chartElement), false, 'A disposed chart cannot be resized by a pending observer callback.');
assert.equal(resizeChartIfNeeded(chart, chartElement), false,
    'The initial observer notification must not interrupt chart animations.');
chartElement.clientWidth = 500;
assert.equal(resizeChartIfNeeded(chart, chartElement), true, 'A width change must resize the chart.');
assert.equal(resizeCount, 1);
assert.equal(resizeChartIfNeeded(chart, chartElement), false, 'The repeated size must not resize twice.');
chartElement.clientHeight = 240;
assert.equal(resizeChartIfNeeded(chart, chartElement), true, 'A height change must resize the chart.');
assert.equal(resizeCount, 2);

for (const moduleName of [
    'EChartsGaugeSingle', 'EChartsGaugeMulti', 'EChartsGaugeTacho',
    'EChartsGaugeChronograph', 'EChartsTimeSeries', 'EChartsBarCategory',
    'EChartsBarHistory', 'EChartsBarWaterfall', 'EChartsPie'
]) {
    const renderer = fs.readFileSync(path.join(__dirname, '..', moduleName, 'visualization', 'app.js'), 'utf8');
    const observer = renderer.match(/new ResizeObserver\(function \(\) \{([\s\S]*?)\}\)\.observe\(chartElement\)/);
    assert.ok(observer, `${moduleName} must observe chart element changes.`);
    assert.match(observer[1], /resizeChartIfNeeded\(chart, chartElement\)/,
        `${moduleName} must share the unchanged-size guard.`);
    assert.doesNotMatch(observer[1], /chart\.resize\(/,
        `${moduleName} must not interrupt animations with a redundant observer resize.`);
}

const waterfallTemplate = fs.readFileSync(path.join(__dirname, '..', 'EChartsBarWaterfall',
    'visualization', 'index.html'), 'utf8');
assert.match(waterfallTemplate, /\{\{ECHARTS_DESIGN_SCRIPT\}\}/,
    'Waterfall must load the shared resize helper in its browser document.');

for (const [moduleName, warningId] of [
    ['EChartsGaugeSingle', 'echarts-gauge-warning'],
    ['EChartsGaugeMulti', 'echarts-gauge-warning'],
    ['EChartsGaugeTacho', 'echarts-gauge-warning'],
    ['EChartsGaugeChronograph', 'echarts-gauge-warning'],
    ['EChartsTimeSeries', 'echarts-timeseries-warning'],
    ['EChartsBarCategory', 'echarts-bar-category-warning'],
    ['EChartsBarWaterfall', 'echarts-bar-waterfall-warning'],
    ['EChartsBarPolar', 'echarts-bar-polar-warning'],
    ['EChartsPie', 'echarts-pie-warning']
]) {
    const moduleRoot = path.join(__dirname, '..', moduleName);
    const html = fs.readFileSync(path.join(moduleRoot, 'visualization', 'index.html'), 'utf8');
    const renderer = fs.readFileSync(path.join(moduleRoot, 'visualization', 'app.js'), 'utf8');
    const module = fs.readFileSync(path.join(moduleRoot, 'module.php'), 'utf8');
    assert.ok(html.includes(`id="${warningId}"`), `${moduleName} needs a visible retry notice.`);
    assert.match(renderer, /createTransientFailureRetainer\(/,
        `${moduleName} must retain the last chart through a transient read failure.`);
    assert.match(module, /RegisterTransientRefresh\(/,
        `${moduleName} must register its read retry timer.`);
    assert.match(module, /ScheduleTransientRefresh\(/,
        `${moduleName} must activate retry only after a transient read failure.`);
}

for (const moduleName of [
    'EChartsGaugeSingle', 'EChartsGaugeMulti', 'EChartsGaugeTacho', 'EChartsGaugeChronograph',
    'EChartsTimeSeries', 'EChartsBarCategory', 'EChartsBarHistory', 'EChartsBarWaterfall',
    'EChartsBarPolar', 'EChartsPie', 'EChartsScatter', 'EChartsHeatmap'
]) {
    const renderer = fs.readFileSync(path.join(__dirname, '..', moduleName, 'visualization', 'app.js'), 'utf8');
    assert.match(renderer, /parseVisualizationState\(/,
        `${moduleName} must validate incoming chart states through the shared guard.`);
}

console.log('Shared ECharts design utilities verified.');
