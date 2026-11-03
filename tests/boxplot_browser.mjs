// Local browser fixture only; no connection to Symcon and no real variable data.
import http from 'node:http';
import fs from 'node:fs';
import path from 'node:path';
import {fileURLToPath} from 'node:url';

const root = path.dirname(path.dirname(fileURLToPath(import.meta.url)));
const read = p => fs.readFileSync(path.join(root, p), 'utf8');
const style = { orientation: 'vertical', showGrid: true, showAxisLabels: true, showTooltip: true,
    boxWidth: 40, borderWidth: 2, boxOpacityPercent: 70, titleFontSizePercent: 100, axisFontSizePercent: 100, animationEnabled: false };
function state(count) {
    return { schemaVersion: 1, family: 'boxplot', status: 'ready', error: null, chart: {
        schemaVersion: 1, family: 'boxplot', theme: 'auto', boxplot: { title: 'Raumtemperaturen · Stundenmittel', unit: '°C',
            sampling: 'completed-hourly-means', whiskers: 'minmax', quantile: 'R7',
            data: Array.from({length: count}, (_, index) => ({
                label: index === 2 || index === 3 ? 'Gleicher Raumname' : 'Messraum ' + String(index + 1).padStart(2, '0'),
                id: 'variable-' + index, unit: '°C', decimals: 1, color: '',
                statistics: index === 8 ? null : index === count - 1 ? [-8, 1, 7, 15, 35]
                    : [15 + index % 3, 18 + index % 3, 20 + index % 3, 22 + index % 3, 25 + index % 3],
                count: index === 8 ? 0 : 168 - index * 5, firstTimestamp: 1791504000, lastTimestamp: 1792108800
            })), style: { ...style } }
    } };
}
const variables = ':root{--symc-background:#303236;--symc-text:#f4f5f7;--symc-text-muted:#969aa2;--symc-border:#555860;--symc-surface:#25272b}';
const translations = JSON.parse(read('EChartsBoxplot/locale.json')).translations.de;
function page(mode, extended = false, query = new URLSearchParams()) {
    const initialState = state(Math.max(1, Math.min(32, Number(query.get('count')) || 32)));
    if (query.get('whiskers') === 'iqr') {
        initialState.chart.boxplot.whiskers = 'iqr';
        initialState.chart.boxplot.data.forEach((item, index) => {
            if (!item.statistics) { Object.assign(item, {outliers: [], outlierCount: 0, sampleMinimum: null, sampleMaximum: null}); return; }
            Object.assign(item, {statistics: [16, 17.5, 20, 22.5, 24], sampleMinimum: 5, sampleMaximum: index === 31 ? 35 : 32,
                count: 8, outlierCount: 2, outliers: [[1791504000, 5], [1792108800, index === 31 ? 35 : 32]]});
        });
    }
    if (query.get('fractional') === '1') {
        initialState.chart.boxplot.data.forEach((item, index) => {
            if (item.statistics) { item.statistics = [Math.PI, 10, 12, 15, index === initialState.chart.boxplot.data.length - 1 ? 37.123456789 : 20]; }
        });
    }
    if (mode === 'ipsview') {
        initialState.chart.boxplot.style.orientation = 'horizontal';
        initialState.chart.boxplot.style.axisColor = '#A9DCD2';
    }
    if (['horizontal', 'vertical'].includes(query.get('orientation'))) { initialState.chart.boxplot.style.orientation = query.get('orientation'); }
    initialState.chart.boxplot.style.axisFontSizePercent = Math.max(50, Math.min(200, Number(query.get('font')) || 100));
    initialState.chart.boxplot.style.boxWidth = Math.max(1, Math.min(80, Number(query.get('box')) || 40));
    if (extended) {
        Object.assign(initialState.chart.boxplot.style, {
            minBoxWidth: 20, borderType: 'dashed', borderColor: '#CBD6E8', shadowBlur: 8, shadowColor: '#000000',
            shadowOffsetX: 3, shadowOffsetY: 3, hoverFocus: 'self', blurOpacityPercent: 10,
            axisLineWidth: 2, showAxisTicks: false, axisLabelRotate: mode === 'ipsview' ? 0 : 45, axisLabelMaxWidth: 100,
            titleBold: false, axisLabelBold: true, splitNumber: 8, gridLineType: 'dotted', gridLineWidth: 1,
            gridOpacityPercent: 50, gridColor: '#85929E'
        });
    }
    const bootstrap = { mode, language: 'de-DE', state: initialState, options: { tileHeaderVisible: mode === 'symcon', tileHeaderInset: mode === 'symcon' ? 58 : 0, echartsThemes: {auto: {background:'#303236',text:'#f4f5f7',muted:'#969aa2',border:'#777b83',accent:'#55CBB5',seriesColors:['#5070dd','#b6d634','#ff994d','#fb628b']}},
        adaptToBackground: false,  }, translations };
    const replacements = { HTML_LANGUAGE: 'de', HTML_CLASSES: '', ROOT_FONT_SIZE: '16px', VIEWPORT_CONTENT: 'width=device-width, initial-scale=1',
        DOCUMENT_TITLE: 'Boxplot browser fixture', VISUALIZATION_THEME: variables, MODULE_STYLE: read('EChartsBoxplot/visualization/style.css'),
        INTERACTION_SCRIPT: '<script>' + read('libs/helper/visualization/interaction.min.js') + '</script>',
        IPSVIEW_STYLE: '', ECHARTS_SCRIPT: read('libs/echarts/6.1.0/echarts.boxplot.min.js'), ECHARTS_THEME_SCRIPT: '',
        ECHARTS_DESIGN_SCRIPT: read('libs/echarts-design.js'), BOOTSTRAP_JSON: JSON.stringify(bootstrap),
        MODULE_SCRIPT: read('EChartsBoxplot/visualization/app.js'), IPSVIEW_TRANSPORT_SCRIPT: '' };
    let html = read('EChartsBoxplot/visualization/index.html').replace(/\{\{([A-Z_]+)\}\}/g, (_, key) => replacements[key] ?? '');
    // Fixture-only DOM evidence: real browser wheel receipt and the resulting native viewport.
    html = html.replace('</body>', `<script>
        document.addEventListener('wheel', function (event) {
            document.body.dataset.wheelCount = String(Number(document.body.dataset.wheelCount || 0) + 1);
            document.body.dataset.wheelDelta = String(event.deltaY);
            document.body.dataset.wheelTarget = String(event.target.tagName);
            document.body.dataset.wheelPoint = event.clientX + ',' + event.clientY;
        }, {capture:true, passive:true});
        var navigationChart = echarts.getInstanceByDom(document.getElementById('echarts-boxplot-chart'));
        navigationChart.getZr().on('mousewheel', function(event) {
            document.body.dataset.chartWheelCount = String(Number(document.body.dataset.chartWheelCount || 0) + 1);
            document.body.dataset.chartWheelPoint = event.offsetX + ',' + event.offsetY;
            document.body.dataset.chartWheelDelta = String(event.wheelDelta);
        });
        function recordWindow() {
            var zoom = navigationChart.getOption().dataZoom[0];
            document.body.dataset.categoryStart = String(zoom ? zoom.startValue : 0);
            document.body.dataset.categoryEnd = String(zoom ? zoom.endValue : ${initialState.chart.boxplot.data.length - 1});
        }
        navigationChart.on('datazoom', recordWindow); recordWindow();
    </script></body>`);
    if (mode === 'symcon') { html = html.replace('<body>', '<body><div style="position:absolute;top:16px;left:20px;font:18px Arial;z-index:4">EChartsBoxplot <span style="position:fixed;right:18px">⤢</span></div>'); }
    if (query.get('tail') === '1') {
        html = html.replace('</body>', '<script>var fixtureChart=echarts.getInstanceByDom(document.getElementById("echarts-boxplot-chart"));'
            + 'var fixtureZoom=fixtureChart.getOption().dataZoom[0];if(fixtureZoom){var fixtureCount=fixtureZoom.endValue-fixtureZoom.startValue+1;'
            + 'fixtureChart.dispatchAction({type:"dataZoom",dataZoomId:"category-scroll",startValue:' + (initialState.chart.boxplot.data.length)
            + '-fixtureCount,endValue:' + (initialState.chart.boxplot.data.length - 1) + '});}</script></body>');
    }
    return html;
}
http.createServer((req, res) => {
    const url = new URL(req.url, 'http://127.0.0.1');
    res.setHeader('Content-Type', 'text/html; charset=utf-8');
    if (url.pathname === '/chart') { res.end(page(url.searchParams.get('mode') === 'ipsview' ? 'ipsview' : 'symcon', url.searchParams.get('design') === 'extended', url.searchParams)); return; }
    res.end('<!doctype html><html lang="de"><head><title>Boxplot – lokale Browserprüfung</title></head><body style="background:#0b0c0e;color:white;font:16px Arial;margin:20px">'
        + '<h1 style="font-size:20px">Boxplot · synthetische Beispieldaten</h1><div style="display:flex;gap:16px;align-items:start;flex-wrap:wrap">'
        + '<section><h2 style="font-size:16px">Kachel · hoch</h2><iframe title="Kachel" src="/chart?mode=symcon" style="border:0;width:400px;height:600px"></iframe></section>'
        + '<section><h2 style="font-size:16px">IPSView · breit</h2><iframe title="IPSView" src="/chart?mode=ipsview" style="border:0;width:790px;height:440px"></iframe></section></div></body></html>');
}).listen(Number(process.env.PORT || 8798), '127.0.0.1', () => console.log('Boxplot browser fixture: http://127.0.0.1:' + (process.env.PORT || 8798)));
