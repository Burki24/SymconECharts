'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const root = path.join(__dirname, '..');
const source = fs.readFileSync(path.join(root, 'EChartsFunnel/visualization/app.js'), 'utf8');
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
        getElementById: id => ({ 'echarts-funnel-root': rootElement, 'echarts-funnel-chart': chartElement,
            'echarts-funnel-error': errorElement, 'echarts-funnel-empty': emptyElement,
            'echarts-funnel-warning': warningElement })[id]
    };
    vm.runInNewContext(designSource, { window });
    vm.runInNewContext(source, { window, document });
    return { option, chartElement, errorElement, emptyElement, warningElement, window };
}

const model = {family:'funnel',theme:'dark',funnel:{title:'Stufenvergleich',unit:'W',data:[
    {id:'variable-1',label:'<Stufe>',value:100,unit:'W',decimals:1,color:'#abcdef',order:0},
    {id:'variable-2',label:'<Stufe>',value:60,unit:'W',decimals:1,color:'',order:1},
    {id:'variable-3',label:'Zero',value:0,unit:'W',decimals:1,color:'',order:2},
    {id:'variable-4',label:'Again',value:60,unit:'W',decimals:1,color:'',order:3}
],style:{orientation:'vertical',sortOrder:'configured',alignment:'center',gap:3,showLabels:true,showValues:true,showTooltip:true,animationEnabled:false}}};
const tile = render(model);
assert.equal(tile.chartElement.style.top,'58px');
assert.equal(tile.option.series[0].type,'funnel');
assert.equal(tile.option.series[0].min,0);
assert.equal(tile.option.series[0].max,100);
assert.equal(tile.option.series[0].sort,'none');
assert.equal(tile.option.series[0].data.map(d=>d.value).join(','),'100,60,0,60');
assert.equal(tile.option.series[0].data[0].itemStyle.color,'#abcdef');
assert.equal(tile.option.series[0].data[0].id,'variable-1');
assert.equal(tile.option.series[0].data[1].id,'variable-2');
assert.equal(tile.option.animation,false);
assert.match(tile.option.tooltip.formatter({data:tile.option.series[0].data[0]}),/&lt;Stufe&gt;.*100 W/);
assert.equal(tile.option.series[0].label.formatter({data:tile.option.series[0].data[0]}),'<Stufe>\n100 W');
assert.ok(tile.option.series[0].top > tile.option.title.top + tile.option.title.textStyle.fontSize);
assert.equal(render(model,'symcon',false).chartElement.style.top,'0px');
const empty=render({...model,funnel:{...model.funnel,data:model.funnel.data.map(d=>({...d,value:0}))}});
assert.equal(empty.emptyElement.hidden,false);
assert.equal(empty.chartElement.hidden,true);
const emptyDecorated=render({...model,funnel:{...model.funnel,data:model.funnel.data.map(d=>({...d,value:0})),
    style:{...model.funnel.style,minSizePercent:20,maxSizePercent:90}}});
assert.equal(emptyDecorated.emptyElement.hidden,false,'A positive visual floor must not turn an all-zero dataset into a nonempty chart.');
tile.window.handleMessage({status:'error',error:'The Funnel values could not be loaded.'});
tile.window.handleMessage({status:'error',error:'The Funnel values could not be loaded.'});
assert.equal(tile.chartElement.hidden,false);
assert.equal(tile.warningElement.hidden,false);
tile.window.handleMessage({status:'error',error:'Funnel requires non-negative values.'});
assert.equal(tile.chartElement.hidden,true);
assert.equal(tile.errorElement.hidden,false);
tile.window.handleMessage({status:'ready',chart:model});
assert.equal(tile.chartElement.hidden,false);
const echarts=require(path.join(root,'.tools/echarts-runtime/node_modules/echarts'));
const decoration={minSizePercent:20,maxSizePercent:85,borderType:'dashed',shadowBlur:8,shadowColor:'#123456',
    shadowOffsetX:3,shadowOffsetY:-2,hoverFocus:'none',blurOpacityPercent:15,hideOverlappingLabels:false,
    labelMaxWidth:70,showLabelLines:true,labelLineLength:24,labelLineWidth:2,labelLineType:'dotted',
    labelLineColor:'#fedcba',labelLineOpacityPercent:60,outsideLabelSide:'start',fontFamily:'Georgia, serif'};
const decorated=render({...model,funnel:{...model.funnel,style:{...model.funnel.style,...decoration,labelPosition:'outside'}}});
assert.equal(decorated.option.series[0].minSize,'20%','Funnel must expose the minimum visual stage size.');
assert.equal(decorated.option.series[0].maxSize,'85%');
assert.equal(decorated.option.series[0].itemStyle.borderType,'dashed');
assert.equal(decorated.option.series[0].itemStyle.shadowBlur,8);
assert.equal(decorated.option.series[0].itemStyle.shadowOffsetY,-2);
assert.equal(decorated.option.series[0].emphasis.disabled,true);
assert.equal(decorated.option.series[0].blur.itemStyle.opacity,0.15);
assert.equal(decorated.option.series[0].labelLayout.hideOverlap,false);
assert.equal(decorated.option.series[0].label.position,'left');
assert.equal(decorated.option.series[0].label.fontFamily,'Georgia, serif');
assert.equal(decorated.option.series[0].labelLine.length,24);
assert.equal(decorated.option.series[0].labelLine.lineStyle.width,2);
assert.equal(decorated.option.series[0].labelLine.lineStyle.type,'dotted');
assert.equal(decorated.option.series[0].labelLine.lineStyle.color,'#fedcba');
assert.equal(decorated.option.series[0].labelLine.lineStyle.opacity,0.6);
assert.equal(decorated.option.series[0].data[0].label.width,70);
const decoratedEngine=echarts.init(null,null,{renderer:'svg',ssr:true,width:900,height:500});
decoratedEngine.setOption(decorated.option);
const decoratedData=decoratedEngine.getModel().getSeriesByIndex(0).getData();
const decoratedShape=decoratedData.getItemGraphicEl(0);
assert.equal(decoratedShape.style.lineDash,'dashed');
assert.equal(decoratedShape.style.shadowBlur,8);
assert.equal(decoratedShape.style.shadowOffsetY,-2);
assert.equal(decoratedShape.states.blur.style.opacity,0.15);
assert.equal(decoratedShape.getTextGuideLine().style.lineWidth,2);
assert.equal(decoratedShape.getTextGuideLine().style.stroke,'#fedcba');
assert.equal(decoratedShape.getTextContent().style.fontFamily,'Georgia, serif');
const visualCross=900-decorated.option.series[0].left-decorated.option.series[0].right;
assert.ok(Math.abs(decoratedData.getItemLayout(2).points[1][0]-decoratedData.getItemLayout(2).points[0][0]-visualCross*0.2)<0.001,
    'A configured minimum size maps a legitimate zero to the explicit visual floor without modifying its value.');
assert.equal(decorated.option.series[0].data[2].value,0);
decoratedEngine.dispose();
const zeroStyles=render({...model,funnel:{...model.funnel,style:{...model.funnel.style,labelPosition:'outside',
    labelLineWidth:0,labelLineLength:0,labelLineOpacityPercent:0,blurOpacityPercent:0,borderWidth:0,shadowBlur:0}}}).option.series[0];
assert.equal(zeroStyles.labelLine.lineStyle.width,0);
assert.equal(zeroStyles.labelLine.lineStyle.opacity,0);
assert.equal(zeroStyles.labelLine.length,0);
assert.equal(zeroStyles.blur.itemStyle.opacity,0);
assert.equal(zeroStyles.itemStyle.borderWidth,0);
const linesOff=render({...model,funnel:{...model.funnel,style:{...model.funnel.style,labelPosition:'outside',showLabelLines:false}}}).option;
assert.equal(linesOff.series[0].labelLine.show,false);
const linesOffEngine=echarts.init(null,null,{renderer:'svg',ssr:true,width:900,height:500});
linesOffEngine.setOption(linesOff);
assert.equal(linesOffEngine.getModel().getSeriesByIndex(0).getData().getItemGraphicEl(0).getTextGuideLine().ignore,true);
linesOffEngine.dispose();
const crowded={...model,funnel:{...model.funnel,data:Array.from({length:16},(_,index)=>({...model.funnel.data[0],id:'variable-'+index})),
    style:{...model.funnel.style,gap:30,labelFontSizePercent:200}}};
assert.equal(render(crowded).option.series[0].data[0].label.show,false);
assert.equal(render({...crowded,funnel:{...crowded.funnel,style:{...crowded.funnel.style,hideOverlappingLabels:false}}}).option.series[0].data[0].label.show,true);
for(const width of [100,350,900]){
    for(const orientation of ['vertical','horizontal']){
        for(const alignment of ['start','center','end']){
            for(const sortOrder of ['configured','ascending','descending']){
                const custom={...model,funnel:{...model.funnel,style:{...model.funnel.style,orientation,alignment,sortOrder,gap:30,
                    borderWidth:0,opacityPercent:0,labelColor:'#123456',labelFontSizePercent:150,titleBold:false,labelBold:true}}};
                const result=render(custom,'ipsview',true,width);
                const option=result.option;
                const s=option.series[0];
                assert.equal(s.itemStyle.borderWidth,0);
                assert.equal(s.itemStyle.opacity,0);
                assert.equal(s.data[0].label.color,'#123456');
                assert.equal(option.title.textStyle.fontWeight,'normal');
                assert.equal(s.label.fontWeight,'bold');
                const ids=s.data.map(d=>d.id);
                assert.equal(ids.join(','), sortOrder==='ascending'?'variable-3,variable-2,variable-4,variable-1'
                    :sortOrder==='descending'?'variable-1,variable-2,variable-4,variable-3':'variable-1,variable-2,variable-3,variable-4');
                assert.equal(s.data.find(d=>d.id==='variable-1').itemStyle.color,'#abcdef','Sorting must preserve source colors.');
                const engine=echarts.init(null,null,{renderer:'svg',ssr:true,width,height:500});
                engine.setOption(option);
                const data=engine.getModel().getSeriesByIndex(0).getData();
                const cross=orientation==='horizontal'?500-s.top-s.bottom:width-s.left-s.right;
                const extent=orientation==='horizontal'?width-s.left-s.right:500-s.top-s.bottom;
                const step=(extent-s.gap*(s.data.length-1))/s.data.length;
                for(let i=0;i<data.count();i++){
                    const points=data.getItemLayout(i).points;
                    assert.ok(points.flat().every(Number.isFinite));
                    const current=s.data[i].value/s.max*cross;
                    const next=(s.data[i+1]?.value||0)/s.max*cross;
                    const distance=(a,b)=>Math.hypot(a[0]-b[0],a[1]-b[1]);
                    assert.ok(Math.abs(distance(points[0],points[1])-current)<0.001);
                    assert.ok(Math.abs(distance(points[2],points[3])-next)<0.001);
                    const primary=orientation==='horizontal'?0:1;
                    assert.ok(Math.abs(points[3][primary]-points[0][primary]-step)<0.001);
                    assert.ok(points.every(p=>p[0]>=s.left-0.001 && p[0]<=width-s.right+0.001 && p[1]>=s.top-0.001 && p[1]<=500-s.bottom+0.001));
                }
                assert.match(engine.renderToSVGString(),/<svg/);
                engine.dispose();
            }
        }
    }
}
for(const orientation of ['vertical','horizontal']){
    const option=render({...model,funnel:{...model.funnel,style:{...model.funnel.style,orientation,labelPosition:'outside'}}}).option;
    assert.equal(option.series[0].label.position,orientation==='horizontal'?'bottom':'right');
    assert.equal(option.series[0].labelLine.show,true);
    assert.equal(option.series[0].labelLayout.hideOverlap,true);
    const start=render({...model,funnel:{...model.funnel,style:{...model.funnel.style,orientation,labelPosition:'outside',outsideLabelSide:'start'}}}).option.series[0];
    assert.equal(start.label.position,orientation==='horizontal'?'top':'left');
    assert.ok(orientation==='horizontal'?start.top>option.series[0].top:start.left>option.series[0].left);
    for(const width of [100,350]){
        for(const outsideLabelSide of ['start','end']){
            const compact=render({...model,funnel:{...model.funnel,style:{...model.funnel.style,...decoration,
                orientation,outsideLabelSide,labelPosition:'outside',labelLineLength:60}}},'symcon',true,width).option;
            const engine=echarts.init(null,null,{renderer:'svg',ssr:true,width,height:500});
            engine.setOption(compact);
            const compactData=engine.getModel().getSeriesByIndex(0).getData();
            for(let index=0;index<compactData.count();index++){
                assert.ok(compactData.getItemLayout(index).points.every(p=>Number.isFinite(p[0])&&Number.isFinite(p[1])
                    &&p[0]>=0&&p[0]<=width&&p[1]>=compact.series[0].top&&p[1]<=500));
            }
            assert.ok(compact.series[0].labelLine.length<=width*0.2);
            engine.dispose();
        }
    }
}
const huge=render({...model,funnel:{...model.funnel,data:model.funnel.data.map(d=>({...d,value:Number.MAX_VALUE}))}});
const hugeEngine=echarts.init(null,null,{renderer:'svg',ssr:true,width:900,height:500});
hugeEngine.setOption(huge.option);
assert.ok(hugeEngine.getModel().getSeriesByIndex(0).getData().getItemLayout(0).points.flat().every(Number.isFinite));
hugeEngine.dispose();
// Compare real PHP preview polygons to the real pinned ECharts layout at the preview canvas dimensions.
const {execFileSync}=require('node:child_process');
const php = `ob_start(); require 'tests/gateway_gauges.php'; ob_end_clean();
foreach ([100,60,0,60] as $i=>$v) { $GLOBALS['symconTestVariables'][88400+$i]=['VariableType'=>2,'VariableUpdated'=>1780000000,'Value'=>(float)$v,'Name'=>'Stage','Presentation'=>['SUFFIX'=>' W','DIGITS'=>1]]; }
$f=new TestableEChartsFunnel();$f->Create();$f->SetTestProperty('Sources',json_encode(array_map(static fn($i)=>['VariableID'=>88400+$i,'Label'=>'Stage'],range(0,3))));
$m=new ReflectionMethod(EChartsFunnel::class,'BarPreviewSvg');$out=[];
foreach(['vertical','horizontal'] as $o) { foreach(['configured','ascending','descending'] as $sort) {
    $draft=['Orientation'=>$o,'SortOrder'=>$sort,'Gap'=>3];
    $out[$o.'-'.$sort]=$m->invoke($f,$draft,false);
    $out['decorated-'.$o.'-'.$sort]=$m->invoke($f,[...$draft,'MinSizePercent'=>20,'MaxSizePercent'=>85,
        'LabelPosition'=>'outside','OutsideLabelSide'=>'start','LabelLineLength'=>24,'FontFamily'=>'Georgia, serif'],false);
} }
echo json_encode($out,JSON_THROW_ON_ERROR);`;
const previews=JSON.parse(execFileSync('php',['-r',php],{cwd:root,encoding:'utf8',maxBuffer:1024*1024}));
for(const [key,svg] of Object.entries(previews)){
    const isDecorated=key.startsWith('decorated-');
    const [orientation,sortOrder]=key.replace('decorated-','').split('-');
    const style={...model.funnel.style,orientation,sortOrder,...(isDecorated?{...decoration,labelPosition:'outside'}:{})};
    const option=render({...model,funnel:{...model.funnel,style}}).option;
    Object.assign(option.series[0],{left:isDecorated&&orientation==='vertical'?183:18,right:18,
        top:isDecorated&&orientation==='horizontal'?144:65,bottom:18,gap:3});
    const engine=echarts.init(null,null,{renderer:'svg',ssr:true,width:720,height:360});
    engine.setOption(option);
    const data=engine.getModel().getSeriesByIndex(0).getData();
    const polygons=[...svg.matchAll(/<polygon data-stage="(\d+)" points="([^"]+)"/g)];
    assert.equal(polygons.length,4);
    polygons.forEach((match,index)=>{
        const points=match[2].split(' ').map(pair=>pair.split(',').map(Number)).flat();
        const actual=data.getItemLayout(index).points.flat();
        points.forEach((value,i)=>assert.ok(Math.abs(value-actual[i])<0.011,key+' preview point '+i));
        assert.equal(Number(match[1]),option.series[0].data[index].order);
    });
    engine.dispose();
}
console.log('Funnel renderer, pinned-engine geometry and PHP preview parity verified.');

