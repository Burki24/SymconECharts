// Local, synthetic Graph stress fixture. No Symcon connection or installation data.
import http from 'node:http';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { gzipSync } from 'node:zlib';
import { execFileSync } from 'node:child_process';

const root = path.dirname(path.dirname(fileURLToPath(import.meta.url)));
const read = file => fs.readFileSync(path.join(root, file), 'utf8');
function state(layout, updated = false, zigbee = false, selection = 'strongest', directed = false, count = 300, weighted = false, maximumWidth = 9) {
    const nodes = Array.from({ length: count }, (_, index) => ({
        id: 'object-' + (1000 + index), name: 'Sensor mit langem Namen ' + (index % 50),
        kind: zigbee ? (index === 0 ? 'Coordinator' : index % 3 === 0 ? 'EndDevice' : 'Router') : 'variable',
        displayValue: (updated ? '23' : '21') + ' °C'
    }));
    const links = [];
    const maximum = count === 300 ? 1000 : count;
    for (let distance = 1; links.length < maximum; distance++) {
        for (let index = 0; index < nodes.length && links.length < maximum; index++) {
            links.push({ source: nodes[index].id, target: nodes[(index + distance) % nodes.length].id,
                lqi: (index * 13) % 256 });
        }
    }
    let model = { nodes, links, totalLinks: 1000, capturedAt: 1800000000 };
    if ((directed || weighted) && !zigbee) {
        const pairs = count === 300 ? links.slice(0, 500) : links.slice(0, count === 2 ? 1 : 2);
        const edgeRows = directed ? pairs.flatMap(link => [
            { SourceObjectID: Number(link.source.slice(7)), TargetObjectID: Number(link.target.slice(7)) },
            { SourceObjectID: Number(link.target.slice(7)), TargetObjectID: Number(link.source.slice(7)) }
        ]) : links.map(link => ({ SourceObjectID: Number(link.source.slice(7)), TargetObjectID: Number(link.target.slice(7)) }));
        if (weighted) { edgeRows.forEach((edge, index) => {
            if (index % 4 !== 3) { Object.assign(edge, { WeightMode: 'manual',
                Weight: [updated ? 50 : 25, 75, 0][index % 4], WeightUnit: 'W', WeightDecimals: 0 }); }
        }); }
        model = JSON.parse(execFileSync('php', ['-r',
            'require $argv[1]; $v=json_decode(stream_get_contents(STDIN),true,512,JSON_THROW_ON_ERROR); echo json_encode(\\SymconECharts\\GraphModel::Build($v["nodes"],$v["edges"],static fn(int $id):array=>["ObjectType"=>2,"ObjectName"=>"Same name"],static fn(int $id):string=>"25 %",$v["directed"],$v["weights"]),JSON_THROW_ON_ERROR);',
            path.join(root, 'libs/GraphModel.php')], { input: JSON.stringify({
                nodes: nodes.map(node => ({ ObjectID: Number(node.id.slice(7)), Label: node.name })), edges: edgeRows, directed,
                weights: { minimum: 0, maximum: 100, minimumWidth: 1, maximumWidth }
            }), encoding: 'utf8' }));
        if (updated) { model.nodes.forEach(node => { node.displayValue = '26 %'; }); }
    }
    if (zigbee) {
        const addresses = new Map(nodes.map((node, index) => [node.id, '0x' + (index + 1).toString(16).padStart(16, '0')]));
        const raw = { captured_at: 1800000000,
            nodes: nodes.map(node => ({ ieeeAddr: addresses.get(node.id), friendlyName: node.name, type: node.kind })),
            links: links.map(link => ({ sourceIeeeAddr: addresses.get(link.source), targetIeeeAddr: addresses.get(link.target), lqi: link.lqi })) };
        const input = { topology: raw, selection: selection === 'empty' ? 'range' : selection,
            minimum: selection === 'range' ? 64 : selection === 'empty' ? 256 : 0, maximum: selection === 'range' ? 128 : 255 };
        // Empty fixture uses a real valid range, but no matching saved links.
        if (selection === 'empty') { input.minimum = 1; input.maximum = 1;
            input.topology.links = input.topology.links.filter(link => link.lqi !== 1); }
        model = JSON.parse(execFileSync('php', ['-r',
            'require $argv[1]; $v=json_decode(stream_get_contents(STDIN),true,512,JSON_THROW_ON_ERROR); echo json_encode(\\SymconECharts\\ZigbeeGraphModel::Build($v["topology"],$v["minimum"],300,$v["selection"],$v["maximum"]),JSON_THROW_ON_ERROR);',
            path.join(root, 'libs/ZigbeeGraphModel.php')], { input: JSON.stringify(input), encoding: 'utf8' }));
    }
    return { status: 'ready', chart: { family: zigbee ? 'zigbee-graph' : 'graph', theme: 'auto', graph: {
        title: count + ' Knoten · ' + model.links.length + ' Verbindungen', ...model,
        style: { graphLayout: layout, nodeSize: 12, roamEnabled: true,
            showLabels: true, showValues: true, animationEnabled: false }
    } } };
}
function page(mode, layout, zigbee, directed = false, count = 300, weighted = false) {
    const module = zigbee ? 'EChartsGraphZ2M' : 'EChartsGraph';
    const chartID = zigbee ? 'echarts-zigbee-graph-chart' : 'echarts-graph-chart';
    const bootstrap = { mode, state: null,
        translations: JSON.parse(read(module + '/locale.json')).translations.de,
        options: { tileHeaderVisible: mode === 'symcon', tileHeaderInset: mode === 'symcon' ? 58 : 0, echartsThemes: {},
            compressedStateGzipBase64: gzipSync(JSON.stringify(state(layout, false, zigbee, 'strongest', directed, count, weighted, mode === 'ipsview' ? 17 : 9))).toString('base64') } };
    const replacements = { HTML_LANGUAGE: 'de', HTML_CLASSES: '', ROOT_FONT_SIZE: '16px',
        VIEWPORT_CONTENT: 'width=device-width, initial-scale=1', DOCUMENT_TITLE: 'Graph browser fixture',
        VISUALIZATION_THEME: ':root{--symc-background:#303236;--symc-text:#f4f5f7;--symc-text-muted:#969aa2}',
        MODULE_STYLE: read(module + '/visualization/style.css'), IPSVIEW_STYLE: '',
        ECHARTS_SCRIPT: read('libs/echarts/6.1.0/echarts.graph.min.js'), ECHARTS_THEME_SCRIPT: '',
        ECHARTS_DESIGN_SCRIPT: read('libs/echarts-design.js') + '\n' + read('libs/echarts-startup.js')
            + '\n' + read('libs/echarts-graph.js'),
        BOOTSTRAP_JSON: JSON.stringify(bootstrap), MODULE_SCRIPT: read(module + '/visualization/app.js'),
        IPSVIEW_TRANSPORT_SCRIPT: '' };
    let html = read(module + '/visualization/index.html').replace(/\{\{([A-Z_]+)\}\}/g, (_, key) => replacements[key] ?? '');
    const script = `let wheelCount=0;
        function graphChart(){return echarts.getInstanceByDom(document.getElementById('${chartID}'));}
        function report(){const s=graphChart().getModel().getSeriesByIndex(0);
        document.getElementById('result').textContent='Zoom '+Number(s.get('zoom')||1).toFixed(2)+' · '+s.getData().count()+' Knoten · '+s.getEdgeData().count()+' Kanten · Wheel '+wheelCount
        + (${weighted} ? ' · Breiten '+Array.from({length:Math.min(4,s.getEdgeData().count())},(_,i)=>s.getEdgeData().getItemVisual(i,'style').lineWidth).join('/') : '');}
        function observe(){if(!graphChart()){requestAnimationFrame(observe);return;}
        graphChart().on('graphRoam',report);
        document.getElementById('${chartID}').addEventListener('wheel',()=>{wheelCount++;requestAnimationFrame(report);},{passive:true,capture:true});report();}observe();
        function roam(){graphChart().dispatchAction({type:'graphRoam',seriesId:'graph',dx:20,dy:10});
        graphChart().dispatchAction({type:'graphRoam',seriesId:'graph',zoom:1.5,originX:200,originY:200});report();}
        const samples=${JSON.stringify(Object.fromEntries((zigbee ? ['strongest', 'weakest', 'range', 'empty'] : ['strongest'])
            .map(selection => [selection, state(layout, true, zigbee, selection, directed, count, weighted, mode === 'ipsview' ? 17 : 9)])))};
        let selection='strongest';
        function selectLinks(next){selection=next;refresh();}
        function refresh(){window.handleMessage(samples[selection]);report();}
        function unweight(){const v=samples[selection];window.handleMessage({...v,chart:{...v.chart,graph:{...v.chart.graph,
        links:v.chart.graph.links.map(({weight,width,...link})=>link)}}});report();}`;
    html = html.replace('</head>', '<style>.echarts-graph-root,.echarts-zigbee-graph-root{height:calc(100% - 36px)}</style></head>');
    html = html.replace('</body>', '<div style="position:absolute;left:12px;bottom:4px;z-index:5;font:10px Arial">'
        + '<button onclick="roam()">Verschieben / Zoom</button><button onclick="refresh()">Werte aktualisieren</button>'
        + (weighted ? '<button onclick="unweight()">Gewichtung aus</button>' : '')
        + (zigbee ? '<button onclick="selectLinks(\'strongest\')">Stärkste</button><button onclick="selectLinks(\'weakest\')">Schwächste</button>'
            + '<button onclick="selectLinks(\'range\')">LQI 64–128</button><button onclick="selectLinks(\'empty\')">Ohne Treffer</button>' : '')
        + '<span id="result"></span></div><script>' + script + '</script></body>');
    if (mode === 'symcon') {
        html = html.replace('<body>', '<body><div style="position:absolute;top:16px;left:20px;font:18px Arial;z-index:4">' + module + '</div>');
    }
    return html;
}
http.createServer((req, res) => {
    const url = new URL(req.url, 'http://127.0.0.1');
    const layout = url.searchParams.get('layout') === 'circular' ? 'circular' : 'force';
    const zigbee = url.searchParams.get('family') === 'z2m';
    const family = zigbee ? 'z2m' : 'manual';
    const directed = !zigbee && url.searchParams.get('directed') === '1';
    const weighted = !zigbee && url.searchParams.get('weighted') === '1';
    const count = (directed || weighted) && url.searchParams.get('small') === '1' ? (weighted ? 3 : 2) : 300;
    const suffix = '&directed=' + (directed ? '1' : '0') + '&small=' + (count < 300 ? '1' : '0') + '&weighted=' + (weighted ? '1' : '0');
    res.setHeader('Content-Type', 'text/html; charset=utf-8');
    if (url.pathname === '/chart') {
        res.end(page(url.searchParams.get('mode') === 'ipsview' ? 'ipsview' : 'symcon', layout, zigbee, directed, count, weighted)); return;
    }
    res.end('<!doctype html><html lang="de"><head><title>Graph · lokale Browserprüfung</title></head>'
        + '<body style="background:#101114;color:white;font:16px Arial;margin:18px"><h1 style="font-size:20px">'
        + (zigbee ? 'GraphZ2M' : 'Graph') + ' · ' + count + ' Knoten · ' + layout + (directed ? ' · gerichtet' : '') + '</h1>'
        + '<nav><a style="color:#55cbb5" href="/?layout=force&family=' + family + suffix + '">Kräftebasiert</a> · '
        + '<a style="color:#55cbb5" href="/?layout=circular&family=' + family + suffix + '">Kreisförmig</a> · '
        + '<a style="color:#55cbb5" href="/?layout=' + layout + '&family=' + (zigbee ? 'manual' : 'z2m') + '">Familie wechseln</a></nav>'
        + '<div style="display:flex;gap:18px;flex-wrap:wrap"><section><h2>Kachel</h2><iframe title="Kachel" src="/chart?mode=symcon&layout='
        + layout + '&family=' + family + suffix + '" style="border:0;width:400px;height:520px"></iframe></section><section><h2>IPSView</h2>'
        + '<iframe title="IPSView" src="/chart?mode=ipsview&layout=' + layout
        + '&family=' + family + suffix + '" style="border:0;width:720px;height:520px"></iframe></section></div></body></html>');
}).listen(8814, '127.0.0.1', () => console.log('Graph browser fixture: http://127.0.0.1:8814'));
