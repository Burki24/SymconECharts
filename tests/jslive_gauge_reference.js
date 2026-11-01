'use strict';

// Optional independent reference check. Reads the original engine from a sibling
// checkout; never shipped, imported or required by the ordinary CI suite.
const fs=require('node:fs'),path=require('node:path'),vm=require('node:vm'),assert=require('node:assert/strict'),cp=require('node:child_process');
const root=path.join(__dirname,'..'),referenceRoot=path.resolve(process.argv[2]||''),ec=require(path.join(root,'.tools/echarts-runtime/node_modules/echarts'));
const states=JSON.parse(cp.execFileSync('php',['tests/jslive_models.php','--emit'],{cwd:root,encoding:'utf8',maxBuffer:8388608}));
const box={module:{exports:{}},GAUGES_NO_AUTO_INIT:true,console,setTimeout,clearTimeout,devicePixelRatio:1};box.window=box;
if(!process.argv[2])throw new Error('Usage: node tests/jslive_gauge_reference.js <JSLive checkout> [--snapshot <output JSON>]');
const bundle=fs.readFileSync(path.join(referenceRoot,'SymconJSLive/js/canvas-gauges/2.1.7-jslive.1/gauge.min.js'),'utf8');
const sourceHash=require('node:crypto').createHash('sha256').update(bundle).digest('hex');
assert.equal(sourceHash,'2cde1666441e112088cf3094f29e187f0d818ce4a2b9939091b890e9f132520e','Review a changed reference engine before regenerating geometry.');
vm.runInNewContext(bundle,box);
const lib=box.module.exports;
const window={echarts:ec,SYMC_VISUALIZATION:{options:{locale:'de-DE'}}},ctx=vm.createContext({window});
for(const file of ['libs/jslive/format.js','EChartsGaugeSingle/JSLiveRenderer.js'])vm.runInContext(fs.readFileSync(path.join(root,file),'utf8'),ctx);
let assertions=0,fontFamilies=0;const snapshots=[];
function near(a,b,t,why){assert.ok(Math.abs(a-b)<=t,why+': '+a+' vs '+b);++assertions;}
function surface(x,y,r,phase,log){
 let matrix=[1,0,0,1,x,y],stack=[],points=[];
 const pos=(x,y)=>[matrix[0]*x+matrix[2]*y+matrix[4],matrix[1]*x+matrix[3]*y+matrix[5]];
 let font='10px sans-serif';
 const target={max:r,maxRadius:null,textAlign:'start',textBaseline:'alphabetic',
 save(){stack.push({matrix:[...matrix],font:this.font,textAlign:this.textAlign,textBaseline:this.textBaseline});},
 restore(){const p=stack.pop();if(p){matrix=p.matrix;Object.assign(this,{font:p.font,textAlign:p.textAlign,textBaseline:p.textBaseline});}},
 translate(x,y){matrix[4]+=matrix[0]*x+matrix[2]*y;matrix[5]+=matrix[1]*x+matrix[3]*y;},
 rotate(a){const [u,v,w,z]=matrix;matrix[0]=u*Math.cos(a)+w*Math.sin(a);matrix[1]=v*Math.cos(a)+z*Math.sin(a);matrix[2]=-u*Math.sin(a)+w*Math.cos(a);matrix[3]=-v*Math.sin(a)+z*Math.cos(a);},
 beginPath(){points=[];},moveTo(x,y){points.push(pos(x,y));},lineTo(x,y){points.push(pos(x,y));},
 fill(){if(phase.name==='beforeNeedle'&&points.length>=4)log.paths.push(points.map(p=>[...p]));},
 stroke(){if(phase.name==='beforeNeedle'&&points.length===2)log.paths.push(points.map(p=>[...p]));},
 fillText(text,x,y,maxWidth){log.texts.push({phase:phase.name,text:String(text),point:pos(x,y),font:this.font,baseline:this.textBaseline,maxWidth});},
 measureText(text){return {width:ec.format.getTextRect(String(text),this.font).width};},
 createLinearGradient(){return {addColorStop(){}};},createRadialGradient(){return {addColorStop(){}};}
 };
 // Canvas ignores invalid CSS fonts. In particular an empty unit family leaves
 // the previously active font in place; a plain property fake would miss this.
 Object.defineProperty(target,'font',{get:()=>font,set:value=>{if(/\d(?:\.\d+)?px\s+\S/.test(value))font=value;}});
 return new Proxy(target,{get:(t,k)=>k in t?t[k]:()=>{}});
}
function reference(state,width,height){
 const c=state.settings,compass=c.template==='CanvasGauges-Compass',linear=c.template.includes('Linear'),vertical=c.template.includes('(vertical)');
 let w=width-20,h=height-20;if(compass)w=h=Math.min(width,height)-20;if(linear){if(vertical)w=Math.min(w,h/2);else h=Math.min(w/4,h);}
 const ticks=compass?['N','NE','E','SE','S','SW','W','NW','N']:[c.min,...c.Ticks.slice().sort((a,b)=>a.Value-b.Value).map(x=>x.Value),c.max].map(x=>window.SYMC_JSLIVE_FORMAT.number(x,c.precision,'.',''));
 const o={...lib.GenericOptions,width:w,height:h,value:compass?0:state.data.value,minValue:compass?0:c.min,maxValue:compass?360:c.max,
 majorTicks:ticks,exactTicks:compass?c.ticks_exactTicks:c.ticks_exactTicks,minorTicks:compass?22:c.ticks_minorTicks,startAngle:compass?180:c.radial_startAngle,ticksAngle:compass?360:c.radial_ticksAngle,
 title:c.title_display?c.title_text:false,units:c.plate_unit,fontTitleSize:c.title_fontSize,fontUnitsSize:c.plate_unit_fontSize,fontNumbersSize:c.ticks_fontSize,fontValueSize:c.valuebox_fontSize,
 fontTitle:c.title_fontFamily||'Arial',fontUnits:c.plate_unit_fontFamily,fontNumbers:c.ticks_fontFamily||'Arial',fontValue:c.valuebox_fontFamily||'Arial',
 borderOuterWidth:compass?3:0,borderMiddleWidth:0,borderInnerWidth:compass?3:0,borderShadowWidth:0,
 needle:c.needle_display,needleType:c.needle_Type,needleStart:c.needle_start,needleEnd:c.needle_end,needleWidth:c.needle_width,needleCircleSize:c.needle_CircleSize,needleCircleInner:true,needleCircleOuter:c.needle_CircleOuter,
 tickSide:c.linear_tickSide,numberSide:c.linear_numberSide,needleSide:c.linear_needleSide,strokeTicks:compass?false:c.ticks_strokeTicks,
 barWidth:compass?0:(c.progressbar_display?c.progressbar_barWidth:0),barStrokeWidth:0,barBeginCircle:false,barLength:85,ticksPadding:5,ticksWidth:10,ticksWidthMinor:5,
 barStartPosition:linear||compass?'left':c.progressbar_barStartPosition,animationTarget:c.animation_target,animation:false,
 valueBox:!compass&&c.valuebox_display,valueDec:c.precision,valueInt:1,valueText:window.SYMC_JSLIVE_FORMAT.number(state.data.value,c.precision,',',c.valuebox_separator?'.':''),valueTextShadow:false,valueBoxStroke:0,valueBoxWidth:10,
 highlights:compass?[]:c.Highlights.map(h=>({from:h.From,to:h.To,color:'#123'})),highlightsWidth:c.ticks_highlightsWidth};
 const Type=linear?lib.LinearGauge:lib.RadialGauge,g=Object.create(Type.prototype),phase={name:''},log={texts:[],paths:[]};
 // The reference uses instanceof Array, so its options must share its VM realm.
 box.__encoded=JSON.stringify(o);g.options=Type.configure(vm.runInContext('JSON.parse(__encoded)',box));g.emit=name=>{phase.name=name;};
 const mx=width/2,my=8+h/2,r=Math.min(w,h)/2;
 g.canvas={drawX:w/2,drawY:h/2,drawWidth:w,drawHeight:h,context:surface(mx,my,r,phase,log),contextClone:surface(mx,my,r,phase,log),elementClone:{initialized:false},commit(){}};
 g.draw();
 // The Compass template first draws curValue=0, then loads the source value.
 // A rotating plate keeps the first needle in its cached canvas.
 if(compass){if(c.animation_target==='needle')log.paths=[];g.options.value=state.data.value;g.draw();}
 return log;
}
for(let i=14;i<22;i++)for(const [width,height] of [[620,500],[1200,700],[300,242]]){
 const state=states[i].chart,original=reference(state,width,height),option=window.SYMC_JSLIVE_OPTION(state,width,height);
 const record={case:i+1,width,height,texts:{},absent:[],needle:null};
 for(const [phase,id] of [['beforeTitle','title'],['beforeUnits','unit'],['beforeNumbers',state.settings.template.includes('Linear')?(state.settings.linear_numberSide==='right'?'number:right:0':'number:left:0'):'number:0'],['beforeValueBox','value']]){
  const matches=original.texts.filter(x=>x.phase===phase&&x.point.every(Number.isFinite)),needle=option.graphic.find(x=>x.id===id);
  if(!matches.length){assert.equal(!!needle,false,'Unexpected '+id+' case '+(i+1));record.absent.push(id);continue;}
  assert.ok(needle,'Missing '+id+' case '+(i+1));
  const ref=matches[0],size=Number(/([\d.]+)px/.exec(ref.font)[1]),ry=ref.point[1]-(ref.baseline==='alphabetic'?size*0.35:0);
  const family=ref.font.split(/px\s+/)[1];
  assert.equal(needle.style.fontFamily,family,'Source font family '+id+' case '+(i+1));++fontFamilies;
  near(needle.style.fontSize,size,0.01,'font '+id+' case '+(i+1));
  near(needle.style.x,ref.point[0],0.6,'x '+id+' case '+(i+1));
  near(needle.style.y,ry,0.6,'y '+id+' case '+(i+1));
  record.texts[id]={fontSize:size,family,x:ref.point[0],baselineY:ref.point[1],baseline:ref.baseline};
 }
 const linear=state.settings.template.includes('Linear'),id=linear?'needle:'+(state.settings.linear_needleSide==='right'?'right':'left'):'needle',n=option.graphic.find(x=>x.id===id);
 if(n&&original.paths.length){const points=original.paths[0],actual=n.type==='line'?[[n.shape.x1,n.shape.y1],[n.shape.x2,n.shape.y2]]:n.shape.points;assert.equal(actual.length,points.length);points.forEach((p,j)=>{near(actual[j][0],p[0],linear?0.6:0.01,'needle x '+(i+1));near(actual[j][1],p[1],linear?0.6:0.01,'needle y '+(i+1));});record.needle={id,points};}
 snapshots.push(record);
}
console.log('Original Canvas Gauges 2.1.7 reference: '+assertions+' native measurements and '+fontFamilies+' font-family checks passed (8 variants x 3 sizes).');
if(process.argv[3]==='--snapshot')fs.writeFileSync(path.resolve(process.argv[4]),JSON.stringify({source:'Canvas Gauges 2.1.7 / JSLive 2.1.7-jslive.1',sha256:'2cde1666441e112088cf3094f29e187f0d818ce4a2b9939091b890e9f132520e',cases:snapshots},null,4)+'\n');
