<?php
declare(strict_types=1);

if (!defined('AICDA_API')) {
    http_response_code(403);
    exit;
}

// Everything is stored and compared in UTC (the same way Prisma stores dates);
// IST is only used explicitly where the business rules need it.
date_default_timezone_set('UTC');
ini_set('display_errors', '0');

require __DIR__ . '/Config.php';
require __DIR__ . '/Http.php';
require __DIR__ . '/Db.php';
require __DIR__ . '/Dates.php';
require __DIR__ . '/Crypto.php';
require __DIR__ . '/Validation.php';
require __DIR__ . '/Membership.php';
require __DIR__ . '/SpecialDates.php';
require __DIR__ . '/Upload.php';
require __DIR__ . '/Auth.php';
require __DIR__ . '/controllers/AuthController.php';
require __DIR__ . '/controllers/DashboardController.php';
require __DIR__ . '/controllers/GalleryController.php';
require __DIR__ . '/controllers/MemberController.php';
require __DIR__ . '/controllers/PartnerController.php';
require __DIR__ . '/controllers/EnquiryController.php';
require __DIR__ . '/controllers/ImportantDateController.php';
require __DIR__ . '/controllers/SuperAdminController.php';
require __DIR__ . '/routes.php';
require __DIR__ . '/App.php';
