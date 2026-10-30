<?php
// Copy this file to ONE of these places and fill in the values:
//   1. /home/<user>/aicda-config.php   (one level above public_html — recommended)
//   2. public_html/api/config.php      (blocked from the web by api/.htaccess)
//
// The values are the same ones the Node backend keeps in AICDA-Backend/.env.

return [
    // 'production' → login cookie is Secure (HTTPS only). Use 'development' locally.
    'env' => 'production',

    // MySQL — the same database the Node/Prisma backend uses.
    'db' => [
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'aicda',
        'user' => '',
        'pass' => '',
        // Remote MySQL with TLS (e.g. Aiven): path to the CA certificate file.
        'ssl_ca' => null,
        'ssl_verify' => true,
    ],

    // JWT_SECRET from the Node .env (keep it the same so existing logins stay valid).
    'jwt_secret' => '',

    // PASSWORD_ENCRYPTION_KEY from the Node .env (64 hex characters). Must be
    // identical, or "reveal password" cannot decrypt existing admins.
    'password_encryption_key' => '',

    // Frontend_URL — only needed if the site is ever served from another domain.
    'frontend_url' => '',

    // Where uploads are stored. Default: public_html/uploads (served at /uploads).
    'upload_dir' => null,
];
