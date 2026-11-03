/*
 * loading-bar 0.1.1 / JSLive 0.1.1-jslive.1 preset and SVG-fit compatibility,
 * adapted to native ECharts graphics. No loading-bar runtime is included.
 *
 * The MIT License (MIT)
 * Copyright (c) 2017, loading.io
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
(function(root){
    'use strict';
    const lengths=new Map();
    const animations=new WeakMap();
    // One bounded native canvas per active paint, one frame loop per chart.
    // Never execute an SVG resource generator or insert animation markup.
    function animatedPaint(chart,value,factor,channel){
        let owner=animations.get(chart);
        if(!owner){owner={paints:new Map(),active:new Set(),frame:null};animations.set(chart,owner);}
        owner.active.add(channel);
        const signature=JSON.stringify(value);
        let record=owner.paints.get(channel);
        if(!record||record.signature!==signature){
            record={signature:signature,value:value,canvas:root.document.createElement('canvas'),start:root.performance.now()};
            owner.paints.set(channel,record);
        }
        record.factor=factor;
        function draw(now){
            const p=record.value,canvas=record.canvas,w=Math.ceil(p.width*record.factor),h=Math.ceil(p.height*record.factor);
            if(canvas.width!==w||canvas.height!==h){canvas.width=w;canvas.height=h;}
            const ctx=canvas.getContext('2d'),radians=p.angle*Math.PI/180;
            let gx=Math.cos(radians)**2,gy=Math.sqrt(Math.max(0,gx-gx**2));
            if(p.angle>45){gy=Math.sin(radians)**2;gx=Math.sqrt(Math.max(0,gy-gy**2));}
            const phase=((now-record.start)/(p.duration*1000)%1+1)%1,shift=1-phase;
            ctx.setTransform(w/100,0,0,h/100,0,0);ctx.clearRect(0,0,100,100);
            const gradient=ctx.createLinearGradient(-gx*100*shift,-gy*100*shift,gx*(400-100*shift),gy*(400-100*shift));
            const count=p.colors.length*4;
            for(let i=0;i<=count;i++){gradient.addColorStop(i/count,p.colors[i%p.colors.length]);}
            ctx.fillStyle=gradient;ctx.fillRect(0,0,100,100);
            return {image:canvas,repeat:'repeat',scaleX:p.width/w,scaleY:p.height/h};
        }
        record.draw=draw;
        return draw(root.performance.now());
    }
    function stop(chart){const owner=animations.get(chart);if(owner&&owner.frame!==null){root.cancelAnimationFrame(owner.frame);owner.frame=null;}}
    root.SYMC_JSLIVE_DISPOSE=stop;
    root.SYMC_JSLIVE_AFTER_RENDER=function(chart){
        const owner=animations.get(chart);
        if(!owner){return;}
        if(!owner.active.size){stop(chart);return;}
        if(owner.frame!==null){return;}
        let last=-Infinity;
        function frame(now){
            owner.frame=null;
            if(chart.isDisposed()){owner.paints.clear();return;}
            if(!owner.active.size){return;}
            if(!root.document.hidden&&now-last>=1000/30){
                const style={};owner.active.forEach(function(channel){style[channel]=owner.paints.get(channel).draw(now);});
                chart.setOption({graphic:[{id:'progress-body',children:[{id:'progress',transition:[],style:style}]}]},{lazyUpdate:false});
                last=now;
            }
            owner.frame=root.requestAnimationFrame(frame);
        }
        owner.frame=root.requestAnimationFrame(frame);
    };
    // Replay the public Canvas path interface to measure paths without depending on zrender private fields.
    function pathLength(path){
        if(lengths.has(path)){return lengths.get(path);}
        let x=0,y=0,sx=0,sy=0,total=0;
        const line=function(a,b){total+=Math.hypot(a-x,b-y);x=a;y=b;};
        const cubic=function(a,b,c,d,e,g){
            const ox=x,oy=y;
            // A fixed workload also bounds adversarial high-curvature paths accepted by the grammar.
            for(let i=1;i<=64;i++){const t=i/64,u=1-t;
                line(u*u*u*ox+3*u*u*t*a+3*u*t*t*c+t*t*t*e,u*u*u*oy+3*u*u*t*b+3*u*t*t*d+t*t*t*g);}
        };
        const ctx={moveTo:function(a,b){x=sx=a;y=sy=b;},lineTo:line,
            bezierCurveTo:function(a,b,c,d,e,g){cubic(a,b,c,d,e,g);},
            quadraticCurveTo:function(a,b,c,d){cubic(x+2*(a-x)/3,y+2*(b-y)/3,c+2*(a-c)/3,d+2*(b-d)/3,c,d);},
            closePath:function(){line(sx,sy);},rect:function(a,b,w,h){ctx.moveTo(a,b);line(a+w,b);line(a+w,b+h);line(a,b+h);ctx.closePath();},
            arc:function(cx,cy,r,a,b,ccw){ctx.ellipse(cx,cy,r,r,0,a,b,ccw);},
            ellipse:function(cx,cy,rx,ry,rotation,a,b,ccw){let delta=b-a;if(ccw&&delta>0){delta-=Math.PI*2;}if(!ccw&&delta<0){delta+=Math.PI*2;}
                const count=Math.max(1,Math.ceil(Math.abs(delta)*128));for(let i=1;i<=count;i++){const t=a+delta*i/count;
                    line(cx+rx*Math.cos(t)*Math.cos(rotation)-ry*Math.sin(t)*Math.sin(rotation),cy+rx*Math.cos(t)*Math.sin(rotation)+ry*Math.sin(t)*Math.cos(rotation));}}
        };
        const shape=root.echarts.graphic.makePath(path,{});shape.buildPath(ctx,shape.shape);
        if(!Number.isFinite(total)){throw new Error('Invalid progress path length');}lengths.set(path,total);return total;
    }
    // The pinned runtime misreads arc records when measuring a partial stroke.
    // Replay only stroked paths through the public Canvas interface, expressing
    // each arc as at most sixteen cubic segments. This retains native strokePercent,
    // dash patterns and transitions without patching ECharts or its path internals.
    function strokePath(ctx,path){
        const canvas={};
        ['moveTo','lineTo','bezierCurveTo','quadraticCurveTo','rect','closePath'].forEach(function(name){
            canvas[name]=function(){ctx[name].apply(ctx,arguments);};
        });
        canvas.ellipse=function(cx,cy,rx,ry,rotation,start,end,ccw){
            let delta=end-start;
            if(ccw&&delta>0){delta-=Math.PI*2;}else if(!ccw&&delta<0){delta+=Math.PI*2;}
            delta=Math.max(-Math.PI*2,Math.min(Math.PI*2,delta));
            const count=Math.max(1,Math.ceil(Math.abs(delta)/(Math.PI/8))),step=delta/count;
            const cos=Math.cos(rotation),sin=Math.sin(rotation);
            const point=function(a){return [cx+rx*Math.cos(a)*cos-ry*Math.sin(a)*sin,cy+rx*Math.cos(a)*sin+ry*Math.sin(a)*cos];};
            const tangent=function(a){return [-rx*Math.sin(a)*cos-ry*Math.cos(a)*sin,-rx*Math.sin(a)*sin+ry*Math.cos(a)*cos];};
            let p=point(start),v=tangent(start);
            ctx.lineTo(p[0],p[1]);
            for(let i=1;i<=count;i++){
                const angle=start+step*i,q=point(angle),w=tangent(angle),k=4/3*Math.tan(step/4);
                ctx.bezierCurveTo(p[0]+k*v[0],p[1]+k*v[1],q[0]-k*w[0],q[1]-k*w[1],q[0],q[1]);
                p=q;v=w;
            }
        };
        canvas.arc=function(cx,cy,r,start,end,ccw){canvas.ellipse(cx,cy,r,r,0,start,end,ccw);};
        const shape=root.echarts.graphic.makePath(path,{});shape.buildPath(canvas,shape.shape);
    }
    // The source engine uses Image.width/height, not viewBox units. Reconstruct
    // the intrinsic replaced-image size from the already validated inert SVG;
    // no source markup is executed or added to the page DOM here.
    function imageSize(image){
        const tag=root.atob(image.dataUri.split(',')[1]).match(/<svg\b[^>]*>/i)[0];
        function length(name){
            const attr=new RegExp('\\s'+name+'="([^"]*)"').exec(tag);
            const value=attr&&/^\s*(\d+(?:\.\d*)?|\.\d+)\s*(px|in|cm|mm|q|pt|pc)?\s*$/i.exec(attr[1]);
            return value?Number(value[1])*({px:1,in:96,cm:96/2.54,mm:96/25.4,q:96/101.6,pt:96/72,pc:16}[(value[2]||'px').toLowerCase()]):0;
        }
        const ratio=image.width/image.height;let w=length('width'),h=length('height');
        if(!w&&!h){w=Math.min(300,150*ratio);h=w/ratio;}
        else if(!w){w=h*ratio;}else if(!h){h=w/ratio;}
        w=Math.floor(w);h=Math.floor(h);return Number.isFinite(w)&&Number.isFinite(h)&&w>0&&h>0?[w,h]:[100,100];
    }
    function fit(c,box,width,height,titleSpace){
        const fontSize=(Number.isFinite(c.style_fontSize)?c.style_fontSize:16)*(c.__fontScale||1);
        // The shared HTML adapter already supplies the source body's 8px margin
        // on each side. Reconstruct the window dimensions before applying
        // ResizeProgressbar; a second local margin would shrink the SVG again.
        const w=Math.max(1,width+16-40),h=Math.max(1,height+16-titleSpace-(c.style_fontPosition==='center'?40:60+fontSize));
        const padding=Math.max(Number(c.stroke_width)||0,Number(c.stroke_trailWidth)||0,Number(c.fill_backgroundExtrude)||0)*1.5;
        const vw=box[2]+padding*2,vh=box[3]+padding*2,scale=Math.max(1e-9,Math.min(w/vw,h/vh));
        return {scale:scale,x:(w-vw*scale)/2-(box[0]-padding)*scale,
            y:titleSpace+(h-vh*scale)/2-(box[1]-padding)*scale,
            viewBox:[box[0]-padding,box[1]-padding,vw,vh],
            labelX:w/2,labelY:titleSpace+(c.style_fontPosition==='center'?h/2:h+5),fontSize:fontSize};
    }
    function build(state,width,height,chart){
        const owner=chart&&animations.get(chart);if(owner){owner.active.clear();}
        const f=root.SYMC_JSLIVE_FORMAT,c=state.settings,d=state.data;
        const presets={line:{path:'M10 10L90 10',box:[10,10,80,10]},rainbow:{path:'M10 10L90 10',box:[10,10,80,10]},
            circle:{path:'M50 10A40 40 0 0 1 50 90A40 40 0 0 1 50 10',box:[10,10,80,80]},
            bubble:{path:'M50 10A40 40 0 0 1 50 90A40 40 0 0 1 50 10',box:[10,10,80,80]},
            fan:{path:'M10 90A40 40 0 0 1 90 90',box:[10,50,80,40]},
            energy:{path:'M15 5L85 5A5 5 0 0 1 85 15L15 15A5 5 0 0 1 15 5',box:[10,5,80,10]},
            stripe:{path:'M15 5L85 5A5 5 0 0 1 85 15L15 15A5 5 0 0 1 15 5',box:[10,5,80,10]},text:{box:[0,0,70,20]}};
        const preset=presets[c.shape_preset];let image=c.shape_svg && typeof c.shape_svg==='object'?c.shape_svg:null;
        let path=c.shape_path || preset.path,box=preset.box.slice(),pathBounds=null;
        if(image){const size=imageSize(image);box=[0,0,size[0],size[1]];}
        else if(path){
            const b=root.echarts.graphic.makePath(path,{}).getBoundingRect();pathBounds=[b.x,b.y,b.width,b.height];
            if(c.shape_path){box=b.width&&b.height?pathBounds.slice():[0,0,100,100];}
        }
        else if(!path){
            // This is a fixed trusted preset, not user SVG. Retain the source
            // glyph baseline and default black paint without relaxing SVG imports.
            image={dataUri:'data:image/svg+xml;base64,'+root.btoa('<svg xmlns="http://www.w3.org/2000/svg" width="70" height="20" viewBox="0 0 70 20"><text x="35" y="10" text-anchor="middle" dominant-baseline="central" font-family="arial">LOADING</text></svg>')};
        }
        const stroke=c.Type==='stroke'&&!image;
        const title=c.title_display?String(c.title_text||''):'',titleSize=18*(c.__fontScale||1),titleSpace=title?titleSize*1.7:0;
        const layout=fit(c,box,width,height,titleSpace);
        const children=[];
        function paint(name){
            const value=state.paints&&state.paints[name];
            if(!value){return f.paint(c,name+'_color');}
            if(value.kind==='color'){return value.color;}
            // CanvasPattern otherwise rasterizes SVG at its intrinsic size and
            // enlarges that bitmap with the path. Render sufficient image pixels
            // while retaining the source pattern's original coordinate period.
            const factor=typeof root.DOMMatrix==='function'?Math.min(Math.floor(4096/Math.max(value.width,value.height)),
                Math.max(1,Math.ceil(layout.scale*(root.devicePixelRatio||1)*2))):1;
            if(chart&&value.kind==='gradient'&&value.duration>0&&c.__animate!==false){return animatedPaint(chart,value,factor,name);}
            if(factor===1){return {image:value.dataUri,repeat:'repeat'};}
            const svg=root.atob(value.dataUri.split(',')[1]).replace(/<svg\b[^>]*>/,function(tag){
                return tag.replace(/\swidth="[^"]*"/,' width="'+Math.ceil(value.width*factor)+'"')
                    .replace(/\sheight="[^"]*"/,' height="'+Math.ceil(value.height*factor)+'"');
            });
            return {image:'data:image/svg+xml;base64,'+root.btoa(svg),repeat:'repeat',scaleX:value.width/Math.ceil(value.width*factor),scaleY:value.height/Math.ceil(value.height*factor)};
        }
        if(!root.echarts.graphic.getShapeClass('jsliveProgressPath')){
            root.echarts.graphic.registerShape('jsliveProgressPath',root.echarts.graphic.extendShape({shape:{pathData:'',strokePath:false},buildPath:function(ctx,shape){
                if(shape.strokePath){strokePath(ctx,shape.pathData);}else{const p=root.echarts.graphic.makePath(shape.pathData,{});p.buildPath(ctx,p.shape);}
            }}));
        }
        // The original template sets "stroke-lincap", which the source engine
        // never consumes. Preserve its effective SVG default, not that raw typo.
        function pathElement(id,paint,isStroke){return {id:id,type:'jsliveProgressPath',shape:{pathData:path,strokePath:isStroke},style:{fill:isStroke?null:paint,stroke:isStroke?paint:null,lineWidth:isStroke?c.stroke_width:0,lineCap:'butt',strokeNoScale:false}};}
        function clip(){const bounds=pathBounds&&!image?pathBounds:box;let x0=bounds[0],y0=bounds[1],w=bounds[2],h=bounds[3];
            if(c.fill_dir==='rtl'){x0+=w*(1-d.ratio);w*=d.ratio;}else if(c.fill_dir==='btt'){y0+=h*(1-d.ratio);h*=d.ratio;}
            else if(c.fill_dir==='ttb'){h*=d.ratio;}else{w*=d.ratio;}
            return {type:'rect',shape:{x:x0,y:y0,width:w,height:h}};
        }
        // Images and path fills share the trusted alpha-mask adapter. Paths use
        // the original object-bounds filter, including its default 1px white
        // stroke before morphology. A native thick stroke is not equivalent.
        function maskedBackground(content,rect,imageFilter){
            const r=Math.abs(c.fill_backgroundExtrude),operator=c.fill_backgroundExtrude>=0?'dilate':'erode';
            const region=' x="'+rect[0]+'" y="'+rect[1]+'" width="'+rect[2]+'" height="'+rect[3]+'"';
            const filter=imageFilter?' filterUnits="userSpaceOnUse"'+region:' x="-1" y="-1" width="3" height="3"';
            const svg='<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="'+rect.join(' ')+'"><defs><filter id="d"'+filter+'><feMorphology in="SourceAlpha" operator="'+operator+'" radius="'+r+'"/></filter><mask id="m" mask-type="alpha" maskUnits="userSpaceOnUse"'+region+'>'+content+'</mask></defs><rect'+region+' fill="'+f.paint(c,'fill_backgroundColor')+'" mask="url(#m)"/></svg>';
            return 'data:image/svg+xml;base64,'+root.btoa(svg);
        }
        if(image){
            const r=Math.abs(c.fill_backgroundExtrude);
            const background=maskedBackground('<image xlink:href="'+image.dataUri+'" width="'+box[2]+'" height="'+box[3]+'" filter="url(#d)"/>',[-r,-r,box[2]+r*2,box[3]+r*2],true);
            children.push({id:'image-background',type:'image',style:{image:background,x:-r,y:-r,width:box[2]+r*2,height:box[3]+r*2}});
            children.push({id:'image-progress',type:'image',clipPath:clip(),style:{image:image.dataUri,x:0,y:0,width:box[2],height:box[3]}});
        }else if(stroke){
            const trail=pathElement('trail',f.paint(c,'stroke_trailColor'),true);trail.style.lineWidth=c.stroke_trailWidth;children.push(trail);
            const foreground=pathElement('progress',paint('stroke'),true);
            if(c.stroke_dir==='reverse'){const total=pathLength(path);foreground.style.lineDash=[0,total*(1-d.ratio),total*d.ratio,0];}
            else{foreground.style.strokePercent=d.ratio;}
            if(c.shape_path&&c.stroke_Dash1>0&&c.stroke_Dash2>0&&c.stroke_dir!=='reverse'){foreground.style.lineDash=[c.stroke_Dash1,c.stroke_Dash2];}
            children.push(foreground);
        }else{
            const empty=!pathBounds[2]||!pathBounds[3],rect=layout.viewBox;
            const background=maskedBackground('<path d="'+f.escape(path)+'" fill="#fff" stroke="#fff" filter="url(#d)"/>',rect,false);
            children.push({id:'background',type:'image',invisible:empty,style:{image:background,x:rect[0],y:rect[1],width:rect[2],height:rect[3]}});
            const foreground=pathElement('progress',paint('fill'),false);foreground.invisible=empty;foreground.clipPath=clip();children.push(foreground);
        }
        const graphics=[{id:'progress-body',type:'group',x:layout.x,y:layout.y,scaleX:layout.scale,scaleY:layout.scale,children:children}];
        if(title){graphics.push({id:'chart-title',type:'text',style:{text:title,x:width/2,y:4,fontSize:titleSize,
            fontFamily:c.style_fontFamily||'Arial',fill:f.paint(c,'style_fontColor','#000'),align:'center',verticalAlign:'top'}});}
        const label=d.label||{prefix:'',suffix:''};
        if(c.style_fontDisplay){graphics.push({id:'progress-label',type:'text',style:{text:label.prefix+String(d.displayValue)+label.suffix,
            x:layout.labelX,y:layout.labelY,
            fill:f.paint(c,'style_fontColor','#000'),fontFamily:c.style_fontFamily||'Times New Roman',fontSize:layout.fontSize,align:'center',verticalAlign:c.style_fontPosition==='center'?'middle':'top',
            textShadowColor:c.style_fontPosition==='center'?'#fff':'transparent',textShadowBlur:c.style_fontPosition==='center'?3:0}});}
        return {backgroundColor:state.background,animation:c.__animate!==false,animationDurationUpdate:c.data_animationDuration*1000,
            graphic:f.graphicTransitions(graphics,function(){return f.escape(f.format(d.value,d.native));}),
            tooltip:{show:c.tooltips_enabled!==false,confine:true,formatter:function(){return f.escape(f.format(d.value,d.native));}}};
    }
    root.SYMC_JSLIVE_OPTION=build;
    if(typeof module==='object'&&module.exports){module.exports=build;}
}(typeof window==='object'?window:globalThis));
