// Browser check: a partner without a member shows in Admin > Partners, its details page and its public profile
// open without errors. Needs the local site running. Run: node AICDA-PHP-local/tests/ui-partner-no-member.mjs
import { execFileSync, spawn, execSync } from "node:child_process";
import { mkdirSync, writeFileSync } from "node:fs";
import { tmpdir } from "node:os";
import path from "node:path";

const SITE = "http://localhost:8080";
const PORT = 9337;
const EDGE = "C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe";
const OUT = path.join(import.meta.dirname, "screenshots");
mkdirSync(OUT, { recursive: true });
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
const php = (code) => execFileSync("php", ["-r", `define('AICDA_API', true); require 'E:/Project ANK/AICDA/AICDA-PHP/public_html/api/src/bootstrap.php'; ${code}`], { encoding: "utf8" })
  .split("\n").filter((l) => l && !/ionCube/.test(l)).pop().trim();
const token = php("echo Crypto::signToken(DB::one('SELECT id FROM Admin WHERE isActive = 1 ORDER BY createdAt LIMIT 1')['id']);");
const p = JSON.parse(php("echo json_encode(DB::one('SELECT id, partnerId, partnerName FROM Partner WHERE memberId IS NULL AND isShow = 1 ORDER BY id LIMIT 1'));"));

const edge = spawn(EDGE, ["--headless=new", "--disable-gpu", `--remote-debugging-port=${PORT}`, `--user-data-dir=${path.join(tmpdir(), "aicda-nm-" + Date.now())}`, "--window-size=1500,1000", "--no-first-run", "about:blank"], { stdio: "ignore" });
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
  const crashed = `document.body.innerText.includes("This page didn't load")`;

  await send("Page.enable"); await send("Runtime.enable");
  await send("Page.navigate", { url: `${SITE}/admin/login` });
  await waitFor(`location.origin === ${JSON.stringify(SITE)} && document.readyState === "complete"`);
  await ev(`localStorage.setItem('aicda_access_token', ${JSON.stringify(token)})`);

  console.log(`Partner ${p.partnerId} (${p.partnerName}), no member`);
  await send("Page.navigate", { url: `${SITE}/admin/directory/partener` });
  check("Partners tab loads", await waitFor(`document.querySelectorAll('table tbody tr').length > 0`, 25000));
  await ev(`(() => { const i = document.querySelector('input[placeholder="Partner ID, name, member, mobile, or company…"]'); i.focus(); return true; })()`);
  await send("Input.insertText", { text: p.partnerId });
  check("search shows the partner row", await waitFor(`Array.from(document.querySelectorAll('table tbody tr')).some(tr => Array.from(tr.querySelectorAll('td')).some(td => td.innerText.trim() === ${JSON.stringify(p.partnerId)}))`));
  check("no crash screen on the list", !(await ev(crashed)));
  await shot("nm-list.png");

  await send("Page.navigate", { url: `${SITE}/admin/directory/partner/x-${p.id}/details` });
  check("details page opens and shows the name", await waitFor(`document.body.innerText.includes(${JSON.stringify(p.partnerName)})`));
  check("no crash screen on details", !(await ev(crashed)));
  await shot("nm-details.png");

  await send("Page.navigate", { url: `${SITE}/profile/partner/${p.id}` });
  check("public profile opens and shows the name", await waitFor(`document.body.innerText.includes(${JSON.stringify(p.partnerName)})`));
  check("no crash screen on the public profile", !(await ev(crashed)));

  await send("Page.navigate", { url: `${SITE}/management` });
  check("public Management page loads with members", await waitFor(`document.body.innerText.includes('Member Detail')`, 25000));
  check("no crash screen on the Management page", !(await ev(crashed)));

  check("no JavaScript errors", errors.length === 0, errors.slice(0, 2).join(" | "));
  ws.close();
} catch (e) {
  fail++; console.log("  [FAIL] crashed: " + e.message);
} finally {
  try { execSync(`taskkill /pid ${edge.pid} /T /F`, { stdio: "ignore" }); } catch {}
  console.log(`\nRESULT: ${pass} passed, ${fail} failed`);
  process.exit(fail ? 1 : 0);
}
