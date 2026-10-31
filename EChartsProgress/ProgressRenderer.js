(function(root){
    'use strict';
    const lengths=new Map();
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
    function build(state,width,height){
        const f=root.SYMC_JSLIVE_FORMAT,c=state.settings,d=state.data;
        const presets={line:{path:'M10 10L90 10',box:[10,5,80,10]},rainbow:{path:'M10 10L90 10',box:[10,5,80,10]},
            circle:{path:'M50 10A40 40 0 0 1 50 90A40 40 0 0 1 50 10',box:[10,10,80,80]},
            bubble:{path:'M50 10A40 40 0 0 1 50 90A40 40 0 0 1 50 10',box:[10,10,80,80]},
            fan:{path:'M10 90A40 40 0 0 1 90 90',box:[10,50,80,40]},
            energy:{path:'M15 5L85 5A5 5 0 0 1 85 15L15 15A5 5 0 0 1 15 5',box:[10,5,80,10]},
            stripe:{path:'M15 5L85 5A5 5 0 0 1 85 15L15 15A5 5 0 0 1 15 5',box:[10,5,80,10]},text:{box:[0,0,70,20]}};
        const preset=presets[c.shape_preset],image=c.shape_svg && typeof c.shape_svg==='object'?c.shape_svg:null;
        let path=c.shape_path || preset.path,box=preset.box.slice();
        if(image){box=[0,0,image.width,image.height];}
        else if(c.shape_path && root.echarts && root.echarts.graphic){const b=root.echarts.graphic.makePath(path,{}).getBoundingRect();box=[b.x,b.y,Math.max(1,b.width),Math.max(1,b.height)];}
        const textShape=!image&&!path;
        const stroke=c.Type==='stroke'&&!image&&!textShape;
        const labelSpace=c.style_fontDisplay?(c.style_fontPosition==='center'?40:60+c.style_fontSize):20;
        const title=c.title_display?String(c.title_text||''):'',titleSize=18*(c.__fontScale||1),titleSpace=title?titleSize*1.7:0;
        const padding=stroke?Math.max(c.stroke_width,c.stroke_trailWidth):Math.max(1,c.fill_backgroundExtrude);
        const scale=Math.max(1e-9,Math.min(Math.max(1,width-40)/(box[2]+padding*2),Math.max(1,height-labelSpace-titleSpace-20)/(box[3]+padding*2)));
        const bodyW=box[2]*scale,bodyH=box[3]*scale;
        const x=(width-bodyW)/2-box[0]*scale,y=titleSpace+(height-titleSpace-labelSpace-bodyH)/2-box[1]*scale+10;
        const children=[];
        if(!root.echarts.graphic.getShapeClass('jsliveProgressPath')){
            root.echarts.graphic.registerShape('jsliveProgressPath',root.echarts.graphic.extendShape({shape:{pathData:''},buildPath:function(ctx,shape){const p=root.echarts.graphic.makePath(shape.pathData,{});p.buildPath(ctx,p.shape);}}));
        }
        function pathElement(id,paint,isStroke){return {id:id,type:'jsliveProgressPath',shape:{pathData:path},style:{fill:isStroke?null:paint,stroke:isStroke?paint:null,lineWidth:isStroke?c.stroke_width:0,lineCap:c.stroke_lincap || 'butt',strokeNoScale:false}};}
        function clip(){let x0=box[0],y0=box[1],w=box[2],h=box[3];
            if(c.fill_dir==='rtl'){x0+=w*(1-d.ratio);w*=d.ratio;}else if(c.fill_dir==='btt'){y0+=h*(1-d.ratio);h*=d.ratio;}
            else if(c.fill_dir==='ttb'){h*=d.ratio;}else{w*=d.ratio;}
            return {type:'rect',shape:{x:x0,y:y0,width:w,height:h}};
        }
        function tintedImage(color){
            const svg='<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 '+box[2]+' '+box[3]+'"><defs><mask id="m" mask-type="alpha" maskUnits="userSpaceOnUse" x="0" y="0" width="'+box[2]+'" height="'+box[3]+'"><image xlink:href="'+image.dataUri+'" width="'+box[2]+'" height="'+box[3]+'"/></mask></defs><rect width="100%" height="100%" fill="'+color+'" mask="url(#m)"/></svg>';
            return 'data:image/svg+xml;base64,'+root.btoa(svg);
        }
        if(image){
            children.push({id:'image-background',type:'image',style:{image:tintedImage(f.paint(c,'fill_backgroundColor')),x:0,y:0,width:box[2],height:box[3]}});
            children.push({id:'image-progress',type:'image',clipPath:clip(),style:{image:tintedImage(f.paint(c,'fill_color')),x:0,y:0,width:box[2],height:box[3]}});
        }else if(textShape){
            const style={text:'LOADING',x:35,y:10,fontSize:16,fontFamily:'Arial',align:'center',verticalAlign:'middle'};
            children.push({id:'text-background',type:'text',style:Object.assign({fill:f.paint(c,'fill_backgroundColor')},style)});
            children.push({id:'text-progress',type:'text',clipPath:clip(),style:Object.assign({fill:f.paint(c,'fill_color')},style)});
        }else if(stroke){
            const trail=pathElement('trail',f.paint(c,'stroke_trailColor'),true);trail.style.lineWidth=c.stroke_trailWidth;children.push(trail);
            const foreground=pathElement('progress',f.paint(c,'stroke_color'),true);
            if(c.stroke_dir==='reverse'){const total=pathLength(path);foreground.style.lineDash=[0,total*(1-d.ratio),total*d.ratio,0];}
            else{foreground.style.strokePercent=d.ratio;}
            if(c.shape_path&&c.stroke_Dash1>0&&c.stroke_Dash2>0&&c.stroke_dir!=='reverse'){foreground.style.lineDash=[c.stroke_Dash1,c.stroke_Dash2];}
            children.push(foreground);
        }else{
            const background=pathElement('background',f.paint(c,'fill_backgroundColor'),false);
            background.style.stroke=f.paint(c,'fill_backgroundColor');background.style.lineWidth=c.fill_backgroundExtrude*2;children.push(background);
            const foreground=pathElement('progress',f.paint(c,'fill_color'),false);foreground.clipPath=clip();children.push(foreground);
        }
        const graphics=[{id:'progress-body',type:'group',x:x,y:y,scaleX:scale,scaleY:scale,children:children}];
        if(title){graphics.push({id:'chart-title',type:'text',style:{text:title,x:width/2,y:4,fontSize:titleSize,
            fontFamily:c.style_fontFamily||'Arial',fill:f.paint(c,'style_fontColor','#000'),align:'center',verticalAlign:'top'}});}
        if(c.style_fontDisplay){graphics.push({id:'progress-label',type:'text',style:{text:(d.effective.prefix||'')+String(Number(d.displayValue.toPrecision(14)))+(d.effective.suffix||''),
            x:width/2,y:c.style_fontPosition==='center'?y+(box[1]+box[3]/2)*scale:Math.min(height-16,y+(box[1]+box[3])*scale+30+c.style_fontSize/2),
            fill:f.paint(c,'style_fontColor','#000'),fontFamily:c.style_fontFamily||'Arial',fontSize:c.style_fontSize*(c.__fontScale||1),align:'center',verticalAlign:'middle'}});}
        return {backgroundColor:state.background,animation:c.__animate!==false,animationDurationUpdate:c.data_animationDuration*1000,
            graphic:f.graphicTransitions(graphics,function(){return f.escape(f.format(d.value,d.native));}),
            tooltip:{show:c.tooltips_enabled!==false,confine:true,formatter:function(){return f.escape(f.format(d.value,d.native));}}};
    }
    root.SYMC_JSLIVE_OPTION=build;
    if(typeof module==='object'&&module.exports){module.exports=build;}
}(typeof window==='object'?window:globalThis));
