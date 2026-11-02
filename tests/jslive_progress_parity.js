'use strict';

const assert=require('node:assert/strict'),fs=require('node:fs'),vm=require('node:vm'),path=require('node:path'),cp=require('node:child_process');
const root=path.join(__dirname,'..'),read=file=>fs.readFileSync(path.join(root,file),'utf8');
const states=JSON.parse(cp.execFileSync(process.env.PHP_BINARY||'php',['tests/jslive_models.php','--emit'],{cwd:root,encoding:'utf8',maxBuffer:8388608}));
const window={SYMC_VISUALIZATION:{options:{locale:'de-DE'}},btoa:value=>Buffer.from(value,'binary').toString('base64'),atob:value=>Buffer.from(value,'base64').toString('binary')};
const context=vm.createContext({window,navigator:{userAgent:'Node.js'},console,setTimeout,clearTimeout});
for(const file of ['libs/echarts/6.1.0/echarts.gauge.min.js','libs/jslive/format.js','EChartsProgress/ProgressRenderer.js'])vm.runInContext(read(file),context);
const build=(state,w=620,h=500)=>window.SYMC_JSLIVE_OPTION(state,w,h),byID=(option,id)=>option.graphic.find(g=>g.id===id);
const near=(a,b,why,tolerance=0.001)=>assert.ok(Math.abs(a-b)<=tolerance,why+': '+a+' vs '+b);
const choice=process.argv[2];let images=0,layouts=0,fills=0,emptyPaints=0;
if(!['--layout','--fills','--empty','--borders'].includes(choice)){
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
if(!['--images','--fills','--empty','--borders'].includes(choice)){
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
if(!['--images','--layout'].includes(choice)){
    const fixture=JSON.parse(read('tests/fixtures/jslive-progress-geometry.json'));
    assert.equal(fixture.fills.length,24);
    for(const record of fixture.fills){
        const empty=!record.bounds.width||!record.bounds.height;
        if(choice==='--empty'&&!empty||choice==='--borders'&&empty)continue;
        const state=structuredClone(states[record.case-1].chart);Object.assign(state.settings,record.variant||{});
        state.settings.style_fontDisplay=false;state.settings.__animate=false;state.background='transparent';state.data.ratio=0.5;
        const before=JSON.stringify(state),option=build(state,record.width-16,record.height-16),body=byID(option,'progress-body');
        const background=body.children.find(g=>g.id==='background');
        if(empty){
            const paints=[];const canvas={nodeName:'CANVAS',style:{},width:record.width-16,height:record.height-16,setAttribute(){}};
            const ctx=new Proxy({canvas,fill(){paints.push('fill');},stroke(){paints.push('stroke');},drawImage(){paints.push('image');},
                measureText:text=>({width:String(text).length*8})},{get:(target,key)=>key in target?target[key]:()=>{}});
            canvas.getContext=()=>ctx;window.echarts.setPlatformAPI({createCanvas:()=>canvas});
            const chart=window.echarts.init(canvas,null,{renderer:'canvas',ssr:true,width:canvas.width,height:canvas.height});
            try{
                const visible=structuredClone(states[23].chart);visible.settings.style_fontDisplay=false;visible.settings.__animate=false;visible.background='transparent';
                const full=build(visible,canvas.width,canvas.height);
                // A loaded public image object isolates native graphic merge and
                // visibility updates; the browser test separately paints real SVGs.
                byID(full,'progress-body').children.find(g=>g.id==='background').style.image={width:100,height:100};
                full.animation=false;chart.setOption(full,true);chart.getZr().flush();assert.ok(paints.length>0,'Visible initial fill must paint.');
                paints.length=0;option.animation=false;chart.setOption(option,false);chart.getZr().flush();
                assert.deepEqual(paints,[],'F06: zero-width/height fill paths must paint neither a gray stroke nor a foreground (case '+record.case+').');
                chart.setOption(full,false);chart.getZr().flush();assert.ok(paints.length>0,'Returning from an empty path must restore the fill.');
            }finally{chart.dispose();}
            ++emptyPaints;
        }else{
            assert.equal(background.type,'image','F06: fill margins use the original morphology mask, not an approximate stroked path.');
            const svg=Buffer.from(background.style.image.split(',')[1],'base64').toString();
            for(const key of ['x','y','width','height'])assert.ok(svg.includes(key+'="'+record.filter[key]+'"'),'Original filter region '+key);
            assert.match(svg,new RegExp('operator="'+record.filter.operator+'"'));
            assert.match(svg,new RegExp('radius="'+record.filter.radius+'"'));
            assert.match(svg,/stroke="#fff"/,'Original masks include the default white 1px stroke before morphology.');
            assert.ok(!background.style.stroke,'Do not simulate morphology with a second native stroke.');
            const frame=record.backgroundSVG.match(/<rect\b[^>]+class="frame"[^>]*>/)?.[0];
            assert.ok(frame,'Independent source frame must be present.');
            for(const [key,actual] of [['x',background.style.x],['y',background.style.y],['width',background.style.width],['height',background.style.height]]){
                const expected=Number(new RegExp('\\b'+key+'="([^"]+)"').exec(frame)[1]);near(actual,expected,'Original fill frame '+key);
            }
        }
        assert.equal(JSON.stringify(state),before,'F06: filling must not alter original values, settings or source identities.');++fills;
    }
}
console.log('JSLive Progress F05/F06: '+images+' original-image clips, '+layouts+' independent layouts, '+fills+' source fill masks and '+emptyPaints+' empty Canvas paints passed.');
