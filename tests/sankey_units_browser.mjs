// Local browser fixture: real module/Gateway output with synthetic Kernel variables only.
import http from 'node:http';
import { execFileSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.dirname(path.dirname(fileURLToPath(import.meta.url)));
const cases = ['manual', 'automatic', 'checked', 'mismatch', 'unitless', 'reverse', 'reverse-cycle',
    'balance', 'balance-inflow', 'balance-equal', 'balance-budget'];
http.createServer((req, res) => {
    const url = new URL(req.url, 'http://127.0.0.1');
    if (url.pathname === '/hook/SymconECharts/assets/interaction.js') {
        res.setHeader('Content-Type', 'application/javascript; charset=utf-8');
        res.end(fs.readFileSync(path.join(root, 'libs/helper/visualization/interaction.min.js'))); return;
    }
    const selected = cases.includes(url.searchParams.get('case')) ? url.searchParams.get('case') : 'automatic';
    if (url.pathname === '/chart') {
        const adapter = ['ipsview', 'preview'].includes(url.searchParams.get('adapter')) ? url.searchParams.get('adapter') : 'symcon';
        const flag = selected.startsWith('balance') ? '--sankey-balance' : selected.startsWith('reverse') ? '--sankey-reversal' : '--sankey-units';
        let output = execFileSync('php', ['tests/gateway_gauges.php', flag, 'browser', selected, adapter],
            { cwd: root, encoding: 'utf8', maxBuffer: 2 * 1024 * 1024 });
        res.setHeader('Content-Type', 'text/html; charset=utf-8');
        if (adapter === 'preview') {
            output = '<!doctype html><html><body style="margin:0;background:#151619"><img alt="Sankey-Vorschau" style="width:100%;height:auto" src="data:image/svg+xml;base64,'
                + Buffer.from(output).toString('base64') + '"></body></html>';
        }
        if (adapter === 'symcon') {
            output = output.replace('<body>', '<body><div style="position:absolute;top:16px;left:20px;color:white;z-index:3">EChartsSankey · Test</div>');
        }
        res.end(output); return;
    }
    res.setHeader('Content-Type', 'text/html; charset=utf-8');
    res.end('<!doctype html><html lang="de"><head><title>Sankey · Einheitenprüfung</title></head>'
        + '<body style="background:#101114;color:white;font:16px Arial;margin:18px"><h1>Sankey · ' + selected + '</h1><nav>'
        + cases.map(value => '<a style="color:#55cbb5;margin-right:20px" href="/?case=' + value + '">' + value + '</a>').join('')
        + '</nav><div style="display:flex;flex-wrap:wrap;gap:16px">'
        + [['symcon', 'Kachel'], ['ipsview', 'IPSView'], ['preview', 'SVG-Vorschau']].map(([adapter, label]) => '<section><h2>' + label
            + '</h2><iframe title="' + label + '" src="/chart?case=' + selected + '&adapter=' + adapter
            + '" style="border:0;width:480px;height:520px"></iframe></section>').join('')
        + '</div><p>Nur synthetische Werte. Kein Zugriff auf eine Symcon-Installation.</p></body></html>');
}).listen(8815, '127.0.0.1', () => console.log('Sankey units: http://127.0.0.1:8815'));
