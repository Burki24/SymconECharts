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
const cases = all ? Array.from({length:40}, (_,i)=>i+1) : [6,10,12,16,20,22,28,39,40];
let count=0;
const instrumentation = `<script>window.__jsliveErrors=[];addEventListener('error',function(e){window.__jsliveErrors.push(String(e.message));});addEventListener('unhandledrejection',function(e){window.__jsliveErrors.push(String(e.reason));});</script>`;
const probe = `<script>setTimeout(function(){
var b=window.SYMC_VISUALIZATION,e=document.getElementById('echarts-import-chart'),r=document.getElementById('echarts-import-root');
window.SYMC_ECHARTS_STARTUP(b,function(state){
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
var pixels=0,ink=0,canvases=e.querySelectorAll('canvas'),pixelError='';
try{Array.from(canvases).forEach(function(canvas){var p=canvas.getContext('2d').getImageData(0,0,canvas.width,canvas.height).data;for(var i=0;i<p.length;i+=16){if(p[i+3]>0&&Math.min(p[i],p[i+1],p[i+2])<230){ink++;}if(p[i+3]>0&&Math.max(p[i],p[i+1],p[i+2])-Math.min(p[i],p[i+1],p[i+2])>20){pixels++;}}});}catch(err){pixelError=String(err);}
var result={errors:window.__jsliveErrors,canvas:canvases.length,pixels:pixels,ink:ink,pixelError:pixelError,updates:calls,legendRetained:retained,warningRetained:warningRetained,progressTitle:progressTitle,
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
            '--screenshot='+path.join(directory,name+'.png'),'--virtual-time-budget=6000','--dump-dom',pathToFileURL(test).href],
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
        assert.equal(metrics.scrollUnchanged,true,name+': wheel moved the outer document');
        assert.ok(mode==='tile'?metrics.headerInset>=58:metrics.headerInset<58,name+': wrong native tile header inset');
        console.log(name+': native canvas, fonts, refresh, legend, transient error and wheel smoke passed.');
        ++count;
    }
}
console.log('JSLive isolated Chromium smoke: '+count+' real documents passed.');
