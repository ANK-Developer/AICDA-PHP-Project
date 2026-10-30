<?php
// Local development router for PHP's built-in server — does what
// public_html/.htaccess does on Apache. Start with start-local.bat.
$public = 'E:/Project ANK/AICDA/AICDA-PHP/public_html';
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// API → api/index.php
if (preg_match('#^/api(/|$)#', $path)) {
    require $public . '/api/index.php';
    return true;
}

// Real files (assets, uploads, favicon) are served as they are.
$file = realpath($public . $path);
if ($path !== '/' && $file && str_starts_with(str_replace('\\', '/', $file), $public) && is_file($file)) {
    if (str_ends_with($file, '.php') || basename($file) === '.htaccess') {
        http_response_code(403);
        return true;
    }
    return false;
}

// React pages → index.html
header('Content-Type: text/html; charset=utf-8');
readfile($public . '/index.html');
return true;
