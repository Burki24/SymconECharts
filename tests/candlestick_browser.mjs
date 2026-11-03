// Local browser fixture only; no connection to Symcon and no real variable data.
import http from 'node:http';
import fs from 'node:fs';
import path from 'node:path';
import zlib from 'node:zlib';
import {fileURLToPath} from 'node:url';

const root = path.dirname(path.dirname(fileURLToPath(import.meta.url)));
const read = p => fs.readFileSync(path.join(root, p), 'utf8');
const style = { orientation: 'vertical', showGrid: true, showAxisLabels: true, showTooltip: true,
    candleWidth: 40, riseColor:'#3FBE95', fallColor:'#E66B6B', borderWidth: 2, candleOpacityPercent: 70, titleFontSizePercent: 100, axisFontSizePercent: 100, animationEnabled: false, enableZoom: true };
function state(interval = 'hour', panels = 0) {
    const value = { schemaVersion: 1, family: 'candlestick', status: 'ready', error: null, chart: {
        schemaVersion: 1, family: 'candlestick', theme: 'auto', range: {key:'7d',startTimestamp:1791504000,endTimestamp:1792108800}, candlestick: { title: 'Temperatur · Archivintervalle', unit: '°C', variableID:12345, interval:'hour',
            sourceLabel: 'Außentemperatur',
            data: Array.from({length:168},(_,index) => ({
                id: 'interval-' + index, label: new Date((1791504000+index*3600)*1000).toISOString().slice(0,16), unit: '°C', decimals: 1, color: '',
                statistics: index % 19 === 3 ? null : index % 19 === 4 ? [20,20,20,20] : [18+index%7, index % 2 === 0 ? 22+index%7 : 16+index%7, 14+index%7,24+index%7],
                count: 168 - index * 5, firstTimestamp: 1791504000, lastTimestamp: 1792108800
            })), style: { ...style } }
    } };
    if (['fifteen-minutes','thirty-minutes','week'].includes(interval)) {
        const step = {'fifteen-minutes':900,'thirty-minutes':1800,week:604800}[interval];
        const count = interval === 'week' ? 12 : 96;
        const start = Date.parse('2026-07-06T00:00:00Z') / 1000;
        value.chart.range = {key:'absolute',timezone:'UTC',startTimestamp:start,endTimestamp:start+count*step-1};
        value.chart.candlestick.interval = interval;
        value.chart.candlestick.title = 'Synthetische Rohwertkerzen · ' + interval;
        value.chart.candlestick.data = Array.from({length:count},(_,i)=>({
            ...value.chart.candlestick.data[i%168],id:String(start+i*step),count:3+i%7,
            label:interval==='week' ? '2026-W'+String(28+i).padStart(2,'0')
                : new Date((start+i*step)*1000).toISOString().slice(0,16).replace('T',' ')+' +00:00',
            firstTimestamp:start+i*step,lastTimestamp:start+(i+1)*step-1
        }));
    }
    if (panels) {
        const c=value.chart.candlestick;
        c.panels=Array.from({length:panels},(_,i)=>({id:String(12345+i),variableID:12345+i,
            sourceLabel:['Raumtemperatur','Luftfeuchtigkeit','Vorlauftemperatur','Luftdruck'][i],unit:i===1?'%':i===3?'hPa':'°C',
            data:c.data.map(item=>({...item,unit:i===1?'%':i===3?'hPa':'°C',statistics:item.statistics?.map(v=>v*(i+1)*10)??null}))}));
        c.data=[];
        c.title='Benannte Vergleichspanels';
    }
    return value;
}
const variables = ':root{--symc-background:#303236;--symc-text:#f4f5f7;--symc-text-muted:#969aa2;--symc-border:#555860;--symc-surface:#25272b}';
const translations = JSON.parse(read('EChartsCandlestick/locale.json')).translations.de;
function page(mode, extended = false, light = false, interval = 'hour', panels = 0, compressed = false) {
    const initialState = state(interval, panels);
    if (mode === 'ipsview') {
        initialState.chart.candlestick.style.orientation = 'horizontal';
        initialState.chart.candlestick.style.axisColor = light ? '#333333' : '#A9DCD2';
    }
    if (extended) {
        Object.assign(initialState.chart.candlestick.style, {
            minCandleWidth: 20, borderType: 'dashed', borderColor: '#CBD6E8', shadowBlur: 8, shadowColor: '#000000',
            shadowOffsetX: 3, shadowOffsetY: 3, hoverFocus: 'self', blurOpacityPercent: 10,
            axisLineWidth: 2, showAxisTicks: false, axisLabelRotate: mode === 'ipsview' ? 0 : 45, axisLabelMaxWidth: 100,
            titleBold: false, axisLabelBold: true, splitNumber: 8, gridLineType: 'dotted', gridLineWidth: 1,
            gridOpacityPercent: 50, gridColor: '#85929E',
            fixedCandleWidth:22,riseHollow:true,fallHollow:false,riseBorderColor:'#46B897',fallBorderColor:'#E66B6B',
            hoverBorderWidth:5,hoverBorderColor:light?'#333333':'#ffffff',hoverOpacityPercent:100,
            axisLineColor:light?'#444444':'#B9C9D2',axisLineType:'dashed',axisLabelMargin:16,
            reverseTimeAxis:true,includeZero:true,fontFamily:'Georgia, serif',titleItalic:true,axisLabelItalic:true
        });
    }
    const bootstrap = { mode, state: initialState, options: { tileHeaderVisible: mode === 'symcon', tileHeaderInset: mode === 'symcon' ? 58 : 0, echartsThemes: {auto: {background:'#303236',text:'#f4f5f7',muted:'#969aa2',border:'#777b83',accent:'#55CBB5',seriesColors:['#5070dd','#b6d634','#ff994d','#fb628b']}},
        adaptToBackground: false,  }, translations };
    if (compressed) {
        bootstrap.options.compressedStateGzipBase64=zlib.gzipSync(JSON.stringify(initialState)).toString('base64');
        bootstrap.state=null;
    }
    const replacements = { HTML_LANGUAGE: 'de', HTML_CLASSES: '', ROOT_FONT_SIZE: '16px', VIEWPORT_CONTENT: 'width=device-width, initial-scale=1',
        DOCUMENT_TITLE: 'Candlestick browser fixture', VISUALIZATION_THEME: light ? ':root{--symc-background:#ffffff;--symc-text:#222222;--symc-text-muted:#666666;--symc-border:#cccccc;--symc-surface:#eeeeee}' : variables, MODULE_STYLE: read('EChartsCandlestick/visualization/style.css'),
        IPSVIEW_STYLE: '', ECHARTS_SCRIPT: read('libs/echarts/6.1.0/echarts.candlestick.min.js'), ECHARTS_THEME_SCRIPT: '',
        ECHARTS_DESIGN_SCRIPT: read('libs/echarts-design.js')+'\n'+read('libs/echarts-startup.js'), ECHARTS_ZOOM_SCRIPT: read('libs/echarts-zoom.js'),
        INTERACTION_SCRIPT: '<script>'+read('libs/helper/visualization/interaction.js')+'</script>', BOOTSTRAP_JSON: JSON.stringify(bootstrap),
        MODULE_SCRIPT: read('EChartsCandlestick/visualization/app.js'), IPSVIEW_TRANSPORT_SCRIPT: '' };
    let html = read('EChartsCandlestick/visualization/index.html').replace(/\{\{([A-Z_]+)\}\}/g, (_, key) => replacements[key] ?? '');
    html = html.replace('</body>', `<output id="zoom-proof" style="position:fixed;bottom:0;left:5px;font:9px monospace;color:#ddd;pointer-events:none"></output>
        <button id="refresh-proof" style="position:fixed;top:0;left:0;font:9px Arial" onclick="window.handleMessage(window.fixtureState)">Refresh fixture</button>
        <script>window.fixtureState=${JSON.stringify(initialState).replace(/</g,'\\u003c')};window.addEventListener('wheel',()=>{window.fixtureWheels=(window.fixtureWheels||0)+1;},{capture:true});setInterval(function(){var c=window.echarts.getInstanceByDom(document.getElementById('echarts-candlestick-chart'));if(!c)return;
        var o=c.getOption(),z=o.dataZoom[0];document.getElementById('zoom-proof').textContent=JSON.stringify({start:z.start,end:z.end,
        scales:o.series.map((_,i)=>c.getModel().getComponent('${mode==='ipsview'?'xAxis':'yAxis'}',i).axis.scale.getExtent()),wheel:window.fixtureWheels||0});},150);</script></body>`);
    if (mode === 'symcon') { html = html.replace('<body>', '<body><div style="position:absolute;top:16px;left:20px;font:18px Arial;z-index:4">EChartsCandlestick <span style="position:fixed;right:18px">⤢</span></div>'); }
    return html;
}
http.createServer((req, res) => {
    const url = new URL(req.url, 'http://127.0.0.1');
    res.setHeader('Content-Type', 'text/html; charset=utf-8');
    const interval = ['fifteen-minutes','thirty-minutes','week'].includes(url.searchParams.get('interval')) ? url.searchParams.get('interval') : 'hour';
    const panels=Math.max(0,Math.min(4,Number(url.searchParams.get('panels'))||0));
    if (url.pathname === '/chart') { res.end(page(url.searchParams.get('mode') === 'ipsview' ? 'ipsview' : 'symcon', url.searchParams.get('design') === 'extended', url.searchParams.get('theme') === 'light', interval, panels, url.searchParams.get('compressed')==='true')); return; }
    res.end('<!doctype html><html lang="de"><head><title>Candlestick – lokale Browserprüfung</title></head><body style="background:#0b0c0e;color:white;font:16px Arial;margin:20px">'
        + '<h1 style="font-size:20px">Candlestick · synthetische Beispieldaten</h1><nav>'
        + ['fifteen-minutes','thirty-minutes','week'].map(item=>'<a style="color:#aaddff;margin-right:18px" href="/?interval='+item+'">'+item+'</a>').join('')
        + '</nav><div style="display:flex;gap:16px;align-items:start;flex-wrap:wrap">'
        + '<section><h2 style="font-size:16px">Kachel · hoch</h2><iframe title="Kachel" src="/chart?mode=symcon&design=extended&panels='+panels+'&compressed=true&interval='+interval+'" style="border:0;width:400px;height:600px"></iframe></section>'
        + '<section><h2 style="font-size:16px">IPSView · breit</h2><iframe title="IPSView" src="/chart?mode=ipsview&design=extended&panels='+panels+'&compressed=true&interval='+interval+'" style="border:0;width:790px;height:440px"></iframe></section></div></body></html>');
}).listen(8800, '127.0.0.1', () => console.log('Candlestick browser fixture: http://127.0.0.1:8800'));
