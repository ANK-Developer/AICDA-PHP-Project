<?php
// Tests GET /api/v1/gallery (public): search + pagination, compared with straight SQL.
// Needs the local site running. Run: php AICDA-PHP-local/tests/verify-gallery-api.php
define('AICDA_API', true);
require 'E:/Project ANK/AICDA/AICDA-PHP/public_html/api/src/bootstrap.php';

function get(string $query): array
{
    $ch = curl_init('http://localhost:8080/api/v1/gallery' . $query);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30]);
    $body = json_decode((string) curl_exec($ch), true);
    return [curl_getinfo($ch, CURLINFO_HTTP_CODE), $body];
}
$pass = 0; $fail = 0;
$check = function ($n, $ok, $d = '') use (&$pass, &$fail) { $ok ? $pass++ : $fail++; printf("  [%s] %s%s\n", $ok ? 'PASS' : 'FAIL', $n, $ok || $d === '' ? '' : "  -> $d"); };
$sql = fn (string $c) => (int) DB::scalar('SELECT COUNT(*) FROM Gallery WHERE isActive = 1 AND category = ?', [$c]);

foreach (['ASSOCIATION' => 'Association events', 'POLITICAL_ACHIEVEMENT' => 'Political achievements', 'IMAGE' => 'Image', 'LETTER' => 'Letters'] as $cat => $label) {
    $total = $sql($cat);
    echo "$label ($cat, $total items)\n";
    [$s, $r] = get("?category=$cat&page=1&limit=12");
    $check("page 1 has 12 items, total = $total", $s === 200 && count($r['gallery']) === min(12, $total) && $r['count'] === $total && $r['pagination']['total'] === $total, json_encode($r['pagination'] ?? null));
    $check('totalPages = ceil(total / 12)', ($r['pagination']['totalPages'] ?? 0) === max(1, (int) ceil($total / 12)));
    $ids = []; $pages = 0;
    for ($p = 1; $p <= ($r['pagination']['totalPages'] ?? 1); $p++) { [, $x] = get("?category=$cat&page=$p&limit=12"); $pages++; foreach ($x['gallery'] as $g) $ids[] = $g['id']; }
    $check("all $pages pages together hold every item exactly once", count($ids) === $total && count(array_unique($ids)) === $total, count($ids) . ' / ' . count(array_unique($ids)));
    $db = array_column(DB::all('SELECT id FROM Gallery WHERE isActive = 1 AND category = ? ORDER BY createdAt DESC', [$cat]), 'id');
    $check('same newest-first order as before', $ids === $db);
    [, $all] = get("?category=$cat");
    $check('without page/limit it still returns everything (old behaviour)', $all['count'] === $total && count($all['gallery']) === $total && !isset($all['pagination']));
    $sample = DB::scalar('SELECT title FROM Gallery WHERE isActive = 1 AND category = ? AND title <> "" ORDER BY id LIMIT 1', [$cat]);
    $word = $sample ? explode(' ', trim($sample))[0] : '';
    if ($word !== '') {
        $expect = (int) DB::scalar('SELECT COUNT(*) FROM Gallery WHERE isActive = 1 AND category = ? AND (title LIKE ? OR description LIKE ?)', [$cat, DB::like($word), DB::like($word)]);
        [, $q] = get("?category=$cat&page=1&limit=12&search=" . urlencode($word));
        $check("search '$word' = $expect matches (same as SQL)", $q['count'] === $expect && $q['pagination']['total'] === $expect, (string) $q['count']);
        $check('every result really contains the word', !array_filter($q['gallery'], fn ($g) => stripos(($g['title'] ?? '') . ' ' . ($g['description'] ?? ''), $word) === false));
        [, $q2] = get("?category=$cat&page=1&limit=12&search=" . urlencode(strtolower($word)));
        $check('search ignores upper/lower case', $q2['count'] === $expect);
    }
    [, $none] = get("?category=$cat&page=1&limit=12&search=zzzqqq999");
    $check('a word that is nowhere gives 0 results (no error)', $none['count'] === 0 && $none['gallery'] === []);
}

echo "Odd input\n";
[$s, $r] = get('?category=LETTER&page=1&limit=12&search=' . urlencode("x' OR '1'='1"));
$check('SQL-injection text -> 0 results, no error', $s === 200 && $r['count'] === 0);
[$s, $r] = get('?category=LETTER&page=1&limit=12&search=' . urlencode('%'));
$check("a lone % is searched literally (not 'match everything')", $s === 200 && $r['count'] === (int) DB::scalar("SELECT COUNT(*) FROM Gallery WHERE isActive = 1 AND category = 'LETTER' AND (title LIKE '%\\%%' OR description LIKE '%\\%%')"));
[$s, $r] = get('?category=LETTER&page=0&limit=12');
$check('page=0 -> page 1', $s === 200 && $r['pagination']['page'] === 1);
[$s, $r] = get('?category=LETTER&page=99999&limit=12');
$check('page past the end -> empty list, same total, no error', $s === 200 && $r['gallery'] === [] && $r['count'] === $sql('LETTER'));
[$s, $r] = get('?category=LETTER&limit=5000');
$check('limit is capped at 100', $r['pagination']['limit'] === 100);
[$s, $r] = get('?category=LETTER&page=abc&limit=xyz');
$check('non-numbers fall back to page 1, 12 per page', $s === 200 && $r['pagination']['page'] === 1 && $r['pagination']['limit'] === 12);
[$s, $r] = get('?category=LETTER&page=1&limit=12&search[]=a');
$check('array instead of text does not crash', $s === 200);
[$s] = get('?category=NOPE&page=1&limit=12');
$check('unknown category still 400', $s === 400);

echo "Photo / video filter (Image page)\n";
[, $img] = get('?category=IMAGE&page=1&limit=12&resourceType=IMAGE');
[, $vid] = get('?category=IMAGE&page=1&limit=12&resourceType=VIDEO');
$check('IMAGE type = all photos', $img['count'] === (int) DB::scalar("SELECT COUNT(*) FROM Gallery WHERE isActive = 1 AND category = 'IMAGE' AND resourceType = 'IMAGE'"));
$check('VIDEO type = all videos (' . $vid['count'] . ')', $vid['count'] === (int) DB::scalar("SELECT COUNT(*) FROM Gallery WHERE isActive = 1 AND category = 'IMAGE' AND resourceType = 'VIDEO'"));
$check('photos + videos = everything', $img['count'] + $vid['count'] === $sql('IMAGE'));

echo "Other users of this endpoint still work\n";
[$s, $r] = get('?category=BANNER&limit=100');
$check('banner query (limit=100) works', $s === 200 && isset($r['gallery']));
$ch = curl_init('http://localhost:8080/api/v1/gallery/admin'); curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); curl_exec($ch);
$check('admin list is untouched (still needs login -> 401)', curl_getinfo($ch, CURLINFO_HTTP_CODE) === 401);

printf("\nRESULT: %d passed, %d failed\n", $pass, $fail);
exit($fail ? 1 : 0);
