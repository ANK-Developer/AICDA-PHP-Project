<?php
// Tests partners that have no member (old data: the member does not exist), through the PHP API.
// Read-only except one show/hide that is put back. Needs the local site running.
// Run: php AICDA-PHP-local/tests/verify-partner-no-member.php
define('AICDA_API', true);
require 'E:/Project ANK/AICDA/AICDA-PHP/public_html/api/src/bootstrap.php';

$t = Crypto::signToken(DB::one('SELECT id FROM Admin WHERE isActive = 1 ORDER BY createdAt LIMIT 1')['id']);
function call(string $m, string $p, $json = null, bool $auth = true): array
{
    global $t;
    $ch = curl_init("http://localhost:8080/api/v1$p");
    $h = $auth ? ["Authorization: Bearer $t"] : [];
    if ($json !== null) { $h[] = 'Content-Type: application/json'; curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($json)); }
    curl_setopt_array($ch, [CURLOPT_CUSTOMREQUEST => $m, CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => $h]);
    $r = json_decode((string) curl_exec($ch), true);
    return [curl_getinfo($ch, CURLINFO_HTTP_CODE), $r];
}
$pass = 0; $fail = 0;
$check = function ($n, $ok, $d = '') use (&$pass, &$fail) { $ok ? $pass++ : $fail++; printf("  [%s] %s%s\n", $ok ? 'PASS' : 'FAIL', $n, $ok || $d === '' ? '' : "  -> $d"); };

$total = (int) DB::scalar('SELECT COUNT(*) FROM Partner');
$none = DB::one('SELECT id, partnerId, partnerName, isShow FROM Partner WHERE memberId IS NULL AND isShow = 1 ORDER BY id LIMIT 1');
$withMember = DB::one('SELECT id, partnerId FROM Partner WHERE memberId IS NOT NULL LIMIT 1');
echo "Partner without a member used for the test: {$none['partnerId']} ({$none['partnerName']})\n";

echo "Admin\n";
[$s, $r] = call('GET', '/partners?limit=1');
$check("admin partner list total = all partners ($total), including those without a member", $s === 200 && ($r['pagination']['total'] ?? 0) === $total, (string) ($r['pagination']['total'] ?? $s));
[$s, $r] = call('GET', '/partners?search=' . rawurlencode($none['partnerId']) . '&limit=50');
$row = array_values(array_filter($r['data'] ?? [], fn ($p) => $p['partnerId'] === $none['partnerId']))[0] ?? null;
$check('search finds it, with member = null', $row !== null && array_key_exists('member', $row) && $row['member'] === null, json_encode($row['member'] ?? 'missing'));
[$s, $r] = call('GET', "/partners/{$none['id']}");
$check('details open (200) with member = null', $s === 200 && array_key_exists('member', $r['data'] ?? []) && $r['data']['member'] === null, "status $s");
[$s, $r] = call('GET', '/partners/' . rawurlencode($none['partnerId']));
$check('details open by partner ID too', $s === 200 && ($r['data']['partnerId'] ?? '') === $none['partnerId'], "status $s");
[$s, $r] = call('GET', "/partners/{$withMember['id']}");
$check('a normal partner still comes with its member', $s === 200 && !empty($r['data']['member']['memberId']), "status $s");
$all = 0; $page = 1; $seen = [];
do { [$s, $r] = call('GET', "/partners?limit=100&page=$page"); foreach ($r['data'] ?? [] as $p) $seen[$p['id']] = 1; $page++; } while ($page <= ($r['pagination']['totalPages'] ?? 0));
$check('paging through the whole admin list returns every partner once', count($seen) === $total, count($seen) . " of $total");

echo "Public website\n";
$shown = (int) DB::scalar('SELECT COUNT(*) FROM Partner WHERE isShow = 1');
[$s, $r] = call('GET', '/partners/public', null, false);
$check("public partners = shown partners ($shown), including those without a member", ($r['count'] ?? 0) === $shown, (string) ($r['count'] ?? ''));
$check('it is in the public list with member = null', in_array($none['id'], array_column(array_filter($r['data'] ?? [], fn ($p) => $p['member'] === null), 'id'), true));
[$s, $r] = call('GET', "/partners/public/{$none['id']}", null, false);
$check('public profile opens (200)', $s === 200 && ($r['data']['partnerId'] ?? '') === $none['partnerId'], "status $s");

echo "Edit / hide still work\n";
[$s, $r] = call('PATCH', "/partners/{$none['id']}/visibility", ['isShow' => false]);
$check('hide works (isShow false, public 404)', $s === 200 && ($r['data']['isShow'] ?? null) === false && call('GET', "/partners/public/{$none['id']}", null, false)[0] === 404, "status $s");
[$s, $r] = call('PATCH', "/partners/{$none['id']}/visibility", ['isShow' => true]);
$check('show again works', $s === 200 && ($r['data']['isShow'] ?? null) === true, "status $s");
[$s, $r] = call('PATCH', "/partners/{$none['id']}/status", ['isActive' => true]);
$check('status change works (200)', $s === 200, "status $s");
DB::run('UPDATE Partner SET statusReason = NULL, statusActionBy = NULL, statusActionAt = NULL WHERE id = ?', [$none['id']]);

echo "Nothing else changed\n";
$check("partners still $total, $shown shown, 45 without a member",
    (int) DB::scalar('SELECT COUNT(*) FROM Partner') === $total && (int) DB::scalar('SELECT COUNT(*) FROM Partner WHERE isShow = 1') === $shown
    && (int) DB::scalar('SELECT COUNT(*) FROM Partner WHERE memberId IS NULL') === 45);

printf("\nRESULT: %d passed, %d failed\n", $pass, $fail);
exit($fail ? 1 : 0);
