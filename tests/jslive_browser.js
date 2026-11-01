'use strict';

// Optional real Chromium smoke test. Uses only anonymous local HTML in an isolated headless profile.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {spawnSync} = require('node:child_process');
const {pathToFileURL} = require('node:url');
const [browser, directory] = process.argv.slice(2);
if (!browser || !directory || !path.isAbsolute(browser) || !path.isAbsolute(directory)) {
    throw new Error('Usage: node tests/jslive_browser.js <absolute Chromium executable> <absolute local HTML output directory>');
}
const all = process.env.JSLIVE_BROWSER_ALL === '1';
const cases = process.argv[4]==='--progress' ? Array.from({length:18}, (_,i)=>i+23) : all ? Array.from({length:40}, (_,i)=>i+1) : [6,10,12,16,20,22,28,38,39,40];
let count=0;
const instrumentation = `<script>window.__jsliveErrors=[];addEventListener('error',function(e){window.__jsliveErrors.push(String(e.message));});addEventListener('unhandledrejection',function(e){window.__jsliveErrors.push(String(e.reason));});</script>`;
const probe = `<script>setTimeout(function(){
var b=window.SYMC_VISUALIZATION,e=document.getElementById('echarts-import-chart'),r=document.getElementById('echarts-import-root');
window.SYMC_ECHARTS_STARTUP(b,async function(state){
var c=window.echarts.getInstanceByDom(e),calls=0,fn=window.SYMC_JSLIVE_OPTION;window.SYMC_JSLIVE_OPTION=function(){calls++;return fn.apply(null,arguments);};
var legend=c&&c.getOption().legend,first=legend&&legend[0]&&legend[0].data&&legend[0].data[0];if(first&&typeof first==='object'){first=first.name;}
if(first){c.dispatchAction({type:'legendUnSelect',name:first});}
window.handleMessage(JSON.stringify(state));
var retained=!first||c.getOption().legend[0].selected[first]===false;
window.handleMessage({status:'error',error:'Synthetic transient archive failure'});
var warningRetained=!document.getElementById('echarts-import-warning').hidden&&!!e.querySelector('canvas');
window.handleMessage(state);
var progressTitle=true;
if(state.chart.family==='progress'){
var titled=JSON.parse(JSON.stringify(state));titled.chart.settings.title_display=true;titled.chart.settings.title_text='Imported progress title';
window.handleMessage(titled);progressTitle=JSON.stringify(c.getOption().graphic).includes('Imported progress title');window.handleMessage(state);
}
var before=document.scrollingElement.scrollTop;e.dispatchEvent(new WheelEvent('wheel',{deltaY:120,bubbles:true,cancelable:true}));
if(first){c.dispatchAction({type:'legendSelect',name:first});}
var gaugePaint=null;
if(state.chart.family==='gauge'){
gaugePaint={progress:[],track:[],hubShadow:[]};
['CanvasGauges-Linear','CanvasGauges-Linear(vertical)','CanvasGauges-Radial','CanvasGauges-Compass'].forEach(function(template){
var input=JSON.parse(JSON.stringify(state.chart)),s=input.settings;
Object.assign(s,{template:template,__animate:false,min:0,max:100,precision:2,plate_display:false,title_display:false,plate_unit:'',valuebox_display:false,
Highlights:[],Ticks:[{Value:0},{Value:50},{Value:100}],ticks_exactTicks:false,progressbar_display:true,progressbar_barWidth:10,
progressbar_colorBar:0xcbd5e1,progressbar_colorBar_Alpha:1,progressbar_colorBarProgress:0x22d3ee,progressbar_colorBarProgress_Alpha:1,
needle_display:template.indexOf('Linear')<0,needle_Type:'line',needle_start:0,needle_end:0,needle_width:0,needle_CircleSize:20,needle_CircleOuter:false,
needle_colorNeedleCircleInner:0xffffff,needle_colorNeedleCircleInner_Alpha:1,needle_colorNeedleCircleInnerEnd:0xffffff,needle_colorNeedleCircleInnerEnd_Alpha:1,
needle_colorShadowDown:0xff0000,needle_colorShadowDown_Alpha:1});
input.data.value=50;input.background='#fff';
var canvas=document.createElement('canvas'),test=window.echarts.init(canvas,null,{renderer:'canvas',width:300,height:400,devicePixelRatio:1});
try{
var option=fn(input,300,400);option.animation=false;test.setOption(option,true);test.getZr().flush();
var ctx=canvas.getContext('2d'),get=function(id){return option.graphic.find(function(g){return g.id===id;});};
var pixel=function(x,y){return Array.from(ctx.getImageData(Math.round(x),Math.round(y),1,1).data);};
if(template.indexOf('Linear')>=0){
var p=get('progress').shape,t=get('track').shape,vertical=template.indexOf('(vertical)')>=0;
gaugePaint.progress.push(pixel(p.x+p.width/2,p.y+p.height/2));
gaugePaint.track.push(pixel(t.x+t.width*(vertical?0.5:0.8),t.y+t.height*(vertical?0.2:0.5)));
}else{
var hub=get('hub').shape,red=0;
for(var y=Math.floor(hub.cy-hub.r-10);y<=hub.cy+hub.r+10;y++)for(var x=Math.floor(hub.cx-hub.r-10);x<=hub.cx+hub.r+10;x++){
var distance=Math.hypot(x-hub.cx,y-hub.cy);
if(distance>hub.r+1&&distance<hub.r+8){var px=pixel(x,y);if(px[0]>px[1]+10&&px[0]>px[2]+10){red++;}}
}
gaugePaint.hubShadow.push(red);
}
}finally{test.dispose();}
});
}
var progressPaint=null;
if(state.chart.family==='progress'&&(state.chart.settings.shape_svg||state.chart.settings.shape_preset==='text')){
progressPaint=[];
var shapes=[state.chart.settings.shape_svg||{dataUri:'data:image/svg+xml;base64,'+btoa('<svg xmlns="http://www.w3.org/2000/svg" width="70" height="20" viewBox="0 0 70 20"><text x="35" y="10" text-anchor="middle" dominant-baseline="central" font-family="arial">LOADING</text></svg>')}];
if(state.chart.settings.shape_svg)shapes.push({dataUri:'data:image/svg+xml;base64,'+btoa('<svg xmlns="http://www.w3.org/2000/svg" width="120" height="60" viewBox="0 0 120 60"><defs><linearGradient id="g"><stop stop-color="#f00"/><stop offset="1" stop-color="#00f"/></linearGradient></defs><rect x="10" y="10" width="100" height="40" fill="url(#g)" fill-opacity=".6"/></svg>'),width:120,height:60,viewBox:'0 0 120 60'});
for(var source of shapes){
var original=await new Promise(function(resolve,reject){var img=new Image();img.onload=function(){resolve(img);};img.onerror=reject;img.src=source.dataUri;});
for(var dir of ['ltr','rtl','ttb','btt'])for(var ratio of [0,0.5,1]){
var input=JSON.parse(JSON.stringify(state.chart)),s=input.settings;
Object.assign(s,{__animate:false,style_fontDisplay:false,title_display:false,fill_backgroundColor_Alpha:0,fill_backgroundExtrude:0,fill_color:0x00ff00,fill_color_Alpha:1,fill_dir:dir});
if(state.chart.settings.shape_svg)s.shape_svg=source;input.data.ratio=ratio;input.background='#fff';
var actual=document.createElement('canvas'),test=window.echarts.init(actual,null,{renderer:'canvas',width:300,height:242,devicePixelRatio:1});
try{
var option=fn(input,300,242),body=option.graphic.find(function(g){return g.id==='progress-body';}),image=body.children.find(function(g){return g.id==='image-progress';});
await Promise.all(body.children.filter(function(g){return g.type==='image';}).map(function(g){return new Promise(function(resolve,reject){var img=new Image();img.onload=resolve;img.onerror=reject;img.src=g.style.image;});}));
option.animation=false;test.setOption(option,true);await new Promise(function(resolve){setTimeout(resolve,100);});test.getZr().flush();
var expected=document.createElement('canvas');expected.width=300;expected.height=242;
var ref=expected.getContext('2d');ref.fillStyle='#fff';ref.fillRect(0,0,300,242);ref.translate(body.x,body.y);ref.scale(body.scaleX,body.scaleY);
// Reference the unmodified source SVG, not the renderer's foreground URI.
var iw=image.style.width,ih=image.style.height,rx=dir==='rtl'?iw*(1-ratio):0,ry=dir==='btt'?ih*(1-ratio):0;
ref.beginPath();ref.rect(rx,ry,iw*(dir==='ltr'||dir==='rtl'?ratio:1),ih*(dir==='ttb'||dir==='btt'?ratio:1));ref.clip();ref.drawImage(original,0,0,iw,ih);
var a=actual.getContext('2d').getImageData(0,0,300,242).data,z=ref.getImageData(0,0,300,242).data,ink=0,mismatch=0;
for(var p=0;p<a.length;p+=4){if(Math.min(z[p],z[p+1],z[p+2])<245)ink++;if(Math.max(Math.abs(a[p]-z[p]),Math.abs(a[p+1]-z[p+1]),Math.abs(a[p+2]-z[p+2]))>32)mismatch++;}
progressPaint.push({dir:dir,ratio:ratio,ink:ink,mismatch:mismatch});
}finally{test.dispose();}
}
}
}
var pixels=0,ink=0,canvases=e.querySelectorAll('canvas'),pixelError='';
try{Array.from(canvases).forEach(function(canvas){var p=canvas.getContext('2d').getImageData(0,0,canvas.width,canvas.height).data;for(var i=0;i<p.length;i+=16){if(p[i+3]>0&&Math.min(p[i],p[i+1],p[i+2])<230){ink++;}if(p[i+3]>0&&Math.max(p[i],p[i+1],p[i+2])-Math.min(p[i],p[i+1],p[i+2])>20){pixels++;}}});}catch(err){pixelError=String(err);}
var chartRect=e.getBoundingClientRect(),progressFrame=state.chart.family==='progress'?{left:chartRect.x,top:chartRect.y,width:innerWidth-e.clientWidth,height:innerHeight-e.clientHeight}:null;
var result={errors:window.__jsliveErrors,canvas:canvases.length,pixels:pixels,ink:ink,pixelError:pixelError,gaugePaint:gaugePaint,progressPaint:progressPaint,progressFrame:progressFrame,updates:calls,legendRetained:retained,warningRetained:warningRetained,progressTitle:progressTitle,
errorVisible:!document.getElementById('echarts-import-error').hidden,warningVisible:!document.getElementById('echarts-import-warning').hidden,
fontFailures:Array.from(document.fonts).filter(function(f){return f.status!=='loaded';}).map(function(f){return f.family+':'+f.status;}),
headerInset:parseFloat(getComputedStyle(r).paddingTop),scrollUnchanged:document.scrollingElement.scrollTop===before};
var out=document.createElement('pre');out.id='jslive-browser-result';out.hidden=true;out.textContent=JSON.stringify(result);document.body.appendChild(out);
},function(){window.__jsliveErrors.push('Probe startup failed');});
},4000);</script>`;
for(const index of cases){
    for(const mode of ['tile','ipsview']){
        const name='case-'+String(index).padStart(2,'0')+'-'+mode;
        const input=path.join(directory,name+'.html'),test=path.join(directory,name+'-test.html');
        let html=fs.readFileSync(input,'utf8');
        html=html.replace('<head>','<head>'+instrumentation).replace('</body>',probe+'</body>');
        fs.writeFileSync(test,html);
        const result=spawnSync(browser,['--headless=new','--disable-gpu','--no-first-run','--no-default-browser-check',
            '--host-resolver-rules=MAP * ~NOTFOUND','--allow-file-access-from-files','--hide-scrollbars','--window-size=660,600',
            '--user-data-dir='+path.join(directory,'browser-profile-'+name),
            '--screenshot='+path.join(directory,name+'.png'),'--virtual-time-budget=12000','--dump-dom',pathToFileURL(test).href],
            {encoding:'utf8',windowsHide:true,maxBuffer:4194304,timeout:45000});
        fs.writeFileSync(path.join(directory,name+'.browser.log'),result.stderr||'');
        assert.equal(result.status,0,name+': browser failed: '+(result.error||result.stderr||'').toString().slice(-1000));
        const match=/<pre id="jslive-browser-result"[^>]*>(.*?)<\/pre>/s.exec(result.stdout||'');
        assert.ok(match,name+': probe did not finish; see isolated browser log');
        const metrics=JSON.parse(match[1].replace(/&quot;/g,'"').replace(/&gt;/g,'>').replace(/&lt;/g,'<').replace(/&amp;/g,'&'));
        fs.writeFileSync(path.join(directory,name+'.metrics.json'),JSON.stringify(metrics,null,2)+'\n');
        assert.deepEqual(metrics.errors,[],name+': script errors');
        assert.deepEqual(metrics.fontFailures,[],name+': font loading failed');
        assert.equal(metrics.canvas>0,true,name+': no native canvas');
        assert.equal(metrics.ink>30,true,name+': empty native canvas: '+JSON.stringify(metrics));
        assert.equal(metrics.errorVisible,false,name+': visible startup error');
        assert.equal(metrics.warningVisible,false,name+': unexpected warning');
        assert.equal(metrics.updates>=2,true,name+': native live messages were ignored');
        assert.equal(metrics.legendRetained,true,name+': native update reset the selected legend');
        assert.equal(metrics.warningRetained,true,name+': transient failure discarded the previous chart');
        assert.equal(metrics.progressTitle,true,name+': configured Progress title is missing');
        if(index>=15&&index<=22){
            assert.ok(metrics.gaugePaint,name+': gauge pixel probe did not run');
            assert.deepEqual(metrics.gaugePaint.progress,[[34,211,238,255],[34,211,238,255]],name+': solid cyan progress pixels');
            assert.deepEqual(metrics.gaugePaint.track,[[203,213,225,255],[203,213,225,255]],name+': solid track pixels');
            assert.ok(metrics.gaugePaint.hubShadow.length===2&&metrics.gaugePaint.hubShadow.every(n=>n>20),name+': visible configured hub-shadow pixels');
        }
        if([37,38,40].includes(index)){
            assert.ok(metrics.progressPaint&&metrics.progressPaint.length>=12,name+': original-image raster probe did not finish');
            for(const paint of metrics.progressPaint){
                assert.ok(paint.mismatch<=10,name+': F05 original SVG/text paints differ: '+JSON.stringify(paint));
                if(paint.ratio===1)assert.ok(paint.ink>100,name+': empty original SVG/text raster');
            }
        }
        if(index>=23){
            assert.deepEqual(metrics.progressFrame,{left:8,top:metrics.headerInset,width:16,height:8+metrics.headerInset},
                name+': the shared HTML adapter must provide the source margin exactly once');
        }
        assert.equal(metrics.scrollUnchanged,true,name+': wheel moved the outer document');
        assert.ok(mode==='tile'?metrics.headerInset>=58:metrics.headerInset<58,name+': wrong native tile header inset');
        console.log(name+': native canvas, fonts, refresh, legend, transient error and wheel smoke passed.');
        ++count;
    }
}
console.log('JSLive isolated Chromium smoke: '+count+' real documents passed.');
