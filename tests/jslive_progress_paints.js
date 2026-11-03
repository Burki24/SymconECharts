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

// Deterministic frame clock: unchanged values must still move, polling/resize
// must not restart the cycle, and a second render must not create another loop.
let clock=0,next=0;const frames=new Map();
window.performance={now:()=>clock};
window.requestAnimationFrame=callback=>{frames.set(++next,callback);return next;};
window.cancelAnimationFrame=id=>frames.delete(id);
window.document={hidden:false,createElement:()=>{
    const canvas={width:0,height:0};
    canvas.getContext=()=>({setTransform(){},clearRect(){},fillRect(){},
        createLinearGradient(...coordinates){canvas.coordinates=coordinates;return {addColorStop(){}};}});
    return canvas;
}};
function tick(time){clock=time;const pending=Array.from(frames.values());frames.clear();pending.forEach(callback=>callback(time));}
for(const [name,vector] of [['stroke-gradient',[100,0]],['fill-gradient',[0,100]],['reverse',[50,50]]]){
    clock=0;const state=structuredClone(states[name].chart),before=JSON.stringify(state),channel=state.settings.Type==='stroke'?'stroke':'fill';
    let disposed=false,latest;
    const chart={isDisposed:()=>disposed,setOption:option=>{latest=option.graphic[0].children[0].style[channel];}};
    const build=(w=620,h=500)=>window.SYMC_JSLIVE_OPTION(state,w,h,chart);
    latest=build().graphic[0].children.find(g=>g.id==='progress').style[channel];
    assert.equal(typeof latest.image,'object','A live chart needs a native animated canvas.');
    window.SYMC_JSLIVE_AFTER_RENDER(chart);window.SYMC_JSLIVE_AFTER_RENDER(chart);
    assert.equal(frames.size,1,'Only one frame loop per chart.');
    const duration=state.paints[channel].duration*1000;
    for(const fraction of [0,.25,.5,.75,.98,1,1.25]){
        tick(duration*fraction);
        const phase=fraction%1;
        for(let axis=0;axis<2;axis++){
            assert.ok(Math.abs(latest.image.coordinates[axis]+vector[axis]*(1-phase))<1e-8,'Original SMIL from=-vector, to=0, linear phase.');
            assert.ok(Math.abs(latest.image.coordinates[axis+2]-vector[axis]*(3+phase))<1e-8);
        }
    }
    const coordinates=latest.image.coordinates.slice();build(310,240);window.SYMC_JSLIVE_AFTER_RENDER(chart);
    assert.deepEqual(latest.image.coordinates,coordinates,'Resize/polling retain phase.');
    assert.equal(frames.size,1);assert.equal(JSON.stringify(state),before);
    window.document.hidden=true;const old=latest;tick(duration*1.5);assert.equal(latest,old,'Hidden documents do no paint work.');
    window.document.hidden=false;disposed=true;tick(duration*1.75);assert.equal(frames.size,0,'Disposal ends the loop.');
}
const disabled=structuredClone(states['stroke-gradient'].chart);disabled.settings.__animate=false;
const chart={isDisposed:()=>false,setOption(){throw new Error('Disabled animations must not draw frames.');}};
window.SYMC_JSLIVE_OPTION(disabled,620,500,chart);window.SYMC_JSLIVE_AFTER_RENDER(chart);assert.equal(frames.size,0);
const zero=structuredClone(disabled);zero.settings.__animate=true;zero.paints.stroke.duration=0;
window.SYMC_JSLIVE_OPTION(zero,620,500,chart);window.SYMC_JSLIVE_AFTER_RENDER(chart);assert.equal(frames.size,0);
const labelState=structuredClone(states['stroke-gradient'].chart);labelState.settings.style_fontDisplay=true;
labelState.data.displayValue=.3;labelState.data.effective={prefix:'native ',suffix:' %'};
const label=()=>window.SYMC_JSLIVE_OPTION(labelState,620,500).graphic.find(g=>g.id==='progress-label').style.text;
assert.equal(label(),'0.3','Native source affixes do not change the original visible number.');
labelState.data.label={prefix:'legacy ',suffix:' W'};assert.equal(label(),'legacy 0.3 W');
labelState.data.native.parameters.SUFFIX=' %';
assert.match(window.SYMC_JSLIVE_OPTION(labelState,620,500).tooltip.formatter(),/%/,'Tooltip retains the full native unit independently of label affixes.');
console.log('JSLive temporal paints: 21 phase checks, resize, polling, visibility, disposal, disabled/zero duration and original labels passed.');
