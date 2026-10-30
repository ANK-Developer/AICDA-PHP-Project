<?php
// Tests isShow (shown / hidden on the public website) through the PHP API.
// Changes it makes are put back at the end. Needs the local site running.
// Run: php AICDA-PHP-local/tests/verify-visibility-api.php
define('AICDA_API', true);
require 'E:/Project ANK/AICDA/AICDA-PHP/public_html/api/src/bootstrap.php';

$t = Crypto::signToken(DB::one('SELECT id FROM Admin WHERE isActive = 1 ORDER BY createdAt LIMIT 1')['id']);
function call(string $m, string $p, $json = null, bool $auth = true): array
{
    global $t;
    $ch = curl_init("http://localhost:8080/api/v1$p");
    $headers = $auth ? ["Authorization: Bearer $t"] : [];
    if ($json !== null) { $headers[] = 'Content-Type: application/json'; curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($json)); }
    curl_setopt_array($ch, [CURLOPT_CUSTOMREQUEST => $m, CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => $headers]);
    $r = json_decode((string) curl_exec($ch), true);
    return [curl_getinfo($ch, CURLINFO_HTTP_CODE), $r];
}
$pass = 0; $fail = 0;
$check = function ($n, $ok, $d = '') use (&$pass, &$fail) { $ok ? $pass++ : $fail++; printf("  [%s] %s%s\n", $ok ? 'PASS' : 'FAIL', $n, $ok || $d === '' ? '' : "  -> $d"); };
$count = fn (string $sql) => (int) DB::scalar($sql);

echo "Public website only shows isShow = true\n";
$shownM = $count('SELECT COUNT(*) FROM Member WHERE isShow = 1');
$shownP = $count('SELECT COUNT(*) FROM Partner WHERE isShow = 1');
[$s, $r] = call('GET', '/members/public', null, false);
$public = $r['data'] ?? [];
$check("public members = shown members ($shownM)", count($public) === $shownM, (string) count($public));
$hiddenIds = array_map('intval', DB::run('SELECT id FROM Member WHERE isShow = 0')->fetchAll(PDO::FETCH_COLUMN));
$check('no hidden member is in the public list', !array_intersect(array_column($public, 'id'), $hiddenIds));
[$s, $r] = call('GET', '/partners/public', null, false);
$check("public partners = shown partners ($shownP)", ($r['count'] ?? -1) === $shownP, (string) ($r['count'] ?? ''));
$hiddenM = DB::one('SELECT id, memberId FROM Member WHERE isShow = 0 LIMIT 1');
$shownMember = DB::one('SELECT id, memberId FROM Member WHERE isShow = 1 LIMIT 1');
$check("hidden member's public profile -> 404 (member {$hiddenM['memberId']})", call('GET', "/members/public/{$hiddenM['id']}", null, false)[0] === 404);
$check("shown member's public profile -> 200 (member {$shownMember['memberId']})", call('GET', "/members/public/{$shownMember['id']}", null, false)[0] === 200);
$hp = DB::one('SELECT id, partnerId FROM Partner WHERE isShow = 0 LIMIT 1');
$check("hidden partner's public profile -> 404 by id and by partner ID ({$hp['partnerId']})",
    call('GET', "/partners/public/{$hp['id']}", null, false)[0] === 404 && call('GET', "/partners/public/{$hp['partnerId']}", null, false)[0] === 404);
// a shown member that has a hidden partner: the public profile must not list that partner
$mix = DB::one('SELECT m.id, m.memberId, p.partnerId FROM Member m JOIN Partner p ON p.memberId = m.id WHERE m.isShow = 1 AND p.isShow = 0 LIMIT 1');
if ($mix) {
    [$s, $r] = call('GET', "/members/public/{$mix['id']}", null, false);
    $check("member {$mix['memberId']}'s public profile does not list hidden partner {$mix['partnerId']}", $s === 200 && !in_array($mix['partnerId'], array_column($r['data']['partners'] ?? [], 'partnerId'), true));
}
echo "Admin still sees everybody\n";
[$s, $r] = call('GET', '/members?limit=1');
$check('admin member list total = all members', ($r['pagination']['total'] ?? 0) === $count('SELECT COUNT(*) FROM Member'));
[$s, $r] = call('GET', '/members?search=' . rawurlencode($hiddenM['memberId']) . '&limit=100');
$row = array_values(array_filter($r['data'] ?? [], fn ($m) => $m['memberId'] === $hiddenM['memberId']))[0] ?? null;
$check('admin list includes a hidden member, with isShow = false', $row && $row['isShow'] === false, json_encode($row['isShow'] ?? null));
$check('admin details of a hidden member open (200)', call('GET', "/members/{$hiddenM['id']}")[0] === 200);

echo "Hide / show a member\n";
$pk = $shownMember['id'];
[$s, $r] = call('PATCH', "/members/$pk/visibility", ['isShow' => false]);
$check('hide -> 200, isShow false', $s === 200 && ($r['data']['isShow'] ?? null) === false, "status $s");
$check('stays active (hiding is not blocking)', ($r['data']['status']['status'] ?? '') === 'ACTIVE');
$check('now gone from the public list', !in_array($pk, array_column(call('GET', '/members/public', null, false)[1]['data'] ?? [], 'id'), true));
$check('now 404 on the public profile', call('GET', "/members/public/$pk", null, false)[0] === 404);
[$s, $r] = call('PATCH', "/members/$pk/visibility", ['isShow' => true]);
$check('show again -> 200, isShow true, back on the public profile', $s === 200 && ($r['data']['isShow'] ?? null) === true && call('GET', "/members/public/$pk", null, false)[0] === 200, "status $s");
foreach ([['yes', '"yes"'], [1, '1'], [null, 'null']] as [$bad, $label]) {
    $check("isShow = $label -> 400", call('PATCH', "/members/$pk/visibility", ['isShow' => $bad])[0] === 400);
}
$check('missing isShow -> 400', call('PATCH', "/members/$pk/visibility", ['other' => true])[0] === 400);
$check('without login -> 401', call('PATCH', "/members/$pk/visibility", ['isShow' => false], false)[0] === 401);
$check('unknown member -> 404', call('PATCH', '/members/99999999/visibility', ['isShow' => false])[0] === 404);

echo "Hide / show a partner\n";
$sp = DB::one('SELECT id, partnerId FROM Partner WHERE isShow = 1 LIMIT 1');
[$s, $r] = call('PATCH', "/partners/{$sp['id']}/visibility", ['isShow' => false]);
$check("hide partner {$sp['partnerId']} -> isShow false, 404 on public", $s === 200 && ($r['data']['isShow'] ?? null) === false && call('GET', "/partners/public/{$sp['id']}", null, false)[0] === 404, "status $s");
[$s, $r] = call('PATCH', "/partners/{$sp['partnerId']}/visibility", ['isShow' => true]);
$check('show it again (by partner ID) -> back on public', $s === 200 && ($r['data']['isShow'] ?? null) === true && call('GET', "/partners/public/{$sp['id']}", null, false)[0] === 200, "status $s");
$check('partner isShow = "no" -> 400', call('PATCH', "/partners/{$sp['id']}/visibility", ['isShow' => 'no'])[0] === 400);

echo "Nothing left changed\n";
$check("shown members back to $shownM, shown partners back to $shownP",
    $count('SELECT COUNT(*) FROM Member WHERE isShow = 1') === $shownM && $count('SELECT COUNT(*) FROM Partner WHERE isShow = 1') === $shownP);
$check('nobody blocked', $count('SELECT COUNT(*) FROM Member WHERE isActive = 0') + $count('SELECT COUNT(*) FROM Partner WHERE isActive = 0') === 0);

printf("\nRESULT: %d passed, %d failed\n", $pass, $fail);
exit($fail ? 1 : 0);
