// Local synthetic Tree fixture. No Symcon connection or installation data.
import http from 'node:http';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { spawnSync } from 'node:child_process';

const root = path.dirname(path.dirname(fileURLToPath(import.meta.url)));
const read = file => fs.readFileSync(path.join(root, file), 'utf8');
function state(health, orientation, updated = false) {
    let node = { id: 'level-12', name: 'Temperatur mit langem Variablennamen <Probe>', kind: 'variable',
        displayValue: updated ? '23 °C' : '21 °C', health: 'warning', children: [] };
    for (let depth = 11; depth >= 0; depth--) {
        node = { id: 'level-' + depth, name: 'Nachkommensebene ' + depth + ' · langer Objektname',
            kind: 'category', health: 'warning', children: [node] };
    }
    return { status: 'ready', chart: { family: 'tree', theme: 'auto', tree: {
        title: 'Zwölf Nachkommensebenen', root: node, count: 13, truncated: false,
        healthScope: health ? { ruleCount: 2, evaluatedRuleCount: 1 } : undefined,
        style: { treeLayout: orientation === 'radial' ? 'radial' : 'orthogonal',
            orientation: orientation === 'radial' ? 'LR' : orientation, initialDepth: 12,
            roamEnabled: true, showLabels: true, showValues: true, nodeSize: 8,
            animationEnabled: false, labelFontSizePercent: 100 }
    } } };
}
function page(mode, health, orientation) {
    const module = health ? 'EChartsTreeSystemHealth' : 'EChartsTree';
    const bootstrap = { mode, state: state(health, orientation),
        translations: JSON.parse(read(module + '/locale.json')).translations.de,
        options: { tileHeaderVisible: true, treeMaxDepth: 12, echartsThemes: {} } };
    const replacements = { HTML_LANGUAGE: 'de', HTML_CLASSES: '', ROOT_FONT_SIZE: '16px',
        VIEWPORT_CONTENT: 'width=device-width, initial-scale=1', DOCUMENT_TITLE: 'Tree browser fixture',
        VISUALIZATION_THEME: ':root{--symc-background:#303236;--symc-text:#f4f5f7;--symc-text-muted:#969aa2;--symc-surface:#25272b}',
        MODULE_STYLE: read(module + '/visualization/style.css'), IPSVIEW_STYLE: '',
        ECHARTS_SCRIPT: read('libs/echarts/6.1.0/echarts.tree.min.js'), ECHARTS_THEME_SCRIPT: '',
        ECHARTS_DESIGN_SCRIPT: read('libs/echarts-design.js') + '\n' + read('libs/echarts-tree.js'),
        BOOTSTRAP_JSON: JSON.stringify(bootstrap), MODULE_SCRIPT: read(module + '/visualization/app.js'),
        IPSVIEW_TRANSPORT_SCRIPT: '' };
    let html = read(module + '/visualization/index.html').replace(/\{\{([A-Z_]+)\}\}/g, (_, key) => replacements[key] ?? '');
    const script = `function treeChart(){return echarts.getInstanceByDom(document.getElementById('echarts-tree-chart'));}
        function report(){const s=treeChart().getModel().getSeriesByIndex(0);const n=s.getData().tree.getNodeById('level-7');
        document.getElementById('result').textContent='Zoom '+s.get('zoom')+' · Ebene 7 '+(n.isExpand?'offen':'zu');}
        function collapse(){const s=treeChart().getModel().getSeriesByIndex(0);treeChart().dispatchAction({type:'treeExpandAndCollapse',seriesId:'tree',dataIndex:s.getData().tree.getNodeById('level-7').dataIndex});report();}
        function roam(){treeChart().dispatchAction({type:'treeRoam',seriesId:'tree',dx:25,dy:15});treeChart().dispatchAction({type:'treeRoam',seriesId:'tree',zoom:1.2,originX:200,originY:200});report();}
        function refresh(){window.handleMessage(${JSON.stringify(state(health, orientation, true))});report();}`;
    html = html.replace('</body>', '<div style="position:absolute;right:8px;top:105px;z-index:5;font:11px Arial">'
        + '<button onclick="collapse()">Ebene 7 umschalten</button><button onclick="roam()">Verschieben / Zoom</button>'
        + '<button onclick="refresh()">Werte aktualisieren</button><span id="result"></span></div><script>' + script + '</script></body>');
    if (mode === 'symcon') {
        html = html.replace('<body>', '<body><div style="position:absolute;top:16px;left:20px;font:18px Arial;z-index:4">' + module + '</div>');
    }
    return html;
}
http.createServer((req, res) => {
    const url = new URL(req.url, 'http://127.0.0.1');
    const health = url.searchParams.get('family') === 'health';
    const orientation = ['LR', 'RL', 'TB', 'BT', 'radial'].includes(url.searchParams.get('orientation'))
        ? url.searchParams.get('orientation') : 'LR';
    if (url.pathname === '/preview.svg') {
        const result = spawnSync('php', ['tests/tree_preview.php', '--svg', orientation], { cwd: root, encoding: 'utf8' });
        res.setHeader('Content-Type', 'image/svg+xml');
        res.end(result.stdout); return;
    }
    res.setHeader('Content-Type', 'text/html; charset=utf-8');
    if (url.pathname === '/chart') {
        res.end(page(url.searchParams.get('mode') === 'ipsview' ? 'ipsview' : 'symcon', health, orientation)); return;
    }
    const query = '&family=' + (health ? 'health' : 'tree') + '&orientation=' + orientation;
    res.end('<!doctype html><html lang="de"><head><title>Tree · lokale Browserprüfung</title></head>'
        + '<body style="background:#101114;color:white;font:16px Arial;margin:18px"><h1 style="font-size:20px">'
        + (health ? 'TreeSystemHealth' : 'Tree') + ' · zwölf Nachkommensebenen · ' + orientation + '</h1>'
        + '<nav>' + ['LR', 'RL', 'TB', 'BT', 'radial'].map(o => '<a style="color:#55cbb5;margin-right:12px" href="/?family='
            + (health ? 'health' : 'tree') + '&orientation=' + o + '">' + o + '</a>').join('')
        + '<a style="color:#55cbb5" href="/?family=' + (health ? 'tree' : 'health') + '&orientation=' + orientation + '">Familie wechseln</a></nav>'
        + '<div style="display:flex;gap:18px;flex-wrap:wrap"><section><h2>Kachel</h2><iframe title="Kachel" src="/chart?mode=symcon'
        + query + '" style="border:0;width:400px;height:520px"></iframe></section><section><h2>IPSView</h2><iframe title="IPSView" src="/chart?mode=ipsview'
        + query + '" style="border:0;width:720px;height:520px"></iframe></section></div>'
        + '<h2>Schematische Vorschau</h2><img alt="Vorschau" src="/preview.svg?orientation=' + orientation + '" width="720" height="360"></body></html>');
}).listen(8812, '127.0.0.1', () => console.log('Tree browser fixture: http://127.0.0.1:8812'));
