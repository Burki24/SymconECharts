(function (root) {
    'use strict';
    function build(state, width, height) {
        const f = root.SYMC_JSLIVE_FORMAT, c = state.settings, option = f.base(state, width, height), box = option.__layout;
        delete option.__layout;
        const rings = state.data.rings;
        const span = Math.min(360, Math.max(0, c.rotation_length));
        const start = (c.rotation_start - 90) * Math.PI / 180;
        const extent = [start, start + span * Math.PI / 180];
        for (let a = Math.ceil(start / (Math.PI / 2)) * Math.PI / 2; a < extent[1]; a += Math.PI / 2) { extent.push(a); }
        const cutout = c.type === 'pie' ? 0 : Math.max(0, Math.min(99, rings[0].settings.cutout < 0 ? 50 : rings[0].settings.cutout)) / 100;
        const coords = extent.flatMap(function (angle) { return [[Math.cos(angle), Math.sin(angle)], [cutout * Math.cos(angle), cutout * Math.sin(angle)]]; });
        if (span === 360) { coords.push([-1,-1], [1,1]); }
        const xs = coords.map(function (p) { return p[0]; }), ys = coords.map(function (p) { return p[1]; });
        const x0 = Math.min.apply(null, xs), x1 = Math.max.apply(null, xs), y0 = Math.min.apply(null, ys), y1 = Math.max.apply(null, ys);
        const plotWidth = Math.max(1, width - box.left - box.right), plotHeight = Math.max(1, height - box.top - box.bottom);
        const radius = Math.max(1, Math.min(plotWidth / Math.max(0.01, x1 - x0), plotHeight / Math.max(0.01, y1 - y0)) / 2 * 1.9);
        const center = [box.left + plotWidth / 2 - (x0 + x1) / 2 * radius, box.top + plotHeight / 2 - (y0 + y1) / 2 * radius];
        const labelMap = {}, sourceMap = {};
        rings.forEach(function (ring) { ring.items.forEach(function (item) { labelMap['variable:' + item.variableID] = item.label; sourceMap[item.key] = item; }); });
        option.legend.data = Object.keys(labelMap);
        option.legend.formatter = function (key) { return labelMap[key] || key; };
        option.tooltip.trigger = 'item';
        option.tooltip.formatter = function (p) {
            const item = sourceMap[p.data.id];
            return item ? f.escape(item.label) + ': ' + f.escape(f.format(item.originalValue, item.native)) : '';
        };
        option.series = rings.map(function (ring, index) {
            const r = ring.settings;
            const ownCutout = c.type === 'pie' ? 0 : (r.cutout < 0 ? 0.5 : r.cutout / 100);
            const thickness = radius * (1 - ownCutout) / rings.length;
            const outer = radius - thickness * index;
            const inner = Math.max(0, outer - thickness), total = ring.items.reduce(function(sum,item){return sum+item.value;},0);
            let cursor = start;
            const angles = ring.items.map(function(item){const arc=total?item.value/total*span*Math.PI/180:0,mid=cursor+arc/2;cursor+=arc;return mid;});
            // The source anchor is a radius within this ring, independent of the
            // text's alignment. Keep the original segment order and values.
            function labelLayout(p) {
                // The native inside-label rectangle follows filtered legend
                // geometry. Use its direction, not the unfiltered value angles.
                const x=p.labelRect.x+p.labelRect.width/2-center[0],y=p.labelRect.y+p.labelRect.height/2-center[1];
                const angle=Number.isFinite(x)&&Number.isFinite(y)&&Math.hypot(x,y)>0?Math.atan2(y,x):angles[p.dataIndex],vx=Math.cos(angle),vy=Math.sin(angle);
                const anchor=r.datalabels_anchoring||c.datalabels_anchoring||'center';
                const distance=anchor==='start'?inner:anchor==='end'?outer:(inner+outer)/2;
                let dx=0,dy=0;const align=c.datalabels_align||'center';
                if(align==='start'||align==='end'){const sign=align==='start'?-1:1;dx=vx*sign;dy=vy*sign;}
                else if(align==='top'){dy=-1;}else if(align==='bottom'){dy=1;}else if(align==='left'){dx=-1;}else if(align==='right'){dx=1;}
                const scale=1/Math.max(Math.abs(dx),Math.abs(dy),1e-12),offset=c.datalabels_offset==null?4:c.datalabels_offset;
                return {x:center[0]+vx*distance+dx*(p.labelRect.width/2*scale+offset),
                    y:center[1]+vy*distance+dy*(p.labelRect.height/2*scale+offset),align:'center',verticalAlign:'middle',hideOverlap:true};
            }
            return {id: 'ring:' + ring.key, type: 'pie', name: r.Title, radius: [Math.max(0, outer - thickness), outer], center: center,
                startAngle: 90 - c.rotation_start, endAngle: 90 - c.rotation_start - span, clockwise: true,
                stillShowZeroSum: false, avoidLabelOverlap: true, labelLayout: labelLayout,labelLine:{show:false},
                label: Object.assign(f.dataLabel(c,r),{
                    position:'inside',
                    formatter: function (p) { const item = sourceMap[p.data.id]; if (!item || item.value === 0) { return ''; }
                        return f.dataLabelValue(item.value,item.native,c,r); }}),
                data: ring.items.map(function (item) { return {id: item.key, name: 'variable:' + item.variableID, value: item.value,
                    label:Object.assign(f.dataLabel(c,r,item.settings),{position:'inside',show:!!r.datalabels_enable&&item.value!==0}),
                    itemStyle: {color: f.paint(item.settings, 'BackgroundColor'), borderColor: f.paint(item.settings, 'BorderColor'), borderWidth: item.settings.BorderWidth}}; })};
        });
        return option;
    }
    root.SYMC_JSLIVE_OPTION = build;
    if (typeof module === 'object' && module.exports) { module.exports = build; }
}(typeof window === 'object' ? window : globalThis));
