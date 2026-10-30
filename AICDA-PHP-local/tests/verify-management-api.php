<?php
// Tests GET /api/v1/management (the public Management page): pagination, order, ID-only search.
// Needs the local site running. Public endpoint: no login is used.
// Run: php AICDA-PHP-local/tests/verify-management-api.php
define('AICDA_API', true);
require 'E:/Project ANK/AICDA/AICDA-PHP/public_html/api/src/bootstrap.php';

function get(string $query): array
{
    $ch = curl_init('http://localhost:8080/api/v1/management' . $query);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30]);
    $body = json_decode((string) curl_exec($ch), true);
    return [curl_getinfo($ch, CURLINFO_HTTP_CODE), $body];
}
$pass = 0; $fail = 0;
$check = function ($n, $ok, $d = '') use (&$pass, &$fail) { $ok ? $pass++ : $fail++; printf("  [%s] %s%s\n", $ok ? 'PASS' : 'FAIL', $n, $ok || $d === '' ? '' : "  -> $d"); };
$code = fn ($r) => $r['type'] === 'partner' ? $r['partnerId'] : $r['memberId'];

$shownMembers = (int) DB::scalar('SELECT COUNT(*) FROM Member WHERE isShow = 1');
$shownPartners = (int) DB::scalar('SELECT COUNT(*) FROM Partner WHERE isShow = 1');
$all = $shownMembers + $shownPartners;

echo "Pagination\n";
[$s, $r] = get('?page=1&limit=10');
$check('works without login (200)', $s === 200 && ($r['success'] ?? false), "status $s");
$check("total = shown members + shown partners ($all)", ($r['pagination']['total'] ?? 0) === $all, (string) ($r['pagination']['total'] ?? ''));
$check('10 items on page 1', count($r['data'] ?? []) === 10);
$check('totalPages = ceil(total / 10)', ($r['pagination']['totalPages'] ?? 0) === (int) ceil($all / 10));
$check('every item says whether it is a member or a partner', !array_filter($r['data'], fn ($x) => !in_array($x['type'] ?? '', ['member', 'partner'], true)));
$check('no private fields (PAN, Aadhaar, date of birth)', !array_filter($r['data'], fn ($x) => array_key_exists('panCardNo', $x) || array_key_exists('aadharNo', $x) || array_key_exists('dateOfBirth', $x)));

echo "Walking every page\n";
$seen = []; $codes = []; $page = 1; $lastTotalPages = 1;
do {
    [$s, $r] = get("?page=$page&limit=50");
    foreach ($r['data'] as $x) { $seen[$x['type'] . ':' . $x['id']] = ($seen[$x['type'] . ':' . $x['id']] ?? 0) + 1; $codes[] = [$x['type'], $code($x), (int) $code($x)]; }
    $lastTotalPages = $r['pagination']['totalPages'];
    $page++;
} while ($page <= $lastTotalPages);
$check("all pages together hold exactly $all records", count($seen) === $all, (string) count($seen));
$check('nobody appears twice and nobody is skipped', !array_filter($seen, fn ($c) => $c !== 1));
$hiddenInList = 0;
foreach (array_keys($seen) as $k) { [$t, $id] = explode(':', $k); if (!DB::scalar('SELECT isShow FROM `' . ucfirst($t) . '` WHERE id = ?', [$id])) $hiddenInList++; }
$check('no hidden member or partner is in the list', $hiddenInList === 0, (string) $hiddenInList);
$out = 0; for ($i = 1; $i < count($codes); $i++) if ($codes[$i][2] < $codes[$i - 1][2]) $out++;
$check('list is in Member ID order (009, 009A, 0019, ...)', $out === 0, "$out places out of order");
$check('a member comes right before its own partners (e.g. 009 then 009A)', array_slice(array_column($codes, 1), 0, 2) === ['009', '009A']);

echo "Search - IDs only\n";
[$s, $r] = get('?search=0560');
$ids = array_map($code, $r['data']);
$check('0560 finds member 0560 and its partner(s)', in_array('0560', $ids, true) && count($ids) >= 1, implode(',', $ids));
$check('0560 does NOT find member 560 (different person)', !in_array('560', $ids, true));
[$s, $r] = get('?search=560');
$ids = array_map($code, $r['data']);
$check('560 finds member 560, not 0560', in_array('560', $ids, true) && !in_array('0560', $ids, true), implode(',', $ids));
$partner = DB::one("SELECT p.partnerId, p.partnerName FROM Partner p WHERE p.isShow = 1 AND p.memberId IS NOT NULL LIMIT 1");
[$s, $r] = get('?search=' . urlencode($partner['partnerId']));
$check("partner ID {$partner['partnerId']} finds exactly that partner", count($r['data']) === 1 && $r['data'][0]['type'] === 'partner' && $r['data'][0]['partnerId'] === $partner['partnerId'], json_encode(array_map($code, $r['data'])));
[$s, $r] = get('?search=' . urlencode(strtolower($partner['partnerId'])));
$check('upper/lower case does not matter', count($r['data']) === 1);
[$s, $r] = get('?search=' . urlencode('  ' . $partner['partnerId'] . '  '));
$check('spaces around the ID are ignored', count($r['data']) === 1);
$orphan = DB::one('SELECT partnerId FROM Partner WHERE isShow = 1 AND memberId IS NULL LIMIT 1');
[$s, $r] = get('?search=' . urlencode($orphan['partnerId']));
$check("partner without a member ({$orphan['partnerId']}) can be found by its ID", count($r['data']) === 1 && $r['data'][0]['member'] === null, json_encode(array_map($code, $r['data'])));
$name = explode(' ', DB::scalar("SELECT memberName FROM Member WHERE memberId = '0560'"))[0];
[$s, $r] = get('?search=' . urlencode($name));
$check("a NAME ($name) finds nothing", $s === 200 && $r['pagination']['total'] === 0);
$mobile = DB::scalar("SELECT mobile FROM Member WHERE memberId = '0560'");
[$s, $r] = get('?search=' . urlencode($mobile));
$check('a MOBILE number finds nothing', $s === 200 && $r['pagination']['total'] === 0);
[$s, $r] = get('?search=' . urlencode('GAGAN CAR BAZAR'));
$check('a COMPANY finds nothing', $s === 200 && $r['pagination']['total'] === 0);
[$s, $r] = get('?search=056');
$check('part of an ID (056) finds nothing - exact ID only', $s === 200 && $r['pagination']['total'] === 0);
$hidden = DB::scalar('SELECT memberId FROM Member WHERE isShow = 0 LIMIT 1');
[$s, $r] = get('?search=' . urlencode($hidden));
$check("hidden member $hidden is not found", $s === 200 && !in_array($hidden, array_map($code, $r['data']), true));
[$s, $r] = get('?search=');
$check('empty search = everything', $r['pagination']['total'] === $all);

echo "Odd input\n";
[$s, $r] = get('?search=' . urlencode("x' OR '1'='1"));
$check('SQL-injection text -> 0 results, no error', $s === 200 && $r['pagination']['total'] === 0);
[$s, $r] = get('?search[]=a&search[]=b');
$check('array instead of text does not crash', $s === 200);
[$s, $r] = get('?page=0&limit=10');
$check('page=0 is treated as page 1', $s === 200 && $r['pagination']['page'] === 1);
[$s, $r] = get('?page=999999&limit=10');
$check('a page past the end -> empty list, no error, same total', $s === 200 && $r['data'] === [] && $r['pagination']['total'] === $all);
[$s, $r] = get('?limit=1000');
$check('limit is capped at 50', $r['pagination']['limit'] === 50 && count($r['data']) === 50);
[$s, $r] = get('?limit=abc&page=xyz');
$check('non-numbers fall back to page 1, 10 per page', $s === 200 && $r['pagination']['page'] === 1 && $r['pagination']['limit'] === 10);
[$s, $r] = get('?limit=-5');
$check('negative limit does not crash', $s === 200 && $r['pagination']['limit'] >= 1);

echo "Old endpoints still work\n";
foreach (['/members/public', '/partners/public'] as $path) {
    $ch = curl_init('http://localhost:8080/api/v1' . $path); curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); curl_exec($ch);
    $check("$path still answers (200)", curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200);
}

printf("\nRESULT: %d passed, %d failed\n", $pass, $fail);
exit($fail ? 1 : 0);
