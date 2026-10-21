'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const root = path.join(__dirname, '..');
const source = fs.readFileSync(path.join(root, 'EChartsCandlestick/visualization/app.js'), 'utf8');
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
            options: { echartsThemes: { auto: palette, dark: palette }, tileHeaderVisible: mode === 'symcon' && (headerVisible), tileHeaderInset: mode === 'symcon' && (headerVisible) ? 58 : 0,
                adaptToBackground: mode === 'ipsview' } },
        echarts: { init: () => chart }, addEventListener: () => {}, getComputedStyle: () => ({ color: '' })
    };
    const document = {
        body: { appendChild: () => {} }, createElement: () => ({ style: {}, remove: () => {} }),
        getElementById: id => ({ 'echarts-candlestick-root': rootElement, 'echarts-candlestick-chart': chartElement,
            'echarts-candlestick-error': errorElement, 'echarts-candlestick-empty': emptyElement,
            'echarts-candlestick-warning': warningElement })[id]
    };
    vm.runInNewContext(designSource, { window });
    vm.runInNewContext(source, { window, document });
    return { option, chartElement, errorElement, emptyElement, warningElement, window };
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
