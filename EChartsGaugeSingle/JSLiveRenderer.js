/*
 * Canvas Gauges 2.1.7 coordinate compatibility, adapted to native ECharts
 * primitives. No Canvas Gauges runtime is included.
 *
 * The MIT License (MIT)
 * Copyright (c) 2016 Mykhailo Stadnyk <mikhus@gmail.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
(function (root) {
    'use strict';
    function build(state,width,height) {
        const f=root.SYMC_JSLIVE_FORMAT,c=state.settings,d=state.data;
        const compass=c.template==='CanvasGauges-Compass',linear=c.template.indexOf('Linear')>=0,vertical=c.template.indexOf('(vertical)')>=0;
        const minimum=compass?0:c.min,maximum=compass?360:c.max,rad=Math.PI/180,round=Math.round;
        // Source canvas dimensions; a vertical instrument is at most half as wide as high.
        let faceW=Math.max(1,width-20),faceH=Math.max(1,height-20);
        if(compass){faceW=faceH=Math.max(1,Math.min(width,height)-20);}
        if(linear){if(vertical){faceW=Math.max(1,Math.min(faceW,faceH/2));}else{faceH=Math.max(1,Math.min(faceH,faceW/4));}}
        const fx=(width-faceW)/2,fy=8,cx=width/2,cy=fy+faceH/2,r=Math.min(faceW,faceH)/2,fontScale=c.__fontScale||1;
        const ticks=compass?['N','NE','E','SE','S','SW','W','NW','N']:[c.min].concat(c.Ticks.slice().sort(function(a,b){return a.Value-b.Value;}).map(function(t){return t.Value;}),[c.max]);
        // GenerateTicks rounds to precision, but JSLive serializes numeric
        // strings as numbers. Use ASCII wire digits, independent of the client
        // locale, then paint 20 rather than "20.0". Value formatting stays local.
        const tickFormat=new Intl.NumberFormat('en-US',{useGrouping:false,maximumFractionDigits:Math.min(20,Math.max(0,c.precision))});
        const tickText=ticks.map(function(t){return compass?t:String(Number(tickFormat.format(Number(t))));});
        const ratio=function(value){return Math.max(0,Math.min(1,(Number(value)-minimum)/(maximum-minimum)));};
        function convert(value) {
            if(!c.AutoConvertValue || compass || value<=minimum || value>=maximum){return value;}
            let last=minimum;const step=(maximum-minimum)/(ticks.length-1);
            for(let i=0;i<ticks.length;i++){
                if(value===Number(ticks[i])){return minimum+i*step;}
                if(value<Number(ticks[i])){return minimum+(i-1)*step+(value-last)/(Number(ticks[i])-last)*step;}
                last=Number(ticks[i]);
            }
            return value;
        }
        const graphics=[],highlights=compass?[]:c.Highlights;
        function minorPositions(){
            const step=compass?22:Math.abs(c.ticks_minorTicks),exact=c.ticks_exactTicks;
            const wanted=Math.max(0,exact?(step?Math.ceil((maximum-minimum)/step):0):step*(ticks.length-1));
            // Dense exact scales retain regular steps across the whole instrument.
            const stride=exact?Math.max(1,Math.ceil(wanted/2000)):1,count=Math.min(2000,Math.ceil(wanted/stride));
            const offset=exact&&step&&!compass?((Number(ticks[0])%step)+step)%step:0;
            return Array.from({length:count},function(_,i){return exact?(offset+i*stride*step)/(maximum-minimum):i/Math.max(1,count);});
        }
        function gradient(name,verticalDirection){return {type:'linear',x:0,y:0,x2:verticalDirection===false?1:0,y2:verticalDirection===false?0:1,colorStops:[{offset:0,color:f.paint(c,name)},{offset:1,color:f.paint(c,name+'End',f.paint(c,name))}]};}
        function line(id,a,b,color,width){graphics.push({id:id,type:'line',shape:{x1:a[0],y1:a[1],x2:b[0],y2:b[1]},style:{stroke:color,lineWidth:width}});}
        function rect(id,x,y,w,h,fill){graphics.push({id:id,type:'rect',shape:{x:x,y:y,width:w,height:h},style:{fill:fill}});}
        function measure(value,size,family){return root.echarts.format.getTextRect(String(value),size+'px '+(family||'Arial')).width;}
        function text(id,value,x,y,size,color,family,align,baseline,maxWidth){
            size=Math.max(1,size);const measured=measure(value,size,family),scale=maxWidth&&measured>maxWidth?maxWidth/measured:1;
            graphics.push({id:id,type:'text',originX:x,scaleX:scale,style:{text:String(value),x:x,y:baseline?y-size*0.35:y,fill:color,fontSize:size,fontFamily:family||'Arial',align:align||'center',verticalAlign:'middle'}});
        }
        function valueBox(value,x,y,base){
            const unit=base/100,size=c.valuebox_fontSize*base/200*fontScale,glyph=size+1.6*unit;
            const w=Math.min(2*base,Math.max(0.2*base,measure(value,size,c.valuebox_fontFamily)+10*unit)),h=1.1*glyph+1.6*unit;
            rect('value-box',x-w/2,y-h/2,w,h,f.paint(c,'valuebox_colorValueBoxBackground'));
            text('value',value,x,y+h/2-glyph/3,size,f.paint(c,'valuebox_fontColor'),c.valuebox_fontFamily,'center',true);
        }
        function needleShadow(){return {shadowColor:f.paint(c,'needle_colorShadowDown'),shadowBlur:10,shadowOffsetX:2,shadowOffsetY:2};}
        function polygon(id,points,fill,shadow){graphics.push({id:id,type:'polygon',shape:{points:points},style:Object.assign({fill:fill},shadow?needleShadow():{})});}
        const valueText=f.number(d.value,c.precision,',',c.valuebox_separator?'.':'');
        if(!linear){
            const plateR=Math.max(1,r-(compass?5:0)),start=(compass?180:c.radial_startAngle)*rad,sweep=(compass?360:c.radial_ticksAngle)*rad;
            const plateRotation=c.animation_target==='plate'?-ratio(d.value)*sweep:0;
            const point=function(angle,radius){return [cx-Math.sin(angle)*radius,cy+Math.cos(angle)*radius];};
            const theta=function(value){return start+ratio(value)*sweep+plateRotation;};
            function arc(id,from,to,radius,color,width){if(width>0){graphics.push({id:id,type:'arc',shape:{cx:cx,cy:cy,r:Math.max(0,radius),startAngle:from+Math.PI/2,endAngle:to+Math.PI/2,clockwise:true},style:{stroke:color,lineWidth:width,fill:null}});}}
            if(compass){arc('compass-border',0,Math.PI*2,r-1.5,'#ccc',3);arc('compass-inner',0,Math.PI*2,r-4,{type:'linear',x:0,y:0,x2:0,y2:1,colorStops:[{offset:0,color:'#fafafa'},{offset:1,color:'#ccc'}]},3);}
            if(c.plate_display){graphics.push({id:'plate',type:'circle',shape:{cx:cx,cy:cy,r:plateR},style:{fill:{type:'radial',x:0.5,y:0.5,r:0.5,colorStops:[{offset:0.5,color:f.paint(c,'plate_colorPlate')},{offset:1,color:f.paint(c,'plate_colorPlateEnd',f.paint(c,'plate_colorPlate'))}]}}});}
            const barWidth=!compass&&c.progressbar_display?Math.min(50,c.progressbar_barWidth):0;
            const tickR=plateR-r*0.05-(barWidth?(barWidth+5)*r/100:0),numberR=tickR-r*0.15;
            highlights.forEach(function(h,i){const from=c.AutoConvertHighlight?convert(h.From):h.From,to=c.AutoConvertHighlight?convert(h.To):h.To;
                arc('highlight:'+i,theta(from),theta(to),tickR-c.ticks_highlightsWidth*r/200,f.color(h.HighlightColor,Math.round(h.HighlightColor_Alpha*100)/100),c.ticks_highlightsWidth*r/100);});
            const exact=c.ticks_exactTicks;
            minorPositions().forEach(function(t,i){
                const a=start+t*sweep+plateRotation;
                if(t<=1){line('minor:'+i,point(a,tickR),point(a,tickR-r*0.075),f.paint(c,'ticks_colorMinorTicks'),1);}
            });
            ticks.forEach(function(t,i){
                // The source cannot position named compass ticks on a numeric
                // exact scale. Preserve their absence without emitting NaN.
                if(compass&&exact){return;}
                const a=start+(exact?ratio(t):i/(ticks.length-1))*sweep+plateRotation;
                line('tick:'+i,point(a,tickR),point(a,tickR-r*0.15),f.paint(c,'ticks_colorMajorTick'),2);
                const size=c.ticks_fontSize*r/200*fontScale,extent=Math.hypot(measure(tickText[i],size,c.ticks_fontFamily),c.ticks_fontSize*fontScale)/2;
                const p=point(a,numberR-extent-r*0.01);text('number:'+i,tickText[i],p[0],p[1],size,f.paint(c,'ticks_colorNumbers'),c.ticks_fontFamily);
            });
            if(!compass&&c.ticks_strokeTicks){arc('tick-stroke',start+plateRotation,start+sweep+plateRotation,tickR,f.paint(c,'ticks_colorMajorTick'),2);}
            if(barWidth){
                const radius=plateR-r*0.05-barWidth*r/200;
                arc('track',start+plateRotation,start+sweep+plateRotation,radius,f.paint(c,'progressbar_colorBar'),barWidth*r/100);
                let from=start,to=start+ratio(convert(d.value))*sweep;
                if(c.progressbar_barStartPosition==='right'){from=start+sweep-(to-start);to=start+sweep;}
                else if(c.progressbar_barStartPosition==='middle'){from=Math.PI;to=Math.PI+(ratio(convert(d.value))-0.5)*sweep;if(to<from){const swap=from;from=to;to=swap;}}
                arc('progress',from+plateRotation,to+plateRotation,radius,f.paint(c,'progressbar_colorBarProgress'),barWidth*r/100);
            }
            if(c.needle_display){
                let angle=c.animation_target==='plate'?start:start+ratio(convert(d.value))*sweep;
                if(c.progressbar_barStartPosition==='right'&&!compass){angle=start+sweep-ratio(convert(d.value))*sweep;}
                const tip=plateR*c.needle_end/100,tail=plateR*c.needle_start/100,full=plateR*c.needle_width/100,half=full/2;
                const transform=function(x,y){return [cx+x*Math.cos(angle)-y*Math.sin(angle),cy+x*Math.sin(angle)+y*Math.cos(angle)];};
                const a=transform(0,0),b=transform(0,tip-tail),paint={type:'linear',x:a[0],y:a[1],x2:b[0],y2:b[1],global:true,colorStops:[{offset:0,color:f.paint(c,'needle_colorNeedle')},{offset:1,color:f.paint(c,'needle_colorNeedleEnd')}]};
                const points=c.needle_Type==='line'?[[-half,tip],[-half,tail],[half,tail],[half,tip]]:[[-half,-tail],[-full,0],[-1,tip],[1,tip],[full,0],[half,-tail]];
                polygon('needle',points.map(function(p){return transform(p[0],p[1]);}),paint,true);
                if(c.needle_Type!=='line'){polygon('needle-shade',[[-0.5,tip],[-1,tip],[-full,0],[-half,-tail],[half/2-2,-tail]].map(function(p){return transform(p[0],p[1]);}),f.paint(c,'needle_colorShadowUp'));}
                if(c.needle_CircleSize>0){
                    const hub=plateR*c.needle_CircleSize/100;
                    if(c.needle_CircleOuter){graphics.push({id:'hub-outer',type:'circle',shape:{cx:cx,cy:cy,r:hub},style:Object.assign({fill:gradient('needle_colorNeedleCircleOuter')},needleShadow())});}
                    graphics.push({id:'hub',type:'circle',shape:{cx:cx,cy:cy,r:hub*0.75},style:Object.assign({fill:gradient('needle_colorNeedleCircleInner')},needleShadow())});
                }
            }
            if(c.title_display){text('title',c.title_text,cx,cy-r/4.25,c.title_fontSize*r/200*fontScale,f.paint(c,'title_fontColor'),c.title_fontFamily,'center',c.animation_target==='plate',r*0.8);}
            if(c.plate_unit){
                const plateMode=c.animation_target==='plate',family=c.plate_unit_fontFamily||(plateMode?'sans-serif':c.ticks_fontFamily||'Arial');
                const size=c.plate_unit_fontFamily?c.plate_unit_fontSize*r/200*fontScale:(plateMode?10*fontScale:c.ticks_fontSize*r/200*fontScale);
                text('unit',c.plate_unit,cx,cy+r/3.25,size,f.paint(c,'plate_unit_fontColor'),family,'center',plateMode,r*0.8);
            }
            if(c.valuebox_display&&!compass){valueBox(valueText,cx,cy+r*0.67,r);}
        }else{
            if(c.plate_display){rect('plate',fx,fy,faceW,faceH,gradient('plate_colorPlate',vertical));}
            // Reserve the source title/unit/value rows before deriving linear scale fonts.
            let cross=vertical?faceW*0.85:faceH,length=vertical?faceH:faceW,x=vertical?cx+round(-cross/2):fx,y=fy;
            const titleMargin=c.title_display?round((vertical?length*0.075:cross*0.15)):0;
            const unitMargin=c.plate_unit?round((vertical?length*0.05:cross*0.15)):0;
            const valueMargin=vertical&&c.valuebox_display?round(length*0.11):0;
            if(vertical){length=Math.max(1,length-titleMargin-unitMargin-valueMargin);}else{cross=Math.max(1,cross-titleMargin-unitMargin);}
            y+=titleMargin;
            const barWidth=round(cross*(c.progressbar_display?c.progressbar_barWidth:0)/100),barLength=round(length*0.85),margin=round((length-barLength)/2);
            const hasLeft=c.linear_needleSide!=='right'||c.linear_tickSide!=='right'||c.linear_numberSide!=='right';
            const hasRight=c.linear_needleSide!=='left'||c.linear_tickSide!=='left'||c.linear_numberSide!=='left';
            const shift=hasLeft&&hasRight?0:(hasRight?-1:1)*cross*0.1;
            const X=x+(vertical?shift:0),Y=y+(vertical?0:shift),near=(cross-barWidth)/2,padding=cross*0.05;
            const at=function(v){const t=ratio(v);return vertical?[X+cross/2,Y+length-margin-barLength*t]:[X+margin+barLength*t,Y+cross/2];};
            if(c.progressbar_display){
                const from=at(minimum),to=at(convert(d.value));
                // Source templates set no bar end colors: both paints are solid.
                rect('track',vertical?round(X+near):round(X+margin),vertical?round(Y+length-margin-barLength):round(Y+near),vertical?barWidth:barLength,vertical?barLength:barWidth,f.paint(c,'progressbar_colorBar'));
                rect('progress',vertical?round(X+near):from[0],vertical?to[1]:round(Y+near),vertical?barWidth:to[0]-from[0],vertical?from[1]-to[1]:barWidth,f.paint(c,'progressbar_colorBarProgress'));
            }
            highlights.forEach(function(h,i){
                const a=at(c.AutoConvertHighlight?convert(h.From):h.From),b=at(c.AutoConvertHighlight?convert(h.To):h.To),thick=cross*c.ticks_highlightsWidth/100;
                const inner=near-round(cross*0.15)-(thick-cross*0.1),outer=near+round(barWidth+padding);
                if(c.linear_tickSide!=='right'){rect('highlight:left:'+i,vertical?X+inner:a[0],vertical?b[1]:Y+inner,vertical?thick:b[0]-a[0],vertical?a[1]-b[1]:thick,f.color(h.HighlightColor,h.HighlightColor_Alpha));}
                if(c.linear_tickSide!=='left'){rect('highlight:right:'+i,vertical?X+outer:a[0],vertical?b[1]:Y+outer,vertical?thick:b[0]-a[0],vertical?a[1]-b[1]:thick,f.color(h.HighlightColor,h.HighlightColor_Alpha));}
            });
            function tick(id,value,span,width,color){const p=at(value);
                if(c.linear_tickSide!=='right'){const side=near-padding;line(id+':left',vertical?[X+side,p[1]]:[p[0],Y+side],vertical?[round(X+side-span),p[1]]:[p[0],round(Y+side-span)],color,width);}
                if(c.linear_tickSide!=='left'){const side=near+barWidth+span+padding;line(id+':right',vertical?[X+side,p[1]]:[p[0],round(Y+side)],vertical?[round(X+side-span),p[1]]:[p[0],Y+side-span],color,width);}
            }
            minorPositions().forEach(function(t,i){if(t<1){tick('minor:'+i,minimum+t*(maximum-minimum),cross*0.05,1,f.paint(c,'ticks_colorMinorTicks'));}});
            ticks.forEach(function(t,i){const value=c.ticks_exactTicks?Number(t):minimum+i/(ticks.length-1)*(maximum-minimum),p=at(value),size=c.ticks_fontSize*cross/200*fontScale;
                tick('tick:'+i,value,cross*0.1,2,f.paint(c,'ticks_colorMajorTick'));
                const inner=near-cross*0.2-cross*0.01,outer=near+barWidth+cross*0.2+cross*0.01;
                if(c.linear_numberSide!=='right'){text('number:left:'+i,tickText[i],vertical?X+inner:p[0],vertical?p[1]+size/3:Y+inner,size,f.paint(c,'ticks_colorNumbers'),c.ticks_fontFamily,vertical?'right':'center',true);}
                if(c.linear_numberSide!=='left'){text('number:right:'+i,tickText[i],vertical?X+outer:p[0],vertical?p[1]+size/3:Y+outer+size,size,f.paint(c,'ticks_colorNumbers'),c.ticks_fontFamily,vertical?'left':'center',true);}
            });
            if(c.ticks_strokeTicks){const a=at(minimum),b=at(maximum),inner=near-padding,outer=near+barWidth+padding;
                if(c.linear_tickSide!=='right'){line('tick-stroke:left',vertical?[X+inner,a[1]+1]:[a[0]-1,Y+inner],vertical?[X+inner,b[1]-1]:[b[0]+1,Y+inner],f.paint(c,'ticks_colorMajorTick'),2);}
                if(c.linear_tickSide!=='left'){line('tick-stroke:right',vertical?[X+outer,a[1]+1]:[a[0]-1,Y+outer],vertical?[X+outer,b[1]-1]:[b[0]+1,Y+outer],f.paint(c,'ticks_colorMajorTick'),2);}
            }
            if(c.needle_display){const p=at(convert(d.value)),span=barWidth/2+cross*0.15,tail=span*c.needle_start/100,end=span*c.needle_end/100;
                ['left','right'].forEach(function(side){if(c.linear_needleSide!=='both'&&c.linear_needleSide!==side){return;}
                    const sign=side==='left'?1:-1,base=side==='left'?near-cross*0.15-tail:near+barWidth+cross*0.15+tail;
                    const a=vertical?[round(X+base),round(p[1])]:[round(p[0]),round(Y+base)],b=vertical?[a[0]+sign*end,a[1]]:[a[0],a[1]+sign*end];
                    const paint=gradient('needle_colorNeedle',!vertical);
                    if(c.needle_Type==='line'){line('needle:'+side,a,b,paint,c.needle_width);}
                    else{const half=c.needle_width/2,shoulder=sign*(end-round(end*0.4));
                        polygon('needle:'+side,vertical?[[a[0],a[1]-half],[a[0],a[1]+half],[a[0]+shoulder,a[1]+half],b,[a[0]+shoulder,a[1]-half],[a[0],a[1]-half]]:[[a[0]-half,a[1]],[a[0]+half,a[1]],[a[0]+half,a[1]+shoulder],b,[a[0]-half,a[1]+shoulder],[a[0]-half,a[1]]],paint,true);}
                });
            }
            const titleSize=c.title_fontSize*cross/200*fontScale,unitSize=c.plate_unit_fontSize*cross/200*fontScale;
            if(c.title_display){text('title',c.title_text,round(x+(vertical?cross:length)/2),round(y+titleMargin/2-(vertical?titleSize:titleSize/2)-0.025*(vertical?length:cross)),titleSize,f.paint(c,'title_fontColor'),c.title_fontFamily,'center',true,vertical?cross:length);}
            if(c.plate_unit){
                const family=c.plate_unit_fontFamily||(c.title_display?c.title_fontFamily:c.ticks_fontFamily)||'Arial';
                const size=c.plate_unit_fontFamily?unitSize:(c.title_display?titleSize:c.ticks_fontSize*cross/200*fontScale);
                text('unit',c.plate_unit,round(x+(vertical?cross:length)/2),round(y+(vertical?length:cross)+unitMargin/2-unitSize/2),size,f.paint(c,'plate_unit_fontColor'),family,'center',true,vertical?cross:length);
            }
            if(vertical&&c.valuebox_display){const size=c.valuebox_fontSize*faceW/200*fontScale;valueBox(valueText,fx+faceW/2,fy+faceH-size-(faceH*0.11-size)/2,faceW);}
        }
        return {backgroundColor:state.background,animation:c.__animate!==false,animationDurationUpdate:c.animation_duration,animationEasingUpdate:f.easing(c.animation_rule),
            tooltip:{show:c.tooltips_enabled!==false,confine:true,formatter:function(){return f.escape(f.format(d.value,d.native));}},
            graphic:f.graphicTransitions(graphics,function(){return f.escape(f.format(d.value,d.native));})};
    }
    root.SYMC_JSLIVE_OPTION=build;
    if(typeof module==='object'&&module.exports){module.exports=build;}
}(typeof window==='object'?window:globalThis));
