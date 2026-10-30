// Browser test of the public /directory page:
//  - big notice image + search box by default, ID-card samples under the sidebar
//  - search by Member ID / Partner ID shows the info (same block as Management) instead of the image
//  - Back brings the big image back
// Needs the local site running. Run: node AICDA-PHP-local/tests/ui-directory.mjs
import { spawn, execSync } from "node:child_process";
import { mkdirSync, writeFileSync } from "node:fs";
import { tmpdir } from "node:os";
import path from "node:path";

const PORT = 9340;
const OUT = path.join(import.meta.dirname, "screenshots");
mkdirSync(OUT, { recursive: true });
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
const edge = spawn("C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe", ["--headless=new", "--disable-gpu", `--remote-debugging-port=${PORT}`, `--user-data-dir=${path.join(tmpdir(), "aicda-dir-" + Date.now())}`, "--window-size=1366,900", "--no-first-run", "about:blank"], { stdio: "ignore" });
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
  let id = 1; const pending = new Map(); const errors = []; const calls = [];
  ws.onmessage = (e) => {
    const m = JSON.parse(e.data);
    if (m.id && pending.has(m.id)) { pending.get(m.id)(m); pending.delete(m.id); return; }
    if (m.method === "Runtime.exceptionThrown") errors.push(m.params.exceptionDetails.exception?.description?.split("\n")[0] ?? "exception");
    if (m.method === "Network.requestWillBeSent" && m.params.request.url.includes("/api/v1/management")) calls.push(m.params.request.url);
  };
  const send = (method, params = {}) => new Promise((res) => { const i = id++; pending.set(i, res); ws.send(JSON.stringify({ id: i, method, params })); });
  const ev = async (x) => (await send("Runtime.evaluate", { expression: x, returnByValue: true })).result?.result?.value;
  const waitFor = async (x, ms = 20000) => { const end = Date.now() + ms; while (Date.now() < end) { try { if (await ev(x)) return true; } catch {} await sleep(150); } return false; };
  const shot = async (name) => writeFileSync(path.join(OUT, name), Buffer.from((await send("Page.captureScreenshot", { format: "png" })).result.data, "base64"));
  const type = async (text) => {
    await ev(`(() => { const i = document.querySelector('input[type="search"]'); i.focus(); i.select(); return true; })()`);
    await send("Input.dispatchKeyEvent", { type: "keyDown", key: "Backspace", code: "Backspace", windowsVirtualKeyCode: 8 });
    await send("Input.dispatchKeyEvent", { type: "keyUp", key: "Backspace", code: "Backspace", windowsVirtualKeyCode: 8 });
    if (text) await send("Input.insertText", { text });
  };
  const enter = async () => { await send("Input.dispatchKeyEvent", { type: "keyDown", key: "Enter", code: "Enter", windowsVirtualKeyCode: 13, text: "\r" }); await send("Input.dispatchKeyEvent", { type: "keyUp", key: "Enter", code: "Enter", windowsVirtualKeyCode: 13 }); };
  const SLIDES = `Array.from(document.querySelectorAll('h3')).filter(h => h.innerText.trim() === 'Member Detail')`;
  const NOTICE = `document.querySelector('img[alt^="Notice from AICDA"]')`;
  const idle = `!document.querySelector('[class*="animate-pulse"]')`;

  await send("Page.enable"); await send("Network.enable"); await send("Runtime.enable");
  await send("Page.navigate", { url: "http://localhost:8080/directory" });

  console.log("1. Default view");
  check("page loads with the big notice image", await waitFor(`${NOTICE} && ${NOTICE}.complete && ${NOTICE}.naturalWidth > 0`));
  const big = JSON.parse(await ev(`JSON.stringify((() => { const r = ${NOTICE}.getBoundingClientRect(); return { w: Math.round(r.width), h: Math.round(r.height), nat: ${NOTICE}.naturalWidth + 'x' + ${NOTICE}.naturalHeight }; })())`));
  check(`the image is big (${big.w}x${big.h}, original ${big.nat})`, big.w >= 600 && big.h >= 700, JSON.stringify(big));
  check("the whole image is in proportion (not stretched)", Math.abs(big.w / big.h - Number(big.nat.split("x")[0]) / Number(big.nat.split("x")[1])) < 0.02);
  check("search box is there", await ev(`!!document.querySelector('input[type="search"]') && document.body.innerText.includes('Search Member')`));
  check("no member info is shown yet", (await ev(`${SLIDES}.length`)) === 0);
  check("nothing was requested from the server for the member list", calls.length === 0, JSON.stringify(calls));
  check("old 'Dealer Directory' gallery search is gone", !(await ev(`document.body.innerText.includes('Dealer Directory') || document.body.innerText.includes('Search the Directory')`)));

  console.log("2. ID-card samples under the sidebar");
  const side = JSON.parse(await ev(`JSON.stringify((() => { const a = document.querySelector('aside'); const imgs = Array.from(a.querySelectorAll('img')); const cat = a.querySelector('ul'); const catBottom = cat.getBoundingClientRect().bottom + window.scrollY; return { count: imgs.length, belowCategories: imgs.every(i => i.getBoundingClientRect().top + window.scrollY >= catBottom - 1), heading: a.innerText.includes('Identity Card'), alts: imgs.map(i => i.alt) }; })())`));
  check("exactly one card image is in the sidebar (the downloaded card)", side.count === 1 && /front and back/.test(side.alts[0]), JSON.stringify(side));
  check("they sit BELOW the categories list", side.belowCategories);
  check("they have an 'Identity Card' heading", side.heading);
  await waitFor(`Array.from(document.querySelectorAll('aside img')).every(i => i.complete && i.naturalWidth > 0)`, 10000);
  check("the card image loads", await ev(`Array.from(document.querySelectorAll('aside img')).every(i => i.complete && i.naturalWidth > 0)`));
  const hero = JSON.parse(await ev(`JSON.stringify(Array.from(document.querySelectorAll('div[aria-hidden="true"][style*="background-image"]')).map(d => d.style.backgroundImage))`));
  check("the hero shows banners (" + hero.length + " slides, dots to switch)", hero.length >= 2 && (await ev(`document.querySelectorAll('button[aria-label^="Go to banner"]').length`)) === hero.length, JSON.stringify(hero.slice(0, 1)));
  check("they are the built-in banners, not anything uploaded to a Directory section", hero.every((u) => !u.includes("/uploads/")), JSON.stringify(hero.filter((u) => u.includes("/uploads/"))));
  await shot("dir-1-default.png");

  console.log("3. Search a member ID");
  await type("0560"); await enter();
  check("info shows", await waitFor(`${idle} && ${SLIDES}.length >= 1`));
  check("the big image is hidden while info is shown", await ev(`!${NOTICE}`));
  const ids = JSON.parse(await ev(`JSON.stringify(${SLIDES}.map(h => Array.from(h.closest('.grid').querySelectorAll('p')).find(p => p.innerText.startsWith('ID :')).innerText.replace('ID :','').trim()))`));
  check("it is member 0560 (and its partner), not 560", ids.includes("0560") && !ids.includes("560") && ids.every((x) => x.startsWith("0560")), ids.join(","));
  check("details are there (name, address, mobile ...)", await ev(`document.body.innerText.includes('GANGANDEEP SINGH') && document.body.innerText.includes('Mobile')`));
  check("the same Download / Share buttons as Management are there", await ev(`!!document.querySelector('button[aria-label="Download ID card"]') && !!document.querySelector('button[aria-label="Share card"]')`));
  check("a Back button is shown", await ev(`Array.from(document.querySelectorAll('button')).some(b => b.innerText.trim() === 'Back')`));
  check("the sidebar card stays", (await ev(`document.querySelectorAll('aside img').length`)) === 1);
  await shot("dir-2-member-info.png");

  console.log("4. Back");
  await ev(`Array.from(document.querySelectorAll('button')).find(b => b.innerText.trim() === 'Back').click()`);
  check("Back brings the big image back", await waitFor(`${NOTICE} && ${SLIDES}.length === 0`));
  check("and empties the search box", (await ev(`document.querySelector('input[type="search"]').value`)) === "");

  console.log("5. Search a partner ID");
  await type("0560A"); await enter();
  await waitFor(`${idle} && (${SLIDES}.length >= 1 || document.body.innerText.includes('No member or partner found'))`);
  const pIds = JSON.parse(await ev(`JSON.stringify(${SLIDES}.map(h => Array.from(h.closest('.grid').querySelectorAll('p')).find(p => p.innerText.startsWith('ID :')).innerText.replace('ID :','').trim()))`));
  const noteP = await ev(`document.body.innerText.includes('No member or partner found')`);
  check("a partner ID shows that partner (0560A) - or says not found if there is none", (pIds.length === 1 && pIds[0] === "0560A") || (pIds.length === 0 && noteP), JSON.stringify(pIds));

  console.log("6. Not found / by name");
  await type("GANGANDEEP"); await enter();
  check("a name is not searchable: it says not found", await waitFor(`${idle} && document.body.innerText.includes('No member or partner found with this ID')`));
  check("the notice image is not shown on a failed search", await ev(`!${NOTICE}`));
  await type("");
  check("clearing the box brings the big image back", await waitFor(`!!${NOTICE}`));
  await type("999999"); await enter();
  check("an ID that does not exist says not found", await waitFor(`${idle} && document.body.innerText.includes('No member or partner found with this ID')`));
  await ev(`Array.from(document.querySelectorAll('button')).find(b => b.innerText.trim() === 'Back').click()`);
  check("Back works from the not-found view too", await waitFor(`!!${NOTICE}`));

  console.log("7. Phone-size screen");
  await send("Emulation.setDeviceMetricsOverride", { width: 400, height: 860, deviceScaleFactor: 2, mobile: true });
  await sleep(1200);
  check("no sideways scrolling", await ev(`document.documentElement.scrollWidth <= window.innerWidth + 1`), String(await ev(`document.documentElement.scrollWidth`)));
  check("search box and button fit", await ev(`(() => { const i = document.querySelector('input[type="search"]').getBoundingClientRect(); const b = Array.from(document.querySelectorAll('button')).find(x => x.innerText.trim() === 'Search').getBoundingClientRect(); return i.left >= 0 && b.right <= window.innerWidth + 1; })()`));
  check("the big image fits the screen", await ev(`${NOTICE}.getBoundingClientRect().right <= window.innerWidth + 1`));
  await shot("dir-3-mobile.png");
  await send("Emulation.clearDeviceMetricsOverride");

  console.log("8. Health");
  check("no JavaScript errors", errors.length === 0, errors.slice(0, 2).join(" | "));
  ws.close();
} catch (e) {
  fail++; console.log("  [FAIL] crashed: " + e.message);
} finally {
  try { execSync(`taskkill /pid ${edge.pid} /T /F`, { stdio: "ignore" }); } catch {}
  console.log(`\nRESULT: ${pass} passed, ${fail} failed   (pictures in AICDA-PHP-local/tests/screenshots)`);
  process.exit(fail ? 1 : 0);
}
