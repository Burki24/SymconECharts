import fs from 'node:fs';
import path from 'node:path';
import os from 'node:os';
import cp from 'node:child_process';
import assert from 'node:assert/strict';
import { fileURLToPath } from 'node:url';
const root=path.dirname(path.dirname(fileURLToPath(import.meta.url)));
const [browserExecutable]=process.argv.slice(2);
if(!browserExecutable || !path.isAbsolute(browserExecutable) || !fs.existsSync(browserExecutable) || !fs.statSync(browserExecutable).isFile()) throw new Error('Usage: node tests/tree_json_browser.mjs <existing absolute Chromium executable>');
let artifact;
const profile=fs.mkdtempSync(path.join(os.tmpdir(),'echarts-tree-json-browser-'));
artifact=profile+'.png';
const server=cp.spawn('node',['tests/tree_browser.mjs'],{cwd:root,env:{...process.env,TREE_BROWSER_PORT:'0'},windowsHide:true});
const browser=cp.spawn(browserExecutable,['--headless=new','--no-first-run','--no-default-browser-check','--remote-debugging-port=0','--user-data-dir='+profile,'--window-size=1440,1100','about:blank'],{windowsHide:true,stdio:'ignore'});
let ws;
const pause=ms=>new Promise(r=>setTimeout(r,ms));
try {
 const url=await new Promise((resolve,reject)=>{const timeout=setTimeout(()=>reject(new Error('Fixture did not start')),10000);server.stdout.on('data',chunk=>{const m=String(chunk).match(/http:\/\/127.0.0.1:\d+/);if(m){clearTimeout(timeout);resolve(m[0]);}});server.on('error',reject);});
 let port;
 for(let i=0;i<100;i++){if(fs.existsSync(path.join(profile,'DevToolsActivePort'))){port=fs.readFileSync(path.join(profile,'DevToolsActivePort'),'utf8').split('\n')[0];break;}await pause(100);}
 assert.ok(port,'Isolated browser did not start');
 const targets=await(await fetch('http://127.0.0.1:'+port+'/json/list')).json();
 ws=new WebSocket(targets.find(t=>t.type==='page').webSocketDebuggerUrl);
 await new Promise((r,j)=>{ws.addEventListener('open',r,{once:true});ws.addEventListener('error',j,{once:true});});
 let seq=0;const pending=new Map(),errors=[];
 ws.addEventListener('message',event=>{const m=JSON.parse(event.data);if(m.method==='Runtime.exceptionThrown')errors.push(m.params.exceptionDetails.text);if(m.id&&pending.has(m.id)){const p=pending.get(m.id);pending.delete(m.id);clearTimeout(p.timer);m.error?p.reject(new Error(JSON.stringify(m.error))):p.resolve(m.result);}});
 const send=(method,params={})=>new Promise((resolve,reject)=>{const id=++seq;pending.set(id,{resolve,reject,timer:setTimeout(()=>{pending.delete(id);reject(new Error('CDP timeout: '+method));},10000)});ws.send(JSON.stringify({id,method,params}));});
 const evaluate=async expression=>{const r=await send('Runtime.evaluate',{expression,returnByValue:true,awaitPromise:true});if(r.exceptionDetails)throw new Error(JSON.stringify(r.exceptionDetails));return r.result.value;};
 await send('Runtime.enable');await send('Page.enable');await send('Page.navigate',{url:url});
 for(let i=0;i<100;i++){if(await evaluate('document.querySelectorAll("iframe").length===2 && [...document.querySelectorAll("iframe")].every(f=>f.contentWindow.echarts && f.contentWindow.echarts.getInstanceByDom(f.contentDocument.getElementById("echarts-tree-chart")))'))break;await pause(100);}
 const model=JSON.parse(cp.execFileSync('php',['-r',"require 'libs/EChartsTreeJSON.php'; $m=\\SymconECharts\\EChartsTreeJSON::Read(file_get_contents('docs/examples/tree-network-1.0.json'),false,fn($id)=>[],fn($id)=>''); echo json_encode(\\SymconECharts\\EChartsTreeJSON::Project($m,12,3000,'document'));"],{cwd:root,encoding:'utf8'}));
 await evaluate('document.querySelector("h1").textContent="Tree JSON · lokale Testeinbettung mit synthetischen Daten"; true');
 const state={status:'ready',sourceContext:'local-json-source',chart:{family:'tree',theme:'auto',tree:{title:'Beispielnetzwerk · Tree JSON',...model,style:{treeLayout:'orthogonal',orientation:'LR',initialDepth:12,roamEnabled:true,showLabels:true,showValues:true,animationEnabled:false}}}};
 await evaluate('window.testJSONState='+JSON.stringify(state)+'; [...document.querySelectorAll("iframe")].forEach(f=>{f.contentWindow.handleMessage(window.testJSONState);f.contentWindow.wheels=[];f.contentWindow.addEventListener("wheel",e=>f.contentWindow.wheels.push({trusted:e.isTrusted,deltaY:e.deltaY}),true);}); true');
 const observations=[];
 for(let i=0;i<2;i++){
  const probe=()=>evaluate('(()=>{const f=document.querySelectorAll("iframe")['+i+'];const c=f.contentWindow.echarts.getInstanceByDom(f.contentDocument.getElementById("echarts-tree-chart"));const s=c.getModel().getSeriesByIndex(0);const r=f.getBoundingClientRect();return {zoom:s.get("zoom")||1,count:s.getData().count(),x:r.x+r.width/2,y:r.y+r.height/2,scrollY:window.scrollY,wheels:f.contentWindow.wheels};})()');
  const before=await probe();assert.ok(before.count>=6);
  await send('Input.dispatchMouseEvent',{type:'mouseMoved',x:before.x,y:before.y});
  await send('Input.dispatchMouseEvent',{type:'mouseWheel',x:before.x,y:before.y,deltaX:0,deltaY:-120});await pause(150);
  const up=await probe();assert.ok(up.zoom>before.zoom,'Wheel up must zoom the actual chart');
  await send('Input.dispatchMouseEvent',{type:'mouseWheel',x:before.x,y:before.y,deltaX:0,deltaY:120});await pause(150);
  const down=await probe();assert.ok(down.zoom<up.zoom,'Wheel down must reverse zoom');assert.equal(down.scrollY,before.scrollY,'Chart wheel must not scroll the outer fixture');assert.ok(down.wheels.length>=2&&down.wheels.every(w=>w.trusted));
  observations.push({mode:i===0?'tile-layout':'ipsview-layout',before:before.zoom,up:up.zoom,down:down.zoom,trustedWheels:down.wheels.length});
 }
 const stale=structuredClone(state);stale.chart.tree.source={mode:'variable',quality:'stale',acceptedAt:1793771784,diagnostic:'JSON_INVALID'};
 await evaluate('[...document.querySelectorAll("iframe")].forEach(f=>f.contentWindow.handleMessage('+JSON.stringify(stale)+')); true');
 assert.equal(await evaluate('[...document.querySelectorAll("iframe")].every(f=>!f.contentDocument.getElementById("echarts-tree-warning").hidden && f.contentDocument.getElementById("echarts-tree-warning").textContent.includes("2026"))'),true);
 const screenshot=await send('Page.captureScreenshot',{format:'png',captureBeyondViewport:false});fs.writeFileSync(artifact,Buffer.from(screenshot.data,'base64'));
 const changed={status:'error',sourceContext:'different-json-source',chart:null,error:'The Tree could not be loaded.'};
 await evaluate('[...document.querySelectorAll("iframe")].forEach(f=>f.contentWindow.handleMessage('+JSON.stringify(changed)+')); true');
 assert.equal(await evaluate('[...document.querySelectorAll("iframe")].every(f=>f.contentDocument.getElementById("echarts-tree-chart").hidden)'),true);
 assert.deepEqual(errors,[]);
 console.log(JSON.stringify({passed:true,observations,staleVisible:true,changedSourceClearsPreviousChart:true,errors,artifact}));
 try{await send('Browser.close');}catch{}
} finally {
 if(ws)ws.close();server.kill();browser.kill();
 const relative=path.relative(os.tmpdir(),profile);if(relative.startsWith('echarts-tree-json-browser-')&&!relative.includes('..')){try{fs.rmSync(profile,{recursive:true,force:true,maxRetries:20,retryDelay:100});}catch{}}
}
