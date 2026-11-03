(function (root) {
    'use strict';
    function build(state, width, height) {
        const f = root.SYMC_JSLIVE_FORMAT, c = state.settings, data = state.data, swap = !!c.axes_swap;
        const option = f.base(state, width, height), box = option.__layout; delete option.__layout;
        const map = {}, labels = {}, positions = {left:0, right:0, top:0, bottom:0};
        const axes = data.axes.map(function (axis, index) {
            map[axis.Ident] = index;
            const native = axis.native && axis.native.parameters || {};
            let position = axis.Side || 'left'; if (swap) { position = position === 'right' ? 'top' : 'bottom'; }
            const offset = positions[position]++ * 54;
            const boolLabels = axis.booleanLabels;
            const format = axis.native || {parameters:{DIGITS:c.data_precision}};
            const result = {id: axis.Ident, type: axis.Bool ? 'category' : 'value', position: position, offset: offset,
                show: c.axes_display, inverse: !swap && !!axis.Bool, data: axis.Bool ? boolLabels : undefined,
                scale: axis.beginAtZero < 0 ? !c.axes_beginAtZero : axis.beginAtZero !== 1,
                axisLine: {show:c.axes_drawBorder, lineStyle:{color:f.color(c.axes_color, c.axes_colorAlpha), width:c.axes_lineWidth}},
                axisTick: {show:c.axes_drawTicks}, splitLine: {show:c.axes_drawOnChartArea, lineStyle:{color:f.color(c.axes_color,c.axes_colorAlpha), width:c.axes_lineWidth}},
                axisLabel: {show:true, color:f.paint(c,'axes_fontColor','#000'), fontFamily:c.axes_fontFamily || 'Arial', fontSize:c.axes_tickfontSize * (c.__fontScale || 1),
                    formatter:function (value) { return axis.Bool ? String(value) : f.format(value, format); }},
                name: axis.native && c.axes_showLabel ? (c.axes_labelText === 2 ? native.SUFFIX || '' : (axis.Title || '') + (c.axes_labelText === 1 ? '(' + (native.SUFFIX || '') + ')' : '')) : '',
                nameTextStyle:{color:f.paint(c,'axes_fontColor','#000'), fontSize:c.axes_labelfontSize * (c.__fontScale || 1), fontFamily:c.axes_fontFamily || 'Arial'}};
            // JSLive only applies numeric overrides in its profile-backed axis path.
            if (axis.native && !axis.DynScale && !axis.Bool) {
                if (axis.OverrideMinMax) { result.min = axis.Minimum; result.max = axis.Maximum; }
                else if (native.MIN || native.MAX) { result.min = native.MIN; result.max = native.MAX; }
                if (axis.OverrideStepSize > 0) { result.interval = axis.OverrideStepSize; }
                else if (axis.effective && axis.effective.stepSize > 0) { result.interval = axis.effective.stepSize; }
            }
            return result;
        });
        const zone = state.timezone;
        const timeAxis = {type:'time', min:data.window.fromInclusive * 1000, max:(data.window.untilExclusive - 1) * 1000, show:c.xaxes_display,
            inverse:swap, axisLine:{show:c.axes_drawBorder, lineStyle:{color:f.color(c.axes_color,c.axes_colorAlpha),width:c.axes_lineWidth}},
            axisTick:{show:c.axes_drawTicks}, splitLine:{show:c.axes_drawOnChartArea,lineStyle:{color:f.color(c.axes_color,c.axes_colorAlpha)}},
            axisLabel:{color:f.paint(c,'axes_fontColor','#000'),fontFamily:c.axes_fontFamily || 'Arial',fontSize:c.axes_tickfontSize * (c.__fontScale || 1),hideOverlap:true,
                formatter:function (value) {
                    const period = data.window.period;
                    const opts = period === 0 ? {year:'numeric'} : period < 3 ? {month:'short',year:'numeric'} : period < 5 ? {day:'2-digit',month:'2-digit'} : period === 5 ? {hour:'2-digit'} : period === 6 ? {hour:'2-digit',minute:'2-digit'} : {second:'2-digit'};
                    return new Intl.DateTimeFormat('de-DE',Object.assign({timeZone:zone},opts)).format(new Date(value));
                }}};
        if (c.xaxes_overrideDynamic) {
            const override = c.xaxes_override_list.find(function (row) {return row.ID === data.window.period;});
            if (override && override.maxTicksLimit > 0) { timeAxis.splitNumber = override.maxTicksLimit; }
            if (override && override.stepSize > 0) { timeAxis.minInterval = [365*86400000,30*86400000,30*86400000,86400000,86400000,3600000,60000,1000][data.window.period] * override.stepSize; }
        }
        option.grid = {left:box.left + (swap ? 34 : 52 * Math.max(1,positions.left)),right:box.right + (swap ? 12 : 52 * positions.right),
            top:box.top + (swap ? 32 * positions.top : 8),bottom:box.bottom + (swap ? 32 * Math.max(1,positions.bottom) : 28),containLabel:true};
        option.xAxis = swap ? axes : timeAxis; option.yAxis = swap ? timeAxis : axes;
        const visible = data.series.filter(function (series) {return series.enabled;});
        visible.forEach(function (series) {labels[series.key]=series.label;});
        option.legend.data = visible.map(function (series) {return series.key;});
        option.legend.formatter = function (key) {return labels[key] || key;};
        option.tooltip.trigger = c.tooltips_mode === 'index' ? 'axis' : 'item';
        option.tooltip.formatter = function (parameters) {
            return (Array.isArray(parameters) ? parameters : [parameters]).map(function (p) {
                const series = visible.find(function (s) {return s.key === p.seriesName;}); if (!series) {return '';}
                const value = p.value[swap ? 0 : 1];
                return f.escape(series.label) + ': ' + f.escape(typeof value === 'number' ? f.format(value,series.native) : value);
            }).join('<br>');
        };
        option.series = visible.map(function (series) {
            const s = series.settings, axis = data.axes[map[series.axis]], isBar = s.Type === 'bar';
            const dash = Array.isArray(s.Dash) ? s.Dash.slice().sort(function (a,b) {return a.Order-b.Order;}).map(function (item) {return item.Length;}) : 'solid';
            const point = s.PointStyle || c.points_Style;
            const result = {id:series.key,name:series.key,type:isBar?'bar':'line',
                xAxisIndex:swap?map[series.axis]:0,yAxisIndex:swap?0:map[series.axis],
                data:series.points.map(function (p) {return swap?[p.y,p.x]:[p.x,p.y];}),
                stack:s.StackGroup>0?series.axis+':'+s.StackGroup:undefined,
                lineStyle:{color:f.paint(s,'BorderColor','#000'),width:s.BorderWidth,type:dash},
                itemStyle:{color:f.paint(s,isBar?'BackgroundColor':'BorderColor','#000'),borderColor:f.paint(s,'BorderColor'),borderWidth:isBar?s.BorderWidth:0},
                areaStyle:!isBar && s.BackgroundColor>=0 ? {color:f.paint(s,'BackgroundColor')} : undefined,
                symbol:f.symbol(point),
                symbolSize:2 * (s.PointRadius>=0?s.PointRadius:c.point_radius),showSymbol:true,
                step:axis.Bool?'end':false,smooth:axis.Bool?false:(s.MonotoneInterpolationMode?true:(s.Tension || false)),smoothMonotone:s.MonotoneInterpolationMode?'x':undefined,
                connectNulls:false,label:Object.assign(f.dataLabel(c,s),{
                    formatter:function (p) {const v=p.value[swap?0:1];return typeof v==='number'?f.dataLabelValue(v,series.native,c,s):String(v);}})};
            return result;
        });
        return option;
    }
    root.SYMC_JSLIVE_OPTION=build;
    if (typeof module==='object' && module.exports) {module.exports=build;}
}(typeof window==='object'?window:globalThis));
