<?php
// One-time migration of the OLD PHP site's gallery tables into the new Gallery table.
//   association_event -> ASSOCIATION        political_event -> POLITICAL_ACHIEVEMENT
//   image (Js_Nayol)  -> IMAGE              letter          -> LETTER
// Reads the scratch database `aicda_old`, writes to `aicda_php` (local MySQL 8, port 3308).
// Safe to run again: each row gets a fixed id ("old-<table>-<oldId>"), so nothing is duplicated.
// Existing rows in Gallery are never touched.
//
// Run:  php AICDA-PHP-local/migrate-gallery.php [--dry-run]

$dry = in_array('--dry-run', $argv, true);
$uploads = 'E:/Project ANK/AICDA/AICDA-PHP/public_html/uploads/old-php';
$opts = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];

$old = new PDO('mysql:host=127.0.0.1;port=3308;dbname=aicda_old;charset=latin1', 'root', 'ank12345', $opts);
$old->exec("SET NAMES binary"); // raw bytes — the old tables are latin1 but hold UTF-8 text
$new = new PDO('mysql:host=127.0.0.1;port=3308;dbname=aicda_php;charset=utf8mb4', 'root', 'ank12345', $opts);

$sources = [
    // old table, id col, title col, file col, date col, old folder, new category
    ['association_event', 'Association_Id', 'Association_Title', 'Association_img', 'Submit_Date', 'Association_Event', 'ASSOCIATION'],
    ['political_event',   'political_id',   'political_title',   'political_image', 'Submit_Date', 'Political_Event',   'POLITICAL_ACHIEVEMENT'],
    ['image',             'id',             'caption',           'gallary_img',     'submit_Date', 'Js_Nayol',          'IMAGE'],
    ['letter',            'letter_id',      'title',             'letter_image',    'Submit_Date', 'Letter',            'LETTER'],
];

// Text in the old tables is UTF-8 stored in latin1 columns; fall back to Windows-1252 if not valid UTF-8.
function clean(string $s): string
{
    $s = mb_check_encoding($s, 'UTF-8') ? $s : mb_convert_encoding($s, 'UTF-8', 'Windows-1252');
    return trim(preg_replace('/\s+/u', ' ', $s));
}

$insert = $new->prepare(
    "INSERT IGNORE INTO Gallery (id, title, description, imageUrl, category, resourceType, isActive, sortOrder, createdAt, updatedAt)
     VALUES (?, ?, NULL, ?, ?, 'IMAGE', 1, 0, ?, ?)"
);

$report = [];
$new->beginTransaction();
foreach ($sources as [$table, $idCol, $titleCol, $fileCol, $dateCol, $folder, $category]) {
    $r = ['rows' => 0, 'added' => 0, 'already' => 0, 'skipped_not_image' => [], 'skipped_missing' => [], 'zero_dates' => 0];

    foreach ($old->query("SELECT `$idCol` id, `$titleCol` title, `$fileCol` file, `$dateCol` d FROM `$table` ORDER BY `$idCol`") as $row) {
        $r['rows']++;
        $file = trim($row['file']);

        // Only real image file names (this drops the attacker's summon.php / t6.php / py.php / ssl.php rows).
        if (!preg_match('/^[A-Za-z0-9._() -]+\.(jpe?g|jfif|png|gif|webp)$/i', $file) || str_contains($file, '..')) {
            $r['skipped_not_image'][] = $file;
            continue;
        }
        if (!is_file("$uploads/$folder/$file")) {
            $r['skipped_missing'][] = $file;
            continue;
        }

        // The old tables use 0000-00-00 when the date is unknown: give those a fixed early date
        // that keeps the old order (a higher old id stays newer).
        if (!$row['d'] || str_starts_with($row['d'], '0000')) {
            $r['zero_dates']++;
            $created = gmdate('Y-m-d H:i:s.000', strtotime('2014-01-01 00:00:00 UTC') + (int) $row['id']);
        } else {
            $created = $row['d'] . '.000';
        }

        $title = clean($row['title']);
        $title = $title !== '' ? mb_substr($title, 0, 191) : pathinfo($file, PATHINFO_FILENAME);
        $url = "/uploads/old-php/$folder/" . rawurlencode($file);
        $url = str_replace(['%28', '%29'], ['(', ')'], $url);

        if ($dry) { $r['added']++; continue; }
        $insert->execute(["old-$table-{$row['id']}", $title, $url, $category, $created, $created]);
        $insert->rowCount() ? $r['added']++ : $r['already']++;
    }
    $report[$table] = $r;
}
$dry ? $new->rollBack() : $new->commit();

echo $dry ? "DRY RUN (nothing written)\n" : "MIGRATED\n";
foreach ($report as $t => $r) {
    printf("%-18s old rows %-4d added %-4d already there %-3d zero-date %-3d\n", $t, $r['rows'], $r['added'], $r['already'], $r['zero_dates']);
    if ($r['skipped_not_image']) echo "    skipped (not an image): " . implode(', ', $r['skipped_not_image']) . "\n";
    if ($r['skipped_missing'])   echo "    skipped (photo file missing): " . implode(', ', $r['skipped_missing']) . "\n";
}
