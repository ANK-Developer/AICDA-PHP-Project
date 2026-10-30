// Takes a picture of the sidebar of /directory (scrolled to the card under the categories).
import { spawn, execSync } from "node:child_process"; import { tmpdir } from "node:os"; import path from "node:path"; import { writeFileSync } from "node:fs";
const PORT = 9341, sleep = (ms) => new Promise((r) => setTimeout(r, ms));
const edge = spawn("C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe", ["--headless=new", "--disable-gpu", `--remote-debugging-port=${PORT}`, `--user-data-dir=${path.join(tmpdir(), "aicda-sb-" + Date.now())}`, "--window-size=1366,1200", "--no-first-run", "about:blank"], { stdio: "ignore" });
let wsUrl; for (let i = 0; i < 60 && !wsUrl; i++) { try { wsUrl = (await (await fetch(`http://127.0.0.1:${PORT}/json/list`)).json()).find((t) => t.type === "page")?.webSocketDebuggerUrl; } catch {} if (!wsUrl) await sleep(250); }
const ws = new WebSocket(wsUrl); await new Promise((r) => (ws.onopen = r));
let id = 1; const pend = new Map(); ws.onmessage = (e) => { const m = JSON.parse(e.data); if (m.id && pend.has(m.id)) { pend.get(m.id)(m); pend.delete(m.id); } };
const send = (method, params = {}) => new Promise((res) => { const i = id++; pend.set(i, res); ws.send(JSON.stringify({ id: i, method, params })); });
const ev = async (x) => (await send("Runtime.evaluate", { expression: x, returnByValue: true })).result?.result?.value;
await send("Page.enable"); await send("Page.navigate", { url: "http://localhost:8080/directory" });
for (let i = 0; i < 100 && !(await ev(`document.querySelectorAll('aside img').length === 1 && document.querySelector('aside img').complete`)); i++) await sleep(200);
await ev(`document.querySelector('aside').scrollIntoView()`); await sleep(800);
writeFileSync(process.argv[2], Buffer.from((await send("Page.captureScreenshot", { format: "png" })).result.data, "base64"));
try { execSync(`taskkill /pid ${edge.pid} /T /F`, { stdio: "ignore" }); } catch {} process.exit(0);
