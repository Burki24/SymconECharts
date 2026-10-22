// Local synthetic hierarchy fixture; no connection to Symcon or installation data.
import http from 'node:http';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.dirname(path.dirname(fileURLToPath(import.meta.url)));
const read = file => fs.readFileSync(path.join(root, file), 'utf8');
function hierarchy(multiplier = 1, normalized = false) {
    let node = normalized
        ? { id: 'variable-100', name: 'Raumtemperatur', value: multiplier === 1 ? 50 : 60,
            originalValue: multiplier === 1 ? 20 : 28, unit: '°C', decimals: 1, minimum: -20, maximum: 60 }
        : { id: 'variable-100', name: 'Hauptrechner <Leistung>', value: 12 * multiplier };
    const names = ['Haus', 'Obergeschoss', 'Büro', 'Strom', 'Arbeitsplatz', 'Steckdose', 'Messkanal'];
    for (let i = 6; i >= 0; i--) {
        node = { id: 'level-' + (i + 1), name: names[i], value: node.value, children: [node] };
    }
    return [node, { id: 'garden', name: 'Garten', value: normalized ? 80 : 4 * multiplier,
        children: [normalized ? { id: 'variable-101', name: 'Luftfeuchtigkeit', value: 80,
            originalValue: 80, unit: '%', decimals: 0, minimum: 0, maximum: 100 }
            : { id: 'variable-101', name: 'Beleuchtung', value: 4 * multiplier }] }];
}
function state(family, multiplier = 1, normalized = false, font = 100) {
    return { status: 'ready', chart: { family, theme: 'auto', [family]: {
        title: 'Flexible Hierarchie · acht Ebenen', depth: 8, positiveCount: 2,
        ...(normalized ? { normalized: true } : {}),
        data: hierarchy(multiplier, normalized), style: { showLabels: true, showValues: true,
            titleFontSizePercent: font, labelFontSizePercent: font, animationEnabled: false }
    } } };
}
function page(mode, family, normalized, font) {
    const module = family === 'sunburst' ? 'EChartsSunburst' : 'EChartsTreemap';
    const action = family === 'sunburst' ? 'sunburstRootToNode' : 'treemapRootToNode';
    const bootstrap = { mode, state: state(family, 1, normalized, font), translations: JSON.parse(read(module + '/locale.json')).translations.de,
        options: { tileHeaderVisible: mode === 'symcon', tileHeaderInset: mode === 'symcon' ? 58 : 0, adaptToBackground: false, echartsThemes: {} } };
    const replacements = { HTML_LANGUAGE: 'de', HTML_CLASSES: '', ROOT_FONT_SIZE: '16px',
        VIEWPORT_CONTENT: 'width=device-width, initial-scale=1', DOCUMENT_TITLE: 'Hierarchy browser fixture',
        VISUALIZATION_THEME: ':root{--symc-background:#303236;--symc-text:#f4f5f7;--symc-text-muted:#969aa2;--symc-surface:#25272b}',
        MODULE_STYLE: read(module + '/visualization/style.css'), IPSVIEW_STYLE: '',
        ECHARTS_SCRIPT: read('libs/echarts/6.1.0/echarts.' + family + '.min.js'), ECHARTS_THEME_SCRIPT: '',
        ECHARTS_DESIGN_SCRIPT: read('libs/echarts-design.js') + '\n' + read('libs/echarts-hierarchy-values.js'), BOOTSTRAP_JSON: JSON.stringify(bootstrap),
        MODULE_SCRIPT: read(module + '/visualization/app.js'), IPSVIEW_TRANSPORT_SCRIPT: '' };
    let html = read(module + '/visualization/index.html').replace(/\{\{([A-Z_]+)\}\}/g, (_, key) => replacements[key] ?? '');
    const controls = '<div style="position:absolute;right:8px;bottom:38px;z-index:5;font:12px Arial">'
        + '<button onclick="const c=echarts.getInstanceByDom(document.getElementById(\'echarts-' + family + '-chart\'));'
        + (family === 'treemap' ? '[1,2,3,4,5]' : '[5]') + '.forEach(n=>c.dispatchAction({type:\'' + action
        + '\',seriesId:\'' + family + '\',targetNodeId:\'level-\'+n}));">Ebene 5 öffnen</button> '
        + '<button onclick="window.handleMessage(' + JSON.stringify(state(family, 2, normalized, font)).replace(/"/g, '&quot;') + ')">Werte aktualisieren</button></div>';
    html = html.replace('</body>', controls + '</body>');
    if (mode === 'symcon') {
        html = html.replace('<body>', '<body><div style="position:absolute;top:16px;left:20px;font:18px Arial;z-index:4">' + module + ' <span style="position:fixed;right:18px">⤢</span></div>');
    }
    return html;
}
http.createServer((req, res) => {
    const url = new URL(req.url, 'http://127.0.0.1');
    const family = url.searchParams.get('family') === 'sunburst' ? 'sunburst' : 'treemap';
    const normalized = url.searchParams.get('normalized') === '1';
    const font = url.searchParams.get('font') === '150' ? 150 : 100;
    const query = '&normalized=' + (normalized ? 1 : 0) + '&font=' + font;
    res.setHeader('Content-Type', 'text/html; charset=utf-8');
    if (url.pathname === '/chart') {
        res.end(page(url.searchParams.get('mode') === 'ipsview' ? 'ipsview' : 'symcon', family, normalized, font));
        return;
    }
    res.end('<!doctype html><html lang="de"><head><title>Flexible Hierarchien – lokale Browserprüfung</title></head>'
        + '<body style="background:#101114;color:white;font:16px Arial;margin:18px"><h1 style="font-size:20px">' + family + ' · synthetische Beispieldaten</h1>'
        + '<nav><a style="color:#55cbb5" href="/?family=treemap' + query + '">Treemap</a> · <a style="color:#55cbb5" href="/?family=sunburst' + query + '">Sunburst</a></nav>'
        + '<p>Sieben Gruppen plus Variable. Native Navigation, vollständiger Tooltip und Werteaktualisierung.</p>'
        + '<div style="display:flex;gap:18px;flex-wrap:wrap"><section><h2>Kachel</h2><iframe title="Kachel" src="/chart?mode=symcon&family=' + family + query + '" style="border:0;width:400px;height:480px"></iframe></section>'
        + '<section><h2>IPSView</h2><iframe title="IPSView" src="/chart?mode=ipsview&family=' + family + query + '" style="border:0;width:650px;height:480px"></iframe></section></div></body></html>');
}).listen(8804, '127.0.0.1', () => console.log('Hierarchy browser fixture: http://127.0.0.1:8804'));
