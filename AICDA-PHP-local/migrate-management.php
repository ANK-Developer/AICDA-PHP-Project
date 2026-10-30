<?php
// One-time migration: OLD site `management` (committee) table -> designations on the new Member / Partner.
//   A. numeric id that already exists as a Member   -> that member's designation is updated
//   B. numeric id that is NOT a Member yet          -> a new Member is created (with designation)
//   C. id that is text ("National President", ...)  -> a new Member with a reserved id 700000 + old management_id
//   D. id with letters (712-A, 1158a)               -> that Partner's designation is updated (if the partner exists)
// Old rows with activeStatus = 1 were hidden on the old site: they never change an existing member,
// and a new member made from them is created inactive.
// Reads `aicda_old`, writes `aicda_php` (local MySQL 8, port 3308). Safe to run again.
//
// Run:  php AICDA-PHP-local/migrate-management.php --dry-run   |   php AICDA-PHP-local/migrate-management.php

$dry = in_array('--dry-run', $argv, true);
$here = __DIR__;
$photoDir = 'E:/Project ANK/AICDA/AICDA-PHP/public_html/uploads/old-php/Management_Mamber';
$opts = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
$old = new PDO('mysql:host=127.0.0.1;port=3308;dbname=aicda_old;charset=latin1', 'root', 'ank12345', $opts);
$old->exec('SET NAMES binary');
$new = new PDO('mysql:host=127.0.0.1;port=3308;dbname=aicda_php;charset=utf8mb4', 'root', 'ank12345', $opts);

function clean($v, int $max = 191): ?string
{
    if ($v === null) return null;
    $v = (string) $v;
    $v = mb_check_encoding($v, 'UTF-8') ? $v : mb_convert_encoding($v, 'UTF-8', 'Windows-1252');
    $v = trim(preg_replace('/\s+/u', ' ', $v));
    return $v === '' ? null : mb_substr($v, 0, $max);
}
function normalizeMobile(?string $raw): ?string
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
function parseDate(?string $raw): ?string
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
function lettersToNumber(string $l): int { $n = 0; foreach (str_split(strtoupper($l)) as $c) $n = $n * 26 + ord($c) - 64; return $n; }
function designationOf($v): ?string
{
    $d = clean($v, 100);
    return ($d === null || in_array(strtolower($d), ['0', '00', 'test', '-'], true)) ? null : $d;
}

$states = [];
foreach ($old->query('SELECT state_id, state_name FROM state') as $r) $states[(string) $r['state_id']] = clean($r['state_name']);

$existingMembers = [];
foreach ($new->query('SELECT id, memberId, designation FROM Member') as $m) $existingMembers[(int) $m['memberId']] = $m;
$existingPartners = [];
foreach ($new->query('SELECT id, partnerId, designation FROM Partner') as $p) $existingPartners[strtoupper($p['partnerId'])] = $p;

$rows = $old->query('SELECT * FROM management ORDER BY management_id')->fetchAll(PDO::FETCH_ASSOC);
$byKey = []; $dups = [];
foreach ($rows as $r) { // keep the newest row per id
    $k = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) clean($r['mamber_id'], 100)));
    if (isset($byKey[$k])) $dups[] = [$byKey[$k]['management_id'] . ' (dropped)', $r['management_id'] . ' (kept)', $k];
    $byKey[$k] = $r;
}

$plan = ['updateMember' => [], 'updatePartner' => [], 'newMember' => [], 'newMemberText' => [], 'hiddenSkipped' => [], 'partnerUnresolved' => [], 'noDesignation' => 0, 'overwritten' => 0];
foreach ($byKey as $r) {
    $rawId = (string) clean($r['mamber_id'], 100);
    $hidden = trim((string) $r['activeStatus']) === '1';
    $designation = designationOf($r['designation']);

    if (preg_match('/^0*(\d{1,6})$/', preg_replace('/\s+/', '', $rawId), $m) && (int) $m[1] > 0) {
        $num = (int) $m[1];
        if (isset($existingMembers[$num])) {                                   // A
            if ($hidden) { $plan['hiddenSkipped'][] = [$r['management_id'], $rawId, $r['member_name'], $designation]; continue; }
            if ($designation === null) { $plan['noDesignation']++; continue; }
            if ($existingMembers[$num]['designation'] && $existingMembers[$num]['designation'] !== $designation) $plan['overwritten']++;
            $plan['updateMember'][$num] = $designation;
        } else {                                                               // B
            $plan['newMember'][] = [$num, $r, $hidden, $designation];
        }
    } elseif (preg_match('/^0*(\d{1,6})[\s._-]*\(?([A-Za-z]{1,3})\)?$/', $rawId, $m)) {   // D
        $pid = (int) $m[1] . strtoupper($m[2]);
        if (isset($existingPartners[$pid]) && !$hidden && $designation !== null) $plan['updatePartner'][$pid] = $designation;
        else $plan['partnerUnresolved'][] = [$r['management_id'], $rawId, $r['member_name'], isset($existingPartners[$pid]) ? 'hidden/no designation' : 'partner not found'];
    } else {                                                                   // C
        $plan['newMemberText'][] = [700000 + (int) $r['management_id'], $r, $hidden, $designation];
    }
}

$build = function (int $memberId, array $r, bool $hidden, ?string $designation) use ($states, $photoDir): array {
    $file = trim((string) clean($r['member_photo'], 200));
    $photo = ($file !== '' && is_file("$photoDir/$file")) ? '/uploads/old-php/Management_Mamber/' . str_replace(['%28', '%29'], ['(', ')'], rawurlencode($file)) : null;
    $aad = preg_replace('/\D/', '', (string) $r['adharcardno']);
    $created = (!empty($r['Submit_Date']) && !str_starts_with($r['Submit_Date'], '0000')) ? $r['Submit_Date'] . '.000' : gmdate('Y-m-d H:i:s.000', strtotime('2014-01-01 UTC') + (int) $r['management_id']);
    return [
        'memberId' => $memberId, 'name' => clean($r['member_name']) ?? ('Member ' . $memberId), 'father' => clean($r['father_name']),
        'photo' => $photo, 'resAddr' => clean($r['residential_address']), 'mobile' => normalizeMobile(clean($r['mobile_no'], 100)),
        'resTel' => clean($r['residential_telephone'], 100), 'pan' => ($p = strtoupper((string) clean($r['pancardno'], 20))) !== '' ? $p : null,
        'aadhaar' => strlen($aad) === 12 ? $aad : null, 'designation' => $designation, 'company' => clean($r['company_name']),
        'companyAddr' => clean($r['company_address']), 'companyTel' => clean($r['company_tel'], 100),
        'state' => $states[trim((string) $r['state'])] ?? clean($r['state'], 100), 'city' => clean($r['city'], 100),
        'joined' => parseDate($r['date_of_joining']), 'active' => !$hidden, 'created' => $created,
    ];
};

$newRecords = [];
foreach (array_merge($plan['newMember'], $plan['newMemberText']) as [$id, $r, $hidden, $designation]) {
    if (isset($existingMembers[$id])) continue; // already created by an earlier run
    $newRecords[$id] = $build($id, $r, $hidden, $designation);
}

$junk = (int) $new->query("SELECT COUNT(*) FROM Member WHERE designation IN ('0','00','test','-')")->fetchColumn()
      + (int) $new->query("SELECT COUNT(*) FROM Partner WHERE designation IN ('0','00','test','-')")->fetchColumn();

if (!$dry) {
    $new->beginTransaction();
    try {
        $upM = $new->prepare('UPDATE Member SET designation = ?, updatedAt = ? WHERE memberId = ?');
        $upP = $new->prepare('UPDATE Partner SET designation = ?, updatedAt = ? WHERE partnerId = ?');
        $now = gmdate('Y-m-d H:i:s.000');
        foreach ($plan['updateMember'] as $num => $d) $upM->execute([$d, $now, $num]);
        foreach ($plan['updatePartner'] as $pid => $d) $upP->execute([$d, $now, $pid]);
        $new->exec("UPDATE Member SET designation = NULL WHERE designation IN ('0','00','test','-')");
        $new->exec("UPDATE Partner SET designation = NULL WHERE designation IN ('0','00','test','-')");

        $ins = $new->prepare('INSERT INTO Member (memberId, memberName, fatherName, dateOfBirth, specialDates, photo, residentialAddress, mobile, residentialTelephone, panCardNo, designation, companyName, companyAddress, companyTelephone, packetNo, dateOfJoining, validityFrom, validityTo, aadharNo, district, state, city, isActive, statusReason, statusActionBy, statusActionAt, createdAt, updatedAt) VALUES (?,?,?,NULL,?,?,?,?,?,?,?,?,?,?,NULL,?,NULL,NULL,?,NULL,?,?,?,?,?,?,?,?)');
        foreach ($newRecords as $m) {
            $ins->execute([$m['memberId'], $m['name'], $m['father'], '[]', $m['photo'], $m['resAddr'], $m['mobile'], $m['resTel'], $m['pan'], $m['designation'], $m['company'], $m['companyAddr'], $m['companyTel'], $m['joined'], $m['aadhaar'], $m['state'], $m['city'], $m['active'] ? 1 : 0, $m['active'] ? null : 'Marked inactive on the old website (imported)', $m['active'] ? null : 'Data migration', $m['active'] ? null : $m['created'], $m['created'], $m['created']]);
        }
        $new->commit();
    } catch (Throwable $e) {
        $new->rollBack();
        echo 'FAILED, nothing changed: ' . $e->getMessage() . "\n";
        exit(1);
    }
}

@mkdir("$here/migration-reports");
$csv = function (string $file, array $header, array $data) use ($here) {
    $f = fopen("$here/migration-reports/$file", 'w'); fwrite($f, "\xEF\xBB\xBF"); fputcsv($f, $header); foreach ($data as $row) fputcsv($f, $row); fclose($f);
};
$csv('management-hidden-not-applied.csv', ['management_id', 'id', 'name', 'designation'], $plan['hiddenSkipped']);
$csv('management-partner-unresolved.csv', ['management_id', 'id', 'name', 'why'], $plan['partnerUnresolved']);
$csv('management-duplicates.csv', ['dropped', 'kept', 'id'], $dups);
$csv('management-new-members.csv', ['member_id', 'name', 'designation', 'active', 'source'], array_map(fn ($m) => [$m['memberId'], $m['name'], $m['designation'], $m['active'] ? 'yes' : 'no', $m['memberId'] >= 700000 ? 'text id -> reserved' : 'numeric id'], $newRecords));

echo ($dry ? "DRY RUN (nothing changed)\n" : "MIGRATED\n");
$S = [
    'old management rows' => count($rows),
    'duplicate ids (newest kept)' => count($dups),
    'A existing members: designation set' => count($plan['updateMember']) . " (of which replaced a different designation: {$plan['overwritten']})",
    'A no usable designation -> unchanged' => $plan['noDesignation'],
    'A hidden on old site -> not applied' => count($plan['hiddenSkipped']),
    'B new members from numeric ids' => count(array_filter($newRecords, fn ($m) => $m['memberId'] < 700000)),
    'C new members from text ids (700001+)' => count(array_filter($newRecords, fn ($m) => $m['memberId'] >= 700000)),
    'D partners: designation set' => count($plan['updatePartner']),
    'D partner ids not resolved' => count($plan['partnerUnresolved']),
    'junk designations (0/00/test) cleared' => $junk,
    'new members created inactive' => count(array_filter($newRecords, fn ($m) => !$m['active'])),
    'new members with photo' => count(array_filter($newRecords, fn ($m) => $m['photo'])),
];
foreach ($S as $k => $v) printf("  %-42s %s\n", $k, $v);
