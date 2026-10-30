// Browser test: server-side pagination + search on /association-events, /political-achievements, /image, /letter.
// Needs the local site running. Run: node AICDA-PHP-local/tests/ui-gallery-pages.mjs
import { spawn, execSync } from "node:child_process";
import { tmpdir } from "node:os";
import path from "node:path";

const PORT = 9342;
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
const edge = spawn("C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe", ["--headless=new", "--disable-gpu", `--remote-debugging-port=${PORT}`, `--user-data-dir=${path.join(tmpdir(), "aicda-gp-" + Date.now())}`, "--window-size=1366,1000", "--no-first-run", "about:blank"], { stdio: "ignore" });
let pass = 0, fail = 0;
const check = (n, ok, d = "") => { ok ? pass++ : fail++; console.log(`  [${ok ? "PASS" : "FAIL"}] ${n}${ok || !d ? "" : "  -> " + d}`); };

const PAGES = [
  { url: "/association-events", category: "ASSOCIATION", word: "11th", search: true },
  { url: "/political-achievements", category: "POLITICAL_ACHIEVEMENT", word: "Congress", search: true },
  { url: "/letter", category: "LETTER", word: "press", search: true },
  { url: "/image", category: "IMAGE", search: false },
];

try {
  let wsUrl;
  for (let i = 0; i < 60 && !wsUrl; i++) {
    try { wsUrl = (await (await fetch(`http://127.0.0.1:${PORT}/json/list`)).json()).find((t) => t.type === "page")?.webSocketDebuggerUrl; } catch {}
    if (!wsUrl) await sleep(250);
  }
  const ws = new WebSocket(wsUrl);
  await new Promise((r) => (ws.onopen = r));
  let id = 1; const pending = new Map(); const errors = []; let calls = [];
  ws.onmessage = (e) => {
    const m = JSON.parse(e.data);
    if (m.id && pending.has(m.id)) { pending.get(m.id)(m); pending.delete(m.id); return; }
    if (m.method === "Runtime.exceptionThrown") errors.push(m.params.exceptionDetails.exception?.description?.split("\n")[0] ?? "exception");
    if (m.method === "Network.requestWillBeSent" && m.params.request.url.includes("/api/v1/gallery")) calls.push({ url: m.params.request.url, id: m.params.requestId, status: null });
    if (m.method === "Network.responseReceived") { const c = calls.find((x) => x.id === m.params.requestId); if (c) c.status = m.params.response.status; }
  };
  const send = (method, params = {}) => new Promise((res) => { const i = id++; pending.set(i, res); ws.send(JSON.stringify({ id: i, method, params })); });
  const ev = async (x) => (await send("Runtime.evaluate", { expression: x, returnByValue: true })).result?.result?.value;
  const waitFor = async (x, ms = 20000) => { const end = Date.now() + ms; while (Date.now() < end) { try { if (await ev(x)) return true; } catch {} await sleep(150); } return false; };
  const api = async (q) => (await (await fetch(`http://localhost:8080/api/v1/gallery${q}`)).json());
  const mine = (cat) => calls.filter((c) => c.url.includes(`category=${cat}`));

  const TILES = `Array.from(document.querySelectorAll('button.group'))`;
  const SRCS = `JSON.stringify(${TILES}.map(b => (b.querySelector('img,video')?.getAttribute('src') ?? '')))`;
  const TOTAL = `(() => { const m = document.body.innerText.match(/Total[^:]*:\\s*(\\d+)/); return m ? Number(m[1]) : -1; })()`;
  const PAGEINFO = `(() => { const m = document.body.innerText.match(/Page\\s*:\\s*(\\d+)\\s*of\\s*(\\d+)/); return m ? [Number(m[1]), Number(m[2])] : null; })()`;
  const click = (label) => ev(`(() => { const b = Array.from(document.querySelectorAll('button')).find(x => x.innerText.trim() === ${JSON.stringify(label)}); if (!b || b.disabled) return false; b.click(); return true; })()`);
  const type = async (text) => {
    await ev(`(() => { const i = document.querySelector('input'); i.focus(); i.select(); return true; })()`);
    await send("Input.dispatchKeyEvent", { type: "keyDown", key: "Backspace", code: "Backspace", windowsVirtualKeyCode: 8 });
    await send("Input.dispatchKeyEvent", { type: "keyUp", key: "Backspace", code: "Backspace", windowsVirtualKeyCode: 8 });
    for (const ch of text) { await send("Input.insertText", { text: ch }); await sleep(60); } // like a person typing
  };

  await send("Page.enable"); await send("Network.enable"); await send("Runtime.enable");

  for (const pg of PAGES.filter((p) => !process.env.ONLY || process.env.ONLY.split(",").includes(p.url))) {
    console.log(`\n${pg.url}`);
    calls = [];
    const total = (await api(`?category=${pg.category}`)).count;
    await send("Page.navigate", { url: `http://localhost:8080${pg.url}` });
    check("page loads with 12 items", await waitFor(`${TILES}.length === 12`), String(await ev(`${TILES}.length`)));
    const first = await ev(SRCS);
    check(`total shown = ${total}`, (await ev(TOTAL)) === total, String(await ev(TOTAL)));
    const info = await ev(PAGEINFO);
    check(`pagination says Page 1 of ${Math.ceil(total / 12)}`, info?.[0] === 1 && info?.[1] === Math.ceil(total / 12), JSON.stringify(info));
    check("the browser asked the server for page=1&limit=12 (not the whole list)", mine(pg.category).some((c) => c.url.includes("page=1") && c.url.includes("limit=12") && c.status === 200) && mine(pg.category).every((c) => c.url.includes("limit=")), JSON.stringify(mine(pg.category).map((c) => c.url.split("?")[1])));

    // ---- Next / Prev ----
    check("Next works", await click("Next"));
    const p2 = await waitFor(`(${PAGEINFO})?.[0] === 2 && ${TILES}.length > 0 && ${SRCS} !== ${JSON.stringify(first)}`);
    check("page 2 shows other items", p2, JSON.stringify({ info: await ev(PAGEINFO), tiles: await ev(`${TILES}.length`), sameAsFirst: (await ev(SRCS)) === first, text: (await ev(`(document.body.innerText.match(/Page[^\n]{0,20}/g)||[]).join(" | ")`)) }));
    const second = JSON.parse(await ev(SRCS));
    check("page 2 holds different items than page 1", !second.some((s) => JSON.parse(first).includes(s)));
    check("the browser asked the server for page=2", mine(pg.category).some((c) => c.url.includes("page=2") && c.status === 200));
    check("Prev goes back to page 1", (await click("Prev")) && (await waitFor(`(${PAGEINFO})?.[0] === 1 && ${SRCS} === ${JSON.stringify(first)}`)));

    // ---- Lightbox on this page ----
    await ev(`${TILES}[0].click()`);
    check("clicking an item opens the big view", await waitFor(`!!document.querySelector('[role="dialog"] img, [role="dialog"] video, [role="dialog"] iframe')`));
    const shown1 = await ev(`document.querySelector('[role="dialog"] img')?.getAttribute('src')`);
    const nextArrow = await ev(`(() => { const b = document.querySelector('[role="dialog"] button[aria-label^="Next"]'); if (b) b.click(); return !!b; })()`);
    await sleep(500);
    const shown2 = await ev(`document.querySelector('[role="dialog"] img')?.getAttribute('src')`);
    check("the arrow shows the next item of this page", nextArrow && shown2 && shown2 !== shown1);
    await send("Input.dispatchKeyEvent", { type: "keyDown", key: "Escape", code: "Escape", windowsVirtualKeyCode: 27 });
    await sleep(400);

    if (pg.search) {
      // ---- Search ----
      calls = [];
      const expect = (await api(`?category=${pg.category}&page=1&limit=12&search=${encodeURIComponent(pg.word)}`)).count;
      await type(pg.word);
      check(`search "${pg.word}" shows the total from the server (${expect})`, await waitFor(`${TOTAL} === ${expect}`), String(await ev(TOTAL)));
      const searchCalls = mine(pg.category).filter((c) => c.url.includes("search="));
      check("typing letter by letter did not fire a request per letter (waits for a pause)", searchCalls.length <= 2, `${searchCalls.length} requests`);
      check("the last request carried the whole word", searchCalls.at(-1)?.url.includes(`search=${encodeURIComponent(pg.word)}`), searchCalls.at(-1)?.url);
      check("the search box did not lose focus or get reset", await ev(`document.activeElement === document.querySelector('input') && document.querySelector('input').value === ${JSON.stringify(pg.word)}`));
      check("results are on page 1 again", (await ev(PAGEINFO))?.[0] === 1 || (await ev(PAGEINFO)) === null);
      await type("zzzqqq999");
      check("a word that is nowhere shows the 'No ... Found' message", await waitFor(`/No .* Found/.test(document.body.innerText) && ${TILES}.length === 0`));
      check("the search box is still there after an empty result", await ev(`!!document.querySelector('input')`));
      await type("");
      check("clearing the box brings everything back", await waitFor(`${TOTAL} === ${total} && ${TILES}.length === 12`));
    } else {
      // ---- Image page: All / Image / Video ----
      calls = [];
      check("the Video button can be clicked", await click("Video"));
      await sleep(600);
      const vid = (await api(`?category=IMAGE&page=1&limit=12&resourceType=VIDEO`)).count;
      check(`Video total = ${vid}`, (await ev(TOTAL)) === vid || (vid === 0 && (await ev(`document.body.innerText.includes('No Results Found')`))), String(await ev(TOTAL)));
      check("request carried resourceType=VIDEO", mine("IMAGE").some((c) => c.url.includes("resourceType=VIDEO") && c.status === 200));
      await click("Image");
      check(`'Image' filter shows ${total} photos`, await waitFor(`${TOTAL} === ${total} && ${TILES}.length === 12`));
      await click("All");
      check("'All' shows everything", await waitFor(`${TOTAL} === ${total}`));
    }
  }

  console.log("\nHealth");
  check("no JavaScript errors", errors.length === 0, errors.slice(0, 2).join(" | "));
  check("no failed (4xx/5xx) gallery requests", calls.every((c) => c.status === null || c.status < 400), JSON.stringify(calls.filter((c) => c.status >= 400)));
  ws.close();
} catch (e) {
  fail++; console.log("  [FAIL] crashed: " + e.message);
} finally {
  try { execSync(`taskkill /pid ${edge.pid} /T /F`, { stdio: "ignore" }); } catch {}
  console.log(`\nRESULT: ${pass} passed, ${fail} failed`);
  process.exit(fail ? 1 : 0);
}
