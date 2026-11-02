'use strict';

// Optional real Chromium raster check against SVGs emitted by the unchanged
// source engine. Only anonymous local models and the shipped target runtime run.
const fs=require('node:fs'),path=require('node:path'),os=require('node:os'),assert=require('node:assert/strict'),cp=require('node:child_process');
const {pathToFileURL}=require('node:url'),root=path.join(__dirname,'..'),read=file=>fs.readFileSync(path.join(root,file),'utf8');
const [browser,choice]=process.argv.slice(2);
if(!browser||!path.isAbsolute(browser))throw new Error('Usage: node tests/jslive_progress_fills_browser.js <absolute Chromium executable> [--empty|--borders]');
const states=JSON.parse(cp.execFileSync(process.env.PHP_BINARY||'php',['tests/jslive_models.php','--emit'],{cwd:root,encoding:'utf8',maxBuffer:8388608}));
const fixture=JSON.parse(read('tests/fixtures/jslive-progress-geometry.json'));
const inputs=fixture.fills.filter(r=>choice==='--empty'?!r.bounds.width||!r.bounds.height:choice==='--borders'?r.bounds.width&&r.bounds.height:true)
    .map(record=>({record,chart:states[record.case-1].chart}));
const directory=fs.mkdtempSync(path.join(os.tmpdir(),'SymconECharts-progress-fills-')),html=path.join(directory,'fills.html');
const probe=`<script>
var results=[],errors=[];addEventListener('error',function(e){errors.push(String(e.message));});
addEventListener('unhandledrejection',function(e){errors.push(String(e.reason));});
(async function(){
var inputs=${JSON.stringify(inputs)},pending=[];
echarts.setPlatformAPI({loadImage:function(src,onload,onerror){var image=new Image();pending.push(new Promise(function(resolve,reject){
image.onload=function(){onload.call(image);resolve();};image.onerror=function(e){if(onerror)onerror.call(image,e);reject(new Error('Native SVG image did not load'));};image.src=src;}));return image;}});
for(var input of inputs){
var r=input.record,state=JSON.parse(JSON.stringify(input.chart));Object.assign(state.settings,r.variant||{},
    {style_fontDisplay:false,__animate:false,fill_backgroundColor:0,fill_backgroundColor_Alpha:1});
state.data.ratio=0;state.background='#fff';var before=JSON.stringify(state),w=r.width-16,h=r.height-16;
var actual=document.createElement('canvas'),expected=document.createElement('canvas');expected.width=w;expected.height=h;
var native=echarts.init(actual,null,{renderer:'canvas',width:w,height:h,devicePixelRatio:1});
try{
var source=new DOMParser().parseFromString(r.backgroundSVG,'image/svg+xml');
// High contrast changes only the diagnostic color, preserving the source mask,
// filter, stroke and viewport. Light gray would hide border errors in RGB tolerances.
source.querySelector('rect.frame').setAttribute('fill','#000');
var original=await new Promise(function(resolve,reject){var image=new Image();image.onload=function(){resolve(image);};image.onerror=reject;
image.src='data:image/svg+xml;base64,'+btoa(new XMLSerializer().serializeToString(source.documentElement));});
var option=SYMC_JSLIVE_OPTION(state,w,h);option.animation=false;pending=[];native.setOption(option,true);await Promise.all(pending);native.getZr().flush();
var ref=expected.getContext('2d');ref.fillStyle='#fff';ref.fillRect(0,0,w,h);ref.drawImage(original,r.root.x-8,r.root.y-8,r.root.width,r.root.height);
var a=actual.getContext('2d').getImageData(0,0,w,h).data,b=ref.getImageData(0,0,w,h).data;
var am=new Uint8Array(w*h),bm=new Uint8Array(w*h),inkA=0,inkB=0,far=0;
for(var i=0;i<am.length;i++){am[i]=a[i*4]<128?1:0;bm[i]=b[i*4]<128?1:0;inkA+=am[i];inkB+=bm[i];}
function matches(mask,x,y){for(var dy=-1;dy<=1;dy++)for(var dx=-1;dx<=1;dx++){var u=x+dx,v=y+dy;if(u>=0&&v>=0&&u<w&&v<h&&mask[v*w+u])return true;}return false;}
for(var y=0;y<h;y++)for(var x=0;x<w;x++){var i=y*w+x;if(am[i]&&!matches(bm,x,y)||bm[i]&&!matches(am,x,y))far++;}
var item={case:r.case,width:r.width,height:r.height,variant:r.variant,expectedInk:inkB,actualInk:inkA,pixelsBeyondOnePixel:far,
    unchanged:JSON.stringify(state)===before};results.push(item);
if((r.case===30&&r.width===316)||(r.case===34&&!r.variant&&r.width===636)||r.variant&&r.variant.shape_path==='M5 5L50 90L95 5Z'){
var snapshot=document.createElement('canvas');snapshot.width=w;snapshot.height=h;snapshot.getContext('2d').drawImage(actual,0,0);
var row=document.createElement('div');row.style.cssText='display:flex;gap:8px;align-items:flex-start';
for(var pair of [['Original source mask',expected],['Native ECharts',snapshot]]){var cell=document.createElement('div'),label=document.createElement('div');label.textContent='Case '+r.case+' · '+pair[0];cell.append(label,pair[1]);row.append(cell);}document.body.append(row);
}
}finally{native.dispose();}
}
})().catch(function(e){errors.push(String(e));}).finally(function(){var out=document.createElement('pre');out.hidden=true;out.id='fill-results';out.textContent=JSON.stringify({results:results,errors:errors});document.body.append(out);});
</script>`;
fs.writeFileSync(html,'<!doctype html><html><head><meta charset="utf-8"></head><body><script>'+read('libs/echarts/6.1.0/echarts.gauge.min.js')+'</script><script>'+read('libs/jslive/format.js')+'</script><script>'+read('EChartsProgress/ProgressRenderer.js')+'</script>'+probe+'</body></html>');
const output=cp.spawnSync(browser,['--headless=new','--disable-gpu','--no-first-run','--no-default-browser-check','--host-resolver-rules=MAP * ~NOTFOUND',
    '--user-data-dir='+path.join(directory,'profile'),'--window-size=1320,1000','--virtual-time-budget=10000','--screenshot='+path.join(directory,'fills.png'),'--dump-dom',pathToFileURL(html).href],
    {encoding:'utf8',windowsHide:true,maxBuffer:8388608,timeout:45000});
fs.writeFileSync(path.join(directory,'browser.log'),output.stderr||'');
assert.equal(output.status,0,(output.error||output.stderr||'').toString().slice(-800));
const match=/<pre hidden="" id="fill-results">(.*?)<\/pre>/s.exec(output.stdout||'');assert.ok(match,'Fill probe did not finish: '+directory);
const result=JSON.parse(match[1].replace(/&quot;/g,'"').replace(/&gt;/g,'>').replace(/&lt;/g,'<').replace(/&amp;/g,'&'));
fs.writeFileSync(path.join(directory,'metrics.json'),JSON.stringify(result,null,2)+'\n');
assert.deepEqual(result.errors,[]);assert.equal(result.results.length,inputs.length);
for(const r of result.results){assert.equal(r.unchanged,true);assert.ok(r.pixelsBeyondOnePixel<=2,'F06 original fill geometry differs: '+JSON.stringify(r)+'; '+directory);}
console.log('JSLive F06 fill raster: '+result.results.length+' source/native comparisons passed (at most 1px edge antialiasing). Artifacts: '+directory);
