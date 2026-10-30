<?php
// Creates the first Super Admin (same as `prisma db seed`). Run from a terminal:
//   php tools/create-admin.php <email> <password> [firstName] [lastName]
// Uses the same config file as the API.
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

define('AICDA_API', true);
require __DIR__ . '/../public_html/api/src/bootstrap.php';

[, $email, $password, $firstName, $lastName] = $argv + [null, null, null, 'Super', 'Admin'];

if (!$email || !$password || strlen($password) < 6) {
    fwrite(STDERR, "Usage: php tools/create-admin.php <email> <password (min 6)> [firstName] [lastName]\n");
    exit(1);
}

$email = strtolower(trim($email));

if (DB::one('SELECT id FROM `Admin` WHERE email = ?', [$email])) {
    echo "Super Admin already exists\n";
    exit(0);
}

$now = Dates::now();
DB::insert('Admin', [
    'id' => Crypto::cuid(),
    'firstName' => $firstName,
    'lastName' => $lastName,
    'email' => $email,
    'role' => 'SUPER_ADMIN',
    'isActive' => true,
    'createdAt' => $now,
    'updatedAt' => $now,
] + Crypto::passwordFields($password));

echo "Super Admin Created\n";
