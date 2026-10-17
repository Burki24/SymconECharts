// Local browser fixture only; no connection to Symcon and no real variable data.
import http from 'node:http';
import fs from 'node:fs';
import path from 'node:path';
import {fileURLToPath} from 'node:url';

const root = path.dirname(path.dirname(fileURLToPath(import.meta.url)));
const read = p => fs.readFileSync(path.join(root, p), 'utf8');
const style = { orientation: 'vertical', sortOrder: 'configured', alignment: 'center', gap: 4,
    showLabels: true, showValues: true, showTooltip: true, labelPosition: 'inside',
    borderWidth: 1, opacityPercent: 90, titleFontSizePercent: 100, labelFontSizePercent: 100, animationEnabled: false };
function state() {
    return { schemaVersion:1, family:'funnel', status:'ready', error:null, chart:{
        schemaVersion:1, family:'funnel', theme:'auto', funnel:{title:'Leistung · Stufenvergleich',unit:'W',
            data:['Gesamt','Büro','Küche','Wohnbereich','Nebenräume'].map((label,index)=>({
                id:'variable-'+index,label,unit:'W',decimals:1,color:'',value:[400,230,170,90,30][index],order:index
            })),style:{...style}}
    }};
}
const variables = ':root{--symc-background:#303236;--symc-text:#f4f5f7;--symc-text-muted:#969aa2;--symc-border:#555860;--symc-surface:#25272b}';
const translations = JSON.parse(read('EChartsFunnel/locale.json')).translations.de;
function page(mode, extended = false, light = false) {
    const initialState = state();
    if (mode === 'ipsview') {
        Object.assign(initialState.chart.funnel.style,{orientation:'horizontal',labelPosition:'outside',labelColor:light ? '' : '#A9DCD2'});
    }
    if (extended) {
        initialState.chart.funnel.data = Array.from({length:16},(_,i)=>({id:'variable-'+i,label:'Langer Stufenname '+(i+1),
            unit:'W',decimals:1,color:'',value:160-i*10,order:i}));
        Object.assign(initialState.chart.funnel.style,{labelFontSizePercent:150,gap:30});
    }
    const bootstrap = { mode, state: initialState, options: { tileHeaderVisible: true, echartsThemes: {auto: {background:'#303236',text:'#f4f5f7',muted:'#969aa2',border:'#777b83',accent:'#55CBB5',seriesColors:['#5070dd','#b6d634','#ff994d','#fb628b']}},
        adaptToBackground: false,  }, translations };
    const replacements = { HTML_LANGUAGE: 'de', HTML_CLASSES: '', ROOT_FONT_SIZE: '16px', VIEWPORT_CONTENT: 'width=device-width, initial-scale=1',
        DOCUMENT_TITLE: 'Funnel browser fixture', VISUALIZATION_THEME: light ? ':root{--symc-background:#ffffff;--symc-text:#222222;--symc-text-muted:#666666;--symc-border:#cccccc;--symc-surface:#eeeeee}' : variables, MODULE_STYLE: read('EChartsFunnel/visualization/style.css'),
        IPSVIEW_STYLE: '', ECHARTS_SCRIPT: read('libs/echarts/6.1.0/echarts.funnel.min.js'), ECHARTS_THEME_SCRIPT: '',
        ECHARTS_DESIGN_SCRIPT: read('libs/echarts-design.js'), BOOTSTRAP_JSON: JSON.stringify(bootstrap),
        MODULE_SCRIPT: read('EChartsFunnel/visualization/app.js'), IPSVIEW_TRANSPORT_SCRIPT: '' };
    let html = read('EChartsFunnel/visualization/index.html').replace(/\{\{([A-Z_]+)\}\}/g, (_, key) => replacements[key] ?? '');
    if (mode === 'symcon') { html = html.replace('<body>', '<body><div style="position:absolute;top:16px;left:20px;font:18px Arial;z-index:4">EChartsFunnel <span style="position:fixed;right:18px">⤢</span></div>'); }
    return html;
}
http.createServer((req, res) => {
    const url = new URL(req.url, 'http://127.0.0.1');
    res.setHeader('Content-Type', 'text/html; charset=utf-8');
    if (url.pathname === '/chart') { res.end(page(url.searchParams.get('mode') === 'ipsview' ? 'ipsview' : 'symcon', url.searchParams.get('design') === 'extended', url.searchParams.get('theme') === 'light')); return; }
    res.end('<!doctype html><html lang="de"><head><title>Funnel – lokale Browserprüfung</title></head><body style="background:#0b0c0e;color:white;font:16px Arial;margin:20px">'
        + '<h1 style="font-size:20px">Funnel · synthetische Beispieldaten</h1><div style="display:flex;gap:16px;align-items:start;flex-wrap:wrap">'
        + '<section><h2 style="font-size:16px">Kachel · hoch</h2><iframe title="Kachel" src="/chart?mode=symcon" style="border:0;width:400px;height:600px"></iframe></section>'
        + '<section><h2 style="font-size:16px">IPSView · breit</h2><iframe title="IPSView" src="/chart?mode=ipsview" style="border:0;width:790px;height:440px"></iframe></section></div></body></html>');
}).listen(8799, '127.0.0.1', () => console.log('Funnel browser fixture: http://127.0.0.1:8799'));
