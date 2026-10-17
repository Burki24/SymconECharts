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
foreach(['vertical','horizontal'] as $o) { foreach(['configured','ascending','descending'] as $sort) { $out[$o.'-'.$sort]=$m->invoke($f,['Orientation'=>$o,'SortOrder'=>$sort,'Gap'=>3],false); } }
echo json_encode($out,JSON_THROW_ON_ERROR);`;
const previews=JSON.parse(execFileSync('php',['-r',php],{cwd:root,encoding:'utf8',maxBuffer:1024*1024}));
for(const [key,svg] of Object.entries(previews)){
    const [orientation,sortOrder]=key.split('-');
    const option=render({...model,funnel:{...model.funnel,style:{...model.funnel.style,orientation,sortOrder}}}).option;
    Object.assign(option.series[0],{left:18,right:18,top:65,bottom:18,gap:3});
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

