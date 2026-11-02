'use strict';

// Optional independent browser reference. The original engine is read only from
// the sibling checkout and is never copied into the library or ordinary CI.
const assert=require('node:assert/strict'),fs=require('node:fs'),path=require('node:path'),cp=require('node:child_process');
const {pathToFileURL}=require('node:url'),crypto=require('node:crypto'),os=require('node:os');
const [referenceRoot,browser,...args]=process.argv.slice(2),root=path.join(__dirname,'..');
if(!referenceRoot||!browser)throw new Error('Usage: node tests/jslive_progress_reference.js <JSLive checkout> <Chromium executable> [--snapshot <JSON>]');
const read=file=>fs.readFileSync(path.join(referenceRoot,'SymconJSLive',file),'utf8');
const engine=read('js/loading-Bar/0.1.1-jslive.1/loading-bar.js'),template=read('templates/Progressbar.html'),css=read('js/loading-Bar/loading-bar.css');
const sourceHashes=Object.fromEntries(Object.entries({engine,template,css}).map(([key,bytes])=>[key,crypto.createHash('sha256').update(bytes).digest('hex')]));
assert.deepEqual(sourceHashes,JSON.parse(fs.readFileSync(path.join(__dirname,'fixtures/jslive-progress-geometry.json'),'utf8')).sourceHashes,
    'Review changed original template/engine/CSS before regenerating source geometry.');
const states=JSON.parse(cp.execFileSync(process.env.PHP_BINARY||'php',['tests/jslive_models.php','--emit'],{cwd:root,encoding:'utf8',maxBuffer:8388608}));
const inputs=[];
for(let i=22;i<40;i++)for(const [width,height] of [[620,500],[1200,700],[300,242]])inputs.push({case:i+1,width,height,chart:states[i].chart});
// Additional source layout seams, including the 100x100 degenerate-path fallback.
for(const settings of [{style_fontPosition:'center'},{style_fontDisplay:false},{shape_path:'M0 0L100 0'},
    {shape_path:'M0 0L0 100'},{stroke_width:2,stroke_trailWidth:1,fill_backgroundExtrude:12}]){
    const chart=structuredClone(states[22].chart);Object.assign(chart.settings,settings);
    inputs.push({case:23,width:620,height:500,variant:settings,chart});
}
for(const [attributes,viewBox] of [['','0 0 100 100'],['','0 0 400 100'],['width="80"','0 0 120 60'],['width="2in" height="25.4mm"','0 0 120 60']]){
    const chart=structuredClone(states[39].chart),parts=viewBox.split(' ').map(Number);
    const shape_svg={dataUri:'data:image/svg+xml;base64,'+Buffer.from('<svg xmlns="http://www.w3.org/2000/svg" '+attributes+' viewBox="'+viewBox+'"><rect width="100" height="50"/></svg>').toString('base64'),width:parts[2],height:parts[3],viewBox};
    chart.settings.shape_svg=shape_svg;inputs.push({case:40,width:620,height:500,variant:{shape_svg},chart});
}
const layoutCount=inputs.length;
// Separate frozen fill oracles: serialize the actual original background mask,
// filter and frame, with the foreground removed. No target formulas are used.
for(const index of [23,25,27,29,31,33,35])for(const [width,height] of [[316,258],[636,516]]){
    inputs.push({case:index+1,width,height,chart:states[index].chart});
}
for(const settings of [{fill_backgroundExtrude:0},{fill_backgroundExtrude:3},{fill_backgroundExtrude:-1},{fill_backgroundExtrude:12},
    {shape_path:'M0 0L100 0'},{shape_path:'M0 0L0 100'},{shape_path:'M10 10'},
    {shape_path:'M5 5L50 90L95 5Z'},{shape_path:'M0 50C0 0 100 0 100 50Z'},
    {shape_path:'M0 0H40V40H0ZM60 60H100V100H60Z'}]){
    const chart=structuredClone(states[33].chart);Object.assign(chart.settings,settings);
    inputs.push({case:34,width:316,height:258,variant:settings,chart});
}
const functions=template.slice(template.indexOf('    function Load(){'),template.indexOf('    function connect()'))
    .replace('"/hook/JSLive/getSVG?Instance={INSTANCE}&pw={PASSWORD}"','configuration.__safeSVG')
    .replace("document.querySelectorAll('.ldBar-label')","item.querySelectorAll('.ldBar-label')")
    .replace('alert(e);','window.errors.push(String(e));');
const script=`window.errors=[];window.alert=function(e){window.errors.push(String(e));};
addEventListener('error',function(e){window.errors.push(String(e.message));});
setTimeout(function(){if(!document.getElementById('progress-reference')){var out=document.createElement('pre');out.id='progress-reference';out.textContent=JSON.stringify({errors:window.errors,records:[]});document.body.appendChild(out);}},4000);
var configuration,value,item,bar,Bar,activeWidth,activeHeight;
function Get_WindowWidth(){return activeWidth;}function Get_WindowHeight(){return activeHeight;}
function JSLiveObserveSize(){}function RGBAToHexA(r,g,b,a){return 'rgba('+[r,g,b,a].join(',')+')';}
${functions}
var inputs=${JSON.stringify(inputs)},roots=[],layoutCount=${layoutCount};
(async function(){
var fonts=new Map();inputs.forEach(function(input){input.chart.resources.forEach(function(r){if(r.kind==='font')fonts.set(r.family,r.data);});});
await Promise.all(Array.from(fonts,function(pair){var face=new FontFace(pair[0],'url(data:font/woff2;base64,'+pair[1]+')');document.fonts.add(face);return face.load();}));
inputs.forEach(function(input,index){
configuration=JSON.parse(JSON.stringify(input.chart.settings));value=input.chart.data.displayValue;
configuration.prefix=input.chart.data.effective.prefix||'';configuration.suffix=input.chart.data.effective.suffix||'';
configuration.stroke_color_rgb={R:configuration.stroke_color>>16&255,G:configuration.stroke_color>>8&255,B:configuration.stroke_color&255};
configuration.fill_color_rgb={R:configuration.fill_color>>16&255,G:configuration.fill_color>>8&255,B:configuration.fill_color&255};
['style_fontColor','stroke_trailColor','fill_backgroundColor'].forEach(function(k){configuration[k]='#'+configuration[k].toString(16).padStart(6,'0');});
configuration.__safeSVG=configuration.shape_svg&&configuration.shape_svg.dataUri;configuration.shape_svg=!!configuration.shape_svg;
configuration.data_animationTransitionIn=false;configuration.data_animationDuration=0;configuration.reverse=false;
// The source template itself resolves image-based text to its supported fill mode.
activeWidth=input.width;activeHeight=input.height;bar=undefined;
var container=document.createElement('div');container.id='Bar';container.className='myBar';container.style.cssText='position:absolute;left:8px;top:8px';document.body.appendChild(container);
// Recreate the template's inert custom-dash node; masks do not affect measured bounds.
var mask=document.createElementNS('http://www.w3.org/2000/svg','svg');mask.setAttribute('width','0');mask.setAttribute('height','0');mask.innerHTML='<mask id="mask"><path id="dash_path"/></mask>';document.body.appendChild(mask);
Load();container.id='reference-'+index;
// Each real template has its own document. Scope its pseudo-label rules to
// this root so added reference cases cannot change an earlier label's metrics.
var labelStyle=document.head.querySelectorAll('style');labelStyle=labelStyle[labelStyle.length-1];
labelStyle.textContent=labelStyle.textContent.replace(/\\.ldBar-label/g,'#reference-'+index+' .ldBar-label');
container.classList.remove('myBar');mask.remove();roots.push(container);
});
setTimeout(function(){
var fills=[];
var records=inputs.map(function(input,index){var el=roots[index],svg=el.querySelector('svg'),label=el.querySelector('.ldBar-label'),group=svg.querySelectorAll(':scope > g')[1];
function rect(r){return {x:r.x,y:r.y,width:r.width,height:r.height};}
if(index>=layoutCount){
var filter=svg.querySelector('defs > filter'),mask=svg.querySelector('defs > g > mask'),frame=svg.querySelector('rect.frame'),bounds=group.getBBox();
var copy=document.createElementNS('http://www.w3.org/2000/svg','svg'),defs=document.createElementNS('http://www.w3.org/2000/svg','defs');
copy.setAttribute('width',el.clientWidth);copy.setAttribute('height',el.clientHeight);copy.setAttribute('viewBox',svg.getAttribute('viewBox'));copy.setAttribute('preserveAspectRatio',svg.getAttribute('preserveAspectRatio'));
defs.appendChild(filter.cloneNode(true));defs.appendChild(mask.cloneNode(true));copy.appendChild(defs);copy.appendChild(frame.cloneNode(true));
var xml=new XMLSerializer().serializeToString(copy).replace(/ldBar-[a-f0-9]+-/g,'source-');
fills.push({case:input.case,width:input.width,height:input.height,variant:input.variant||null,root:rect(el.getBoundingClientRect()),bounds:rect(bounds),
    filter:{x:filter.getAttribute('x'),y:filter.getAttribute('y'),width:filter.getAttribute('width'),height:filter.getAttribute('height'),operator:filter.querySelector('feMorphology').getAttribute('operator'),radius:filter.querySelector('feMorphology').getAttribute('radius')},
    maskStroke:getComputedStyle(mask.querySelector('path')).strokeWidth,backgroundSVG:xml});
}
return {case:input.case,width:input.width,height:input.height,variant:input.variant||null,root:rect(el.getBoundingClientRect()),viewBox:svg.getAttribute('viewBox').split(' ').map(Number),
    bounds:rect(group.getBBox()),label:rect(label.getBoundingClientRect()),labelFont:getComputedStyle(label).fontFamily,
    lineCap:group.querySelector('path')?getComputedStyle(group.querySelector('path')).strokeLinecap:null,
    image:group.querySelector('image')?{width:+group.querySelector('image').getAttribute('width'),height:+group.querySelector('image').getAttribute('height')}:null};});
var out=document.createElement('pre');out.id='progress-reference';out.textContent=JSON.stringify({errors:window.errors,records:records.slice(0,layoutCount),fills:fills});document.body.appendChild(out);
},1000);
})().catch(function(e){window.errors.push(String(e));});`;
const directory=fs.mkdtempSync(path.join(os.tmpdir(),'SymconECharts-progress-reference-')),html=path.join(directory,'reference.html');
fs.writeFileSync(html,'<!doctype html><html><head><meta charset="utf-8"><style>'+css+'</style></head><body style="text-align:center"><script>'+engine+'</script><script>'+script.replace(/<\/script/gi,'<\\/script')+'</script></body></html>');
const result=cp.spawnSync(browser,['--headless=new','--disable-gpu','--no-first-run','--no-default-browser-check','--host-resolver-rules=MAP * ~NOTFOUND',
    '--user-data-dir='+path.join(directory,'profile'),'--virtual-time-budget=5000','--dump-dom',pathToFileURL(html).href],{encoding:'utf8',windowsHide:true,maxBuffer:16777216,timeout:45000});
assert.equal(result.status,0,(result.error||result.stderr||'').toString().slice(-800));
fs.writeFileSync(path.join(directory,'browser.log'),result.stderr||'');
fs.writeFileSync(path.join(directory,'browser.html'),result.stdout||'');
const match=/<pre id="progress-reference">(.*?)<\/pre>/s.exec(result.stdout||'');
assert.ok(match,'Reference did not finish; see '+directory);
const output=JSON.parse(match[1].replace(/&quot;/g,'"').replace(/&gt;/g,'>').replace(/&lt;/g,'<').replace(/&amp;/g,'&'));
assert.deepEqual(output.errors,[]);assert.equal(output.records.length,63);
assert.equal(output.fills.length,24);
const snapshot={source:'loading-bar 0.1.1 / JSLive 0.1.1-jslive.1; original Progressbar template and CSS',sourceHashes,records:output.records,fills:output.fills};
const snapshotArg=args.indexOf('--snapshot');
if(snapshotArg>=0)fs.writeFileSync(args[snapshotArg+1],JSON.stringify(snapshot,null,4)+'\n');
else assert.deepEqual(snapshot,JSON.parse(fs.readFileSync(path.join(__dirname,'fixtures/jslive-progress-geometry.json'),'utf8')),'Independent source measurements changed.');
console.log('JSLive Progress reference: '+output.records.length+' original layouts and '+output.fills.length+' original fill masks measured.');
