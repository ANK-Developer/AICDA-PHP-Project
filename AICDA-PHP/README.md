# AICDA — PHP version (for public_html hosting)

Same website and admin panel as the React + Node.js project, with the Express
backend rewritten in PHP. The React UI is used as-is (built to static files), so
it looks and works exactly the same. The PHP API keeps the same URLs, request
fields and JSON responses as `AICDA-Backend`.

```
AICDA-PHP/
├─ public_html/              ← upload the CONTENTS of this folder to the host's public_html
│  ├─ index.html, assets/    ← the built React site
│  ├─ .htaccess              ← React routes, /api routing, 301s from old PHP URLs, security
│  ├─ uploads/               ← member / partner photos and media (scripts blocked)
│  └─ api/                   ← PHP API → https://your-domain/api/v1/...
│     ├─ index.php
│     ├─ config.example.php
│     └─ src/                ← code (not web-accessible)
├─ database/schema.sql       ← only for a brand-new empty database
├─ tools/create-admin.php    ← creates the first Super Admin (CLI)
└─ build-frontend.ps1        ← rebuilds the React site into public_html
```

## Requirements
- PHP 8.1 or newer with `pdo_mysql`, `openssl`, `fileinfo`, `mbstring`
- MySQL 5.7+ / MariaDB 10.3+
- Apache or LiteSpeed with `.htaccess` (mod_rewrite) enabled

## Deploy
1. **Upload** everything inside `public_html/` to the host's `public_html`.
   Make sure the hidden `.htaccess` files are uploaded too.
2. **Config:** copy `public_html/api/config.example.php` to `/home/<user>/aicda-config.php`
   (one level above public_html, recommended) or to `public_html/api/config.php`, then fill it in:
   - `db`: the database. To keep the current data, point it at the database the Node backend
     uses, or export that database and import it into the hosting MySQL.
   - `jwt_secret`, `password_encryption_key`: copy `JWT_SECRET` and
     `PASSWORD_ENCRYPTION_KEY` from `AICDA-Backend/.env`. **Use the exact same values**
     so existing admin passwords (and "reveal password") keep working.
3. **Existing uploads:** copy the files from `AICDA-Backend/src/uploads/` into `public_html/uploads/`.
   The database stores them as `/uploads/<file>`, so they show up unchanged.
4. **New database only:** import `database/schema.sql`, then run
   `php tools/create-admin.php superadmin@example.com <password>` (needs SSH access).
5. Enable SSL, then uncomment the HTTPS redirect at the top of `public_html/.htaccess`.
6. If big photos fail to upload, raise `upload_max_filesize` and `post_max_size` in the PHP settings.

## Updating the website UI
Change the React project (`AICDA/AICDA`) as usual, then run `.\build-frontend.ps1`
in PowerShell and upload `public_html/index.html` and `public_html/assets/`.

## Notes
- **Do not copy anything from the old PHP site** (`aicda.htaccess (1)`). It contains
  backdoors. `.htaccess` returns "410 Gone" for the old malware URLs and 301-redirects
  the old page URLs (e.g. `/About_Us.php` → `/about`).
- Login uses the same JWT token as before (httpOnly cookie + Bearer header).
- `env => 'production'` makes the login cookie HTTPS-only. Use `'development'` for local HTTP testing.
