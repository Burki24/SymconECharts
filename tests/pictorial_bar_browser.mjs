// Local browser fixture only; no connection to Symcon and no real variable data.
import http from 'node:http';
import fs from 'node:fs';
import path from 'node:path';
import {fileURLToPath} from 'node:url';

const root = path.dirname(path.dirname(fileURLToPath(import.meta.url)));
const read = p => fs.readFileSync(path.join(root, p), 'utf8');
const style={orientation:'vertical',symbol:'rect',symbolRepeat:true,symbolSize:12,symbolGap:2,barWidth:40,scaleMaximum:100,
    showGrid:true,showLabels:true,showValues:true,showTooltip:true,animationEnabled:false};
function state(){
 return {schemaVersion:1,family:'pictorialBar',status:'ready',error:null,chart:{schemaVersion:1,family:'pictorialBar',theme:'auto',
  pictorialBar:{title:'Batteriestände · aktuelle Werte',unit:'%',data:['Büro','Bad','Wohnbereich','Schlafzimmer','Außen','Keller'].map((label,i)=>({
    id:'v'+i,label,unit:'%',decimals:0,value:[100,75,42,12,120,0][i],order:i,color:''})),style:{...style}}}};
}
const variables = ':root{--symc-background:#303236;--symc-text:#f4f5f7;--symc-text-muted:#969aa2;--symc-border:#555860;--symc-surface:#25272b}';
const translations = JSON.parse(read('EChartsPictorialBar/locale.json')).translations.de;
function page(mode, extended = false, light = false, single = false) {
    const initialState = state();
    if (mode === 'ipsview') {
        initialState.chart.pictorialBar.style.orientation = 'horizontal';
        initialState.chart.pictorialBar.style.axisColor = light ? '#333333' : '#A9DCD2';
    }
    if(extended){Object.assign(initialState.chart.pictorialBar.style,{symbol:'circle',symbolSize:16,symbolGap:3,borderWidth:1,borderColor:light?'#333333':'#eeeeee',
        colorMode:'value',colorMinimum:0,colorMaximum:100,lowColor:'#ee6666',middleColor:'#fac858',highColor:'#91cc75',
        fontFamily:'Verdana, sans-serif',valueBold:true,valuePosition:'outside',valueDistance:8,
        gridType:'dotted',gridWidth:1,gridOpacityPercent:40,borderType:'solid',shadowBlur:5,shadowColor:'#000000',
        shadowOffsetX:2,shadowOffsetY:2,categoryLabelRotate:mode==='ipsview'?0:25});}
    if(single){Object.assign(initialState.chart.pictorialBar.style,{symbol:'diamond',symbolRepeat:false});}
    const bootstrap = { mode, state: initialState, options: { tileHeaderVisible: true, echartsThemes: {auto: {background:'#303236',text:'#f4f5f7',muted:'#969aa2',border:'#777b83',accent:'#55CBB5',seriesColors:['#5070dd','#b6d634','#ff994d','#fb628b']}},
        adaptToBackground: false,  }, translations };
    const replacements = { HTML_LANGUAGE: 'de', HTML_CLASSES: '', ROOT_FONT_SIZE: '16px', VIEWPORT_CONTENT: 'width=device-width, initial-scale=1',
        DOCUMENT_TITLE: 'PictorialBar browser fixture', VISUALIZATION_THEME: light ? ':root{--symc-background:#ffffff;--symc-text:#222222;--symc-text-muted:#666666;--symc-border:#cccccc;--symc-surface:#eeeeee}' : variables, MODULE_STYLE: read('EChartsPictorialBar/visualization/style.css'),
        IPSVIEW_STYLE: '', ECHARTS_SCRIPT: read('libs/echarts/6.1.0/echarts.pictorial-bar.min.js'), ECHARTS_THEME_SCRIPT: '',
        ECHARTS_DESIGN_SCRIPT: read('libs/echarts-design.js'), BOOTSTRAP_JSON: JSON.stringify(bootstrap),
        MODULE_SCRIPT: read('EChartsPictorialBar/visualization/app.js'), IPSVIEW_TRANSPORT_SCRIPT: '' };
    let html = read('EChartsPictorialBar/visualization/index.html').replace(/\{\{([A-Z_]+)\}\}/g, (_, key) => replacements[key] ?? '');
    if (mode === 'symcon') { html = html.replace('<body>', '<body><div style="position:absolute;top:16px;left:20px;font:18px Arial;z-index:4">EChartsPictorialBar <span style="position:fixed;right:18px">⤢</span></div>'); }
    return html;
}
http.createServer((req, res) => {
    const url = new URL(req.url, 'http://127.0.0.1');
    res.setHeader('Content-Type', 'text/html; charset=utf-8');
    if (url.pathname === '/chart') { res.end(page(url.searchParams.get('mode') === 'ipsview' ? 'ipsview' : 'symcon', url.searchParams.get('design') === 'extended', url.searchParams.get('theme') === 'light', url.searchParams.get('symbols') === 'single')); return; }
    res.end('<!doctype html><html lang="de"><head><title>PictorialBar – lokale Browserprüfung</title></head><body style="background:#0b0c0e;color:white;font:16px Arial;margin:20px">'
        + '<h1 style="font-size:20px">PictorialBar · synthetische Beispieldaten</h1><div style="display:flex;gap:16px;align-items:start;flex-wrap:wrap">'
        + '<section><h2 style="font-size:16px">Kachel · hoch</h2><iframe title="Kachel" src="/chart?mode=symcon&design=extended" style="border:0;width:400px;height:600px"></iframe></section>'
        + '<section><h2 style="font-size:16px">IPSView · breit</h2><iframe title="IPSView" src="/chart?mode=ipsview&design=extended" style="border:0;width:790px;height:440px"></iframe></section></div></body></html>');
}).listen(8801, '127.0.0.1', () => console.log('PictorialBar browser fixture: http://127.0.0.1:8801'));
