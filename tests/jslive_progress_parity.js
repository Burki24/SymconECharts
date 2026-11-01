'use strict';

const assert=require('node:assert/strict'),fs=require('node:fs'),vm=require('node:vm'),path=require('node:path'),cp=require('node:child_process');
const root=path.join(__dirname,'..'),read=file=>fs.readFileSync(path.join(root,file),'utf8');
const states=JSON.parse(cp.execFileSync(process.env.PHP_BINARY||'php',['tests/jslive_models.php','--emit'],{cwd:root,encoding:'utf8',maxBuffer:8388608}));
const window={SYMC_VISUALIZATION:{options:{locale:'de-DE'}},btoa:value=>Buffer.from(value,'binary').toString('base64'),atob:value=>Buffer.from(value,'base64').toString('binary')};
const context=vm.createContext({window,navigator:{userAgent:'Node.js'},console,setTimeout,clearTimeout});
for(const file of ['libs/echarts/6.1.0/echarts.gauge.min.js','libs/jslive/format.js','EChartsProgress/ProgressRenderer.js'])vm.runInContext(read(file),context);
const build=(state,w=620,h=500)=>window.SYMC_JSLIVE_OPTION(state,w,h),byID=(option,id)=>option.graphic.find(g=>g.id===id);
const near=(a,b,why,tolerance=0.001)=>assert.ok(Math.abs(a-b)<=tolerance,why+': '+a+' vs '+b);
const choice=process.argv[2];let images=0,layouts=0;
if(choice!=='--layout'){
    for(const index of [37,39]){
        const state=structuredClone(states[index].chart),before=JSON.stringify(state),option=build(state),body=byID(option,'progress-body');
        const image=body.children.find(g=>g.id==='image-progress');
        assert.ok(image,'F05: source text and SVG are native images, not recolored substitute glyphs.');
        if(index===39)assert.equal(image.style.image,state.settings.shape_svg.dataUri,'F05: retain original SVG paints, including black/gradients/alpha.');
        else{
            const svg=Buffer.from(image.style.image.split(',')[1],'base64').toString();
            assert.match(svg,/<text x="35" y="10" text-anchor="middle" dominant-baseline="central" font-family="arial">LOADING<\/text>/,'F05: preserve the trusted preset glyph coordinates and baseline.');
            assert.doesNotMatch(svg,/fill=|font-size=/,'The original preset uses SVG default black and default 16px, not the configured fill color.');
        }
        const background=body.children.find(g=>g.id==='image-background');assert.ok(background);
        const backgroundSVG=Buffer.from(background.style.image.split(',')[1],'base64').toString();
        assert.match(backgroundSVG,/feMorphology[^>]+operator="dilate"[^>]+radius="1"/,'Source SVG background extrusion is retained.');
        assert.equal(JSON.stringify(state),before,'Rendering must not rewrite settings, sources, values or resources.');
        for(const direction of ['ltr','rtl','ttb','btt'])for(const ratio of [0,0.25,1]){
            state.settings.fill_dir=direction;state.data.ratio=ratio;
            const current=byID(build(state),'progress-body').children.find(g=>g.id==='image-progress');
            assert.equal(current.style.image,image.style.image,'Direction and value must only clip the original image.');
            const r=current.clipPath.shape,box=index===39?[300,150]:[70,20];
            near(r.x,direction==='rtl'?box[0]*(1-ratio):0,'Source image clip x');
            near(r.y,direction==='btt'?box[1]*(1-ratio):0,'Source image clip y');
            near(r.width,box[0]*(direction==='ltr'||direction==='rtl'?ratio:1),'Source image clip width');
            near(r.height,box[1]*(direction==='ttb'||direction==='btt'?ratio:1),'Source image clip height');++images;
        }
    }
    // Shape resource takes precedence over a preset and custom path, without color overrides.
    const priority=structuredClone(states[39].chart);priority.settings.shape_path='M0 0L20 20';priority.settings.shape_preset='text';
    assert.equal(byID(build(priority),'progress-body').children.find(g=>g.id==='image-progress').style.image,priority.settings.shape_svg.dataUri);
}
if(choice!=='--images'){
    // Frozen measurements come from the unchanged ldBar engine + real template/CSS
    // in Chromium. Neither its layout formulas nor implementation are invoked here.
    const fixture=JSON.parse(read('tests/fixtures/jslive-progress-geometry.json'));
    for(const record of fixture.records){
        const state=structuredClone(states[record.case-1].chart);Object.assign(state.settings,record.variant||{});
        // Renderer dimensions exclude the shared page's 8px padding. Compare
        // complete documents, including that margin exactly once on both sides.
        const before=JSON.stringify(state),option=build(state,record.width-16,record.height-16),body=byID(option,'progress-body');
        const [x,y,w,h]=record.viewBox,scale=Math.min(record.root.width/w,record.root.height/h);
        near(body.scaleX,scale,'F06 source fit scale, case '+record.case);near(body.scaleY,scale,'Uniform fit');
        near(body.x+8,record.root.x+(record.root.width-w*scale)/2-x*scale,'F06 preserveAspectRatio x');
        near(body.y+8,record.root.y+(record.root.height-h*scale)/2-y*scale,'F06 preserveAspectRatio y');
        const label=byID(option,'progress-label');
        if(state.settings.style_fontDisplay){
            near(label.style.x+8,record.label.x+record.label.width/2,'F06 label center relative to full source widget');
            if(state.settings.style_fontPosition==='center')near(label.style.y+8,record.root.y+record.root.height/2,'Centered label');
            else near(label.style.y+8,record.label.y,'F06 label starts below the full source SVG, not below the fitted path');
            assert.equal(label.style.verticalAlign,state.settings.style_fontPosition==='center'?'middle':'top');
            assert.equal(label.style.fontFamily,state.settings.style_fontFamily||'Times New Roman','Blank source family inherits the browser serif font.');
        }else assert.equal(label,undefined);
        for(const child of body.children)if(child.style.stroke)assert.equal(child.style.lineCap,'butt','F06: template spelling stroke-lincap has no SVG effect; keep effective butt caps.');
        assert.equal(JSON.stringify(state),before,'Frozen source settings remain unchanged.');++layouts;
    }
    // Explicit title and independent font scale are native extensions; retain them
    // without clipping the label or changing imported shape/paint/value identities.
    const state=structuredClone(states[22].chart);Object.assign(state.settings,{title_display:true,title_text:'Test title',__fontScale:2});
    const option=build(state),label=byID(option,'progress-label');
    assert.equal(byID(option,'chart-title').style.text,'Test title');assert.equal(label.style.fontSize,40);
    assert.ok(byID(option,'progress-body').y>byID(build(states[22].chart),'progress-body').y);
    assert.ok(label.style.y+label.style.fontSize<=500,'Scaled bottom text remains inside the widget.');
}
console.log('JSLive Progress F05/F06: '+images+' original-image clips and '+layouts+' independent source layouts passed.');
