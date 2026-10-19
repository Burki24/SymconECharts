// Local synthetic comparisons only; no Symcon connection or live values.
import http from 'node:http';
import fs from 'node:fs';
import path from 'node:path';
import {fileURLToPath} from 'node:url';
const root = path.dirname(path.dirname(fileURLToPath(import.meta.url)));
const read = file => fs.readFileSync(path.join(root, file), 'utf8');
function page(family, mode) {
    const pie = family === 'pie';
    const module = pie ? 'EChartsPie' : 'EChartsBarPolar';
    const palette = {background:'#303236',text:'#f4f5f7',muted:'#969aa2',border:'#777b83',
        surface:'#25272b',accent:'#55cbb5',seriesColors:['#5070dd','#b6d634','#ff994d']};
    const items = [
        {id:'temperature',name:'Temperatur',label:'Temperatur',value:50,originalValue:-5,minimum:-20,maximum:10,unit:'°C',decimals:1,order:0},
        {id:'humidity',name:'Luftfeuchte',label:'Luftfeuchte',value:75,originalValue:75,minimum:0,maximum:100,unit:'%',decimals:0,order:1},
        {id:'signal',name:'Signalqualität',label:'Signalqualität',value:100,originalValue:300,minimum:0,maximum:255,unit:'LQI',decimals:0,order:2}
    ];
    const key = pie ? 'pie' : 'polar';
    const style = pie ? {mode:'donut',showLegend:true,showLabels:true,showPercent:true,animationEnabled:false}
        : {mode:'tangential',showValues:true,showCategoryLabels:true,showGrid:true,animationEnabled:false,
            valueAxisRangeMode:'manual',valueAxisMinimum:-100,valueAxisMaximum:255};
    const chart = {schemaVersion:1,family:pie?'pie':'bar',theme:'auto',items,
        [key]:{normalized:true,title:'Relativer Vergleich · Originalwerte im Tooltip',unit:pie?'':'%',decimals:1,style}};
    const bootstrap = {mode,translations:JSON.parse(read(module+'/locale.json')).translations.de,
        options:{tileHeaderVisible:true,echartsThemes:{auto:palette}},state:{status:'ready',chart}};
    const replacements = {HTML_LANGUAGE:'de',HTML_CLASSES:'',ROOT_FONT_SIZE:'16px',
        VIEWPORT_CONTENT:'width=device-width, initial-scale=1',DOCUMENT_TITLE:module+' normalized fixture',
        VISUALIZATION_THEME:':root{--symc-background:#303236;--symc-text:#f4f5f7;--symc-text-muted:#969aa2;--symc-border:#777b83;--symc-surface:#25272b}',
        MODULE_STYLE:read(module+'/visualization/style.css'),IPSVIEW_STYLE:'',
        ECHARTS_SCRIPT:read('libs/echarts/6.1.0/'+(pie?'echarts.pie.min.js':'echarts.polar.min.js')),
        ECHARTS_THEME_SCRIPT:'',ECHARTS_DESIGN_SCRIPT:read('libs/echarts-design.js'),
        BOOTSTRAP_JSON:JSON.stringify(bootstrap),MODULE_SCRIPT:read(module+'/visualization/app.js'),IPSVIEW_TRANSPORT_SCRIPT:''};
    let html = read(module+'/visualization/index.html').replace(/\{\{([A-Z_]+)\}\}/g,(_, key)=>replacements[key]??'');
    if(mode==='symcon')html=html.replace('<body>','<body><div style="position:absolute;top:16px;left:20px;font:18px Arial;z-index:4">'+module+' <span style="position:fixed;right:18px">⤢</span></div>');
    return html;
}
http.createServer((request,response)=>{
    const url = new URL(request.url,'http://127.0.0.1');
    response.setHeader('Content-Type','text/html; charset=utf-8');
    if(url.pathname==='/chart'){
        response.end(page(url.searchParams.get('family')==='polar'?'polar':'pie',url.searchParams.get('mode')==='ipsview'?'ipsview':'symcon')); return;
    }
    response.end('<!doctype html><html lang="de"><body style="background:#101114;color:white;font:16px Arial"><h1>Normalisierung · synthetische Daten</h1>'
        +'<div style="display:flex;gap:16px;flex-wrap:wrap">'
        +'<iframe title="Pie Kachel" src="/chart?family=pie" style="border:0;width:420px;height:540px"></iframe>'
        +'<iframe title="Polar IPSView" src="/chart?family=polar&mode=ipsview" style="border:0;width:790px;height:440px"></iframe></div></body></html>');
}).listen(8810,'127.0.0.1',()=>console.log('Normalized comparisons fixture: http://127.0.0.1:8810'));
