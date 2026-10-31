(function (root) {
    'use strict';
    function build(state,width,height) {
        const f=root.SYMC_JSLIVE_FORMAT,c=state.settings,data=state.data,option=f.base(state,width,height),box=option.__layout;delete option.__layout;
        const left=box.left+Math.min(70,width*0.13),right=box.right+Math.min(70,width*0.13);
        const radius=Math.max(1,Math.min(width-left-right,height-box.top-box.bottom-40)/2);
        const maximum=Math.max(0,...data.series.flatMap(function (series) {return series.values;}));
        const labels=data.alias.length?data.alias:data.labels;
        const labelMap={}; data.series.forEach(function (series) {labelMap[series.key]=series.label;});
        option.legend.data=data.series.map(function (series) {return series.key;});option.legend.formatter=function (key) {return labelMap[key] || key;};
        option.radar={center:[(left+width-right)/2,(box.top+height-box.bottom)/2],radius:radius,shape:'polygon',
            indicator:labels.map(function (label) {return {name:String(label),max:maximum>0?maximum*1.05:1};}),
            axisName:{show:c.axes_display && c.axes_pointLabels_display,color:f.color(c.axes_pointLabels_color,1,'#000'),fontSize:c.axes_pointLabels_fontSize * (c.__fontScale||1),fontFamily:c.axes_pointLabels_fontFamily || 'Arial'},
            axisLine:{show:c.axes_display && c.axes_angleLines_display,lineStyle:{color:f.color(c.axes_angleLines_color,1,'#000'),width:c.axes_angleLines_lineWidth}},
            splitLine:{show:c.axes_display && c.axes_gridLines_display,lineStyle:{color:f.color(c.axes_gridLines_color,1,'#000'),width:c.axes_gridLines_lineWidth}},
            splitArea:{show:false}};
        option.tooltip.trigger='item';option.tooltip.formatter=function (p) {
            const series=data.series.find(function (s) {return s.key===p.data.id;});if(!series){return '';}
            return f.escape(series.label)+'<br>'+labels.map(function (label,index) {return f.escape(label)+': '+f.escape(f.format(series.values[index],series.native));}).join('<br>');
        };
        option.series=[{id:'historical-classes',type:'radar',data:data.series.map(function (series) {
            const s=series.settings;return {id:series.key,name:series.key,value:series.values,
                lineStyle:{color:f.paint(s,'BorderColor','#000'),width:s.BorderWidth,type:Array.isArray(s.Dash)?s.Dash.slice().sort(function(a,b){return a.Order-b.Order;}).map(function(d){return d.Length;}):'solid'},
                areaStyle:s.BackgroundColor>=0?{color:f.paint(s,'BackgroundColor')}:undefined,
                itemStyle:{color:f.paint(s,'BorderColor','#000')},symbol:f.symbol(s.PointStyle || c.points_Style),
                symbolSize:2*(s.PointRadius>=0?s.PointRadius:c.point_radius),
                label:Object.assign(f.dataLabel(c,s),{formatter:function(p){return f.dataLabelValue(Array.isArray(p.value)?p.value[p.dimensionIndex||0]:p.value,series.native,c,s);}})};
        })}];
        if(c.axes_display){
            const center=option.radar.center,max=option.radar.indicator[0].max,steps=5;
            option.series.push({id:'historical-radar-ticks',type:'custom',coordinateSystem:'none',silent:true,tooltip:{show:false},
                data:Array.from({length:steps},function(_,index){return index+1;}),renderItem:function(params,api){
                    const index=api.value(0);return {type:'text',style:{x:center[0],y:center[1]-radius*index/steps,
                        text:f.number(max*index/steps,c.data_precision,',',''),fill:f.color(c.axes_ticks_fontColor,1,'#000'),
                        backgroundColor:f.paint(c,'axes_ticks_backdropColor'),fontSize:c.axes_ticks_fontSize*(c.__fontScale||1),
                        fontFamily:c.axes_ticks_fontFamily||'Arial',align:'center',verticalAlign:'middle',padding:2}};
                }});
        }
        return option;
    }
    root.SYMC_JSLIVE_OPTION=build;
    if(typeof module==='object'&&module.exports){module.exports=build;}
}(typeof window==='object'?window:globalThis));
