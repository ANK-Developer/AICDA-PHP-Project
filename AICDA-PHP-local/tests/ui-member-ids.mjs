// Browser check: the admin table and the public profile show member IDs exactly (0560, not 560).
// Needs the local site running (start-local.bat). Run: node AICDA-PHP-local/tests/ui-member-ids.mjs
import { execFileSync, spawn, execSync } from "node:child_process";
import { mkdirSync, writeFileSync } from "node:fs";
import { tmpdir } from "node:os";
import path from "node:path";

const SITE = "http://localhost:8080";
const PORT = 9335;
const EDGE = "C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe";
const OUT = path.join(import.meta.dirname, "screenshots");
mkdirSync(OUT, { recursive: true });
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

const php = (code) => execFileSync("php", ["-r", `define('AICDA_API', true); require 'E:/Project ANK/AICDA/AICDA-PHP/public_html/api/src/bootstrap.php'; ${code}`], { encoding: "utf8" })
  .split("\n").filter((l) => l && !/ionCube/.test(l)).pop().trim();
const token = php("echo Crypto::signToken(DB::one('SELECT id FROM Admin WHERE isActive = 1 ORDER BY createdAt LIMIT 1')['id']);");
const pk0560 = php("echo DB::scalar(\"SELECT id FROM Member WHERE memberId = '0560'\");");

const edge = spawn(EDGE, ["--headless=new", "--disable-gpu", `--remote-debugging-port=${PORT}`, `--user-data-dir=${path.join(tmpdir(), "aicda-ids-" + Date.now())}`, "--window-size=1366,1000", "--no-first-run", "about:blank"], { stdio: "ignore" });
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
  const ev = async (expression) => (await send("Runtime.evaluate", { expression, returnByValue: true, awaitPromise: true })).result?.result?.value;
  const waitFor = async (expr, ms = 20000) => { const end = Date.now() + ms; while (Date.now() < end) { if (await ev(expr)) return true; await sleep(200); } return false; };
  const shot = async (name) => writeFileSync(path.join(OUT, name), Buffer.from((await send("Page.captureScreenshot", { format: "png" })).result.data, "base64"));

  await send("Page.enable"); await send("Runtime.enable");
  await send("Page.navigate", { url: `${SITE}/admin/login` });
  await waitFor(`location.origin === ${JSON.stringify(SITE)} && document.readyState === "complete"`);
  await ev(`localStorage.setItem('aicda_access_token', ${JSON.stringify(token)})`);
  await send("Page.navigate", { url: `${SITE}/admin/directory` });

  console.log("Admin > Manage Directory");
  check("table loads", await waitFor(`document.querySelectorAll('table tbody tr').length > 0`));
  const total = await ev(`document.body.innerText.match(/Total[^\\d]*(\\d[\\d,]*)/i)?.[1] ?? ''`);
  console.log(`     (summary on page: total ${total || "n/a"})`);

  for (const [term, expectId, expectName] of [["0560", "0560", "GANGANDEEP SINGH"], ["560", "560", "KAMALJIT SINGH"]]) {
    await ev(`(() => { const i = document.querySelector('input[aria-label="Search members"]'); i.focus(); i.select(); return true; })()`);
    await send("Input.dispatchKeyEvent", { type: "keyDown", key: "Backspace", code: "Backspace", windowsVirtualKeyCode: 8 });
    await send("Input.dispatchKeyEvent", { type: "keyUp", key: "Backspace", code: "Backspace", windowsVirtualKeyCode: 8 });
    await send("Input.insertText", { text: term });
    const ok = await waitFor(`Array.from(document.querySelectorAll('table tbody tr')).some(tr => tr.querySelector('td')?.innerText.trim() === ${JSON.stringify(expectId)} && tr.innerText.includes(${JSON.stringify(expectName)}))`);
    const shown = await ev(`JSON.stringify(Array.from(document.querySelectorAll('table tbody tr')).map(tr => tr.querySelector('td')?.innerText.trim()))`);
    check(`search "${term}" shows a row with ID ${expectId} = ${expectName}`, ok, shown);
    await shot(`ids-search-${term}.png`);
  }

  console.log("Member details page");
  await send("Page.navigate", { url: `${SITE}/admin/directory/x-${pk0560}/details` });
  check("details page of member 0560 shows Member ID 0560", await waitFor(`document.body.innerText.includes('0560') && document.body.innerText.includes('GANGANDEEP SINGH')`));
  await shot("ids-details-0560.png");

  console.log("Public profile");
  await send("Page.navigate", { url: `${SITE}/profile/member/${pk0560}` });
  check("public profile shows 0560", await waitFor(`document.body.innerText.includes('0560') && document.body.innerText.includes('GANGANDEEP SINGH')`));
  await shot("ids-public-0560.png");

  check("no JavaScript errors", errors.length === 0, errors.slice(0, 2).join(" | "));
  ws.close();
} catch (e) {
  fail++; console.log("  [FAIL] crashed: " + e.message);
} finally {
  try { execSync(`taskkill /pid ${edge.pid} /T /F`, { stdio: "ignore" }); } catch {}
  console.log(`\nRESULT: ${pass} passed, ${fail} failed   (screenshots in AICDA-PHP-local/tests/screenshots)`);
  process.exit(fail ? 1 : 0);
}
