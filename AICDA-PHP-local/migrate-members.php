<?php
// One-time migration: OLD site `manage_directory` -> new Member + Partner.
//   Reads the scratch database `aicda_old`, writes to `aicda_php` (local MySQL 8, port 3308).
//   1. Backs up the current Member/Partner/renewal rows of aicda_php to a JSON file.
//   2. Deletes those rows (admins, gallery and enquiries are NOT touched).
//   3. Inserts the old members and partners.
//   Everything is written in ONE transaction. Rejected / changed values go to migration-reports/.
//
// Run:  php AICDA-PHP-local/migrate-members.php --dry-run     (nothing is changed)
//       php AICDA-PHP-local/migrate-members.php               (for real)

$dry = in_array('--dry-run', $argv, true);
$here = __DIR__;
$uploads = 'E:/Project ANK/AICDA/AICDA-PHP/public_html/uploads/old-php/Member_Photo';
$opts = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];

$old = new PDO('mysql:host=127.0.0.1;port=3308;dbname=aicda_old;charset=latin1', 'root', 'ank12345', $opts);
$old->exec('SET NAMES binary'); // old tables are latin1 but hold UTF-8 text
$new = new PDO('mysql:host=127.0.0.1;port=3308;dbname=aicda_php;charset=utf8mb4', 'root', 'ank12345', $opts);

// ---------- helpers ----------
function clean($v, int $max = 191): ?string
{
    if ($v === null) return null;
    $v = (string) $v;
    $v = mb_check_encoding($v, 'UTF-8') ? $v : mb_convert_encoding($v, 'UTF-8', 'Windows-1252');
    $v = trim(preg_replace('/\s+/u', ' ', $v));
    return $v === '' ? null : mb_substr($v, 0, $max);
}

function normalizeMobile(?string $raw): array // [value|null, changed?]
{
    if ($raw === null) return [null, false];
    $candidates = array_filter(preg_split('/[,\/;|]+|\s{2,}|\s+(?=\+?\d{10,})/', $raw)) ?: [$raw];
    array_unshift($candidates, $raw);
    foreach ($candidates as $c) {
        $d = preg_replace('/\D/', '', $c);
        if (strlen($d) === 12 && str_starts_with($d, '91')) $d = substr($d, 2);
        if (strlen($d) === 11 && $d[0] === '0') $d = substr($d, 1);
        if (strlen($d) === 10) return [$d, $d !== $raw];
    }
    return [null, true];
}

function parseDate(?string $raw): array // [Y-m-d H:i:s.000|null, status]
{
    $s = trim((string) $raw);
    if ($s === '' || str_starts_with($s, '0000')) return [null, 'empty'];
    if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})/', $s, $m)) {
        return checkdate((int) $m[2], (int) $m[3], (int) $m[1]) ? [sprintf('%04d-%02d-%02d 00:00:00.000', $m[1], $m[2], $m[3]), 'ok'] : [null, 'invalid'];
    }
    if (preg_match('/^(\d{1,2})[\/.-](\d{1,2})[\/.-](\d{2,4})$/', $s, $m)) { // day first (India)
        $y = (int) $m[3];
        if ($y < 100) $y += $y <= 26 ? 2000 : 1900;
        return checkdate((int) $m[2], (int) $m[1], $y) ? [sprintf('%04d-%02d-%02d 00:00:00.000', $y, $m[2], $m[1]), 'ok'] : [null, 'invalid'];
    }
    return [null, 'unparsed']; // e.g. only a year like "2007"
}

function lettersToNumber(string $letters): int
{
    $n = 0;
    foreach (str_split(strtoupper($letters)) as $ch) $n = $n * 26 + (ord($ch) - 64);
    return $n;
}

// ---------- read old data ----------
$states = [];
foreach ($old->query('SELECT state_id, state_name FROM state') as $r) $states[(string) $r['state_id']] = clean($r['state_name']);

$rows = $old->query('SELECT * FROM manage_directory ORDER BY manage_directory_id')->fetchAll(PDO::FETCH_ASSOC);

$members = [];   // memberId(int) => record
$partners = [];  // "memberId|number" => record
$report = ['skipped' => [], 'duplicates' => [], 'mobile' => [], 'aadhaar' => [], 'dates' => [], 'noMemberForPartner' => [], 'photoMissing' => 0, 'noPhoto' => 0];

foreach ($rows as $r) {
    $rawId = trim((string) clean($r['mamber_id'], 50));
    $oldId = (int) $r['manage_directory_id'];

    if (preg_match('/^\d{1,6}$/', $rawId) && (int) $rawId > 0) {
        $kind = 'member';
        $memberId = (int) $rawId;
        $letters = null;
    } elseif (preg_match('/^(\d{1,6})[\s._-]*([A-Za-z]{1,3})$/', $rawId, $m)) {
        $kind = 'partner';
        $memberId = (int) $m[1];
        $letters = strtoupper($m[2]);
    } else {
        $report['skipped'][] = [$oldId, $rawId, 'member id not understood'];
        continue;
    }

    [$mobile, $mobileChanged] = normalizeMobile(clean($r['mobile_no'], 100));
    if ($mobileChanged) $report['mobile'][] = [$oldId, $rawId, $r['mobile_no'], $mobile];

    $aadhaarDigits = preg_replace('/\D/', '', (string) $r['adhar_card_no']);
    $aadhaar = strlen($aadhaarDigits) === 12 ? $aadhaarDigits : null;
    if (trim((string) $r['adhar_card_no']) !== '' && $aadhaar === null) $report['aadhaar'][] = [$oldId, $rawId, $r['adhar_card_no']];

    [$joined, $joinStatus] = parseDate($r['date_of_joining']);
    if (!in_array($joinStatus, ['ok', 'empty'], true)) $report['dates'][] = [$oldId, $rawId, $r['date_of_joining'], $joinStatus];

    $photoFile = trim((string) clean($r['member_photo'], 200));
    $photo = null;
    if ($photoFile === '') {
        $report['noPhoto']++;
    } elseif (is_file("$uploads/$photoFile")) {
        $photo = '/uploads/old-php/Member_Photo/' . str_replace(['%28', '%29'], ['(', ')'], rawurlencode($photoFile));
    } else {
        $report['photoMissing']++;
    }

    $created = (!empty($r['Submit_Date']) && !str_starts_with($r['Submit_Date'], '0000'))
        ? $r['Submit_Date'] . '.000'
        : gmdate('Y-m-d H:i:s.000', strtotime('2014-01-01 00:00:00 UTC') + $oldId);

    $active = (int) $r['activeStatus'] === 0; // old site: 0 = shown / active
    $stateName = $states[trim((string) $r['state'])] ?? clean($r['state'], 100);

    $rec = [
        'name' => clean($r['member_name']),
        'fatherName' => clean($r['father_name']),
        'photo' => $photo,
        'residentialAddress' => clean($r['residential_address']),
        'mobile' => $mobile,
        'residentialTelephone' => clean($r['residential_telephone'], 100),
        'panCardNo' => ($p = strtoupper((string) clean($r['pan_card_no'], 20))) !== '' ? $p : null,
        'aadharNo' => $aadhaar,
        'designation' => clean($r['designation'], 100),
        'companyName' => clean($r['company_name']),
        'companyAddress' => clean($r['company_address']),
        'companyTelephone' => clean($r['company_tel'], 100),
        'state' => $stateName,
        'city' => clean($r['city'], 100),
        'dateOfJoining' => $joined,
        'isActive' => $active,
        'createdAt' => $created,
        'oldId' => $oldId,
        'rawId' => $rawId,
    ];

    if ($rec['name'] === null) {
        $report['skipped'][] = [$oldId, $rawId, 'no name'];
        continue;
    }

    if ($kind === 'member') {
        if (isset($members[$memberId])) $report['duplicates'][] = ['member', $memberId, $members[$memberId]['oldId'] . ' (dropped)', $oldId . ' (kept)'];
        $members[$memberId] = $rec; // the newest row wins
    } else {
        $key = $memberId . '|' . lettersToNumber($letters);
        if (isset($partners[$key])) $report['duplicates'][] = ['partner', $memberId . $letters, $partners[$key]['oldId'] . ' (dropped)', $oldId . ' (kept)'];
        $rec['memberId'] = $memberId;
        $rec['letters'] = $letters;
        $partners[$key] = $rec;
    }
}

// Partners need their member.
foreach ($partners as $key => $p) {
    if (!isset($members[$p['memberId']])) {
        $report['noMemberForPartner'][] = [$p['oldId'], $p['rawId'], $p['name']];
        unset($partners[$key]);
    }
}

// ---------- write ----------
$summary = [
    'old rows read' => count($rows),
    'members to insert' => count($members),
    'partners to insert' => count($partners),
    'members inactive (old activeStatus=1)' => count(array_filter($members, fn ($m) => !$m['isActive'])),
    'members with photo' => count(array_filter($members, fn ($m) => $m['photo'])),
    'photos missing on disk' => $report['photoMissing'],
    'no photo recorded' => $report['noPhoto'],
    'rows skipped (id/name)' => count($report['skipped']),
    'duplicate ids (newest kept)' => count($report['duplicates']),
    'partners with no member -> left out' => count($report['noMemberForPartner']),
    'mobile numbers cleaned or emptied' => count($report['mobile']),
    'aadhaar not 12 digits -> emptied' => count($report['aadhaar']),
    'joining dates not understood -> empty' => count($report['dates']),
];

@mkdir("$here/migration-reports");
$csv = function (string $file, array $header, array $data) use ($here) {
    $f = fopen("$here/migration-reports/$file", 'w');
    fwrite($f, "\xEF\xBB\xBF");
    fputcsv($f, $header);
    foreach ($data as $row) fputcsv($f, $row);
    fclose($f);
};

if (!$dry) {
    // 1. backup of what is about to be deleted
    $backup = [];
    foreach (['Member', 'Partner', 'MemberRenewal', 'PartnerRenewal'] as $t) $backup[$t] = $new->query("SELECT * FROM `$t`")->fetchAll(PDO::FETCH_ASSOC);
    $backupFile = "$here/migration-reports/backup-before-members-" . date('Ymd-His') . '.json';
    file_put_contents($backupFile, json_encode($backup, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    echo 'Backup written: ' . basename($backupFile) . ' (' . implode(', ', array_map(fn ($t) => count($backup[$t]) . " $t", array_keys($backup))) . ")\n";

    // 2 + 3. delete and insert in one transaction
    $new->beginTransaction();
    try {
        foreach (['PartnerRenewal', 'MemberRenewal', 'Partner', 'Member'] as $t) $new->exec("DELETE FROM `$t`");

        $insM = $new->prepare('INSERT INTO Member (memberId, memberName, fatherName, dateOfBirth, specialDates, photo, residentialAddress, mobile, residentialTelephone, panCardNo, designation, companyName, companyAddress, companyTelephone, packetNo, dateOfJoining, validityFrom, validityTo, aadharNo, district, state, city, isActive, statusReason, statusActionBy, statusActionAt, createdAt, updatedAt) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,NULL,?,NULL,NULL,?,NULL,?,?,?,?,?,?,?,?)');
        $insP = $new->prepare('INSERT INTO Partner (partnerId, partnerNumber, memberId, partnerName, fatherName, dateOfBirth, specialDates, photo, residentialAddress, mobile, residentialTelephone, panCardNo, aadharNo, designation, companyName, companyAddress, companyTelephone, packetNo, state, district, city, dateOfJoining, validityFrom, validityTo, isActive, statusReason, statusActionBy, statusActionAt, createdAt, updatedAt) VALUES (?,?,?,?,?,NULL,?,?,?,?,?,?,?,?,?,?,?,NULL,?,NULL,?,?,NULL,NULL,?,?,?,?,?,?)');
        $reason = 'Marked inactive on the old website (imported)';
        $by = 'Data migration';

        $dbId = [];
        foreach ($members as $memberId => $m) {
            $insM->execute([
                $memberId, $m['name'], $m['fatherName'], null, '[]', $m['photo'], $m['residentialAddress'], $m['mobile'], $m['residentialTelephone'],
                $m['panCardNo'], $m['designation'], $m['companyName'], $m['companyAddress'], $m['companyTelephone'],
                $m['dateOfJoining'], $m['aadharNo'], $m['state'], $m['city'],
                $m['isActive'] ? 1 : 0, $m['isActive'] ? null : $reason, $m['isActive'] ? null : $by, $m['isActive'] ? null : $m['createdAt'],
                $m['createdAt'], $m['createdAt'],
            ]);
            $dbId[$memberId] = (int) $new->lastInsertId();
        }
        // dateOfBirth is not in the old data, so it is bound as NULL (4th value above).

        foreach ($partners as $p) {
            $number = lettersToNumber($p['letters']);
            $insP->execute([
                $p['memberId'] . $p['letters'], $number, $dbId[$p['memberId']], $p['name'], $p['fatherName'], '[]', $p['photo'],
                $p['residentialAddress'], $p['mobile'], $p['residentialTelephone'], $p['panCardNo'], $p['aadharNo'],
                $p['designation'], $p['companyName'], $p['companyAddress'], $p['companyTelephone'],
                $p['state'], $p['city'], $p['dateOfJoining'],
                $p['isActive'] ? 1 : 0, $p['isActive'] ? null : $reason, $p['isActive'] ? null : $by, $p['isActive'] ? null : $p['createdAt'],
                $p['createdAt'], $p['createdAt'],
            ]);
        }
        $new->commit();
    } catch (Throwable $e) {
        $new->rollBack();
        echo "FAILED, nothing changed: " . $e->getMessage() . "\n";
        exit(1);
    }
}

$csv('skipped-rows.csv', ['old_id', 'member_id_text', 'reason'], $report['skipped']);
$csv('duplicate-ids.csv', ['type', 'id', 'old_row_dropped', 'old_row_kept'], $report['duplicates']);
$csv('partners-without-member.csv', ['old_id', 'partner_id_text', 'name'], $report['noMemberForPartner']);
$csv('mobile-changed.csv', ['old_id', 'member_id_text', 'original', 'new_value_or_empty'], $report['mobile']);
$csv('aadhaar-emptied.csv', ['old_id', 'member_id_text', 'original'], $report['aadhaar']);
$csv('dates-not-understood.csv', ['old_id', 'member_id_text', 'original', 'why'], $report['dates']);

echo ($dry ? "DRY RUN (nothing changed)\n" : "MIGRATED\n");
foreach ($summary as $k => $v) printf("  %-42s %s\n", $k, $v);
echo "  reports: AICDA-PHP-local\\migration-reports\\*.csv\n";
