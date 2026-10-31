(function (root) {
    'use strict';
    function build(state,width,height) {
        const f=root.SYMC_JSLIVE_FORMAT,c=state.settings,d=state.data;
        const compass=c.template==='CanvasGauges-Compass',linear=c.template.indexOf('Linear')>=0,vertical=c.template.indexOf('(vertical)')>=0;
        const minimum=compass?0:c.min,maximum=compass?360:c.max;
        let ticks=compass?['N','NE','E','SE','S','SW','W','NW','N']:[c.min].concat(c.Ticks.slice().sort(function(a,b){return a.Value-b.Value;}).map(function(t){return t.Value;}),[c.max]);
        const ratio=function(value){return Math.max(0,Math.min(1,(Number(value)-minimum)/(maximum-minimum)));};
        function convert(value) {
            if(!c.AutoConvertValue || compass || value<=minimum || value>=maximum){return value;}
            let last=minimum;
            const step=(maximum-minimum)/(ticks.length-1);
            for(let i=0;i<ticks.length;i++){
                if(value===Number(ticks[i])){return minimum+i*step;}
                if(value<Number(ticks[i])){return minimum+(i-1)*step+(value-last)/(Number(ticks[i])-last)*step;}
                last=Number(ticks[i]);
            }
            return value;
        }
        const graphics=[];
        const faceW=linear&&vertical?Math.min(width-20,(height-20)/4):Math.max(1,width-20);
        const faceH=linear&&!vertical?Math.min(height-20,(width-20)/4):Math.max(1,height-20);
        const cx=width/2,cy=linear?faceH/2+10:height/2,r=Math.max(1,Math.min(faceW,faceH)/2);
        const fontScale=Math.min(faceW,faceH)/200*(c.__fontScale||1);
        const point=function(angle,rad){return [cx+Math.cos(angle)*rad,cy+Math.sin(angle)*rad];};
        const start=(compass?270:c.radial_startAngle+90)*Math.PI/180,sweep=(compass?360:c.radial_ticksAngle)*Math.PI/180;
        const plateRotation=compass&&c.animation_target==='plate'?-ratio(d.value)*Math.PI*2:0;
        const theta=function(value){return start+ratio(value)*sweep+plateRotation;};
        function gradient(name){return {type:'linear',x:0,y:0,x2:0,y2:1,colorStops:[{offset:0,color:f.paint(c,name)},{offset:1,color:f.paint(c,name+'End',f.paint(c,name))}]};}
        function line(id,a,b,color,width){graphics.push({id:id,type:'line',shape:{x1:a[0],y1:a[1],x2:b[0],y2:b[1]},style:{stroke:color,lineWidth:width}});}
        function text(id,value,x,y,size,color,family,background){graphics.push({id:id,type:'text',style:{text:String(value),x:x,y:y,fill:color,fontSize:Math.max(1,size),fontFamily:family||'Arial',align:'center',verticalAlign:'middle',backgroundColor:background,padding:background?4:0}});}
        function arc(id,from,to,radius,color,width){graphics.push({id:id,type:'arc',shape:{cx:cx,cy:cy,r:radius,startAngle:from,endAngle:to,clockwise:true},style:{stroke:color,lineWidth:width,fill:null}});}
        if(c.plate_display){graphics.push({id:'plate',type:linear?'rect':'circle',shape:linear?{x:(width-faceW)/2,y:10,width:faceW,height:faceH}:{cx:cx,cy:cy,r:r},style:{fill:gradient('plate_colorPlate')}});}
        if(compass){arc('compass-border',0,Math.PI*2,r*0.96,'#ccc',r*0.035);}
        const highlights=compass?[]:c.Highlights;
        if(!linear){
            highlights.forEach(function(h,i){let from=h.From,to=h.To;if(c.AutoConvertHighlight){from=convert(from);to=convert(to);}arc('highlight:'+i,theta(from),theta(to),r*0.82,f.color(h.HighlightColor,Math.round(h.HighlightColor_Alpha*100)/100),Math.max(1,c.ticks_highlightsWidth*r/100));});
            if(c.progressbar_display&&!compass){
                arc('track',start,start+sweep,r*0.74,f.paint(c,'progressbar_colorBar'),c.progressbar_barWidth*r/100);
                const v=theta(convert(d.value));let a=start,b=v;
                if(c.progressbar_barStartPosition==='right'){a=start+sweep-(v-start);b=start+sweep;}
                else if(c.progressbar_barStartPosition==='middle'){a=start+sweep/2;b=v;if(a>b){const x=a;a=b;b=x;}}
                arc('progress',a,b,r*0.74,f.paint(c,'progressbar_colorBarProgress'),c.progressbar_barWidth*r/100);
            }
            const minor=compass?22:c.ticks_minorTicks;
            ticks.forEach(function(t,i){
                const fraction=compass||!c.ticks_exactTicks?i/(ticks.length-1):ratio(t);
                const a=start+fraction*sweep+plateRotation;
                line('tick:'+i,point(a,r*0.88),point(a,r*0.96),f.paint(c,'ticks_colorMajorTick'),Math.max(1,r/150));
                const p=point(a,r*0.70);text('number:'+i,compass?t:f.number(Number(t),c.precision,'.',''),p[0],p[1],c.ticks_fontSize*fontScale,f.paint(c,'ticks_colorNumbers'),c.ticks_fontFamily);
                if(i<ticks.length-1){const next=compass||!c.ticks_exactTicks?(i+1)/(ticks.length-1):ratio(ticks[i+1]);for(let n=1;n<minor;n++){
                    const angle=start+(fraction+(next-fraction)*n/minor)*sweep+plateRotation;line('minor:'+i+':'+n,point(angle,r*0.92),point(angle,r*0.96),f.paint(c,'ticks_colorMinorTicks'),Math.max(0.5,r/220));
                }}
            });
            if(c.needle_display){
                const a=theta(convert(d.value)),end=point(a,r*c.needle_end/100),tail=point(a,-r*c.needle_start/100),half=Math.max(1,c.needle_width*r/200);
                if(c.needle_Type==='line'){line('needle',tail,end,gradient('needle_colorNeedle'),half*2);}
                else{const left=point(a+Math.PI/2,half),right=point(a-Math.PI/2,half);graphics.push({id:'needle',type:'polygon',shape:{points:[tail,left,end,right]},style:{fill:gradient('needle_colorNeedle'),shadowColor:f.paint(c,'needle_colorShadowDown'),shadowBlur:r*0.015,shadowOffsetY:r*0.008}});}
                if(c.needle_CircleSize>0){
                    if(c.needle_CircleOuter){graphics.push({id:'hub-outer',type:'circle',shape:{cx:cx,cy:cy,r:r*c.needle_CircleSize/100},style:{fill:gradient('needle_colorNeedleCircleOuter')}});}
                    graphics.push({id:'hub',type:'circle',shape:{cx:cx,cy:cy,r:r*c.needle_CircleSize/100*(c.needle_CircleOuter?0.75:1)},style:{fill:gradient('needle_colorNeedleCircleInner')}});
                }
            }
            if(c.title_display){text('title',c.title_text,cx,cy-r*0.28,c.title_fontSize*fontScale,f.paint(c,'title_fontColor'),c.title_fontFamily);}
            if(c.plate_unit){text('unit',c.plate_unit,cx,cy+r*0.28,c.plate_unit_fontSize*fontScale,f.paint(c,'plate_unit_fontColor'),c.plate_unit_fontFamily);}
            if(c.valuebox_display&&!compass){text('value',f.number(d.value,c.precision,',',c.valuebox_separator?'.':''),cx,cy+r*0.68,c.valuebox_fontSize*fontScale,f.paint(c,'valuebox_fontColor'),c.valuebox_fontFamily,f.paint(c,'valuebox_colorValueBoxBackground'));}
        }else{
            const x0=vertical?cx:width*0.12,x1=vertical?cx:width*0.88,y0=vertical?faceH*0.12+10:cy,y1=vertical?faceH*0.88+10:cy;
            const at=function(v){const t=ratio(v);return vertical?[cx,y1-(y1-y0)*t]:[x0+(x1-x0)*t,cy];};
            const thickness=Math.max(1,Math.min(faceW,faceH)*c.progressbar_barWidth/100);
            if(c.progressbar_display){
                let from=minimum,to=convert(d.value);
                if(c.progressbar_barStartPosition==='right'){from=maximum-(to-minimum);to=maximum;}else if(c.progressbar_barStartPosition==='middle'){from=(minimum+maximum)/2;}
                line('track',[x0,y0],[x1,y1],f.paint(c,'progressbar_colorBar'),thickness);line('progress',at(from),at(to),f.paint(c,'progressbar_colorBarProgress'),thickness);
            }
            highlights.forEach(function(h,i){line('highlight:'+i,at(c.AutoConvertHighlight?convert(h.From):h.From),at(c.AutoConvertHighlight?convert(h.To):h.To),f.color(h.HighlightColor,h.HighlightColor_Alpha),Math.min(faceW,faceH)*c.ticks_highlightsWidth/100);});
            ticks.forEach(function(t,i){const p=c.ticks_exactTicks?at(t):at(minimum+i/(ticks.length-1)*(maximum-minimum));
                const side=c.linear_tickSide;const span=Math.min(faceW,faceH)*0.12;
                if(side!=='right'){line('tick:left:'+i,vertical?[p[0]-span,p[1]]:[p[0],p[1]-span],p,f.paint(c,'ticks_colorMajorTick'),1);}
                if(side!=='left'){line('tick:right:'+i,p,vertical?[p[0]+span,p[1]]:[p[0],p[1]+span],f.paint(c,'ticks_colorMajorTick'),1);}
                if(c.linear_numberSide!=='right'){text('number:left:'+i,f.number(Number(t),c.precision,'.',''),vertical?p[0]-span*1.5:p[0],vertical?p[1]:p[1]-span*1.7,c.ticks_fontSize*fontScale,f.paint(c,'ticks_colorNumbers'),c.ticks_fontFamily);}
                if(c.linear_numberSide!=='left'){text('number:right:'+i,f.number(Number(t),c.precision,'.',''),vertical?p[0]+span*1.5:p[0],vertical?p[1]:p[1]+span*1.7,c.ticks_fontSize*fontScale,f.paint(c,'ticks_colorNumbers'),c.ticks_fontFamily);}
                if(i<ticks.length-1){
                    const from=c.ticks_exactTicks?Number(t):minimum+i/(ticks.length-1)*(maximum-minimum);
                    const to=c.ticks_exactTicks?Number(ticks[i+1]):minimum+(i+1)/(ticks.length-1)*(maximum-minimum);
                    for(let n=1;n<c.ticks_minorTicks;n++){
                        const q=at(from+(to-from)*n/c.ticks_minorTicks),minorSpan=span*0.6;
                        if(side!=='right'){line('minor:left:'+i+':'+n,vertical?[q[0]-minorSpan,q[1]]:[q[0],q[1]-minorSpan],q,f.paint(c,'ticks_colorMinorTicks'),0.75);}
                        if(side!=='left'){line('minor:right:'+i+':'+n,q,vertical?[q[0]+minorSpan,q[1]]:[q[0],q[1]+minorSpan],f.paint(c,'ticks_colorMinorTicks'),0.75);}
                    }
                }
            });
            if(c.needle_display){const p=at(convert(d.value)),span=Math.min(faceW,faceH)*0.15;
                const a=c.linear_needleSide==='right'?p:(vertical?[p[0]-span,p[1]]:[p[0],p[1]-span]);
                const b=c.linear_needleSide==='left'?p:(vertical?[p[0]+span,p[1]]:[p[0],p[1]+span]);
                line('needle',a,b,gradient('needle_colorNeedle'),Math.max(1,c.needle_width*fontScale));}
            if(c.title_display){text('title',c.title_text,cx,10+faceH*0.04,c.title_fontSize*fontScale,f.paint(c,'title_fontColor'),c.title_fontFamily);}
            if(c.plate_unit){text('unit',c.plate_unit,cx,10+faceH*0.94,c.plate_unit_fontSize*fontScale,f.paint(c,'plate_unit_fontColor'),c.plate_unit_fontFamily);}
            if(c.valuebox_display){text('value',f.number(d.value,c.precision,',',c.valuebox_separator?'.':''),cx,10+faceH*0.78,c.valuebox_fontSize*fontScale,f.paint(c,'valuebox_fontColor'),c.valuebox_fontFamily,f.paint(c,'valuebox_colorValueBoxBackground'));}
        }
        return {backgroundColor:state.background,animation:c.__animate!==false,animationDurationUpdate:c.animation_duration,animationEasingUpdate:f.easing(c.animation_rule),
            tooltip:{show:c.tooltips_enabled!==false,confine:true,formatter:function(){return f.escape(f.format(d.value,d.native));}},
            graphic:f.graphicTransitions(graphics,function(){return f.escape(f.format(d.value,d.native));})};
    }
    root.SYMC_JSLIVE_OPTION=build;
    if(typeof module==='object'&&module.exports){module.exports=build;}
}(typeof window==='object'?window:globalThis));
