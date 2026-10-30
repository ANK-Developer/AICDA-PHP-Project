import { spawn, execSync } from "node:child_process"; import { tmpdir } from "node:os"; import path from "node:path";
const PORT = 9343, sleep = (ms) => new Promise((r) => setTimeout(r, ms));
const edge = spawn("C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe", ["--headless=new","--disable-gpu",`--remote-debugging-port=${PORT}`,`--user-data-dir=${path.join(tmpdir(),"dbg2-"+Date.now())}`,"--window-size=1366,1000","--no-first-run","about:blank"],{stdio:"ignore"});
let wsUrl; for (let i=0;i<60&&!wsUrl;i++){ try{ wsUrl=(await (await fetch(`http://127.0.0.1:${PORT}/json/list`)).json()).find(t=>t.type==="page")?.webSocketDebuggerUrl;}catch{} if(!wsUrl) await sleep(250); }
const ws=new WebSocket(wsUrl); await new Promise(r=>ws.onopen=r); let id=1; const pend=new Map(); const calls=[]; const errs=[];
ws.onmessage=(e)=>{const m=JSON.parse(e.data); if(m.id&&pend.has(m.id)){pend.get(m.id)(m);pend.delete(m.id);return;} if(m.method==="Network.requestWillBeSent"&&m.params.request.url.includes("/api/v1/gallery")) calls.push(m.params.request.url.split("gallery")[1]); if(m.method==="Runtime.exceptionThrown") errs.push(m.params.exceptionDetails.exception?.description?.split("\n")[0]);};
const send=(method,params={})=>new Promise(res=>{const i=id++;pend.set(i,res);ws.send(JSON.stringify({id:i,method,params}));});
const ev=async(x)=>(await send("Runtime.evaluate",{expression:x,returnByValue:true})).result?.result?.value;
await send("Page.enable");await send("Network.enable");await send("Runtime.enable");
const page = process.argv[2] || "/letter";
await send("Page.navigate",{url:"http://localhost:8080"+page}); await sleep(4000);
const state = () => ev(`JSON.stringify({ tiles: document.querySelectorAll('button.group').length, first: document.querySelector('button.group img')?.getAttribute('src')?.slice(-28), pageText: (document.body.innerText.match(/Page\s*:\s*\d+\s*of\s*\d+/)||[''])[0], total: (document.body.innerText.match(/Total[^:]*:\s*(\d+)/)||[])[1] })`);
console.log("before:", await state());
const btns = await ev(`JSON.stringify(Array.from(document.querySelectorAll('button')).map(b=>b.innerText.trim()).filter(Boolean).slice(0,12))`); console.log("buttons:", btns);
if (page === "/image") { console.log("click Video:", await ev(`(()=>{const b=Array.from(document.querySelectorAll('button')).find(x=>x.innerText.trim()==='Video'); if(!b) return 'no button'; b.click(); return 'clicked';})()`)); }
else { console.log("click Next:", await ev(`(()=>{const b=Array.from(document.querySelectorAll('button')).find(x=>x.innerText.trim()==='Next'); if(!b) return 'no button'; if(b.disabled) return 'disabled'; b.click(); return 'clicked';})()`)); }
for (const t of [600, 1500, 3000]) { await sleep(t===600?600:1000); console.log("after", t, await state()); }
console.log("requests:", JSON.stringify(calls)); console.log("errors:", JSON.stringify(errs));
try { execSync(`taskkill /pid ${edge.pid} /T /F`, { stdio: "ignore" }); } catch {} process.exit(0);
