<?php
// Tests the PHP API (what the admin panel calls) with the migrated text member IDs.
// Needs the local site running (start-local.bat). Test records it creates are removed at the end.
// Run: php AICDA-PHP-local/tests/verify-api.php
define('AICDA_API', true);
require 'E:/Project ANK/AICDA/AICDA-PHP/public_html/api/src/bootstrap.php';

$t = Crypto::signToken(DB::one('SELECT id FROM Admin WHERE isActive = 1 ORDER BY createdAt LIMIT 1')['id']);
function call(string $m, string $p, $body = null): array
{
    global $t;
    $ch = curl_init("http://localhost:8080/api/v1$p");
    curl_setopt_array($ch, [CURLOPT_CUSTOMREQUEST => $m, CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => ["Authorization: Bearer $t"]]);
    if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    $r = json_decode((string) curl_exec($ch), true);
    return [curl_getinfo($ch, CURLINFO_HTTP_CODE), $r];
}
$pass = 0; $fail = 0;
$check = function ($n, $ok, $d = '') use (&$pass, &$fail) { $ok ? $pass++ : $fail++; printf("  [%s] %s%s\n", $ok ? 'PASS' : 'FAIL', $n, $ok || $d === '' ? '' : "  -> $d"); };

$members = (int) DB::scalar('SELECT COUNT(*) FROM Member');
$partnersCount = (int) DB::scalar('SELECT COUNT(*) FROM Partner');
$TEST = '0999';
// A test ID that is free both with and without its leading zero (e.g. 0999 and 999).
while (DB::one('SELECT id FROM Member WHERE memberId IN (?, ?)', [$TEST, ltrim($TEST, '0')])) $TEST = '0' . random_int(90000, 99999);

echo "Search & list\n";
[$s, $r] = call('GET', '/members?search=0560&limit=100');
$found = array_column($r['data'] ?? [], 'memberName', 'memberId');
$check('search 0560 finds GANGANDEEP SINGH as 0560', ($found['0560'] ?? '') === 'GANGANDEEP SINGH', json_encode($found));
[$s, $r] = call('GET', '/members?search=560&limit=100');
$ids = array_column($r['data'] ?? [], 'memberId');
$check('search 560 finds KAMALJIT SINGH (560), not 0560 by ID', in_array('560', $ids, true) && !in_array('0560', $ids, true), implode(',', array_slice($ids, 0, 10)));
[$s, $r] = call('GET', '/members?search=06&limit=100');
$check('search 06 finds member 06 if it exists', !DB::one("SELECT id FROM Member WHERE memberId = '06'") || in_array('06', array_column($r['data'] ?? [], 'memberId'), true));
$check('memberId is returned as text with its zero ("0560")', ($found['0560'] ?? null) !== null && array_key_exists('0560', $found));
[$s, $r] = call('GET', '/members?sortBy=memberId&order=asc&limit=15');
$seq = array_column($r['data'] ?? [], 'memberId');
$sorted = $seq; usort($sorted, fn ($a, $b) => ((int) $a <=> (int) $b) ?: strcmp($a, $b));
$check('sort by Member ID goes by number (009, 0019, 060 … not text order)', $seq === $sorted, implode(',', $seq));
[$s, $r] = call('GET', '/members?limit=1');
$check("member list total = $members", ($r['pagination']['total'] ?? 0) === $members, (string) ($r['pagination']['total'] ?? ''));
$shownMembers = (int) DB::scalar('SELECT COUNT(*) FROM Member WHERE isShow = 1');
$check("public members = members shown on the website ($shownMembers)", count(call('GET', '/members/public')[1]['data'] ?? []) === $shownMembers);
$shownPartners = (int) DB::scalar('SELECT COUNT(*) FROM Partner WHERE isShow = 1');
$check("public partners = partners shown on the website ($shownPartners)", (call('GET', '/partners/public')[1]['count'] ?? 0) === $shownPartners);
$pk = DB::scalar("SELECT id FROM Member WHERE memberId = '0560'");
[$s, $r] = call('GET', "/members/$pk");
$check('member 0560 details open, with its partners', $s === 200 && ($r['data']['memberId'] ?? '') === '0560' && is_array($r['data']['partners'] ?? null), "status $s");
[$s, $r] = call('GET', "/members/public/$pk");
$check('public profile of 0560 shows 0560', $s === 200 && ($r['data']['memberId'] ?? '') === '0560', "status $s");

echo "Create / edit with a leading-zero ID\n";
[$s, $r] = call('POST', '/members', ['memberId' => $TEST, 'memberName' => 'ZZ TEST LEADING ZERO']);
$check("create member $TEST -> stored as \"$TEST\"", $s === 201 && ($r['data']['memberId'] ?? null) === $TEST, "status $s " . json_encode($r['data']['memberId'] ?? $r));
$newPk = $r['data']['id'] ?? 0;
[$s, $r] = call('POST', '/members', ['memberId' => $TEST, 'memberName' => 'ZZ DUP']);
$check("same ID $TEST again -> 409 already in use", $s === 409 && str_contains($r['message'] ?? '', 'already in use'), "status $s " . ($r['message'] ?? ''));
[$s, $r] = call('POST', '/members', ['memberId' => ltrim($TEST, '0'), 'memberName' => 'ZZ NO ZERO']);
$check('ID without the zero (' . ltrim($TEST, '0') . ') is a different, allowed member', $s === 201 && ($r['data']['memberId'] ?? '') === ltrim($TEST, '0'), "status $s");
$noZeroPk = $r['data']['id'] ?? 0;
foreach (['abc' => 'letters', '0' => 'only zero', '000' => 'only zeros', '1234567' => '7 digits', '12A' => 'partner-like', '12-3' => 'dash'] as $bad => $why) {
    [$s] = call('POST', '/members', ['memberId' => $bad, 'memberName' => 'ZZ BAD']);
    $check("member ID '$bad' ($why) rejected with 400", $s === 400, "status $s");
}
[$s, $r] = call('PUT', "/members/$newPk", ['memberId' => $TEST, 'memberName' => 'ZZ TEST EDITED']);
$check("edit keeps ID $TEST", $s === 200 && ($r['data']['memberId'] ?? '') === $TEST && $r['data']['memberName'] === 'ZZ TEST EDITED', "status $s");

echo "Partners of a leading-zero member\n";
[$s, $r] = call('POST', '/partners', ['memberId' => $TEST, 'partnerName' => 'ZZ PARTNER']);
$check("partner of $TEST gets ID {$TEST}A", $s === 201 && ($r['data']['partnerId'] ?? '') === $TEST . 'A', "status $s " . json_encode($r['data']['partnerId'] ?? $r));
$check("partners?memberId=$TEST finds it", (call('GET', "/partners?memberId=$TEST")[1]['pagination']['total'] ?? 0) === 1);
$check("/partners/member/$TEST finds it", (call('GET', "/partners/member/$TEST")[1]['count'] ?? 0) === 1);
$check('partners of ' . ltrim($TEST, '0') . ' (no zero) do not include it', (call('GET', '/partners/member/' . ltrim($TEST, '0'))[1]['count'] ?? -1) === 0);
[$s] = call('POST', '/partners', ['memberId' => '99999', 'partnerName' => 'ZZ NO MEMBER']);
$check('partner for a member that does not exist -> 404', $s === 404, "status $s");
$p0560 = (int) DB::scalar("SELECT COUNT(*) FROM Partner p JOIN Member m ON m.id = p.memberId WHERE m.memberId = '0560'");
$check("partners?memberId=0560 matches the database ($p0560)", (call('GET', '/partners?memberId=0560')[1]['pagination']['total'] ?? -1) === $p0560);
[$s] = call('GET', '/important-dates?search=0560&period=all');
$check('important dates search by 0560 works (200)', $s === 200, "status $s");

// clean up the test records
DB::run('DELETE FROM Partner WHERE memberId IN (?, ?)', [$newPk, $noZeroPk]);
DB::run('DELETE FROM Member WHERE id IN (?, ?)', [$newPk, $noZeroPk]);
$check("test records removed (back to $members members / $partnersCount partners)",
    (int) DB::scalar('SELECT COUNT(*) FROM Member') === $members && (int) DB::scalar('SELECT COUNT(*) FROM Partner') === $partnersCount);
printf("\nRESULT: %d passed, %d failed\n", $pass, $fail);
exit($fail ? 1 : 0);
