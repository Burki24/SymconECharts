'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const root = path.join(__dirname, '..');
const source = fs.readFileSync(path.join(root, 'EChartsFunnel/visualization/app.js'), 'utf8');
const designSource = fs.readFileSync(path.join(root, 'libs/echarts-design.js'), 'utf8');
const palette = { background: '#101114', text: '#f4f5f7', muted: '#969aa2', border: '#737780', accent: '#55cbb5',
    seriesColors: ['#55cbb5', '#5470c6', '#91cc75', '#fac858', '#ee6666'] };

function render(model, mode = 'symcon', headerVisible = true, width = 900, height = 500) {
    let boxWidth = width;
    let boxHeight = height;
    const chartElement = { style: {}, hidden: false };
    Object.defineProperties(chartElement, {
        clientWidth: { get: () => chartElement.hidden ? 0 : boxWidth, set: value => { boxWidth = value; } },
        clientHeight: { get: () => chartElement.hidden ? 0 : boxHeight, set: value => { boxHeight = value; } }
    });
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
    const listeners = {};
    window.addEventListener = (event, callback) => { listeners[event] = callback; };
    function element(tag) {
        const node = { tag, style: {}, children: [], hidden: true, listeners: {},
            addEventListener: (name, callback) => { node.listeners[name] = callback; },
            appendChild: child => { node.children.push(child); },
            setAttribute: (name, value) => { node[name] = value; }, remove: () => {},
            querySelectorAll: tagName => node.children.flatMap(child => [
                ...(child.tag === tagName ? [child] : []), ...child.querySelectorAll(tagName)
            ]) };
        Object.defineProperty(node, 'textContent', { get: () => node.text || '', set: value => {
            node.text = value; node.children = [];
        } });
        return node;
    }
    const elements = { 'echarts-funnel-root': rootElement, 'echarts-funnel-chart': chartElement,
            'echarts-funnel-error': errorElement, 'echarts-funnel-empty': emptyElement,
            'echarts-funnel-warning': warningElement };
    for (const suffix of ['controls', 'selection', 'selection-title', 'sources', 'pages', 'previous', 'next', 'range', 'all', 'inverse']) {
        elements['echarts-funnel-' + suffix] = element('div');
    }
    const document = {
        body: { appendChild: () => {} }, createElement: element,
        getElementById: id => elements[id]
    };
    vm.runInNewContext(designSource, { window });
    for (const file of ['echarts-series-selection.js', 'echarts-category-navigation.js', 'echarts-source-controls.js']) {
        vm.runInNewContext(fs.readFileSync(path.join(root, 'libs', file), 'utf8'), { window, document });
    }
    vm.runInNewContext(source, { window, document });
    return { get option() { return option; }, chartElement, errorElement, emptyElement, warningElement, window,
        element: suffix => elements['echarts-funnel-' + suffix],
        click: suffix => elements['echarts-funnel-' + suffix].listeners.click(),
        resize: (nextWidth, nextHeight) => {
            chartElement.clientWidth = nextWidth; chartElement.clientHeight = nextHeight;
            listeners.resize();
        } };
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
const manyModel = { ...model, funnel: { ...model.funnel, enableSourceSelection: true,
    data: Array.from({ length: 32 }, (_, index) => ({ id: 'many-' + index, label: index < 2 ? '<Same>' : 'Stage ' + index,
        value: index === 31 ? 1000 : 10 + index, unit: 'W', decimals: 1,
        initiallyVisible: index !== 31, color: index === 25 ? '#123456' : '' })) } };
for (const mode of ['symcon', 'ipsview']) {
    for (const orientation of ['vertical', 'horizontal']) {
        const current = { ...manyModel, funnel: { ...manyModel.funnel, style: { ...model.funnel.style, orientation } } };
        const result = render(current, mode, true, 450, 400);
        assert.equal(result.element('controls').hidden, false, 'Large Funnels need reachable stage pages.');
        assert.ok(result.option.series[0].data.length < 16, 'Paging must reserve readable stage extents.');
        assert.equal(result.option.series[0].max, 1000, 'Hidden maximum must still determine the complete width scale.');
        const reached = new Set();
        for (let page = 0; page < 32; page++) {
            const series = result.option.series[0];
            assert.equal(series.max, 1000);
            const engine = echarts.init(null, null, { renderer: 'svg', ssr: true, width: 450, height: 400 });
            engine.setOption(result.option);
            const data = engine.getModel().getSeriesByIndex(0).getData();
            for (let index = 0; index < data.count(); index++) {
                const item = series.data[index];
                reached.add(item.id);
                const points = data.getItemLayout(index).points;
                assert.ok(points.flat().every(Number.isFinite));
                const span = orientation === 'vertical' ? 450 - series.left - series.right : 400 - series.top - series.bottom;
                const actualWidth = Math.hypot(points[0][0] - points[1][0], points[0][1] - points[1][1]);
                assert.ok(Math.abs(actualWidth - item.value / 1000 * span) < 0.001, 'Every page retains the full-model scale.');
                if (item.id === 'many-25') { assert.equal(item.itemStyle.color, '#123456'); }
            }
            engine.dispose();
            if (result.element('next').disabled) { break; }
            result.click('next');
        }
        assert.equal(reached.size, 31, 'All initially visible stages must be reachable.');
        result.click('all');
        assert.equal(result.element('range').textContent.endsWith('/ 32'), true);
        const anchoredId = result.option.series[0].data[0].id;
        const anchoredColor = result.option.series[0].data[0].itemStyle.color;
        const refreshed = { ...current, theme: 'auto', funnel: { ...current.funnel,
            data: current.funnel.data.slice().reverse().map(item => ({ ...item, label: 'Renamed' })) } };
        result.window.handleMessage({ status: 'ready', chart: refreshed });
        const anchored = result.option.series[0].data.find(item => item.id === anchoredId);
        assert.ok(anchored, 'The current anchor must survive reorder and rename.');
        assert.equal(anchored.itemStyle.color, anchoredColor, 'Palette assignment is tied to ID across refresh.');
        result.click('inverse');
        assert.equal(result.chartElement.hidden, true);
        assert.equal(result.emptyElement.textContent, 'No sources selected');
        assert.equal(result.element('controls').hidden, false, 'Empty selection remains recoverable.');
        result.click('all');
        assert.equal(result.chartElement.hidden, false);
        assert.equal(result.option.series[0].max, 1000);
        assert.ok(result.option.series[0].data.length > 1, 'Recovery must measure the restored chart even without ResizeObserver.');
        const checkboxes = result.element('sources').querySelectorAll('input');
        assert.equal(checkboxes.length, 32);
        assert.equal(result.element('sources').children[0].children[1].textContent, 'Renamed', 'Picker text remains plain text.');
        const one = checkboxes.find(checkbox => checkbox.value === 'many-25');
        result.click('inverse');
        one.checked = true;
        one.listeners.change();
        assert.equal(result.option.series[0].data.length, 1);
        assert.equal(result.option.series[0].data[0].id, 'many-25');
        assert.equal(result.option.series[0].data[0].itemStyle.color, '#123456');
        assert.equal(result.option.series[0].max, 1000, 'A single selected stage must not expand to full width.');
        result.click('all');
        const beforeResize = result.option.series[0].data[0].id;
        result.resize(800, 650);
        assert.ok(result.option.series[0].data.some(item => item.id === beforeResize), 'Resize retains the stage anchor.');
        const removeId = result.option.series[0].data[0].id;
        const removed = { ...refreshed, funnel: { ...refreshed.funnel, data: refreshed.funnel.data.filter(item => item.id !== removeId) } };
        result.window.handleMessage({ status: 'ready', chart: removed });
        assert.ok(!result.option.series[0].data.some(item => item.id === removeId));
        assert.equal(result.element('sources').querySelectorAll('input').length, 31);
    }
}
const selectedZero = render({ ...model, funnel: { ...model.funnel, enableSourceSelection: true,
    data: model.funnel.data.map(item => ({ ...item, initiallyVisible: item.value === 0 })) } });
assert.equal(selectedZero.emptyElement.textContent, 'All selected stage values are zero.');
assert.equal(selectedZero.element('controls').hidden, false);
selectedZero.click('all');
assert.equal(selectedZero.chartElement.hidden, false);
const oldSixteen = render({ ...model, funnel: { ...model.funnel, data: Array.from({ length: 16 }, (_, index) => ({ ...model.funnel.data[0], id: 'old-' + index })) } });
assert.equal(oldSixteen.option.series[0].data.length, 16);
assert.equal(oldSixteen.element('controls').hidden, true, 'Legacy small lists do not gain a footer.');
const unselected32 = render({ ...manyModel, funnel: { ...manyModel.funnel, enableSourceSelection: false } });
assert.equal(unselected32.element('selection').hidden, true);
assert.equal(unselected32.element('controls').hidden, false);
assert.equal(unselected32.option.series[0].max, 1000, 'An off-page maximum must remain in the scale.');
for (const orientation of ['vertical', 'horizontal']) {
    const smaller = render({ ...manyModel, funnel: { ...manyModel.funnel, style: { ...model.funnel.style, orientation, labelFontSizePercent: 50 } } });
    const larger = render({ ...manyModel, funnel: { ...manyModel.funnel, style: { ...model.funnel.style, orientation, labelFontSizePercent: 200 } } });
    assert.ok(larger.option.series[0].data.length < smaller.option.series[0].data.length,
        'Larger text must reduce the page capacity in both orientations.');
}
const allZero32 = render({ ...manyModel, funnel: { ...manyModel.funnel,
    data: manyModel.funnel.data.map(item => ({ ...item, value: 0 })) } });
assert.equal(allZero32.emptyElement.textContent, 'All stage values are zero.');
assert.equal(allZero32.element('controls').hidden, false);
const zeroPageModel = { ...manyModel, funnel: { ...manyModel.funnel, enableSourceSelection: false,
    data: manyModel.funnel.data.map((item, index) => ({ ...item, value: index < 20 ? 0 : 100 })) } };
const zeroPage = render(zeroPageModel, 'symcon', true, 400, 400);
assert.equal(zeroPage.emptyElement.hidden, true, 'A zero-only page must not claim that every selected stage is zero.');
assert.equal(zeroPage.chartElement.hidden, false);
assert.equal(zeroPage.option.series[0].max, 100);
assert.ok(zeroPage.option.series[0].data.every(item => item.value === 0), 'Legitimate zero stages are not replaced or omitted.');
const chosen = render(manyModel);
const chosenBoxes = chosen.element('sources').querySelectorAll('input');
chosenBoxes[0].checked = false;
chosenBoxes[0].listeners.change();
chosen.click('next');
const retainedIds = chosen.option.series[0].data.map(item => item.id).join(',');
chosen.window.handleMessage({status: 'error', error: 'The Funnel values could not be loaded.'});
chosen.window.handleMessage({status: 'error', error: 'The Funnel values could not be loaded.'});
assert.equal(chosen.warningElement.hidden, false);
assert.equal(chosen.element('controls').hidden, false);
chosen.window.handleMessage({status: 'ready', chart: manyModel});
assert.equal(chosen.option.series[0].data.map(item => item.id).join(','), retainedIds, 'Recovery preserves page and ID selection.');
assert.equal(chosen.element('sources').querySelectorAll('input')[0].checked, false);
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
    const polygons=[...svg.matchAll(/<polygon data-stage="(\d+)"[^>]*? points="([^"]+)"/g)];
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

