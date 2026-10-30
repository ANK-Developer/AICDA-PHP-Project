// Browser test of the public /rto-form page and the navbar link to it.
// Needs the local site running. Run: node AICDA-PHP-local/tests/ui-rto-form.mjs
import { spawn, execSync } from "node:child_process";
import { mkdirSync, writeFileSync } from "node:fs";
import { tmpdir } from "node:os";
import path from "node:path";

const PORT = 9344;
const OUT = path.join(import.meta.dirname, "screenshots");
mkdirSync(OUT, { recursive: true });
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
const edge = spawn("C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe", ["--headless=new", "--disable-gpu", `--remote-debugging-port=${PORT}`, `--user-data-dir=${path.join(tmpdir(), "aicda-rto-" + Date.now())}`, "--window-size=1366,1000", "--no-first-run", "about:blank"], { stdio: "ignore" });
let pass = 0, fail = 0;
const check = (n, ok, d = "") => { ok ? pass++ : fail++; console.log(`  [${ok ? "PASS" : "FAIL"}] ${n}${ok || !d ? "" : "  -> " + d}`); };

try {
  let wsUrl;
  for (let i = 0; i < 60 && !wsUrl; i++) {
    try { wsUrl = (await (await fetch(`http://127.0.0.1:${PORT}/json/list`)).json()).find((t) => t.type === "page")?.webSocketDebuggerUrl; } catch {}
    if (!wsUrl) await sleep(250);
  }
  const ws = new WebSocket(wsUrl);
  await new Promise((r) => (ws.onopen = r));
  let id = 1; const pending = new Map(); const errors = [];
  ws.onmessage = (e) => {
    const m = JSON.parse(e.data);
    if (m.id && pending.has(m.id)) { pending.get(m.id)(m); pending.delete(m.id); return; }
    if (m.method === "Runtime.exceptionThrown") errors.push(m.params.exceptionDetails.exception?.description?.split("\n")[0] ?? "exception");
  };
  const send = (method, params = {}) => new Promise((res) => { const i = id++; pending.set(i, res); ws.send(JSON.stringify({ id: i, method, params })); });
  const ev = async (x) => (await send("Runtime.evaluate", { expression: x, returnByValue: true, awaitPromise: true })).result?.result?.value;
  const waitFor = async (x, ms = 20000) => { const end = Date.now() + ms; while (Date.now() < end) { try { if (await ev(x)) return true; } catch {} await sleep(150); } return false; };
  const shot = async (name) => writeFileSync(path.join(OUT, name), Buffer.from((await send("Page.captureScreenshot", { format: "png" })).result.data, "base64"));
  await send("Page.enable"); await send("Runtime.enable");

  console.log("1. From the navbar");
  await send("Page.navigate", { url: "http://localhost:8080/" });
  check("home page loads", await waitFor(`!!Array.from(document.querySelectorAll('header a')).find(a => a.innerText.trim() === 'RTO Form')`));
  check("'RTO Form' in the top bar is a link to /rto-form", await ev(`Array.from(document.querySelectorAll('header a')).find(a => a.innerText.trim() === 'RTO Form').getAttribute('href')`) === "/rto-form");
  check("the registration number text is still there", await ev(`document.querySelector('header').innerText.includes('Registration No. - S-55924/2006')`));
  await ev(`Array.from(document.querySelectorAll('header a')).find(a => a.innerText.trim() === 'RTO Form').click()`);
  check("clicking it opens the RTO Form page", await waitFor(`location.pathname === '/rto-form' && document.body.innerText.includes('Insurance Transfer')`));

  console.log("2. Page content");
  const body = await ev(`document.body.innerText`);
  check("heading 'RTO Form'", await ev(`Array.from(document.querySelectorAll('h1')).some(h => h.innerText.trim() === 'RTO Form')`));
  check("'Dear Member,' and the sentence about downloading the forms", body.includes("Dear Member,") && body.includes("You can download all the useful RTO forms from the below links."));
  const items = JSON.parse(await ev(`JSON.stringify(Array.from(document.querySelectorAll('main ul li')).filter(li => li.querySelector('a[href^="/rto-forms/"]')).map(li => { const [t, d] = li.querySelectorAll('a'); return { title: t.innerText.trim(), href: t.getAttribute('href'), dlHref: d.getAttribute('href'), dlAttr: d.hasAttribute('download'), target: t.getAttribute('target') }; }))`));
  const want = ["Cash Receipt", "Form - 26", "Form - 28", "Form - 29", "Form - 30", "Insurance Transfer"];
  check("six forms, in the same order as the old page", JSON.stringify(items.map((x) => x.title)) === JSON.stringify(want), JSON.stringify(items.map((x) => x.title)));
  check("each one has a text link and a download icon pointing to the same PDF", items.length === 6 && items.every((x) => x.href === x.dlHref));
  check("the download icon downloads the file; the text link opens it in a new tab", items.every((x) => x.dlAttr && x.target === "_blank"));

  console.log("3. The PDFs");
  for (const it of items) {
    const r = JSON.parse(await ev(`fetch(${JSON.stringify(it.href)}).then(async (r) => { const b = new Uint8Array(await r.arrayBuffer()); return JSON.stringify({ status: r.status, type: r.headers.get('content-type'), size: b.length, magic: String.fromCharCode(...b.slice(0, 5)) }); })`));
    check(`${it.title}: ${it.href} opens as a real PDF (${Math.round(r.size / 1024)} KB)`, r.status === 200 && /pdf/i.test(r.type) && r.magic === "%PDF-" && r.size > 100000, JSON.stringify(r));
  }

  console.log("4. Look");
  const hero = JSON.parse(await ev(`JSON.stringify(Array.from(document.querySelectorAll('div[aria-hidden="true"][style*="background-image"]')).map(d => d.style.backgroundImage))`));
  check(`the default hero banners are shown (${hero.length} slides)`, hero.length >= 2 && hero.every((u) => !u.includes("/uploads/")));
  check("the sidebar with categories is there", await ev(`!!document.querySelector('aside') && document.querySelector('aside').innerText.includes('Categories')`));
  check("page is titled 'RTO Form · AICDA'", (await ev(`document.title`)).includes("RTO Form"));
  await shot("rto-1-desktop.png");

  console.log("5. Direct link + refresh + phone");
  await send("Page.navigate", { url: "http://localhost:8080/rto-form" });
  check("opening /rto-form directly (refresh) works", await waitFor(`document.body.innerText.includes('Insurance Transfer')`));
  await send("Emulation.setDeviceMetricsOverride", { width: 400, height: 860, deviceScaleFactor: 2, mobile: true });
  await sleep(1000);
  check("phone: no sideways scrolling", await ev(`document.documentElement.scrollWidth <= window.innerWidth + 1`), String(await ev(`document.documentElement.scrollWidth`)));
  check("phone: every form row fits the screen", await ev(`Array.from(document.querySelectorAll('main ul li')).every(li => li.getBoundingClientRect().right <= window.innerWidth + 1)`));
  await shot("rto-2-mobile.png");
  await send("Emulation.clearDeviceMetricsOverride");

  check("no JavaScript errors", errors.length === 0, errors.slice(0, 2).join(" | "));
  ws.close();
} catch (e) {
  fail++; console.log("  [FAIL] crashed: " + e.message);
} finally {
  try { execSync(`taskkill /pid ${edge.pid} /T /F`, { stdio: "ignore" }); } catch {}
  console.log(`\nRESULT: ${pass} passed, ${fail} failed   (pictures in AICDA-PHP-local/tests/screenshots)`);
  process.exit(fail ? 1 : 0);
}
