<?php
// AICDA API — PHP port of the Express backend (AICDA-Backend).
// Every request under /api/ is routed here by api/.htaccess.
declare(strict_types=1);

define('AICDA_API', true);

require __DIR__ . '/src/bootstrap.php';

App::run();
