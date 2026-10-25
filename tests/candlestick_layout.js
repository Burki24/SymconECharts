'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const root = path.join(__dirname, '..');
const source = fs.readFileSync(path.join(root, 'EChartsCandlestick/visualization/app.js'), 'utf8');
const designSource = fs.readFileSync(path.join(root, 'libs/echarts-design.js'), 'utf8');
const zoomSource = fs.readFileSync(path.join(root, 'libs/echarts-zoom.js'), 'utf8');
const palette = { background: '#101114', text: '#f4f5f7', muted: '#969aa2', border: '#737780', accent: '#55cbb5' };

function render(model, mode = 'symcon', headerVisible = true, width = 900, engine = null, startup = false) {
    const chartElement = { style: {}, hidden: false, clientWidth: width, clientHeight: 500, addEventListener: () => {} };
    const rootElement = { style: {} };
    const errorElement = { hidden: true, textContent: '' };
    const emptyElement = { hidden: true, textContent: '' };
    const warningElement = { hidden: true, textContent: '' };
    let option;
    let wheel;
    const events = {};
    chartElement.addEventListener = (name, callback) => { if (name === 'wheel') { wheel = callback; } };
    chartElement.getBoundingClientRect = () => ({left:0,top:0,width:chartElement.clientWidth,height:chartElement.clientHeight});
    const chart = { setOption: (next, replace) => { if (replace) {option = next;} if (engine) { engine.setOption(next, replace); } },
        on: (name, listener) => { if (engine) { engine.on(name, listener); } },
        getOption: () => engine ? engine.getOption() : option,
        getModel: engine ? () => engine.getModel() : undefined,
        dispatchAction: action => { if (engine) { engine.dispatchAction(action); } },
        clear: () => { if (engine) { engine.clear(); } },
        resize: () => { if (engine) { engine.resize({width:chartElement.clientWidth,height:chartElement.clientHeight}); } }, dispose: () => {} };
    let resolveStartup, startupJSON;
    const window = {
        SYMC_VISUALIZATION: { mode, state: { status: 'ready', chart: model }, translations: {},
            options: { echartsThemes: { auto: palette, dark: palette }, tileHeaderVisible: mode === 'symcon' && (headerVisible), tileHeaderInset: mode === 'symcon' && (headerVisible) ? 58 : 0,
                adaptToBackground: mode === 'ipsview' } },
        echarts: { init: () => chart }, addEventListener: (name, callback) => { events[name]=callback; }, getComputedStyle: () => ({ color: '' })
    };
    if (startup) {
        const zlib=require('node:zlib');
        startupJSON=JSON.stringify(window.SYMC_VISUALIZATION.state);
        window.SYMC_VISUALIZATION.state=null;
        window.SYMC_VISUALIZATION.options.compressedStateGzipBase64=zlib.gzipSync(startupJSON).toString('base64');
        window.atob=value=>Buffer.from(value,'base64').toString('binary');
        window.Blob=class {constructor(parts){this.bytes=parts[0];}stream(){return {pipeThrough:()=>this.bytes};}};
        window.DecompressionStream=class {};
        window.Response=class {constructor(bytes){startupJSON=zlib.gunzipSync(Buffer.from(bytes)).toString('utf8');}
            text(){return new Promise(resolve=>{resolveStartup=resolve;});}};
    }
    const document = {
        body: { appendChild: () => {} }, createElement: () => ({ style: {}, remove: () => {} }),
        getElementById: id => ({ 'echarts-candlestick-root': rootElement, 'echarts-candlestick-chart': chartElement,
            'echarts-candlestick-error': errorElement, 'echarts-candlestick-empty': emptyElement,
            'echarts-candlestick-warning': warningElement })[id]
    };
    vm.runInNewContext(designSource, { window });
    vm.runInNewContext(zoomSource, { window });
    vm.runInNewContext(fs.readFileSync(path.join(root, 'libs/echarts-startup.js'),'utf8'), { window });
    vm.runInNewContext(source, { window, document });
    return { get option() { return option; }, chartElement, rootElement, errorElement, emptyElement, warningElement, window, events,
        resolveStartup:()=>resolveStartup(startupJSON), wheel: event => wheel(event), resize: width => { chartElement.clientWidth=width;events.resize(); } };
}



const model = { family: 'candlestick', theme: 'dark', candlestick: {title: 'Actual logged temperatures', unit: '°C', sourceLabel: '<Room>',
    data: [
        {id:'1',label:'2026-10-16',statistics:[2,3,-4,9],count:4,unit:'°C',decimals:1,firstTimestamp:1792101600,lastTimestamp:1792112400},
        {id:'2',label:'2026-10-17',statistics:[7,4,2,8],count:6,unit:'°C',decimals:1,firstTimestamp:1792101600,lastTimestamp:1792112400},
        {id:'3',label:'2026-10-18',statistics:[5,5,5,5],count:1,unit:'°C',decimals:1,firstTimestamp:1792101600,lastTimestamp:1792101600},
        {id:'4',label:'2026-10-19',statistics:null,count:0,unit:'°C',decimals:1}
    ],
    style:{orientation:'vertical',candleWidth:40,minCandleWidth:1,borderWidth:2,candleOpacityPercent:70,
        riseColor:'#00aa88',fallColor:'#bb2233',dojiColor:'#eeeeee',animationEnabled:false}
}};
const tile = render(model);
assert.equal(tile.chartElement.style.top,'58px');
assert.equal(tile.option.series[0].type,'candlestick');
assert.equal(tile.option.series[0].large,false);
assert.equal(tile.option.series[0].data[0].value.join(','),'2,3,-4,9');
assert.equal(tile.option.yAxis.scale,true);
assert.equal(tile.option.series[0].itemStyle.color,'#00aa88');
assert.equal(tile.option.series[0].itemStyle.color0,'#bb2233');
assert.equal(tile.option.series[0].itemStyle.borderColorDoji,'#eeeeee');
assert.equal(tile.option.tooltip.formatter({dataIndex:3}),'');
assert.match(tile.option.tooltip.formatter({dataIndex:0}), /&lt;Room&gt;.*First value: 2 °C.*Last value: 3 °C.*Minimum: -4 °C.*Maximum: 9 °C.*Logged samples: 4/);
assert.ok(tile.option.grid.top>tile.option.title.top+tile.option.title.textStyle.fontSize);
assert.equal(render(model,'symcon',false).chartElement.style.top,'0px');
assert.equal(render(model,'ipsview').chartElement.style.top,'0px');
const empty=render({...model,candlestick:{...model.candlestick,data:[model.candlestick.data[3]]}});
assert.equal(empty.emptyElement.hidden,false);
tile.window.handleMessage({status:'error',error:'The Candlestick values could not be loaded.'});
assert.equal(tile.chartElement.hidden,false);
tile.window.handleMessage({status:'error',error:'The Candlestick values could not be loaded.'});
assert.equal(tile.warningElement.hidden,false);
tile.window.handleMessage({status:'error',error:'Configure valid Candlestick archive variables.'});
assert.equal(tile.chartElement.hidden,true);
tile.window.handleMessage({status:'ready',chart:model});
assert.equal(tile.chartElement.hidden,false);
const echarts=require(path.join(root,'.tools/echarts-runtime/node_modules/echarts'));
for(const orientation of ['vertical','horizontal']){
    for(const width of [300,900]){
        const rendered=render({...model,candlestick:{...model.candlestick,style:{...model.candlestick.style,orientation,
            shadowBlur:8,borderType:'dashed',gridLineType:'dotted',titleBold:false,axisLabelRotate:45}}},'symcon',true,width);
        const engine=echarts.init(null,null,{renderer:'svg',ssr:true,width,height:500});
        engine.setOption(rendered.option);
        const series=engine.getModel().getSeriesByIndex(0);
        const data=series.getData();
        assert.equal(data.count(),4);
        for(const i of [0,1,2]){
            assert.ok(data.getItemLayout(i).ends.every(p=>p.every(Number.isFinite)));
        }
        assert.equal(data.hasValue(3),false,'Missing interval must not become a zero candle.');
        assert.equal(data.getItemVisual(0,'style').fill,'#00aa88');
        assert.equal(data.getItemVisual(1,'style').fill,'#bb2233');
        assert.equal(data.getItemVisual(2,'style').stroke,'#eeeeee');
        assert.match(engine.renderToSVGString(),/<path/);
        engine.dispose();
    }
}
const crowded=render({...model,candlestick:{...model.candlestick,data:Array.from({length:720},(_,i)=>({...model.candlestick.data[0],id:''+i,label:'2026-10-17 12:00 +02:00'}))}},'symcon',true,300);
assert.ok(crowded.option.series[0].barMinWidth<=crowded.option.series[0].barMaxWidth);
assert.equal(crowded.option.xAxis.axisLabel.interval,'auto');
console.log('Candlestick renderer, sparse data and real ECharts geometry verified.');

const extendedStyle = { ...model.candlestick.style, fixedCandleWidth: 18, riseHollow: true, fallHollow: true,
    riseBorderColor:'#123456',fallBorderColor:'#654321',hoverBorderWidth:5,hoverBorderColor:'#fedcba',hoverOpacityPercent:95,
    axisLineColor:'#abcdef',axisLineType:'dashed',axisLabelMargin:16,reverseTimeAxis:true,includeZero:true,
    fontFamily:'Georgia, serif',titleItalic:true,axisLabelItalic:true };
const extendedModel={...model,candlestick:{...model.candlestick,style:extendedStyle}};
const extended=render(extendedModel);
assert.equal(extended.option.series[0].barWidth,18);
assert.equal(extended.option.series[0].itemStyle.color,'transparent');
assert.equal(extended.option.series[0].itemStyle.color0,'transparent');
assert.equal(extended.option.series[0].itemStyle.borderColor,'#123456');
assert.equal(extended.option.series[0].itemStyle.borderColor0,'#654321');
assert.equal(extended.option.series[0].emphasis.itemStyle.borderWidth,5);
assert.equal(extended.option.series[0].emphasis.itemStyle.borderColor,'#fedcba');
assert.equal(extended.option.series[0].emphasis.itemStyle.borderColor0,'#fedcba');
assert.equal(extended.option.series[0].emphasis.itemStyle.borderColorDoji,'#fedcba');
assert.equal(extended.option.series[0].emphasis.itemStyle.opacity,0.95);
assert.equal(extended.option.xAxis.inverse,true);
assert.equal(extended.option.xAxis.axisLine.lineStyle.color,'#abcdef');
assert.equal(extended.option.xAxis.axisLine.lineStyle.type,'dashed');
assert.equal(extended.option.xAxis.axisLabel.margin,16);
assert.equal(extended.option.yAxis.scale,false);
assert.equal(extended.option.textStyle.fontFamily,'Georgia, serif');
assert.equal(extended.option.title.textStyle.fontStyle,'italic');
assert.equal(extended.option.xAxis.axisLabel.fontStyle,'italic');
assert.equal(extended.option.yAxis.axisLabel.fontStyle,'italic');
const extendedEngine=echarts.init(null,null,{renderer:'svg',ssr:true,width:900,height:500});
extendedEngine.setOption(extended.option);
const extendedData=extendedEngine.getModel().getSeriesByIndex(0).getData();
assert.equal(extendedData.getLayout('candleWidth'),18);
assert.equal(extendedData.getItemVisual(0,'style').fill,'transparent');
assert.equal(extendedData.getItemVisual(1,'style').stroke,'#654321');
assert.equal(extendedData.getItemGraphicEl(0).states.emphasis.style.lineWidth,5);
assert.equal(extendedData.getItemGraphicEl(0).states.emphasis.style.opacity,0.95);
assert.ok(Math.abs(Math.abs(extendedData.getItemLayout(0).ends[1][0]-extendedData.getItemLayout(0).ends[0][0])-18)<=1,
    'ECharts may snap candle edges by up to one pixel for crisp strokes.');
assert.ok(extendedData.getItemLayout(0).ends[0][0]>extendedData.getItemLayout(1).ends[0][0]);
assert.match(extendedEngine.renderToSVGString(),/Georgia/);
extendedEngine.dispose();
const unchanged=render(model);
assert.equal(unchanged.option.series[0].barWidth,undefined);
assert.equal(unchanged.option.yAxis.scale,true);
assert.equal(unchanged.option.series[0].emphasis.itemStyle.opacity,undefined);
for(const includeZero of [false,true]){
    const positive=render({...model,candlestick:{...model.candlestick,data:[{...model.candlestick.data[0],statistics:[10,12,9,14]}],
        style:{...model.candlestick.style,includeZero}}});
    const engine=echarts.init(null,null,{renderer:'svg',ssr:true,width:900,height:500});
    engine.setOption(positive.option);
    const extent=engine.getModel().getComponent('yAxis').axis.scale.getExtent();
    assert.equal(includeZero?extent[0]===0:extent[0]>0,true,'Zero inclusion is a visual scale choice only.');
    engine.dispose();
}
const narrowFixed=render({...extendedModel,candlestick:{...extendedModel.candlestick,
    data:Array.from({length:720},(_,i)=>({...model.candlestick.data[0],id:''+i}))}},'symcon',true,300);
assert.ok(narrowFixed.option.series[0].barWidth<=narrowFixed.option.series[0].barMaxWidth,'Fixed width must shrink to keep dense charts responsive.');
for(const orientation of ['horizontal','vertical']){
    const rendered=render({...extendedModel,candlestick:{...extendedModel.candlestick,
        style:{...extendedStyle,orientation,axisFontSizePercent:200,titleFontSizePercent:150,axisLabelMargin:30}}},'symcon',true,300);
    const engine=echarts.init(null,null,{renderer:'svg',ssr:true,width:300,height:500});
    engine.setOption(rendered.option);
    const data=engine.getModel().getSeriesByIndex(0).getData();
    for(const index of [0,1,2]){
        assert.ok(data.getItemLayout(index).ends.every(point=>point.every(Number.isFinite)));
    }
    assert.equal((orientation==='horizontal'?rendered.option.yAxis:rendered.option.xAxis).inverse,true);
    assert.ok(engine.renderToSVGString().includes('Georgia'));
    engine.dispose();
}
console.log('Extended Candlestick design verified.');

const nativeWindow = {};
vm.runInNewContext(fs.readFileSync(path.join(root,'libs/echarts/6.1.0/echarts.candlestick.min.js'),'utf8'),
    {window:nativeWindow,navigator:{userAgent:'Node.js'},console,setTimeout,clearTimeout});
nativeWindow.echarts.use(require('../.tools/echarts-runtime/node_modules/echarts/renderers').SVGRenderer);
for (const orientation of ['vertical', 'horizontal']) {
    const data = Array.from({length: 100}, (_, i) => ({...model.candlestick.data[i % 4], id: String(i),
        statistics: i === 0 ? [-20, 60, -30, 80] : (i % 4 === 3 ? null : [10, 15, 8, 18])}));
    const zoomModel = {...model, range: {key:'7d',startTimestamp:1000,endTimestamp:2000},
        candlestick: {...model.candlestick, variableID: 12345, interval:'hour', data,
            style: {...model.candlestick.style, enableZoom:true, orientation}}};
    const engine = nativeWindow.echarts.init(null, null, {renderer:'svg',ssr:true,width:900,height:500});
    const rendered = render(zoomModel, 'ipsview', true, 900, engine);
    assert.equal(rendered.option.dataZoom.length, 2, 'Optional time zoom must have inside and slider controls.');
    assert.equal(rendered.option.dataZoom[0][orientation === 'horizontal' ? 'yAxisIndex' : 'xAxisIndex'], 0);
    assert.equal(rendered.option.dataZoom[0].filterMode, 'none');
    const axis = orientation === 'horizontal' ? 'xAxis' : 'yAxis';
    const fullExtent = engine.getModel().getComponent(axis).axis.scale.getExtent().slice();
    engine.dispatchAction({type:'dataZoom',dataZoomIndex:0,start:30,end:70});
    assert.deepEqual(engine.getModel().getComponent(axis).axis.scale.getExtent(), fullExtent,
        'Offscreen candles must continue to determine the complete value scale.');
    assert.equal(engine.getModel().getSeriesByIndex(0).getData().count(), 100);
    assert.equal(engine.getModel().getSeriesByIndex(0).getData().hasValue(3), false);
    assert.ok(engine.getModel().getComponent(orientation === 'horizontal' ? 'yAxis' : 'xAxis').axis.scale.getExtent()[0] > 0);
    rendered.window.handleMessage({status:'ready',chart:zoomModel});
    assert.equal(rendered.option.dataZoom[0].start, 30, 'Public refresh preserves the selected view.');
    assert.equal(rendered.option.dataZoom[0].end, 70);
    rendered.resize(640);
    assert.equal(rendered.option.dataZoom[0].start, 30, 'Responsive resize preserves the view.');
    rendered.window.handleMessage({status:'ready',chart:{...zoomModel,theme:'auto'}});
    assert.equal(rendered.option.dataZoom[0].start,30,'Theme recreation preserves the relative view.');
    rendered.window.handleMessage({status:'error',error:'The Candlestick values could not be loaded.'});
    rendered.window.handleMessage({status:'ready',chart:zoomModel});
    assert.equal(rendered.option.dataZoom[0].start, 30, 'Transient failures must not reset the view.');
    rendered.window.handleMessage({status:'ready',chart:{...zoomModel,candlestick:{...zoomModel.candlestick,variableID:54321}}});
    assert.equal(rendered.option.dataZoom[0].start, 0, 'Different source IDs reset the view, not source labels.');
    rendered.window.handleMessage({status:'ready',chart:zoomModel});
    engine.dispatchAction({type:'dataZoom',dataZoomIndex:0,start:20,end:60});
    rendered.window.handleMessage({status:'ready',chart:{...zoomModel,candlestick:{...zoomModel.candlestick,interval:'day'}}});
    assert.equal(rendered.option.dataZoom[0].start,0,'Interval changes reset the view.');
    rendered.window.handleMessage({status:'ready',chart:zoomModel});
    engine.dispatchAction({type:'dataZoom',dataZoomIndex:0,start:20,end:60});
    rendered.window.handleMessage({status:'ready',chart:{...zoomModel,range:{...zoomModel.range,startTimestamp:3000,endTimestamp:4000}}});
    assert.equal(rendered.option.dataZoom[0].start,0,'Non-overlapping windows reset the view.');
    rendered.window.handleMessage({status:'ready',chart:zoomModel});
    engine.dispatchAction({type:'dataZoom',dataZoomIndex:0,start:20,end:60});
    rendered.window.handleMessage({status:'ready',chart:{...zoomModel,candlestick:{...zoomModel.candlestick,data:[]}}});
    rendered.window.handleMessage({status:'ready',chart:zoomModel});
    assert.equal(rendered.option.dataZoom[0].start,0,'An empty model clears stale zoom state.');
    rendered.window.handleMessage({status:'ready',chart:model});
    assert.equal(engine.getModel().queryComponents({mainType:'dataZoom'}).length, 0, 'Disabling removes old zoom controls.');
    engine.dispose();
}
assert.equal(render(model).option.dataZoom.length,0,'Legacy designs keep zoom disabled.');
for (const interval of ['fifteen-minutes','thirty-minutes','week']) {
    for (const orientation of ['vertical','horizontal']) {
        for (const mode of ['symcon','ipsview']) {
            const labels = interval === 'week' ? ['2026-W41','2026-W42','2026-W43','2026-W44']
                : ['2026-10-25 02:00 +02:00','2026-10-25 02:30 +02:00','2026-10-25 02:00 +01:00','2026-10-25 02:30 +01:00'];
            const input = {...model,range:{key:'7d',startTimestamp:1000,endTimestamp:3000},candlestick:{...model.candlestick,
                variableID:12345,interval,data:model.candlestick.data.map((item,i)=>({...item,label:labels[i]})),
                style:{...model.candlestick.style,enableZoom:true,orientation}}};
            const engine = nativeWindow.echarts.init(null,null,{renderer:'svg',ssr:true,width:900,height:500});
            const fixture = render(input,mode,true,900,engine);
            const axis = orientation === 'horizontal' ? fixture.option.yAxis : fixture.option.xAxis;
            assert.equal(axis.axisLabel.formatter('1'),labels[0],'New interval labels keep ISO weeks and real offsets.');
            assert.ok(fixture.option.tooltip.formatter({dataIndex:0}).includes(labels[0]));
            assert.equal(engine.getModel().getSeriesByIndex(0).getData().hasValue(3),false);
            assert.ok(engine.getModel().getSeriesByIndex(0).getData().getItemLayout(0).ends.every(point=>point.every(Number.isFinite)),
                'The shipped runtime must compute finite candle geometry for every new interval.');
            engine.dispatchAction({type:'dataZoom',dataZoomIndex:0,start:20,end:70});
            fixture.window.handleMessage({status:'ready',chart:input});
            assert.equal(fixture.option.dataZoom[0].start,20,'New intervals preserve compatible zoom on refresh.');
            fixture.window.handleMessage({status:'ready',chart:{...input,candlestick:{...input.candlestick,interval:'day'}}});
            assert.equal(fixture.option.dataZoom[0].start,0,'Changing from a new interval resets the old view.');
            engine.dispose();
        }
    }
}
for (const orientation of ['vertical','horizontal']) {
    for (const inverse of [false,true]) {
        for (const mode of ['symcon','ipsview']) {
            const input = {...model,range:{key:'30d',startTimestamp:1000,endTimestamp:3000},candlestick:{...model.candlestick,
                variableID:12345,interval:'hour',data:Array.from({length:720},(_,i)=>({...model.candlestick.data[0],id:String(i)})),
                style:{...model.candlestick.style,enableZoom:true,orientation,reverseTimeAxis:inverse}}};
            const before = JSON.stringify(input);
            const engine = nativeWindow.echarts.init(null,null,{renderer:'svg',ssr:true,width:900,height:500});
            const fixture = render(input,mode,true,900,engine);
            const rect = engine.getModel().getComponent('grid').coordinateSystem.getRect();
            const initialGrid = [rect.x,rect.y,rect.width,rect.height];
            let prevented = 0;
            const event = deltaY => ({deltaY,clientX:rect.x+rect.width/2,clientY:rect.y+rect.height/2,
                preventDefault(){prevented++;},stopPropagation(){}});
            const widthBefore = engine.getModel().getSeriesByIndex(0).getData().getLayout('candleWidth');
            for(let i=0;i<10;i++){fixture.wheel(event(-100));}
            const selected = engine.getOption().dataZoom[0];
            const afterGrid = engine.getModel().getComponent('grid').coordinateSystem.getRect();
            assert.deepEqual([afterGrid.x,afterGrid.y,afterGrid.width,afterGrid.height], initialGrid, 'Zoom must not displace the chart grid.');
            assert.ok(selected.end-selected.start<20,'Wheel must zoom with the shipped runtime in both outputs/orientations.');
            assert.ok(engine.getModel().getSeriesByIndex(0).getData().getLayout('candleWidth')>widthBefore,'Zoom must widen dense candles.');
            for(let i=0;i<10;i++){fixture.wheel(event(100));}
            assert.ok(engine.getOption().dataZoom[0].end-engine.getOption().dataZoom[0].start>99.9);
            assert.equal(prevented,20,'Exactly one zoom handler must consume each chart wheel.');
            fixture.wheel({...event(-100),clientX:1,clientY:1});
            assert.equal(prevented,20,'Title and slider margins must not trigger chart zoom.');
            fixture.window.handleMessage({status:'ready',chart:input});
            assert.equal(JSON.stringify(input),before,'View changes never mutate raw statistics or metadata.');
            engine.dispose();
        }
    }
}
console.log('Optional Candlestick time zoom, shipped runtime, refresh/resize, complete scales and wheel routing verified.');

// Native multi-grid panels share time axes, never value scales or tooltip metadata.
function panelModel(count, orientation = 'vertical') {
    return {...model,range:{key:'7d',startTimestamp:100,endTimestamp:200},candlestick:{...model.candlestick,
        data:[],panels:Array.from({length:count},(_,i)=>({variableID:900+i,id:String(900+i),sourceLabel:i===1?'Humidity <room>':'Panel '+i,
            unit:i===1?'%':'°C',data:model.candlestick.data.map(item=>({...item,unit:i===1?'%':'°C',
                statistics:item.statistics?item.statistics.map(v=>v*(i+1)*10):null}))})),
        style:{...model.candlestick.style,orientation,enableZoom:true}}};
}
for (const mode of ['symcon','ipsview']) {
    for (const orientation of ['vertical','horizontal']) {
        for (const count of [2,4]) {
            const comparison=panelModel(count,orientation);
            const rendered=render(comparison,mode,true,1400);
            assert.equal(rendered.rootElement.style.top,mode==='symcon'?'58px':'0px');
            assert.equal(rendered.chartElement.style.top,'0px','Panel scrolling is clipped below the fixed tile header.');
            assert.equal(rendered.option.grid.length,count);
            assert.equal(rendered.option.series.length,count);
            assert.equal(rendered.option.title.length,count+1);
            for(const control of rendered.option.dataZoom) {
                assert.deepEqual(Array.from(control[orientation==='horizontal'?'yAxisIndex':'xAxisIndex']),Array.from({length:count},(_,i)=>i));
                assert.equal(control.filterMode,'none');
            }
            assert.match(rendered.option.tooltip.formatter({seriesIndex:1,dataIndex:0}),/Humidity &lt;room&gt;/);
            assert.match(rendered.option.tooltip.formatter({seriesIndex:1,dataIndex:0}),/%/);
            const engine=echarts.init(null,null,{renderer:'svg',ssr:true,width:1400,height:500});
            engine.setOption(rendered.option);
            const extents=[];
            for(let p=0;p<count;p++) {
                const values=engine.getModel().getSeriesByIndex(p).getData();
                assert.equal(values.hasValue(3),false);
                assert.ok(values.getItemLayout(0).ends.every(point=>point.every(Number.isFinite)));
                extents.push(engine.getModel().getComponent(orientation==='horizontal'?'xAxis':'yAxis',p).axis.scale.getExtent());
            }
            assert.notDeepEqual(extents[0],extents[1],'Numeric domains belong to each panel.');
            engine.dispatchAction({type:'dataZoom',dataZoomIndex:0,start:30,end:70});
            for(let p=0;p<count;p++) {
                const time=engine.getModel().getComponent(orientation==='horizontal'?'yAxis':'xAxis',p).axis.scale.getExtent();
                assert.deepEqual(time,engine.getModel().getComponent(orientation==='horizontal'?'yAxis':'xAxis',0).axis.scale.getExtent());
                assert.deepEqual(extents[p],engine.getModel().getComponent(orientation==='horizontal'?'xAxis':'yAxis',p).axis.scale.getExtent());
            }
            engine.dispose();
        }
    }
}
const multiEngine=echarts.init(null,null,{renderer:'svg',ssr:true,width:900,height:500});
const multiRuntime=render(panelModel(2),'ipsview',true,900,multiEngine);
const secondGrid=multiEngine.getModel().getComponent('grid',1).coordinateSystem.getRect();
let prevented=false;
multiRuntime.wheel({deltaY:-120,clientX:secondGrid.x+secondGrid.width/2,clientY:secondGrid.y+secondGrid.height/2,
    preventDefault(){prevented=true;},stopPropagation(){}});
assert.ok(prevented,'Host-aware wheel routing accepts the second panel, not only grid zero.');
assert.ok(multiEngine.getOption().dataZoom[0].end-multiEngine.getOption().dataZoom[0].start<100);
const multiSaved=multiEngine.getOption().dataZoom[0];
multiRuntime.window.handleMessage({status:'ready',chart:panelModel(2)});
assert.equal(multiEngine.getOption().dataZoom[0].start,multiSaved.start);
assert.equal(multiEngine.getOption().dataZoom[0].end,multiSaved.end);
multiRuntime.resize(1000);
assert.equal(multiEngine.getOption().dataZoom[0].start,multiSaved.start);
const oneEmpty=panelModel(2);oneEmpty.candlestick.panels[0].data=oneEmpty.candlestick.panels[0].data.map(v=>({...v,statistics:null}));
assert.equal(render(oneEmpty).chartElement.hidden,false,'An empty panel does not hide a populated comparison.');
const allEmpty=panelModel(2);for(const p of allEmpty.candlestick.panels){p.data=[];}
assert.equal(render(allEmpty).emptyElement.hidden,false);
multiEngine.dispose();
console.log('Named Candlestick panels, independent scales, shared zoom and all-grid wheel routing verified.');

async function verifyPanelStartup() {
    const delayed=render(panelModel(4),'symcon',true,900,null,true);
    assert.equal(delayed.option,undefined);
    delayed.resolveStartup();await Promise.resolve();
    assert.equal(delayed.option.series.length,4,'Lossless startup restores every panel.');
    const superseded=render(panelModel(4),'symcon',true,900,null,true);
    superseded.window.handleMessage({status:'ready',chart:panelModel(2)});
    superseded.resolveStartup();await Promise.resolve();
    assert.equal(superseded.option.series.length,2,'A delayed startup never replaces a newer full state.');
    const unloaded=render(panelModel(4),'symcon',true,900,null,true);
    unloaded.events.beforeunload();unloaded.resolveStartup();await Promise.resolve();
    assert.equal(unloaded.option,undefined,'Unload cancels panel startup.');
}
verifyPanelStartup().then(()=>console.log('Candlestick compressed startup and cancellation verified.')).catch(error=>{console.error(error);process.exitCode=1;});
