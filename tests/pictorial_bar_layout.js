'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const root = path.join(__dirname, '..');
const source = fs.readFileSync(path.join(root, 'EChartsPictorialBar/visualization/app.js'), 'utf8');
const designSource = fs.readFileSync(path.join(root, 'libs/echarts-design.js'), 'utf8');
const palette = { background: '#101114', text: '#f4f5f7', muted: '#969aa2', border: '#737780', accent: '#55cbb5' };

function render(model, mode = 'symcon', headerVisible = true, width = 900) {
    const chartElement = { style: {}, hidden: false, clientWidth: width, clientHeight: 500 };
    const rootElement = { style: {} };
    const errorElement = { hidden: true, textContent: '' };
    const emptyElement = { hidden: true, textContent: '' };
    const warningElement = { hidden: true, textContent: '' };
    let option;
    const chart = { setOption: next => { option = next; }, clear: () => {}, resize: () => {}, dispose: () => {} };
    const window = {
        SYMC_VISUALIZATION: { mode, state: { status: 'ready', chart: model }, translations: {},
            options: { echartsThemes: { auto: palette, dark: palette }, tileHeaderVisible: headerVisible,
                adaptToBackground: mode === 'ipsview' } },
        echarts: { init: () => chart }, addEventListener: () => {}, getComputedStyle: () => ({ color: '' })
    };
    const document = {
        body: { appendChild: () => {} }, createElement: () => ({ style: {}, remove: () => {} }),
        getElementById: id => ({ 'echarts-pictorial-bar-root': rootElement, 'echarts-pictorial-bar-chart': chartElement,
            'echarts-pictorial-bar-error': errorElement, 'echarts-pictorial-bar-empty': emptyElement,
            'echarts-pictorial-bar-warning': warningElement })[id]
    };
    vm.runInNewContext(designSource, { window });
    vm.runInNewContext(source, { window, document });
    return { option, chartElement, errorElement, emptyElement, warningElement, window };
}




const model={family:'pictorialBar',theme:'dark',pictorialBar:{title:'Current batteries',unit:'%',data:[
    {id:'v1',label:'<Same>',unit:'%',decimals:0,value:0,order:0,color:''},
    {id:'v2',label:'<Same>',unit:'%',decimals:0,value:25,order:1,color:'#123456'},
    {id:'v3',label:'High',unit:'%',decimals:0,value:150,order:2,color:''}
],style:{orientation:'horizontal',symbol:'rect',symbolRepeat:true,symbolSize:12,symbolGap:2,barWidth:40,scaleMaximum:100,animationEnabled:false}}};
const tile=render(model);
assert.equal(tile.chartElement.style.top,'58px');
assert.equal(tile.option.series[0].type,'pictorialBar');
assert.equal(tile.option.series[0].symbolClip,true);
assert.equal(tile.option.xAxis.max,100);
assert.equal(tile.option.series[0].data[2].value,100,'Only drawing is capped; original values remain intact.');
assert.equal(model.pictorialBar.data[2].value,150);
assert.match(tile.option.aria.label.description,/High: 150 %/,'Accessible descriptions must retain uncapped values too.');
assert.equal(tile.option.series[0].label.formatter({dataIndex:2}),'150 %');
assert.match(tile.option.tooltip.formatter({dataIndex:1}),/&lt;Same&gt;.*25 %/);
assert.equal(tile.option.yAxis.data.join(','),'v1,v2,v3');
assert.equal(tile.option.yAxis.axisLabel.formatter('v1'),'<Same>');
assert.equal(tile.option.series[0].data[1].itemStyle.color,'#123456');
assert.equal(render(model,'ipsview').chartElement.style.top,'0px');
assert.equal(render(model,'symcon',false).chartElement.style.top,'0px');
const sorted=render({...model,pictorialBar:{...model.pictorialBar,style:{...model.pictorialBar.style,sortOrder:'descending'}}});
assert.equal(sorted.option.series[0].data[0].id,'v3');
assert.equal(sorted.option.series[0].label.formatter({dataIndex:0}),'150 %');
assert.ok(tile.option.grid.top>tile.option.title.top+tile.option.title.textStyle.fontSize);
tile.window.handleMessage({status:'error',error:'The PictorialBar values could not be loaded.'});
tile.window.handleMessage({status:'error',error:'The PictorialBar values could not be loaded.'});
assert.equal(tile.warningElement.hidden,false);
tile.window.handleMessage({status:'error',error:'PictorialBar requires non-negative values.'});
assert.equal(tile.chartElement.hidden,true);
const echarts=require(path.join(root,'.tools/echarts-runtime/node_modules/echarts'));
const colored=render({...model,pictorialBar:{...model.pictorialBar,data:[0,25,50,75,100,150].map((value,i)=>({id:'c'+i,label:'Battery',unit:'%',value,order:i,color:'#ffffff'})),
    style:{...model.pictorialBar.style,colorMode:'value',colorMinimum:0,colorMaximum:100,lowColor:'#ff0000',middleColor:'#ffff00',highColor:'#00ff00',
        fontFamily:'Georgia, serif',valuePosition:'insideEnd',valueDistance:8,valueBold:true,hideOverlap:false,categoryLabelRotate:25,
        showAxisValues:false,showAxisTicks:true,gridWidth:2,gridType:'dotted',gridOpacityPercent:60,borderType:'dashed',
        shadowBlur:8,shadowColor:'#123456',shadowOffsetX:2,shadowOffsetY:3,emphasisDisabled:true,focus:'none',blurOpacityPercent:30}}});
assert.equal(colored.option.series[0].data.map(x=>x.itemStyle.color).join(','),'#ff0000,#ff8000,#ffff00,#80ff00,#00ff00,#00ff00',
    'Battery colors interpolate over a fixed range, override source colors and clamp without changing values.');
assert.equal(colored.option.series[0].data[5].value,100);
assert.equal(colored.option.series[0].label.formatter({dataIndex:5}),'150 %');
assert.equal(colored.option.textStyle.fontFamily,'Georgia, serif');
assert.equal(colored.option.series[0].label.position,'insideRight');
assert.equal(colored.option.series[0].label.distance,8);
assert.equal(colored.option.series[0].label.fontWeight,'bold');
assert.equal(colored.option.series[0].labelLayout.hideOverlap,false);
assert.equal(colored.option.yAxis.axisLabel.rotate,25);
assert.equal(colored.option.xAxis.axisLabel.show,false);
assert.equal(colored.option.yAxis.axisTick.show,true);
assert.equal(colored.option.xAxis.splitLine.lineStyle.type,'dotted');
assert.equal(colored.option.xAxis.splitLine.lineStyle.width,2);
assert.equal(colored.option.xAxis.splitLine.lineStyle.opacity,0.6);
assert.equal(colored.option.series[0].itemStyle.borderType,'dashed');
assert.equal(colored.option.series[0].itemStyle.shadowBlur,8);
assert.equal(colored.option.series[0].emphasis.disabled,true);
assert.equal(colored.option.series[0].emphasis.focus,'none');
assert.equal(colored.option.series[0].blur.itemStyle.opacity,0.3);
for (const orientation of ['horizontal','vertical']) {
 for (const symbolRepeat of [false,true]) {
  for (const valuePosition of ['outside','inside','insideStart','insideEnd']) {
    const extended=render({...model,pictorialBar:{...model.pictorialBar,style:{...model.pictorialBar.style,...{
        orientation,symbolRepeat,valuePosition,colorMode:'value',colorMinimum:0.5,colorMaximum:100.5,
        lowColor:'#ff0000',middleColor:'#ffff00',highColor:'#00ff00',fontFamily:'Georgia, serif',
        valueDistance:8,valueBold:true,gridType:'dashed',borderWidth:1,borderType:'dotted',shadowBlur:5
    }}}},'ipsview',true,620);
    const engine=echarts.init(null,null,{renderer:'svg',ssr:true,width:620,height:500});
    engine.setOption(extended.option);
    const data=engine.getModel().getSeriesByIndex(0).getData();
    assert.equal(data.getItemVisual(0,'style').fill,'#ff0000');
    assert.equal(data.getItemVisual(2,'style').fill,'#00ff00');
    assert.match(engine.renderToSVGString(),/Georgia/);
    assert.ok(!engine.renderToSVGString().includes('NaN'));
    engine.dispose();
  }
 }
}
const tiny=render({...model,pictorialBar:{...model.pictorialBar,data:[{...model.pictorialBar.data[0],value:1e-14}],
    style:{...model.pictorialBar.style,colorMode:'value',colorMinimum:0,colorMaximum:1e-14,highColor:'#000000'}}});
assert.equal(tiny.option.series[0].data[0].itemStyle.color,'#000000','Small finite ranges and explicit black must work.');
const independentRange=render({...model,pictorialBar:{...model.pictorialBar,data:[{...model.pictorialBar.data[1],value:25}],
    style:{...model.pictorialBar.style,scaleMaximum:0,colorMode:'value',colorMinimum:0,colorMaximum:100,
        lowColor:'#ff0000',middleColor:'#ffff00',highColor:'#00ff00'}}});
assert.equal(independentRange.option.xAxis.max,25);
assert.equal(independentRange.option.series[0].data[0].itemStyle.color,'#ff8000','Colors must not renormalize to current highest value.');
const gradientStyle={...model.pictorialBar.style,colorMode:'gradient',colorMinimum:0,colorMaximum:100,
    lowColor:'#ff0000',middleColor:'#ffff00',highColor:'#00ff00'};
const gradient=render({...model,pictorialBar:{...model.pictorialBar,style:gradientStyle}});
assert.equal(gradient.option.series[0].symbolRepeat,false,'One generated compound symbol must carry the gradient across the entire repeated group.');
assert.match(gradient.option.series[0].symbol,/^path:\/\//);
assert.equal(gradient.option.series[0].symbolClip,true);
assert.equal(JSON.stringify(gradient.option.series[0].data[1].itemStyle.color.colorStops),
    JSON.stringify([{offset:0,color:'#ff0000'},{offset:0.5,color:'#ffff00'},{offset:1,color:'#00ff00'}]));
const gradientSingle=render({...model,pictorialBar:{...model.pictorialBar,style:{...gradientStyle,symbolRepeat:false}}});
assert.equal(gradientSingle.option.series[0].symbol,'rect');
assert.equal(JSON.stringify(gradientSingle.option.series[0].data[1].itemStyle.color.colorStops),
    JSON.stringify([{offset:0,color:'#ff0000'},{offset:1,color:'#ff8000'}]),'A 25% symbol must not stretch the full red-yellow-green ramp into itself.');
assert.equal(gradientSingle.option.series[0].data[0].itemStyle.color,'#ff0000');
const lqi=render({...model,pictorialBar:{...model.pictorialBar,unit:'LQI',data:[40,127.5,255,300,0].map((value,i)=>({id:'l'+i,label:'LQI',value,order:i,unit:'LQI',decimals:1})),
    style:{...gradientStyle,colorMaximum:255,scaleMaximum:255,symbolRepeat:false}}});
assert.equal(lqi.option.series[0].data[0].itemStyle.color.colorStops.at(-1).color,'#ff5000');
assert.equal(lqi.option.series[0].data[1].itemStyle.color.colorStops.at(-1).color,'#ffff00');
assert.equal(lqi.option.series[0].data[2].itemStyle.color.colorStops.at(-1).color,'#00ff00');
assert.equal(lqi.option.series[0].label.formatter({dataIndex:3}),'300 LQI');
const offsetRamp=render({...model,pictorialBar:{...model.pictorialBar,style:{...gradientStyle,colorMinimum:20,colorMaximum:80}}});
assert.equal(JSON.stringify(offsetRamp.option.series[0].data[1].itemStyle.color.colorStops),JSON.stringify([
    {offset:0,color:'#ff0000'},{offset:0.2,color:'#ff0000'},{offset:0.5,color:'#ffff00'},{offset:0.8,color:'#00ff00'},{offset:1,color:'#00ff00'}]));
const autoRamp=render({...model,pictorialBar:{...model.pictorialBar,data:[{...model.pictorialBar.data[1]}],style:{...gradientStyle,scaleMaximum:0}}});
assert.equal(autoRamp.option.series[0].data[0].itemStyle.color.colorStops.at(-1).color,'#ff8000','Automatic geometry must not rescale the color range.');
for (const symbol of ['rect','roundRect','circle','diamond','triangle']) {
 for (const orientation of ['horizontal','vertical']) {
  for (const symbolRepeat of [false,true]) {
    const result=render({...model,pictorialBar:{...model.pictorialBar,style:{...gradientStyle,symbol,orientation,symbolRepeat,borderWidth:1}}},'ipsview',true,620);
    const engine=echarts.init(null,null,{renderer:'svg',ssr:true,width:620,height:500});
    engine.setOption(result.option);
    const data=engine.getModel().getSeriesByIndex(0).getData();
    assert.equal(data.getItemVisual(1,'style').fill.type,'linear');
    assert.equal(data.getItemVisual(1,'style').fill.y,orientation==='horizontal'?0:1);
    assert.ok(!engine.renderToSVGString().includes('NaN'));
    assert.match(engine.renderToSVGString(),/<linearGradient/);
    assert.equal(result.option.series[0].label.formatter({dataIndex:2}),'150 %');
    const graphics=data.getItemGraphicEl(1);
    const bounds=graphics.getBoundingRect();
    assert.ok([bounds.x,bounds.y,bounds.width,bounds.height].every(Number.isFinite));
    if(symbolRepeat) {
        assert.equal((result.option.series[0].symbol.match(/Z/g)||[]).length<=128,true);
        const layout=result.option.series[0].labelLayout({dataIndex:1});
        const g=result.option.grid;
        if(orientation==='horizontal') assert.equal(layout.x,g.left+(620-g.left-g.right)*0.25+5);
        else assert.equal(layout.y,500-g.bottom-(500-g.top-g.bottom)*0.25-5);
        const texts=[];
        graphics.traverse(el=>{const text=el.getTextContent?.();if(text)texts.push(text);});
        assert.equal(texts.length,1);
        const transform=texts[0].getComputedTransform();
        assert.ok(Math.abs(transform[4]-layout.x)<1 && Math.abs(transform[5]-layout.y)<1,
            'Real-engine labels must follow measured values, not full-scale compound bounds.');
    }
    engine.dispose();
  }
 }
}
const hugeGradient=render({...model,pictorialBar:{...model.pictorialBar,style:{...gradientStyle,symbolSize:4,symbolGap:0}}},'ipsview',true,100000);
assert.ok((hugeGradient.option.series[0].symbol.match(/Z/g)||[]).length<=128,'Compound symbols must remain bounded in very wide views.');
assert.ok(hugeGradient.option.series[0].symbol.length<60000,'Generated geometry must have a bounded path payload.');
const fixtureIcons=JSON.parse(fs.readFileSync(path.join(__dirname,'fixtures/pictorial-icons.json'),'utf8'));
const iconStyle={...gradientStyle,symbol:'icon',symbolIcon:'cell',symbolPath:fixtureIcons.light.cell[4],symbolWidth:24,symbolHeight:12};
for(const colorMode of ['category','value','gradient']){
 for(const orientation of ['horizontal','vertical']){
  for(const symbolRepeat of [true,false]){
    const result=render({...model,pictorialBar:{...model.pictorialBar,style:{...iconStyle,colorMode,orientation,symbolRepeat}}},'ipsview',true,620);
    assert.match(result.option.series[0].symbol,/^path:\/\//);
    assert.equal(result.option.series[0].symbolRepeat,false);
    assert.equal(result.option.series[0].symbolClip,symbolRepeat);
    assert.equal(result.option.series[0].label.formatter({dataIndex:2}),'150 %');
    const engine=echarts.init(null,null,{renderer:'svg',ssr:true,width:620,height:500});
    engine.setOption(result.option);
    const svg=engine.renderToSVGString();
    assert.ok(!/NaN|Infinity/.test(svg));
    assert.ok(svg.includes('<path'));
    const graphics=engine.getModel().getSeriesByIndex(0).getData().getItemGraphicEl(1);
    const bounds=graphics.getBoundingRect();
    assert.ok([bounds.x,bounds.y,bounds.width,bounds.height].every(Number.isFinite));
    if(symbolRepeat){
        assert.ok((result.option.series[0].symbol.match(/Z/g)||[]).length<=128,'At most 64 copies of a two-path icon.');
        assert.equal(typeof result.option.series[0].labelLayout,'function');
    }
    if(colorMode==='gradient')assert.match(svg,/<linearGradient/);
    engine.dispose();
  }
 }
}
// The transformer supports all SVG path commands, relative moveto and exponents.
const pathContext={};
vm.runInNewContext(source.slice(source.indexOf('    function iconPath('),source.indexOf('    // One bounded compound')),pathContext);
assert.equal(pathContext.iconPath('m1 2 3 4h5v6l1e1 -2t3 4q1 2 3 4c1 2 3 4 5 6s1 2 3 4a2 3 45 0 1 5 6z',2,10,20),
    'm12 24 6 8 h10 v12 l20 -4 t6 8 q2 4 6 8 c2 4 6 8 10 12 s2 4 6 8 a4 6 45 0 1 10 12 z');
assert.equal(pathContext.iconPath('M1 2H3V4A2 3 45 0 1 5 6Z',2,10,20),'M12 24 H16 V28 A4 6 45 0 1 20 32 Z');
assert.throws(()=>pathContext.iconPath('M0 0 L1',1,0,0));
assert.throws(()=>pathContext.iconPath('L0 0',1,0,0));
const complexPath='M0 0'+('L1 1 '.repeat(2400))+'Z';
const complexIcon=render({...model,pictorialBar:{...model.pictorialBar,style:{...iconStyle,symbolPath:complexPath,symbolSize:4,symbolGap:0}}},'ipsview',true,100000);
assert.ok(complexIcon.option.series[0].symbol.length<262144,'Complex icon repeats must respect the compound path budget.');
const invalidIcon=render({...model,pictorialBar:{...model.pictorialBar,style:{...iconStyle,symbolPath:'M0 0L1'}}});
assert.equal(invalidIcon.errorElement.hidden,false);
assert.equal(invalidIcon.errorElement.textContent,'The selected Symcon icon is unavailable or unsupported.');
for(const symbol of ['rect','roundRect','circle','diamond','triangle']){
 for(const orientation of ['vertical','horizontal']){
  for(const symbolRepeat of [false,true]){
    const output=render({...model,pictorialBar:{...model.pictorialBar,style:{...model.pictorialBar.style,symbol,orientation,symbolRepeat}}},'symcon',true,350);
    const engine=echarts.init(null,null,{renderer:'svg',ssr:true,width:350,height:500});
    engine.setOption(output.option);
    const data=engine.getModel().getSeriesByIndex(0).getData();
    assert.equal(data.count(),3);
    assert.equal(data.getItemVisual(1,'style').fill,'#123456');
    assert.ok(engine.renderToSVGString().includes('<path'));
    const element=data.getItemGraphicEl(1);
    assert.ok(element);
    const bounds=element.getBoundingRect();
    assert.ok([bounds.x,bounds.y,bounds.width,bounds.height].every(Number.isFinite));
    engine.dispose();
  }
 }
}
const zeros=render({...model,pictorialBar:{...model.pictorialBar,data:[model.pictorialBar.data[0]],style:{...model.pictorialBar.style,scaleMaximum:0}}});
assert.equal(zeros.option.xAxis.max,1);
assert.equal(zeros.option.series[0].data[0].value,0);
assert.equal(zeros.option.series[0].label.formatter({dataIndex:0}),'0 %');
console.log('PictorialBar options, identity, caps, zero values and real ECharts geometry verified.');
const huge=render({...model,pictorialBar:{...model.pictorialBar,data:[model.pictorialBar.data[2]],
    style:{...model.pictorialBar.style,symbolSize:4,symbolGap:0,scaleMaximum:150}}},'ipsview',true,100000);
const hugeEngine=echarts.init(null,null,{renderer:'svg',ssr:true,width:100000,height:500});
hugeEngine.setOption(huge.option);
let glyphs=0;
hugeEngine.getModel().getSeriesByIndex(0).getData().getItemGraphicEl(0).traverse(el=>{
    // ECharts adds one transparent rect for label/hit geometry, not a glyph.
    if(el.type==='path')glyphs++;
});
assert.ok(glyphs>0&&glyphs<=128,'Repeat count must stay bounded even in exceptionally large viewports: '+glyphs);
hugeEngine.dispose();
