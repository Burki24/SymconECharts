'use strict';

const assert=require('node:assert/strict'),fs=require('node:fs'),vm=require('node:vm'),path=require('node:path'),cp=require('node:child_process');
const root=path.join(__dirname,'..'),read=file=>fs.readFileSync(path.join(root,file),'utf8');
const states=JSON.parse(cp.execFileSync(process.env.PHP_BINARY||'php',['tests/jslive_progress_paints.php','--emit'],{cwd:root,encoding:'utf8',maxBuffer:8388608}));
const window={SYMC_VISUALIZATION:{options:{locale:'de-DE'}},btoa:value=>Buffer.from(value,'binary').toString('base64'),atob:value=>Buffer.from(value,'base64').toString('binary')};
const context=vm.createContext({window,navigator:{userAgent:'Node.js'},console,setTimeout,clearTimeout});
for(const file of ['libs/echarts/6.1.0/echarts.gauge.min.js','libs/jslive/format.js','EChartsProgress/ProgressRenderer.js'])vm.runInContext(read(file),context);
let count=0;
for(const [name,snapshot] of Object.entries(states))for(const ratio of [0,0.25,0.65,1]){
    const state=structuredClone(snapshot.chart);state.data.ratio=ratio;
    const before=JSON.stringify(state),option=window.SYMC_JSLIVE_OPTION(state,620,500),body=option.graphic.find(g=>g.id==='progress-body');
    const foreground=body.children.find(g=>g.id==='progress'),stroke=state.settings.Type==='stroke',paint=state.paints[stroke?'stroke':'fill'];
    assert.ok(foreground,'A real progress path is retained: '+name);
    assert.equal(foreground.style[stroke?'stroke':'fill'].image,paint.dataUri);
    assert.equal(foreground.style[stroke?'stroke':'fill'].repeat,'repeat','Paint is anchored to original image coordinates.');
    assert.ok(paint.dataUri.startsWith('data:image/svg+xml;base64,'));
    assert.ok(!Buffer.from(paint.dataUri.split(',')[1],'base64').toString().includes('<animate'),'User SVG and static gradient paints contain no executable animation markup.');
    if(stroke){
        if(state.settings.stroke_dir==='reverse')assert.ok(foreground.style.lineDash,'Reverse clipping remains a path operation.');
        else assert.equal(foreground.style.strokePercent,ratio);
    }else assert.equal(foreground.clipPath.type,'rect');
    assert.equal(JSON.stringify(state),before,'Neither source settings, resources nor original values are modified.');++count;
}
const a=window.SYMC_JSLIVE_OPTION(states['fill-gradient'].chart,620,500).graphic[0].children.find(g=>g.id==='progress');
const b=window.SYMC_JSLIVE_OPTION(states['fill-gradient'].chart,310,240).graphic[0].children.find(g=>g.id==='progress');
assert.equal(a.style.fill.image,b.style.fill.image,'Responsive sizing must not rewrite the SVG paint or change its coordinate range.');
window.DOMMatrix=function(){};
for(const dpr of [1,2,3])for(const [w,h] of [[620,500],[310,240],[1200,700]]){
    window.devicePixelRatio=dpr;
    const state=states['svg-pattern'].chart,option=window.SYMC_JSLIVE_OPTION(state,w,h);
    const paint=option.graphic[0].children.find(g=>g.id==='progress').style.fill;
    const tag=Buffer.from(paint.image.split(',')[1],'base64').toString().match(/<svg\b[^>]*>/)[0];
    const width=Number(tag.match(/\swidth="([^"]*)"/)[1]),height=Number(tag.match(/\sheight="([^"]*)"/)[1]);
    assert.ok(width<=4096&&height<=4096,'Derived raster dimensions retain their protection limit.');
    assert.ok(Math.abs(width*(paint.scaleX||1)-100)<1e-9&&Math.abs(height*(paint.scaleY||1)-100)<1e-9,'DPI and responsiveness cannot move the original pattern period.');
}
const own=structuredClone(states['svg-file'].chart),original=own.paints.fill.dataUri;
own.settings.shape_svg={dataUri:original,width:100,height:100,viewBox:'0 0 100 100'};
const graphic=window.SYMC_JSLIVE_OPTION(own,620,500).graphic[0].children;
assert.equal(graphic.find(g=>g.id==='image-progress').style.image,original,'Own SVG forms keep their paints instead of receiving a fill override.');
console.log('JSLive validated paint renderer: '+count+' clipping/scale variants, stable colors and immutable settings passed.');
