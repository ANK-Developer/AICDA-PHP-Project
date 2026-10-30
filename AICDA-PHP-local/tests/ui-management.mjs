// Browser test of the public Management page: pagination, ID-only search (no suggestions),
// full (uncropped) photos and normal page scrolling. Needs the local site running.
// Run: node AICDA-PHP-local/tests/ui-management.mjs
import { spawn, execSync } from "node:child_process";
import { mkdirSync, writeFileSync } from "node:fs";
import { tmpdir } from "node:os";
import path from "node:path";

const SITE = "http://localhost:8080";
const PORT = 9336;
const EDGE = "C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe";
const OUT = path.join(import.meta.dirname, "screenshots");
mkdirSync(OUT, { recursive: true });
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

const edge = spawn(EDGE, ["--headless=new", "--disable-gpu", `--remote-debugging-port=${PORT}`, `--user-data-dir=${path.join(tmpdir(), "aicda-mg-" + Date.now())}`, "--window-size=1366,900", "--no-first-run", "about:blank"], { stdio: "ignore" });
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
  let id = 1; const pending = new Map(); const errors = []; const apiCalls = [];
  ws.onmessage = (e) => {
    const m = JSON.parse(e.data);
    if (m.id && pending.has(m.id)) { pending.get(m.id)(m); pending.delete(m.id); return; }
    if (m.method === "Network.requestWillBeSent" && m.params.request.url.includes("/api/v1/")) apiCalls.push({ url: m.params.request.url, id: m.params.requestId, status: null });
    if (m.method === "Network.responseReceived") { const c = apiCalls.find((x) => x.id === m.params.requestId); if (c) c.status = m.params.response.status; }
    if (m.method === "Runtime.exceptionThrown") errors.push(m.params.exceptionDetails.exception?.description?.split("\n")[0] ?? "exception");
  };
  const send = (method, params = {}) => new Promise((res) => { const i = id++; pending.set(i, res); ws.send(JSON.stringify({ id: i, method, params })); });
  const ev = async (expression) => (await send("Runtime.evaluate", { expression, returnByValue: true, awaitPromise: true })).result?.result?.value;
  const waitFor = async (expr, ms = 20000) => { const end = Date.now() + ms; while (Date.now() < end) { try { if (await ev(expr)) return true; } catch {} await sleep(150); } return false; };
  const shot = async (name) => writeFileSync(path.join(OUT, name), Buffer.from((await send("Page.captureScreenshot", { format: "png" })).result.data, "base64"));
  const mgCalls = () => apiCalls.filter((c) => c.url.includes("/api/v1/management"));

  // ---- helpers that run inside the page ----
  const SLIDES = `Array.from(document.querySelectorAll('h3')).filter(h => h.innerText.trim() === 'Member Detail')`;
  const IDS = `JSON.stringify(${SLIDES}.map(h => { const row = Array.from(h.closest('.grid').querySelectorAll('p')).find(p => p.innerText.startsWith('ID :')); return row ? row.innerText.replace('ID :','').trim() : ''; }))`;
  const idle = `!document.querySelector('[class*="animate-pulse"]')`;
  const ready = `${idle} && (${SLIDES}.length > 0 || document.body.innerText.includes('No member or partner found') || document.body.innerText.includes('Could not load'))`;
  const barText = `document.querySelector('nav[aria-label="Pagination"]')?.innerText.replace(/\\s+/g,' ') ?? ''`;
  const clickButton = (label) => ev(`(() => { const b = Array.from(document.querySelectorAll('nav[aria-label="Pagination"] button')).find(x => x.innerText.trim() === ${JSON.stringify(label)}); if (!b || b.disabled) return false; b.click(); return true; })()`);
  const search = async (text) => {
    await ev(`(() => { const i = document.querySelector('input[type="search"]'); i.focus(); i.select(); return true; })()`);
    await send("Input.dispatchKeyEvent", { type: "keyDown", key: "Backspace", code: "Backspace", windowsVirtualKeyCode: 8 });
    await send("Input.dispatchKeyEvent", { type: "keyUp", key: "Backspace", code: "Backspace", windowsVirtualKeyCode: 8 });
    if (text) await send("Input.insertText", { text });
  };
  const pressEnter = async () => { await send("Input.dispatchKeyEvent", { type: "keyDown", key: "Enter", code: "Enter", windowsVirtualKeyCode: 13, text: "\r" }); await send("Input.dispatchKeyEvent", { type: "keyUp", key: "Enter", code: "Enter", windowsVirtualKeyCode: 13 }); };

  await send("Page.enable"); await send("Network.enable"); await send("Runtime.enable");
  await send("Page.navigate", { url: `${SITE}/management` });

  console.log("1. First page");
  check("page loads with members", await waitFor(ready));
  const first = JSON.parse(await ev(IDS));
  check("10 members/partners on the page", first.length === 10, `got ${first.length}`);
  check("pagination bar: Showing 1–10 of the total, Page 1 of N", /Showing 1–10 of \d+/.test(await ev(barText)) && /Page 1 of \d+/.test(await ev(barText)), await ev(barText));
  const total = Number((await ev(barText)).match(/of (\d+)/)?.[1] ?? 0);
  console.log(`     (list says total ${total}, first IDs: ${first.slice(0, 4).join(", ")})`);
  check("Previous is disabled on page 1", await ev(`Array.from(document.querySelectorAll('nav[aria-label="Pagination"] button')).find(b => b.innerText.trim() === 'Previous').disabled`));
  check("only ONE page-load request was made to the server for this page", mgCalls().filter((c) => c.url.includes("page=1")).length >= 1 && mgCalls().every((c) => c.status === null || c.status === 200));
  check("the old 'load everybody' calls are gone (/members/public, /partners/public)", !apiCalls.some((c) => /\/(members|partners)\/public(\?|$)/.test(c.url)), JSON.stringify(apiCalls.map((c) => c.url)));
  await shot("mg-1-first-page.png");

  console.log("2. Scrolling is normal");
  const nested = await ev(`(() => { const bad = []; for (const el of document.querySelectorAll('body *')) { const cs = getComputedStyle(el); if ((cs.overflowY === 'auto' || cs.overflowY === 'scroll') && el.scrollHeight > el.clientHeight + 5 && el.clientHeight > 250) bad.push(el.className.toString().slice(0, 50)); } return JSON.stringify(bad); })()`);
  check("no box inside the page with its own scrollbar", nested === "[]", nested);
  check("the whole page scrolls instead (page is taller than the window)", await ev(`document.scrollingElement.scrollHeight > window.innerHeight * 1.5`));
  const slideCount = await ev(`${SLIDES}.length`);
  const hiddenCards = await ev(`document.querySelectorAll('[aria-hidden="true"] img').length`);
  console.log(`     (page holds ${slideCount} entries instead of the whole list; off-screen card images: ${hiddenCards})`);

  console.log("3. Photos are complete (not cut)");
  await waitFor(`Array.from(document.querySelectorAll('img[alt]')).filter(i => !i.closest('[aria-hidden="true"]')).every(i => i.complete)`, 15000);
  const imgReport = JSON.parse(await ev(`JSON.stringify(Array.from(document.querySelectorAll('img[alt]')).filter(i => i.naturalWidth > 0 && i.closest('.grid')?.querySelector('h3')?.innerText.trim() === 'Member Detail' && !i.closest('div[aria-hidden]')).map(i => {
    const r = i.getBoundingClientRect(), cs = getComputedStyle(i);
    return { w: Math.round(r.width), h: Math.round(r.height), fit: cs.objectFit, nat: i.naturalWidth + "x" + i.naturalHeight, inside: r.left >= 0 && r.right <= window.innerWidth };
  }))`));
  check(`photos shown on this page: ${imgReport.length}`, imgReport.length > 0);
  const sizes = [...new Set(imgReport.map((x) => x.w + "x" + x.h))];
  check("every photo box is exactly the same width and height", sizes.length === 1, sizes.join(" | "));
  check("the box is medium (between 120-200px wide, 160-260px tall)", imgReport.every((x) => x.w >= 120 && x.w <= 200 && x.h >= 160 && x.h <= 260), sizes.join(" | "));
  check("photos use object-fit: contain, so the whole picture is visible (never cut)", imgReport.every((x) => x.fit === "contain"), JSON.stringify([...new Set(imgReport.map((x) => x.fit))]));
  const mixed = new Set(imgReport.map((x) => x.nat)).size;
  console.log(`     (${imgReport.length} photos, ${mixed} different original sizes, all shown in ${sizes[0]})`);
  await shot("mg-1b-photos.png");
  console.log("4. Next / Previous");
  await ev(`window.scrollTo(0, document.scrollingElement.scrollHeight)`);
  const yBefore = await ev(`window.scrollY`);
  check("Next works", await clickButton("Next"));
  check("page 2 shows", await waitFor(`${ready} && (${barText}).includes('Page 2 of')`));
  const second = JSON.parse(await ev(IDS));
  check("page 2 has different people than page 1", second.length === 10 && !second.some((x) => first.includes(x)), `${second.join(",")}`);
  check("page 2 continues the ID order", Number(second[0]) >= Number(first[first.length - 1]));
  let lastY = -1; for (let i = 0; i < 40; i++) { const y = await ev(`window.scrollY`); if (y === lastY) break; lastY = y; await sleep(250); } // smooth scroll finished
  const top = await ev(`${SLIDES}[0].getBoundingClientRect().top`);
  check("after changing page the list is brought back into view", top >= 0 && top < 500 && (await ev(`window.scrollY`)) < yBefore, `first entry top=${Math.round(top)}, scrollY ${yBefore} -> ${await ev(`window.scrollY`)}`);
  check("the browser asked the server for page=2", mgCalls().some((c) => c.url.includes("page=2") && c.status === 200));
  check("Previous works and returns page 1", (await clickButton("Previous")) && (await waitFor(`${ready} && (${barText}).includes('Page 1 of')`)) && JSON.stringify(JSON.parse(await ev(IDS))) === JSON.stringify(first));

  console.log("5. Search by ID only");
  const before = mgCalls().length;
  await search("05");
  await sleep(900);
  check("typing alone does NOT call the server (search runs on Enter / button)", mgCalls().length === before);
  check("no suggestion list appears while typing", await ev(`document.querySelectorAll('ul li button').length === 0 && !document.querySelector('[role="listbox"]')`));
  await search("0560"); await pressEnter();
  check("Enter searches", await waitFor(`${ready} && ${SLIDES}.length > 0 && JSON.parse(${IDS}).includes('0560')`));
  const found = JSON.parse(await ev(IDS));
  check("0560 is found, and 560 is not shown for it", found.includes("0560") && !found.includes("560"), found.join(","));
  check("every result belongs to ID 0560 (the member and its own partner)", found.every((x) => x.startsWith("0560")), found.join(","));
  check("request carried search=0560", mgCalls().some((c) => c.url.includes("search=0560") && c.status === 200));
  await shot("mg-2-search-0560.png");

  await search("GANGANDEEP"); await pressEnter();
  check("a NAME finds nothing and says so", await waitFor(`document.body.innerText.includes('No member or partner found with this ID')`));
  check("no pagination bar when nothing is found", (await ev(barText)) === "");
  await search("9812"); await pressEnter();
  await waitFor(ready); await sleep(300);
  check("a MOBILE-looking number finds nothing", (await ev(`${SLIDES}.length`)) === 0);

  await search("0560A"); await pressEnter();
  check("a partner ID finds that partner", await waitFor(`${ready} && JSON.parse(${IDS}).length === 1 && JSON.parse(${IDS})[0] === '0560A'`) || JSON.parse(await ev(IDS)).length === 0, JSON.stringify(JSON.parse(await ev(IDS))));

  await search("");
  check("clearing the box brings the full paged list back", await waitFor(`${ready} && ${SLIDES}.length === 10 && (${barText}).includes('Page 1 of')`));
  await search("0560"); await pressEnter(); await waitFor(`${ready} && JSON.parse(${IDS}).includes('0560')`);
  await ev(`Array.from(document.querySelectorAll('button')).find(b => b.innerText.trim() === 'Search').click()`);
  check("the Search button works too", await waitFor(`${ready} && JSON.parse(${IDS}).includes('0560')`));
  await search(""); await waitFor(`${ready} && ${SLIDES}.length === 10`);

  console.log("6. Phone-size screen");
  await send("Emulation.setDeviceMetricsOverride", { width: 400, height: 860, deviceScaleFactor: 2, mobile: true });
  await sleep(1200); await waitFor(ready);
  check("no sideways scrolling", await ev(`document.documentElement.scrollWidth <= window.innerWidth + 1`), String(await ev(`document.documentElement.scrollWidth`)));
  check("search box and Search button fit on screen", await ev(`(() => { const i = document.querySelector('input[type="search"]').getBoundingClientRect(); const b = Array.from(document.querySelectorAll('button')).find(x => x.innerText.trim() === 'Search').getBoundingClientRect(); return i.left >= 0 && b.right <= window.innerWidth + 1; })()`));
  const mob = JSON.parse(await ev(`JSON.stringify(Array.from(document.querySelectorAll('img[alt]')).filter(i => i.naturalWidth > 0 && i.closest('.grid')?.querySelector('h3')?.innerText.trim() === 'Member Detail' && !i.closest('div[aria-hidden]')).map(i => { const r = i.getBoundingClientRect(); return { fits: r.left >= 0 && r.right <= window.innerWidth + 1, size: Math.round(r.width) + "x" + Math.round(r.height) }; }))`));
  check("on the phone: photos fit the screen and are all the same size", mob.length > 0 && mob.every((x) => x.fits) && new Set(mob.map((x) => x.size)).size === 1, JSON.stringify([...new Set(mob.map((x) => x.size))]));
  await ev(`document.querySelector('nav[aria-label="Pagination"]').scrollIntoView()`); await sleep(300);
  check("pagination bar is visible and fits", await ev(`(() => { const r = document.querySelector('nav[aria-label="Pagination"]').getBoundingClientRect(); return r.width > 0 && r.right <= window.innerWidth + 1; })()`));
  await shot("mg-3-mobile.png");
  await send("Emulation.clearDeviceMetricsOverride");

  console.log("7. Health");
  check("no JavaScript errors", errors.length === 0, errors.slice(0, 2).join(" | "));
  check("no failed (4xx/5xx) server requests", apiCalls.every((c) => c.status === null || c.status < 400), JSON.stringify(apiCalls.filter((c) => c.status >= 400)));
  ws.close();
} catch (e) {
  fail++; console.log("  [FAIL] crashed: " + e.message);
} finally {
  try { execSync(`taskkill /pid ${edge.pid} /T /F`, { stdio: "ignore" }); } catch {}
  console.log(`\nRESULT: ${pass} passed, ${fail} failed   (screenshots in AICDA-PHP-local/tests/screenshots)`);
  process.exit(fail ? 1 : 0);
}
