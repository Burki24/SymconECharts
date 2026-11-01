'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const {execFileSync} = require('node:child_process');
const root = path.join(__dirname, '..');
const echarts = require(path.join(root, '.tools/echarts-runtime/node_modules/echarts'));
const states = JSON.parse(execFileSync(process.env.PHP_BINARY || 'php', ['tests/jslive_models.php', '--emit'], {cwd: root, maxBuffer: 8388608, encoding: 'utf8'}));
const files = {'cartesian-history':'EChartsTimeSeries/JSLiveRenderer.js', pie:'EChartsPie/JSLiveRenderer.js',
    gauge:'EChartsGaugeSingle/JSLiveRenderer.js', 'radar-history':'EChartsRadarHistory/RadarHistoryRenderer.js', progress:'EChartsProgress/ProgressRenderer.js'};
let renders = 0;
for (const [index, wire] of states.entries()) {
    const state=wire.chart;
    for (const [width, height] of [[620, 500], [1200, 700], [300, 242]]) {
        const window = {echarts, btoa: value => Buffer.from(value, 'binary').toString('base64'), atob: value => Buffer.from(value, 'base64').toString('binary'), SYMC_VISUALIZATION:{options:{locale:'de-DE'}}};
        const context = vm.createContext({window});
        vm.runInContext(fs.readFileSync(path.join(root, 'libs/jslive/format.js'), 'utf8'), context);
        vm.runInContext(fs.readFileSync(path.join(root, files[state.family]), 'utf8'), context);
        const option = window.SYMC_JSLIVE_OPTION(state, width, height);
        option.animation = false;
        const chart = echarts.init(null, null, {renderer: 'svg', ssr: true, width, height});
        try {
            chart.setOption(option, true);
            const svg = chart.renderToSVGString();
            assert.match(svg, /<svg/);
            const invalid = /NaN|Infinity|undefined/.exec(svg);
            assert.ok(!invalid, 'Invalid geometry in case ' + (index + 1) + ': ' + (invalid ? svg.slice(Math.max(0,invalid.index-80),invalid.index+100) : ''));
            assert.ok(svg.length > 500, 'Empty renderer in case ' + (index + 1));
            if (process.env.JSLIVE_RENDER_OUTPUT && width === 620) {
                fs.mkdirSync(process.env.JSLIVE_RENDER_OUTPUT, {recursive: true});
                fs.writeFileSync(path.join(process.env.JSLIVE_RENDER_OUTPUT, 'case-' + String(index + 1).padStart(2, '0') + '.svg'), svg);
            }
            ++renders;
            if(state.family==='progress'){
                const titled=JSON.parse(JSON.stringify(state));
                titled.settings.title_display=true;titled.settings.title_text='Imported progress title';
                chart.setOption(window.SYMC_JSLIVE_OPTION(titled,width,height),true);
                assert.match(chart.renderToSVGString(),/Imported progress title/,'Configured imported Progress title is missing');
            }
        } catch (error) {
            throw new Error('Case ' + (index + 1) + ' ' + state.family + ' ' + width + 'px: ' + error.message, {cause:error});
        } finally { chart.dispose(); }
    }
}
assert.equal(renders, 120);
console.log('JSLive family renderers: 40 variants × 3 sizes = ' + renders + ' native ECharts renders passed.');
