<?php
// Checks the migrated data in aicda_php against the old data in aicda_old.
// Run: php AICDA-PHP-local/tests/verify-migration.php
$opts = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
$n = new PDO('mysql:host=127.0.0.1;port=3308;dbname=aicda_php;charset=utf8mb4', 'root', 'ank12345', $opts);
$o = new PDO('mysql:host=127.0.0.1;port=3308;dbname=aicda_old;charset=latin1', 'root', 'ank12345', $opts);
$o->exec('SET NAMES binary');
$q = fn ($s) => $n->query($s)->fetchColumn();
$pass = 0; $fail = 0;
$check = function ($name, $ok, $d = '') use (&$pass, &$fail) { $ok ? $pass++ : $fail++; printf("  [%s] %s%s\n", $ok ? 'PASS' : 'FAIL', $name, $ok || $d === '' ? '' : "  -> $d"); };
$nameKey = fn ($s) => preg_replace('/[^A-Z]/', '', strtoupper((string) $s));

// Old ids exactly as typed (only trimmed) -> names
$oldExact = []; $oldNoSep = [];
foreach ($o->query('SELECT mamber_id i, member_name m FROM manage_directory UNION ALL SELECT mamber_id, member_name FROM management') as $r) {
    $raw = strtoupper(trim(preg_replace('/\s+/', ' ', $r['i'])));
    $oldExact[$raw][] = $nameKey($r['m']);
    $oldNoSep[preg_replace('/^(\d+)[\s._-]*\(?([A-Z]{1,3})\)?$/', '$1$2', $raw)][] = $nameKey($r['m']);
}

echo "1. Tables\n";
$check('Admin untouched (3)', (int) $q('SELECT COUNT(*) FROM Admin') === 3);
$check('no banners imported', (int) $q("SELECT COUNT(*) FROM Gallery WHERE category = 'BANNER'") === 0);
$check('gallery = 783 (441 + 137 + 23 + 182)', (int) $q('SELECT COUNT(*) FROM Gallery') === 783);
$check('every gallery item has description = title', (int) $q('SELECT COUNT(*) FROM Gallery WHERE description <> title OR description IS NULL') === 0);
$check('no hacker .php rows', (int) $q("SELECT COUNT(*) FROM Gallery WHERE imageUrl LIKE '%.php%'") === 0);
$check('Member.memberId is text', strtolower($q("SELECT DATA_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='aicda_php' AND TABLE_NAME='Member' AND COLUMN_NAME='memberId'")) === 'varchar');

echo "2. Member IDs are exactly the old mamber_id\n";
$members = $n->query('SELECT memberId, memberName FROM Member')->fetchAll(PDO::FETCH_KEY_PAIR);
$notOld = array_filter(array_keys($members), fn ($id) => !isset($oldExact[(string) $id]));
$check('every member ID exists character-for-character in the old data (' . count($members) . ' members)', !$notOld, count($notOld) . ' e.g. ' . implode(', ', array_slice($notOld, 0, 5)));
$wrongName = array_filter(array_keys($members), fn ($id) => !in_array($nameKey($members[$id]), $oldExact[(string) $id] ?? [], true));
$check('and that old row has the same name', !$wrongName, implode(', ', array_slice($wrongName, 0, 5)));
$zeros = array_filter(array_keys($members), fn ($id) => str_starts_with((string) $id, '0'));
$check('leading zeros kept (' . count($zeros) . ' IDs like ' . implode(', ', array_slice($zeros, 0, 4)) . ')', count($zeros) > 0);
$pairs = $n->query("SELECT memberId, memberName FROM Member WHERE memberId IN ('560','0560','578','0578','2451','02451')")->fetchAll(PDO::FETCH_KEY_PAIR);
$check('560 / 0560, 578 / 0578, 2451 / 02451 are different people', count($pairs) === 6 && $pairs['560'] !== $pairs['0560'] && $pairs['578'] !== $pairs['0578'], json_encode($pairs));
$check('no invented IDs (nothing like 7000xx, 12361, 20811)', !array_filter(array_keys($members), fn ($id) => (int) $id >= 700000 || in_array((string) $id, ['12361', '20811', '1288'], true) && !isset($oldExact[(string) $id])));
$check('every member ID is 1-6 digits', !array_filter(array_keys($members), fn ($id) => !preg_match('/^\d{1,6}$/', (string) $id)));
$check('no duplicate member IDs', (int) $q('SELECT COUNT(*) FROM (SELECT BINARY memberId b FROM Member GROUP BY b HAVING COUNT(*) > 1) x') === 0);

echo "3. Partner IDs\n";
$partners = $n->query('SELECT partnerId, partnerName FROM Partner')->fetchAll(PDO::FETCH_KEY_PAIR);
$exact = array_filter(array_keys($partners), fn ($id) => isset($oldExact[$id]));
$sep = array_filter(array_keys($partners), fn ($id) => !isset($oldExact[$id]) && isset($oldNoSep[$id]));
$other = array_filter(array_keys($partners), fn ($id) => !isset($oldNoSep[$id]));
printf("     %d exactly as old, %d only had a separator / bracket removed (2235-A -> 2235A), %d other\n", count($exact), count($sep), count($other));
$check('every partner ID comes from the old data (exact, or only the separator removed)', !$other, implode(', ', array_slice($other, 0, 5)));
$wrong = array_filter(array_keys($partners), fn ($id) => !in_array($nameKey($partners[$id]), $oldNoSep[$id] ?? [], true));
$check('and that old row has the same name', !$wrong, implode(', ', array_slice($wrong, 0, 5)));
$check('no partner points to a member that does not exist', (int) $q('SELECT COUNT(*) FROM Partner p LEFT JOIN Member m ON m.id = p.memberId WHERE p.memberId IS NOT NULL AND m.id IS NULL') === 0);
$noMember = $n->query('SELECT partnerId FROM Partner WHERE memberId IS NULL')->fetchAll(PDO::FETCH_COLUMN);
$wrongNull = array_filter($noMember, fn ($pid) => isset($members[preg_replace('/[A-Z]+$/', '', $pid)]));
$check('partners without a member (' . count($noMember) . ') really have no member in the data', !$wrongNull, implode(', ', array_slice($wrongNull, 0, 5)));
$missedLink = (int) $q("SELECT COUNT(*) FROM Partner p JOIN Member m ON m.memberId = LEFT(p.partnerId, CHAR_LENGTH(p.partnerId) - CHAR_LENGTH(REGEXP_SUBSTR(p.partnerId, '[A-Z]+$'))) WHERE p.memberId IS NULL");
$check('no partner was left without its member when the member exists', $missedLink === 0, (string) $missedLink);
$check('partner ID = its member ID + letters (0560A belongs to 0560)', (int) $q("SELECT COUNT(*) FROM Partner p JOIN Member m ON m.id = p.memberId WHERE p.partnerId NOT REGEXP CONCAT('^', m.memberId, '[A-Z]{1,3}$')") === 0);
$check('no duplicate partner IDs', (int) $q('SELECT COUNT(*) FROM (SELECT partnerId FROM Partner GROUP BY partnerId HAVING COUNT(*) > 1) x') === 0);

echo "4. Shown / hidden on the public website (isShow)\n";
foreach (['Member', 'Partner'] as $t) {
    $check("$t.isShow column exists", (int) $q("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='aicda_php' AND TABLE_NAME='$t' AND COLUMN_NAME='isShow'") === 1);
}
$check('nobody is "Blocked by Admin" because of the migration', (int) $q("SELECT COUNT(*) FROM Member WHERE statusActionBy = 'Data migration'") + (int) $q("SELECT COUNT(*) FROM Partner WHERE statusActionBy = 'Data migration'") === 0);
// The newest old row with that exact id (directory first, then management) decides: activeStatus 0 = shown, 1 = hidden.
$newestOld = [];
foreach ([['management', 'management_id'], ['manage_directory', 'manage_directory_id']] as [$table, $pk]) {
    foreach ($o->query("SELECT $pk r, mamber_id i, member_name m, activeStatus a FROM $table ORDER BY $pk") as $r) {
        $raw = strtoupper(trim(preg_replace('/\s+/', ' ', $r['i'])));
        $key = preg_replace('/^(\d+)[\s._-]*\(?([A-Z]{1,3})\)?$/', '$1$2', $raw);
        $newestOld[$key][$nameKey($r['m'])] = trim((string) $r['a']) !== '1';
    }
}
$mismatch = [];
foreach ($n->query('SELECT memberId id, memberName name, isShow FROM Member UNION ALL SELECT partnerId, partnerName, isShow FROM Partner') as $r) {
    $expected = $newestOld[$r['id']][$nameKey($r['name'])] ?? null;
    if ($expected === null || $expected !== (bool) $r['isShow']) $mismatch[] = "{$r['id']} (new isShow=" . (int) $r['isShow'] . ')';
}
$check('every isShow matches the old activeStatus (0 -> shown, 1 -> hidden)', !$mismatch, count($mismatch) . ' e.g. ' . implode(', ', array_slice($mismatch, 0, 5)));
printf("     members shown %d / hidden %d, partners shown %d / hidden %d\n",
    $q('SELECT COUNT(*) FROM Member WHERE isShow = 1'), $q('SELECT COUNT(*) FROM Member WHERE isShow = 0'),
    $q('SELECT COUNT(*) FROM Partner WHERE isShow = 1'), $q('SELECT COUNT(*) FROM Partner WHERE isShow = 0'));

echo "5. Nothing silently lost\n";
$dir = (int) $o->query('SELECT COUNT(*) FROM manage_directory')->fetchColumn();
$mg = (int) $o->query('SELECT COUNT(*) FROM management')->fetchColumn();
$review = count(file(__DIR__ . '/../migration-reports/REVIEW-not-imported.csv')) - 1;
$merged = count(file(__DIR__ . '/../migration-reports/merged-duplicates.csv')) - 1;
$excluded = count(file(__DIR__ . '/../migration-reports/excluded.csv')) - 1;
printf("     old rows %d (directory %d + management %d)\n     -> imported as people %d, management that only added a designation to someone %d,\n        same-person duplicates merged %d, review list %d, excluded %d\n",
    $dir + $mg, $dir, $mg, count($members) + count($partners), 127 + 27 + 28, $merged, $review, $excluded);
$check('every old row is accounted for', count($members) + count($partners) + (127 + 27 + 28) + $merged + $review + $excluded === $dir + $mg,
    (count($members) + count($partners) + 182 + $merged + $review + $excluded) . ' vs ' . ($dir + $mg));

printf("\nRESULT: %d passed, %d failed\n", $pass, $fail);
exit($fail ? 1 : 0);
