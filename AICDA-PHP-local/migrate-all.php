<?php
// FULL re-migration of the old PHP site into the new database (replaces migrate-members.php,
// migrate-management.php and migrate-gallery.php).
//
//   Keeps: Admin.   Clears: Member, Partner, renewals, Gallery, Enquiry.   Then imports:
//   - manage_directory  -> Member / Partner      - management -> designations (+ missing people)
//   - association_event, political_event, image, letter -> Gallery (banners are NOT imported)
//
// Rules
//   * Member IDs are kept EXACTLY as in the old site, as text: "0560" and "560" are different members.
//     Only separators are removed from partner IDs: "2235-A" / "2235 A" -> "2235A".
//   * Several rows with the same ID and the SAME person (similar name) are merged: the newest row
//     wins, empty fields are filled from the older rows.
//   * Same ID but a DIFFERENT person: the newest one is imported, the other goes to the review list.
//   * A management row only updates a member when the names match (otherwise -> review list).
//   * Every old row is accounted for; the totals are checked at the end.
//
// Reads `aicda_old`, writes `aicda_php` (local MySQL 8, port 3308).
// Run:  php AICDA-PHP-local/migrate-all.php --dry-run      (reads + reports, changes nothing)
//       php AICDA-PHP-local/migrate-all.php                (backup, clear, import - one transaction)

$dry = in_array('--dry-run', $argv, true);
// --members-only: import members + partners only; Gallery and Enquiry are left exactly as they are.
$membersOnly = in_array('--members-only', $argv, true);
$clearTables = $membersOnly ? ['PartnerRenewal', 'MemberRenewal', 'Partner', 'Member'] : ['PartnerRenewal', 'MemberRenewal', 'Partner', 'Member', 'Gallery', 'Enquiry'];
$here = __DIR__;
$uploads = 'E:/Project ANK/AICDA/AICDA-PHP/public_html/uploads/old-php';
$opts = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
$old = new PDO('mysql:host=127.0.0.1;port=3308;dbname=aicda_old;charset=latin1', 'root', 'ank12345', $opts);
$old->exec('SET NAMES binary'); // old tables are latin1 but hold UTF-8 bytes
$new = new PDO('mysql:host=127.0.0.1;port=3308;dbname=aicda_php;charset=utf8mb4', 'root', 'ank12345', $opts);

// =============================== helpers ===============================
function clean($v, int $max = 191): ?string
{
    if ($v === null) return null;
    $v = (string) $v;
    $v = mb_check_encoding($v, 'UTF-8') ? $v : mb_convert_encoding($v, 'UTF-8', 'Windows-1252');
    $v = trim(preg_replace('/\s+/u', ' ', $v));
    return $v === '' ? null : mb_substr($v, 0, $max);
}
function mobile(?string $raw): ?string
{
    if ($raw === null) return null;
    $parts = preg_split('/[,\/;|]+|\s{2,}|\s+(?=\+?\d{10,})/', $raw) ?: [];
    array_unshift($parts, $raw);
    foreach ($parts as $c) {
        $d = preg_replace('/\D/', '', $c);
        if (strlen($d) === 12 && str_starts_with($d, '91')) $d = substr($d, 2);
        if (strlen($d) === 11 && $d[0] === '0') $d = substr($d, 1);
        if (strlen($d) === 10) return $d;
    }
    return null;
}
function day(?string $raw): ?string
{
    $s = trim((string) $raw);
    if ($s === '' || str_starts_with($s, '0000')) return null;
    if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})/', $s, $m)) return checkdate((int) $m[2], (int) $m[3], (int) $m[1]) ? sprintf('%04d-%02d-%02d 00:00:00.000', $m[1], $m[2], $m[3]) : null;
    if (preg_match('/^(\d{1,2})[\/.-](\d{1,2})[\/.-](\d{2,4})$/', $s, $m)) {
        $y = (int) $m[3];
        if ($y < 100) $y += $y <= 26 ? 2000 : 1900;
        return checkdate((int) $m[2], (int) $m[1], $y) ? sprintf('%04d-%02d-%02d 00:00:00.000', $y, $m[2], $m[1]) : null;
    }
    return null;
}
// Linux is case-sensitive: use the file name exactly as it is on disk (HONEY.jpg -> honey.jpg).
function realCase(string $dir, string $name): string
{
    static $cache = [];
    $cache[$dir] ??= is_dir($dir) ? scandir($dir) : [];
    if ($name === '' || in_array($name, $cache[$dir], true)) return $name;
    foreach ($cache[$dir] as $real) if (strcasecmp($real, $name) === 0) return $real;
    return $name;
}

function letters2num(string $l): int { $n = 0; foreach (str_split($l) as $c) $n = $n * 26 + ord($c) - 64; return $n; }
function designation($v): ?string
{
    $d = clean($v, 100);
    return ($d === null || in_array(strtolower($d), ['0', '00', 'test', '-'], true)) ? null : $d;
}
function nameKey($s): string { return preg_replace('/[^A-Z]/', '', strtoupper((string) $s)); }
function samePerson($a, $b): bool
{
    $a = nameKey($a); $b = nameKey($b);
    if ($a === '' || $b === '') return false;
    if (str_contains($a, $b) || str_contains($b, $a)) return true;
    similar_text($a, $b, $pct);
    return $pct >= 70;
}
// The old mamber_id, kept EXACTLY (leading zeros too: "06" stays "06"). Only spaces before/after are
// trimmed, and a separator between the number and the letters of a partner id ("2235-A", "2235 A")
// is removed so the same partner is not stored twice. Anything else ("1236-1", "(1288)", "PPPPPPP",
// "National President") is NOT guessed: it returns null and the row goes to the review list.
function parseId($raw): ?array
{
    $id = strtoupper(trim((string) clean($raw, 100)));
    if (preg_match('/^(\d{1,6})$/', $id, $m) && !preg_match('/^0+$/', $m[1])) return ['type' => 'member', 'id' => $m[1]];
    // "2235-A", "2235 A" and "1527(A)" are partner ids written with a separator / brackets.
    if (preg_match('/^(\d{1,6})(?:[\s._-]*([A-Z]{1,3})|[\s._-]*\(([A-Z]{1,3})\))$/', $id, $m) && !preg_match('/^0+$/', $m[1])) {
        $m[2] = ($m[2] ?? '') !== '' ? $m[2] : $m[3];
        return ['type' => 'partner', 'id' => $m[1] . $m[2], 'member' => $m[1], 'letters' => $m[2]];
    }
    return null;
}

$states = [];
foreach ($old->query('SELECT state_id, state_name FROM state') as $r) $states[(string) $r['state_id']] = clean($r['state_name']);

// One old row (directory or management) -> a person record in the new format.
function person(array $r, string $src, array $states, string $uploads): array
{
    $isDir = $src === 'directory';
    $folder = $isDir ? 'Member_Photo' : 'Management_Mamber';
    $file = realCase("$uploads/$folder", trim((string) clean($r['member_photo'], 200)));
    $photo = ($file !== '' && is_file("$uploads/$folder/$file"))
        ? "/uploads/old-php/$folder/" . str_replace(['%28', '%29'], ['(', ')'], rawurlencode($file)) : null;
    $aad = preg_replace('/\D/', '', (string) ($isDir ? $r['adhar_card_no'] : $r['adharcardno']));
    $pan = strtoupper((string) clean($isDir ? $r['pan_card_no'] : $r['pancardno'], 20));
    $rowId = (int) ($isDir ? $r['manage_directory_id'] : $r['management_id']);
    $created = (!empty($r['Submit_Date']) && !str_starts_with($r['Submit_Date'], '0000'))
        ? $r['Submit_Date'] . '.000'
        : gmdate('Y-m-d H:i:s.000', strtotime('2014-01-01 UTC') + $rowId + ($isDir ? 0 : 500000));
    return [
        'src' => $src, 'row' => $rowId, 'rawId' => (string) clean($r['mamber_id'], 100),
        'name' => clean($r['member_name']), 'fatherName' => clean($r['father_name']), 'photo' => $photo,
        'residentialAddress' => clean($r['residential_address']), 'mobile' => mobile(clean($r['mobile_no'], 100)),
        'residentialTelephone' => clean($r['residential_telephone'], 100), 'panCardNo' => $pan !== '' ? $pan : null,
        'aadharNo' => strlen($aad) === 12 ? $aad : null, 'designation' => designation($r['designation']),
        'companyName' => clean($r['company_name']), 'companyAddress' => clean($r['company_address']),
        'companyTelephone' => clean($r['company_tel'], 100),
        'state' => $states[trim((string) $r['state'])] ?? clean($r['state'], 100), 'city' => clean($r['city'], 100),
        'dateOfJoining' => day($r['date_of_joining']),
        'active' => trim((string) $r['activeStatus']) !== '1',          // old site: 0 = shown, 1 = hidden -> isShow
        'createdAt' => $created,
    ];
}
const FILLABLE = ['fatherName', 'photo', 'residentialAddress', 'mobile', 'residentialTelephone', 'panCardNo', 'aadharNo',
    'designation', 'companyName', 'companyAddress', 'companyTelephone', 'state', 'city', 'dateOfJoining'];
function fillBlanks(array $into, array $from, array $fields = FILLABLE): array
{
    foreach ($fields as $f) if (($into[$f] ?? null) === null && ($from[$f] ?? null) !== null) $into[$f] = $from[$f];
    return $into;
}

$normalised = []; // partner ids whose separator was removed ("2235-A" -> "2235A")
$review = [];   // rows NOT imported, with everything needed to add them by hand later
$merged = [];   // rows merged into another row of the same person
$excluded = []; // test rows / unreadable ids
$status = ['directory' => [], 'management' => []]; // old row id -> what happened
$toReview = function (array $p, string $why) use (&$review, &$status) {
    $review[] = [$p['src'], $p['row'], $p['rawId'], $p['name'], $why, $p['fatherName'], $p['mobile'], $p['companyName'], $p['city'], $p['state'], $p['designation']];
    $status[$p['src']][$p['row']] = 'review';
};

// =========================== 1. manage_directory ===========================
$groups = [];
foreach ($old->query('SELECT * FROM manage_directory ORDER BY manage_directory_id') as $r) {
    $p = person($r, 'directory', $states, $uploads);
    $parsed = parseId($r['mamber_id']);
    if ($p['name'] === null || ($parsed && $parsed['id'] === '987654') || preg_match('/^test(ing)?$/i', (string) $p['name'])) {
        $excluded[] = ['directory', $p['row'], $p['rawId'], $p['name'], $p['name'] === null ? 'no name' : 'test record'];
        $status['directory'][$p['row']] = 'excluded';
        continue;
    }
    if (!$parsed) {
        $toReview($p, "old ID \"{$p['rawId']}\" is not a member number - not guessed");
        continue;
    }
    if (strtoupper(trim((string) clean($r['mamber_id'], 100))) !== $parsed['id']) $normalised[] = ['directory', $p['row'], trim((string) $r['mamber_id']), $parsed['id'], $p['name']];
    $groups[$parsed['id']][] = $p + ['parsed' => $parsed];
}

$members = [];  // exact member id => person
$partners = []; // exact partner id => person
foreach ($groups as $id => $rows) {
    $newest = end($rows);                       // rows are in old-row order: the last is the newest
    $keep = $newest;
    foreach (array_reverse(array_slice($rows, 0, -1)) as $older) {
        if (samePerson($older['name'], $newest['name'])) {
            $keep = fillBlanks($keep, $older);
            $merged[] = ['directory', $older['row'], $older['rawId'], $older['name'], "same person as row {$newest['row']}"];
            $status['directory'][$older['row']] = 'merged';
        } else {
            $toReview($older, "ID $id is also used by a different person ({$newest['name']}, row {$newest['row']}) - newest kept");
        }
    }
    $status['directory'][$newest['row']] = 'imported';
    if ($newest['parsed']['type'] === 'member') $members[$id] = $keep;
    else $partners[$id] = $keep;
}

// ============================ 2. management ============================
$mgGroups = [];
$designationsApplied = 0; $hiddenNotApplied = 0; $noDesignation = 0;
foreach ($old->query('SELECT * FROM management ORDER BY management_id') as $r) {
    $p = person($r, 'management', $states, $uploads);
    if ($p['name'] === null) { $excluded[] = ['management', $p['row'], $p['rawId'], null, 'no name']; $status['management'][$p['row']] = 'excluded'; continue; }
    $parsed = parseId($r['mamber_id']);
    if (!$parsed) { // e.g. "National President": not a member number, so no id is invented
        $toReview($p, "old ID \"{$p['rawId']}\" is not a member number - not guessed");
        continue;
    }
    if (strtoupper(trim((string) clean($r['mamber_id'], 100))) !== $parsed['id']) $normalised[] = ['management', $p['row'], trim((string) $r['mamber_id']), $parsed['id'], $p['name']];
    $mgGroups[$parsed['id']][] = $p + ['parsed' => $parsed];
}
foreach ($mgGroups as $id => $rows) {
    $m = end($rows);
    foreach (array_reverse(array_slice($rows, 0, -1)) as $older) {
        if (samePerson($older['name'], $m['name'])) {
            $m = fillBlanks($m, $older);
            $merged[] = ['management', $older['row'], $older['rawId'], $older['name'], "same person as management row {$m['row']}"];
            $status['management'][$older['row']] = 'merged';
        } else {
            $toReview($older, "management ID $id is also used by a different person ({$m['name']}) - newest kept");
        }
    }
    $isPartner = $m['parsed']['type'] === 'partner';
    $existing = $isPartner ? ($partners[$id] ?? null) : ($members[$id] ?? null);

    if ($existing) {
        if (!samePerson($existing['name'], $m['name'])) {
            $toReview($m, "management says ID $id is {$m['name']}, but in the directory ID $id is {$existing['name']} - directory kept");
            continue;
        }
        $updated = fillBlanks($existing, $m, array_diff(FILLABLE, ['designation']));
        if (!$m['active']) { $hiddenNotApplied++; $status['management'][$m['row']] = 'hidden-not-applied'; }
        elseif ($m['designation'] === null) { $noDesignation++; $status['management'][$m['row']] = 'no-designation'; }
        else { $updated['designation'] = $m['designation']; $designationsApplied++; $status['management'][$m['row']] = 'designation-applied'; }
        if ($isPartner) $partners[$id] = $updated; else $members[$id] = $updated;
    } else {
        $status['management'][$m['row']] = !empty($m['parsed']['reserved']) ? 'new-member-reserved-id' : ($isPartner ? 'new-partner' : 'new-member');
        if ($isPartner) $partners[$id] = $m; else $members[$id] = $m;
    }
}

// ============ 3. partners whose member is not in the old data ============
// They are still imported (nothing is lost), with no member linked (Partner.memberId = NULL).
$noMember = [];
foreach ($partners as $pid => $p) {
    if (!isset($members[$p['parsed']['member']])) $noMember[] = [$pid, $p['name'], $p['parsed']['member'], $p['src'], $p['row']];
}

// ============================ 4. gallery ============================
$gallery = []; $galleryReport = [];
foreach ([
    ['association_event', 'Association_Id', 'Association_Title', 'Association_img', 'Submit_Date', 'Association_Event', 'ASSOCIATION'],
    ['political_event', 'political_id', 'political_title', 'political_image', 'Submit_Date', 'Political_Event', 'POLITICAL_ACHIEVEMENT'],
    ['image', 'id', 'caption', 'gallary_img', 'submit_Date', 'Js_Nayol', 'IMAGE'],
    ['letter', 'letter_id', 'title', 'letter_image', 'Submit_Date', 'Letter', 'LETTER'],
] as [$table, $idCol, $titleCol, $fileCol, $dateCol, $folder, $category]) {
    $g = ['rows' => 0, 'imported' => 0, 'notImage' => [], 'missing' => []];
    foreach ($old->query("SELECT `$idCol` id, `$titleCol` title, `$fileCol` file, `$dateCol` d FROM `$table` ORDER BY `$idCol`") as $r) {
        $g['rows']++;
        $file = trim($r['file']);
        if (!preg_match('/^[A-Za-z0-9._() -]+\.(jpe?g|jfif|png|gif|webp)$/i', $file) || str_contains($file, '..')) { $g['notImage'][] = $file; continue; }
        if (!is_file("$uploads/$folder/$file")) { $g['missing'][] = $file; continue; }
        $created = (!$r['d'] || str_starts_with($r['d'], '0000')) ? gmdate('Y-m-d H:i:s.000', strtotime('2014-01-01 UTC') + (int) $r['id']) : $r['d'] . '.000';
        $title = clean($r['title']) ?? pathinfo($file, PATHINFO_FILENAME);
        $gallery[] = ["old-$table-{$r['id']}", $title, $title, '/uploads/old-php/' . $folder . '/' . str_replace(['%28', '%29'], ['(', ')'], rawurlencode($file)), $category, $created, $created];
        $g['imported']++;
    }
    $galleryReport[$table] = $g;
}

// ============================ 5. checks ============================
$count = fn (string $src) => (int) $old->query($src === 'directory' ? 'SELECT COUNT(*) FROM manage_directory' : 'SELECT COUNT(*) FROM management')->fetchColumn();
$problems = [];
foreach (['directory', 'management'] as $src) {
    if (count($status[$src]) !== $count($src)) $problems[] = "$src: " . count($status[$src]) . " rows accounted for, old table has " . $count($src);
}
foreach ($members as $id => $m) if (!preg_match('/^\d{1,6}$/', (string) $id)) $problems[] = "bad member id $id";
$pids = []; foreach ($partners as $pid => $p) { $key = $p['parsed']['member'] . '|' . letters2num($p['parsed']['letters']); if (isset($pids[$key])) $problems[] = "partner number clash $pid"; $pids[$key] = 1; }
if ($problems) { echo "CHECKS FAILED, nothing written:\n  " . implode("\n  ", $problems) . "\n"; exit(1); }

// ============================ 6. write ============================
@mkdir("$here/migration-reports");
if (!$dry) {
    // Member ID must be text so leading zeros survive (0560 != 560).
    $type = $new->query("SELECT DATA_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'Member' AND COLUMN_NAME = 'memberId'")->fetchColumn();
    if (strtolower($type) !== 'varchar') $new->exec('ALTER TABLE `Member` MODIFY `memberId` VARCHAR(20) NOT NULL');

    $backup = [];
    foreach ($clearTables as $t) $backup[$t] = $new->query("SELECT * FROM `$t`")->fetchAll(PDO::FETCH_ASSOC);
    $backupFile = "$here/migration-reports/backup-before-full-migration-" . date('Ymd-His') . '.json';
    file_put_contents($backupFile, json_encode($backup, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    echo 'Backup: ' . basename($backupFile) . "\n";

    // Clearing and importing happen in ONE transaction: if anything fails, nothing is deleted.
    $new->beginTransaction();
    try {
        foreach ($clearTables as $t) $new->exec("DELETE FROM `$t`");
        $cols = 'fatherName, photo, residentialAddress, mobile, residentialTelephone, panCardNo, aadharNo, designation, companyName, companyAddress, companyTelephone, state, city, dateOfJoining';
        $insM = $new->prepare("INSERT INTO Member (memberId, memberName, $cols, specialDates, isActive, isShow, statusReason, statusActionBy, statusActionAt, createdAt, updatedAt) VALUES (" . str_repeat('?,', 23) . '?)');
        $insP = $new->prepare("INSERT INTO Partner (partnerId, partnerNumber, memberId, partnerName, $cols, specialDates, isActive, isShow, statusReason, statusActionBy, statusActionAt, createdAt, updatedAt) VALUES (" . str_repeat('?,', 25) . '?)');
        $fields = fn (array $p) => array_map(fn ($f) => $p[$f], array_map('trim', explode(',', $cols)));
        // Old "hidden" (activeStatus 1) means not shown on the public website, not blocked.
        $statusVals = fn (array $p) => [1, $p['active'] ? 1 : 0, null, null, null];
        $pk = [];
        uksort($members, fn ($a, $b) => ((int) $a <=> (int) $b) ?: strcmp($a, $b));
        foreach ($members as $id => $m) {
            $insM->execute([(string) $id, $m['name'], ...$fields($m), '[]', ...$statusVals($m), $m['createdAt'], $m['createdAt']]);
            $pk[$id] = (int) $new->lastInsertId();
        }
        foreach ($partners as $pid => $p) {
            $insP->execute([$pid, letters2num($p['parsed']['letters']), $pk[$p['parsed']['member']] ?? null, $p['name'], ...$fields($p), '[]', ...$statusVals($p), $p['createdAt'], $p['createdAt']]);
        }
        $insG = $new->prepare("INSERT INTO Gallery (id, title, description, imageUrl, category, resourceType, isActive, sortOrder, createdAt, updatedAt) VALUES (?,?,?,?,?,'IMAGE',1,0,?,?)");
        if (!$membersOnly) foreach ($gallery as $row) $insG->execute($row);
        $new->commit();
    } catch (Throwable $e) {
        $new->rollBack();
        echo 'FAILED, nothing changed: ' . $e->getMessage() . "\n";
        exit(1);
    }
}

// ============================ 7. reports ============================
$csv = function (string $file, array $header, array $rows) use ($here) {
    $f = fopen("$here/migration-reports/$file", 'w'); fwrite($f, "\xEF\xBB\xBF"); fputcsv($f, $header); foreach ($rows as $r) fputcsv($f, $r); fclose($f);
};
$csv('REVIEW-not-imported.csv', ['old_table', 'old_row', 'old_id', 'name', 'why', 'father', 'mobile', 'company', 'city', 'state', 'designation'], $review);
$csv('merged-duplicates.csv', ['old_table', 'old_row', 'old_id', 'name', 'merged_into'], $merged);
$csv('excluded.csv', ['old_table', 'old_row', 'old_id', 'name', 'why'], $excluded);
$csv('partners-without-member.csv', ['partner_id', 'name', 'member_id_not_found', 'old_table', 'old_row'], $noMember);
echo '  partners imported without a member (member not in the old data): ' . count($noMember) . "
";
$csv('partner-ids-separator-removed.csv', ['old_table', 'old_row', 'old_id', 'stored_as', 'name'], $normalised);
echo '  partner ids where only the separator was removed (2235-A -> 2235A): ' . count($normalised) . "\n";
$csv('leading-zero-ids.csv', ['member_id', 'name'], array_map(fn ($id) => [$id, $members[$id]['name']], array_values(array_filter(array_keys($members), fn ($id) => str_starts_with((string) $id, '0')))));

$tally = fn (string $src) => array_count_values($status[$src]);
echo ($dry ? "DRY RUN (nothing changed)\n" : "MIGRATED\n");
printf("  members %d   (with leading zero e.g. 0560: %d, reserved 7000xx for text ids: %d)\n", count($members),
    count(array_filter(array_keys($members), fn ($id) => str_starts_with((string) $id, '0'))),
    count(array_filter(array_keys($members), fn ($id) => (int) $id >= 700000)));
printf("  partners %d\n", count($partners));
printf("  members+partners with a designation %d\n", count(array_filter($members, fn ($m) => $m['designation'])) + count(array_filter($partners, fn ($p) => $p['designation'])));
printf("  hidden on the public website (isShow = false) %d\n", count(array_filter($members, fn ($m) => !$m['active'])) + count(array_filter($partners, fn ($p) => !$p['active'])));
echo "  old manage_directory rows (" . $count('directory') . "): " . json_encode($tally('directory')) . "\n";
echo "  old management rows (" . $count('management') . "):     " . json_encode($tally('management')) . "\n";
echo "  review list (not imported, to add by hand): " . count($review) . "   merged duplicates: " . count($merged) . "   excluded: " . count($excluded) . "\n";
foreach ($galleryReport as $t => $g) printf("  gallery %-18s %d of %d%s%s\n", $t, $g['imported'], $g['rows'], $g['notImage'] ? '  (not images: ' . implode(', ', $g['notImage']) . ')' : '', $g['missing'] ? '  (photo missing: ' . implode(', ', $g['missing']) . ')' : '');
