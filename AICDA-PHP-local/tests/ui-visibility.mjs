// Browser test of Show / Hide (isShow) in Admin > Manage Directory, for a member and a partner.
// Clicks like a person: Hide -> Cancel (nothing changes) -> Hide -> confirm -> "Hidden" label + "Show" button,
// gone from the public website -> Show -> confirm -> back. Leaves everything as it was.
// Needs the local site running. Run: node AICDA-PHP-local/tests/ui-visibility.mjs
import { execFileSync, spawn, execSync } from "node:child_process";
import { mkdirSync, writeFileSync } from "node:fs";
import { tmpdir } from "node:os";
import path from "node:path";

const SITE = "http://localhost:8080";
const PORT = 9336;
const EDGE = "C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe";
const OUT = path.join(import.meta.dirname, "screenshots");
mkdirSync(OUT, { recursive: true });
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
const php = (code) => execFileSync("php", ["-r", `define('AICDA_API', true); require 'E:/Project ANK/AICDA/AICDA-PHP/public_html/api/src/bootstrap.php'; ${code}`], { encoding: "utf8" })
  .split("\n").filter((l) => l && !/ionCube/.test(l)).pop().trim();

const token = php("echo Crypto::signToken(DB::one('SELECT id FROM Admin WHERE isActive = 1 ORDER BY createdAt LIMIT 1')['id']);");
const member = JSON.parse(php("echo json_encode(DB::one(\"SELECT id, memberId, memberName FROM Member WHERE isShow = 1 AND memberId REGEXP '^[0-9]+$' ORDER BY CAST(memberId AS UNSIGNED) LIMIT 1\"));"));
const partner = JSON.parse(php("echo json_encode(DB::one(\"SELECT id, partnerId, partnerName FROM Partner WHERE isShow = 1 ORDER BY id LIMIT 1\"));"));
const isShown = (table, id) => php(`echo DB::scalar('SELECT isShow FROM ${table} WHERE id = ?', [${id}]);`) === "1";
const onPublic = async (kind, id) => (await fetch(`${SITE}/api/v1/${kind}/public/${id}`)).status === 200;

const edge = spawn(EDGE, ["--headless=new", "--disable-gpu", `--remote-debugging-port=${PORT}`, `--user-data-dir=${path.join(tmpdir(), "aicda-vis-" + Date.now())}`, "--window-size=1500,1000", "--no-first-run", "about:blank"], { stdio: "ignore" });
let pass = 0, fail = 0;
const check = (n, ok, d = "") => { ok ? pass++ : fail++; console.log(`  [${ok ? "PASS" : "FAIL"}] ${n}${ok || !d ? "" : "  -> " + d}`); };

let ev, send, shot;
async function connect() {
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
  send = (method, params = {}) => new Promise((res) => { const i = id++; pending.set(i, res); ws.send(JSON.stringify({ id: i, method, params })); });
  ev = async (expression) => (await send("Runtime.evaluate", { expression, returnByValue: true, awaitPromise: true })).result?.result?.value;
  shot = async (name) => writeFileSync(path.join(OUT, name), Buffer.from((await send("Page.captureScreenshot", { format: "png" })).result.data, "base64"));
  return { ws, errors };
}
const waitFor = async (expr, ms = 15000) => { const end = Date.now() + ms; while (Date.now() < end) { if (await ev(expr)) return true; await sleep(200); } return false; };
const search = async (placeholder, text) => {
  await ev(`(() => { const i = document.querySelector('input[placeholder=${JSON.stringify(placeholder)}]'); i.focus(); i.select(); return true; })()`);
  await send("Input.dispatchKeyEvent", { type: "keyDown", key: "Backspace", code: "Backspace", windowsVirtualKeyCode: 8 });
  await send("Input.dispatchKeyEvent", { type: "keyUp", key: "Backspace", code: "Backspace", windowsVirtualKeyCode: 8 });
  await send("Input.insertText", { text });
};
// The row (desktop table) whose text includes the given id
// exact match on a cell, so searching "009" does not pick a row whose mobile contains 009
const ROW = (id) => `Array.from(document.querySelectorAll('table tbody tr')).find(tr => Array.from(tr.querySelectorAll('td')).some(td => td.innerText.trim() === ${JSON.stringify(id)}))`;
const rowButton = (id, label) => `(${ROW(id)})?.querySelector('td:last-child') && Array.from((${ROW(id)}).querySelectorAll('button')).find(b => b.innerText.trim() === ${JSON.stringify(label)})`;
const toastButton = (label) => `Array.from(document.querySelectorAll('.Toastify button')).find(b => b.innerText.trim() === ${JSON.stringify(label)})`;
const toastText = `Array.from(document.querySelectorAll('.Toastify__toast')).map(t => t.innerText).join(' | ')`;
const clearToasts = async () => { await ev(`document.querySelectorAll('.Toastify__toast button[aria-label="close"]').forEach(b => b.click()); true`); await sleep(400); };

async function scenario(label, { page, placeholder, id, name, table, kind, pk }) {
  console.log(label);
  await send("Page.navigate", { url: `${SITE}${page}` });
  check("page loads", await waitFor(`!!document.querySelector('input[placeholder=${JSON.stringify(placeholder)}]') && document.querySelectorAll('table tbody tr').length > 0`, 25000));
  await search(placeholder, id);
  check(`row for ${id} (${name}) shown with a "Hide" button`, await waitFor(`!!(${rowButton(id, "Hide")})`));
  check("Hide button uses the eye-off icon", await ev(`!!(${rowButton(id, "Hide")})?.querySelector('svg.lucide-eye-off')`));

  await ev(`(${rowButton(id, "Hide")}).click()`);
  check("confirmation appears", await waitFor(`/from the public website\\?/.test(${toastText})`), await ev(toastText));
  await shot(`vis-${kind}-confirm.png`);
  await ev(`(${toastButton("Cancel")}).click()`);
  await sleep(800);
  check("Cancel: nothing changed (still shown)", isShown(table, pk) && !!(await ev(`!!(${rowButton(id, "Hide")})`)));

  await ev(`(${rowButton(id, "Hide")}).click()`);
  await waitFor(`!!(${toastButton("Hide")})`);
  await ev(`(${toastButton("Hide")}).click()`);
  check('after confirming: row shows "Hidden" and a "Show" button', await waitFor(`!!(${rowButton(id, "Show")}) && (${ROW(id)}).innerText.includes('Hidden')`));
  check("Show button uses the globe icon", await ev(`!!(${rowButton(id, "Show")})?.querySelector('svg.lucide-globe')`));
  check("saved: isShow = false in the database", !isShown(table, pk));
  check("gone from the public website (profile 404)", !(await onPublic(kind, pk)));
  check("success message shown", /hidden from the public website/.test(await ev(toastText)), await ev(toastText));
  await shot(`vis-${kind}-hidden.png`);
  await clearToasts();

  await ev(`(${rowButton(id, "Show")}).click()`);
  check("confirmation for Show appears", await waitFor(`/on the public website\\?/.test(${toastText})`));
  await ev(`(${toastButton("Show")}).click()`);
  check('after confirming: "Hidden" gone, "Hide" button back', await waitFor(`!!(${rowButton(id, "Hide")}) && !(${ROW(id)}).innerText.includes('Hidden')`));
  check("saved: isShow = true again, back on the public website", isShown(table, pk) && (await onPublic(kind, pk)));
  await clearToasts();
}

try {
  const { ws, errors } = await connect();
  await send("Page.enable"); await send("Runtime.enable");
  await send("Page.navigate", { url: `${SITE}/admin/login` });
  await waitFor(`location.origin === ${JSON.stringify(SITE)} && document.readyState === "complete"`);
  await ev(`localStorage.setItem('aicda_access_token', ${JSON.stringify(token)})`);

  await scenario(`Member ${member.memberId} (${member.memberName})`, { page: "/admin/directory", placeholder: "Name, Member ID, Partner ID, mobile, or company…", id: member.memberId, name: member.memberName, table: "Member", kind: "members", pk: member.id });
  await scenario(`Partner ${partner.partnerId} (${partner.partnerName})`, { page: "/admin/directory/partener", placeholder: "Partner ID, name, member, mobile, or company…", id: partner.partnerId, name: partner.partnerName, table: "Partner", kind: "partners", pk: partner.id });

  console.log("Already-hidden members show the label");
  await send("Page.navigate", { url: `${SITE}/admin/directory` });
  await waitFor(`document.querySelectorAll('table tbody tr').length > 0`, 25000);
  const hiddenId = php("echo DB::scalar(\"SELECT memberId FROM Member WHERE isShow = 0 ORDER BY id LIMIT 1\");");
  await search("Name, Member ID, Partner ID, mobile, or company…", hiddenId);
  check(`hidden member ${hiddenId} shows "Hidden" + "Show"`, await waitFor(`!!(${rowButton(hiddenId, "Show")}) && (${ROW(hiddenId)}).innerText.includes('Hidden')`));
  await shot("vis-already-hidden.png");

  check("no JavaScript errors", errors.length === 0, errors.slice(0, 2).join(" | "));
  ws.close();
} catch (e) {
  fail++; console.log("  [FAIL] crashed: " + e.message);
} finally {
  // safety net: put both records back to shown
  php(`DB::run('UPDATE Member SET isShow = 1 WHERE id = ?', [${member.id}]); DB::run('UPDATE Partner SET isShow = 1 WHERE id = ?', [${partner.id}]); echo 'ok';`);
  try { execSync(`taskkill /pid ${edge.pid} /T /F`, { stdio: "ignore" }); } catch {}
  console.log(`\nRESULT: ${pass} passed, ${fail} failed   (screenshots in AICDA-PHP-local/tests/screenshots)`);
  process.exit(fail ? 1 : 0);
}
