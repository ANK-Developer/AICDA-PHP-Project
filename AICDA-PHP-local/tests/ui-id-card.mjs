// Browser check of the ID card used for download on /management:
// the "Validity Upto..." line must be gone and Active / Inactive + thumb icon must show.
// To see BOTH states, the browser (only) is told that the first entry is active; the database is not touched.
// Run: node AICDA-PHP-local/tests/ui-id-card.mjs
import { spawn, execSync } from "node:child_process";
import { mkdirSync, writeFileSync } from "node:fs";
import { tmpdir } from "node:os";
import path from "node:path";

const PORT = 9338;
const OUT = path.join(import.meta.dirname, "screenshots");
mkdirSync(OUT, { recursive: true });
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
const edge = spawn("C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe", ["--headless=new", "--disable-gpu", `--remote-debugging-port=${PORT}`, `--user-data-dir=${path.join(tmpdir(), "aicda-card-" + Date.now())}`, "--window-size=900,1000", "--no-first-run", "about:blank"], { stdio: "ignore" });
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
  const send = (method, params = {}) => new Promise((res) => { const i = id++; pending.set(i, res); ws.send(JSON.stringify({ id: i, method, params })); });
  ws.onmessage = async (e) => {
    const m = JSON.parse(e.data);
    if (m.id && pending.has(m.id)) { pending.get(m.id)(m); pending.delete(m.id); return; }
    if (m.method === "Runtime.exceptionThrown") errors.push(m.params.exceptionDetails.exception?.description?.split("\n")[0] ?? "exception");
    if (m.method === "Fetch.requestPaused") {
      const p = m.params;
      try {
        const body = JSON.parse(Buffer.from((await send("Fetch.getResponseBody", { requestId: p.requestId })).result.body, "base64").toString("utf8"));
        if (Array.isArray(body.data) && body.data[0]) body.data[0].isActive = true; // first entry looks "active" (browser only)
        await send("Fetch.fulfillRequest", { requestId: p.requestId, responseCode: 200, responseHeaders: p.responseHeaders, body: Buffer.from(JSON.stringify(body)).toString("base64") });
      } catch { await send("Fetch.continueRequest", { requestId: p.requestId }); }
    }
  };
  const ev = async (x) => (await send("Runtime.evaluate", { expression: x, returnByValue: true })).result?.result?.value;
  const waitFor = async (x, ms = 20000) => { const end = Date.now() + ms; while (Date.now() < end) { try { if (await ev(x)) return true; } catch {} await sleep(150); } return false; };
  const shot = async (name) => writeFileSync(path.join(OUT, name), Buffer.from((await send("Page.captureScreenshot", { format: "png" })).result.data, "base64"));

  await send("Page.enable"); await send("Runtime.enable"); await send("Network.enable");
  await send("Fetch.enable", { patterns: [{ urlPattern: "*/api/v1/management*", requestStage: "Response" }] });
  await send("Page.navigate", { url: "http://localhost:8080/management" });
  check("page loads", await waitFor(`document.querySelectorAll('div[aria-hidden="true"][style*="-9999px"]').length >= 2 && !document.querySelector('[class*="animate-pulse"]')`));
  await sleep(1500);

  const cards = JSON.parse(await ev(`JSON.stringify(Array.from(document.querySelectorAll('div[aria-hidden="true"][style*="-9999px"]')).slice(0, 2).map(c => { const front = c.firstElementChild; return { text: front.innerText.replace(/\\s+/g, ' ').trim(), svgs: front.querySelectorAll('svg').length, imgs: front.querySelectorAll('img').length }; }))`));
  const [a, b] = cards;
  check("card text no longer has 'Validity Upto' (entry 1)", !/validity/i.test(a.text), a.text);
  check("card text no longer has 'Validity Upto' (entry 2)", !/validity/i.test(b.text), b.text);
  check("entry 1 (active) says Active", /\bActive\b/.test(a.text) && !/Inactive/.test(a.text), a.text);
  check("entry 2 (inactive) says Inactive", /Inactive/.test(b.text), b.text);
  check("each card has its thumb icon (svg)", a.svgs >= 1 && b.svgs >= 1, `${a.svgs}, ${b.svgs}`);
  check("rest of the card is still there (title, name, designation line, signatory)", /Identity Card|ALL INDIA CAR DEALERS ASSOCIATION/i.test(a.text) && /J\.S\. NAYOL|NAYOL/i.test(a.text), a.text);
  const colours = JSON.parse(await ev(`JSON.stringify(Array.from(document.querySelectorAll('div[aria-hidden="true"][style*="-9999px"]')).slice(0, 2).map(c => { const p = Array.from(c.firstElementChild.querySelectorAll('p')).find(x => /Active|Inactive/.test(x.innerText)); const cs = getComputedStyle(p); return cs.color; }))`));
  // The browser reports colours as oklch(L C H): the third number is the hue (green ~ 140-180, red ~ 0-40).
  const hue = (c) => Number(c.match(/oklch\(\s*[\d.]+\s+[\d.]+\s+([\d.]+)/)?.[1]);
  const [ha, hb] = [hue(colours[0]), hue(colours[1])];
  check("Active text is green, Inactive text is red", ha >= 120 && ha <= 190 && (hb <= 40 || hb >= 340), JSON.stringify(colours));
  console.log("     colours:", JSON.stringify(colours));

  // bring the hidden cards on screen for a picture, one after the other
  for (const [n, name] of [[0, "card-active.png"], [1, "card-inactive.png"]]) {
    await ev(`(() => { document.querySelectorAll('div[aria-hidden="true"][style*="-9999px"]').forEach((c, i) => { c.style.left = i === ${n} ? '20px' : '-9999px'; c.style.top = '0px'; c.style.zIndex = '99999'; c.style.background = '#fff'; }); })()`);
    await sleep(500); await shot(name);
  }
  check("no JavaScript errors", errors.length === 0, errors.slice(0, 2).join(" | "));
  ws.close();
} catch (e) {
  fail++; console.log("  [FAIL] crashed: " + e.message);
} finally {
  try { execSync(`taskkill /pid ${edge.pid} /T /F`, { stdio: "ignore" }); } catch {}
  console.log(`\nRESULT: ${pass} passed, ${fail} failed   (pictures: AICDA-PHP-local/tests/screenshots/card-active.png, card-inactive.png)`);
  process.exit(fail ? 1 : 0);
}
