'use strict';

// Optional independent raster oracle: execute the unchanged source engine only
// from the explicitly supplied read-only checkout, never in the target library.
const fs=require('node:fs'),path=require('node:path'),os=require('node:os'),assert=require('node:assert/strict'),cp=require('node:child_process'),crypto=require('node:crypto');
const {pathToFileURL}=require('node:url'),root=path.join(__dirname,'..'),read=file=>fs.readFileSync(path.join(root,file),'utf8');
const [reference,browser,dpr='1']=process.argv.slice(2);
if(!reference||!browser||!path.isAbsolute(browser))throw new Error('Usage: node tests/jslive_progress_paints_browser.js <JSLive checkout> <absolute Chromium executable>');
assert.ok(['1','2','3'].includes(dpr));
const engine=fs.readFileSync(path.join(reference,'SymconJSLive/js/loading-Bar/0.1.1-jslive.1/loading-bar.js'),'utf8');
assert.equal(crypto.createHash('sha256').update(engine).digest('hex'),JSON.parse(read('tests/fixtures/jslive-progress-geometry.json')).sourceHashes.engine);
const states=JSON.parse(cp.execFileSync(process.env.PHP_BINARY||'php',['tests/jslive_progress_paints.php','--emit'],{cwd:root,encoding:'utf8',maxBuffer:8388608}));
const directory=fs.mkdtempSync(path.join(os.tmpdir(),'SymconECharts-progress-paints-')),html=path.join(directory,'paints.html');
const probe=`<script>
// The source engine replaces Element.prototype.append; use appendChild for the probe.
var results=[],errors=[],progress=document.createElement('pre');progress.id='paint-progress';document.body.appendChild(progress);addEventListener('error',function(e){errors.push(String(e.message));progress.textContent=JSON.stringify({results:results,errors:errors});});
(async function(){
var states=${JSON.stringify(states)},pending=[],inputs=[];
echarts.setPlatformAPI({loadImage:function(src,onload,onerror){var image=new Image();pending.push(new Promise(function(resolve,reject){image.onload=function(){onload.call(image);resolve();};image.onerror=function(e){if(onerror)onerror.call(image,e);reject(new Error('Paint image failed'));};image.src=src;}));return image;}});
function image(src){return new Promise(function(resolve,reject){var im=new Image(),timer=setTimeout(function(){reject(new Error('Reference image timed out at '+document.body.dataset.stage));},1000);im.onload=function(){clearTimeout(timer);resolve(im);};im.onerror=function(){clearTimeout(timer);reject(new Error('Reference image failed'));};im.src=src;});}
for(var name of Object.keys(states))for(var ratio of [.25,.65,1]){
var state=JSON.parse(JSON.stringify(states[name].chart)),c=state.settings,w=620,h=500;
document.body.dataset.stage=name+' '+ratio;
state.data.ratio=ratio;c.style_fontDisplay=false;c.__animate=false;state.background='#fff';
var before=JSON.stringify(state),container=document.createElement('div');container.style.cssText='position:absolute;left:8px;top:8px;width:596px;height:436px';document.body.appendChild(container);
var key=c.Type==='stroke'?'stroke':'fill',override=c['override_'+key],paint=override||('data:image/svg+xml;base64,'+c.fill_backgroundFile);
// Freeze only the source generator's cyclic paint motion. The target plan
// explicitly reports this static initial-frame approximation to the user.
paint=paint.replace(/gradient\\(([^,]+),[^,]+,/,'gradient($1,0,');
var options={preset:c.shape_preset,type:c.Type,value:ratio*100,min:0,max:100,precision:.01,duration:0,'transition-in':false,
    'stroke-width':c.stroke_width,'stroke-trail-width':c.stroke_trailWidth,'stroke-trail':'#'+c.stroke_trailColor.toString(16).padStart(6,'0'),
    'stroke-dir':c.stroke_dir,'fill-dir':c.fill_dir,'fill-background':'rgba('+[c.fill_backgroundColor>>16&255,c.fill_backgroundColor>>8&255,c.fill_backgroundColor&255,c.fill_backgroundColor_Alpha].join(',')+')',
    'fill-background-extrude':c.fill_backgroundExtrude};
options[key]=paint;if(c.shape_path)options.path=c.shape_path;
var source=new ldBar(container,options);source.set(ratio*100,false);
inputs.push({name:name,ratio:ratio,state:state,before:before,container:container});
document.body.dataset.prepared=String(inputs.length);
}
for(var input of inputs){
var name=input.name,ratio=input.ratio,state=input.state,c=state.settings,before=input.before,container=input.container,w=620,h=500;
document.body.dataset.stage=name+' source pattern';
document.body.dataset.phase='source';
for(var resource of Array.from(container.querySelectorAll('pattern image'))){var href=resource.getAttributeNS('http://www.w3.org/1999/xlink','href');if(href)await image(href);}
var svg=container.querySelector('svg'),rect=svg.getBoundingClientRect(),copy=svg.cloneNode(true);copy.setAttribute('width',rect.width);copy.setAttribute('height',rect.height);
var original=await image('data:image/svg+xml;base64,'+btoa(new XMLSerializer().serializeToString(copy)));
document.body.dataset.stage=name+' native paint';
document.body.dataset.phase='native';
var canvas=document.createElement('canvas'),expected=document.createElement('canvas');expected.width=w;expected.height=h;
var native=echarts.init(canvas,null,{renderer:'canvas',width:w,height:h,devicePixelRatio:1});
try{
var animated=ratio===.65&&(name==='fill-gradient'||name==='stroke-gradient');
pending=[];
if(animated){
var start=JSON.parse(JSON.stringify(state));start.data.ratio=.25;
native.setOption(SYMC_JSLIVE_OPTION(start,w,h),true);await Promise.all(pending);
var update=JSON.parse(JSON.stringify(state));update.settings.__animate=true;
native.setOption(SYMC_JSLIVE_OPTION(update,w,h),false);await new Promise(function(resolve){setTimeout(resolve,600);});
}else{native.setOption(SYMC_JSLIVE_OPTION(state,w,h),true);await Promise.all(pending);}
native.getZr().flush();
var ctx=expected.getContext('2d');ctx.fillStyle='#fff';ctx.fillRect(0,0,w,h);ctx.drawImage(original,rect.x-8,rect.y-8,rect.width,rect.height);
var a=canvas.getContext('2d').getImageData(0,0,w,h).data,b=ctx.getImageData(0,0,w,h).data,bad=0,ink=0,edges=0;
for(var y=2;y<h-2;y++)for(var x=2;x<w-2;x++){var i=(y*w+x)*4,delta=Math.max(Math.abs(a[i]-b[i]),Math.abs(a[i+1]-b[i+1]),Math.abs(a[i+2]-b[i+2]));
if(a[i]<240||a[i+1]<240||a[i+2]<240)ink++;
if(delta>12){var near=false,edge=false;for(var dy=-1;dy<=1;dy++)for(var dx=-1;dx<=1;dx++){var j=((y+dy)*w+x+dx)*4;
if(Math.max(Math.abs(a[i]-b[j]),Math.abs(a[i+1]-b[j+1]),Math.abs(a[i+2]-b[j+2]))<=12)near=true;
if(Math.max(Math.abs(b[i]-b[j]),Math.abs(b[i+1]-b[j+1]),Math.abs(b[i+2]-b[j+2]))>32)edge=true;}
if(!near&&!edge)bad++;else if(edge)edges++;}}
results.push({name:name,ratio:ratio,animatedUpdate:animated,pixelsBeyondTolerance:bad,sourceEdgePixels:edges,ink:ink,unchanged:before===JSON.stringify(state)});
progress.textContent=JSON.stringify({results:results,errors:errors});
if(ratio===.65){var row=document.createElement('div');row.style.cssText='display:flex';for(var pair of [['JSLive '+name,expected],['ECharts '+name,canvas]]){var cell=document.createElement('div'),label=document.createElement('p');label.textContent=pair[0];cell.appendChild(label);cell.appendChild(pair[1]);row.appendChild(cell);}document.body.appendChild(row);}
}finally{native.dispose();container.remove();}
}
})().catch(function(e){errors.push(String(e));}).finally(function(){var out=document.createElement('pre');out.id='paint-results';out.hidden=true;out.textContent=JSON.stringify({results:results,errors:errors});document.body.appendChild(out);});
</script>`;
fs.writeFileSync(html,'<!doctype html><html><meta charset="utf-8"><body><script>'+engine+'</script><script>'+read('libs/echarts/6.1.0/echarts.gauge.min.js')+'</script><script>'+read('libs/jslive/format.js')+'</script><script>'+read('EChartsProgress/ProgressRenderer.js')+'</script>'+probe+'</body></html>');
const output=cp.spawnSync(browser,['--headless=new','--disable-gpu','--no-first-run','--no-default-browser-check','--host-resolver-rules=MAP * ~NOTFOUND','--force-device-scale-factor='+dpr,'--user-data-dir='+path.join(directory,'profile'),'--window-size=1320,1000','--virtual-time-budget=15000','--dump-dom',pathToFileURL(html).href],{encoding:'utf8',windowsHide:true,maxBuffer:8388608,timeout:45000});
assert.equal(output.status,0,(output.error||output.stderr||'').toString().slice(-800));
fs.writeFileSync(path.join(directory,'browser.log'),output.stderr||'');
fs.writeFileSync(path.join(directory,'dump.html'),output.stdout||'');
const match=/<pre id="paint-results" hidden="">(.*?)<\/pre>/s.exec(output.stdout||'');assert.ok(match,'Paint probe did not finish: '+directory);
const result=JSON.parse(match[1].replace(/&quot;/g,'"').replace(/&gt;/g,'>').replace(/&lt;/g,'<').replace(/&amp;/g,'&'));
fs.writeFileSync(path.join(directory,'metrics.json'),JSON.stringify(result,null,2)+'\n');
assert.deepEqual(result.errors,[]);assert.equal(result.results.length,Object.keys(states).length*3);
assert.equal(result.results.filter(r=>r.animatedUpdate).length,2,'Both stroke and fill must retain native value transitions with image paints.');
for(const r of result.results){assert.equal(r.unchanged,true);assert.ok(r.ink>0);assert.ok(r.pixelsBeyondTolerance<=2,'Paint differs from the source oracle: '+JSON.stringify(r)+'; '+directory);}
console.log('JSLive paint raster: '+result.results.length+' unchanged source/native comparisons passed. Artifacts: '+directory);
