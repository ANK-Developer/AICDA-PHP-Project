<?php
declare(strict_types=1);

// Same routes, order and guards as AICDA-Backend/src/routes (base /api/v1).
function build_router(): Router
{
    $router = new Router();
    $auth = [Auth::class, 'required'];
    $superAdmin = Auth::role('SUPER_ADMIN');

    // Auth
    $router->add('POST', '/auth/login', [AuthController::class, 'login']);
    $router->add('POST', '/auth/logout', $auth, [AuthController::class, 'logout']);
    $router->add('GET', '/auth/me', $auth, [AuthController::class, 'me']);
    $router->add('POST', '/auth/change-password', $auth, [AuthController::class, 'changePassword']);

    // Dashboard (public — used by the home page)
    $router->add('GET', '/dashboard', [DashboardController::class, 'index']);

    // Gallery
    $router->add('POST', '/gallery/upload', $auth, $superAdmin, Upload::single('image'), [GalleryController::class, 'upload']);
    $router->add('GET', '/gallery', [GalleryController::class, 'index']);
    $router->add('GET', '/gallery/admin', $auth, $superAdmin, [GalleryController::class, 'admin']);
    $router->add('PATCH', '/gallery/banners/reorder', $auth, $superAdmin, [GalleryController::class, 'reorderBanners']);
    $router->add('GET', '/gallery/:id', [GalleryController::class, 'show']);
    $router->add('PATCH', '/gallery/:id/visibility', $auth, $superAdmin, [GalleryController::class, 'visibility']);
    $router->add('PATCH', '/gallery/:id', $auth, $superAdmin, Upload::single('image'), [GalleryController::class, 'update']);
    $router->add('DELETE', '/gallery/:id', $auth, $superAdmin, [GalleryController::class, 'destroy']);

    // Members
    $validateMember = [Rules::class, 'member'];
    $specialDate = Schema::middleware(Rules::specialDate());
    $router->add('POST', '/members', $auth, $superAdmin, Upload::single('photo'), $validateMember, [MemberController::class, 'create']);
    $router->add('GET', '/members', $auth, $superAdmin, [MemberController::class, 'index']);
    $router->add('GET', '/management', [MemberController::class, 'publicPage']);   // public Management page
    $router->add('GET', '/members/public', [MemberController::class, 'publicIndex']);
    $router->add('GET', '/members/public/:id', [MemberController::class, 'publicShow']);
    $router->add('GET', '/members/:id', $auth, $superAdmin, [MemberController::class, 'show']);
    $router->add('PUT', '/members/:id', $auth, $superAdmin, Upload::single('photo'), $validateMember, [MemberController::class, 'update']);
    $router->add('PATCH', '/members/:id/status', $auth, $superAdmin, [MemberController::class, 'setStatus']);
    $router->add('PATCH', '/members/:id/visibility', $auth, $superAdmin, [MemberController::class, 'setVisibility']);
    $router->add('PATCH', '/members/:id/renew', $auth, $superAdmin, [Rules::class, 'memberRenew'], [MemberController::class, 'renew']);
    $router->add('POST', '/members/:id/special-dates', $auth, $superAdmin, $specialDate, [MemberController::class, 'addSpecialDate']);
    $router->add('PUT', '/members/:id/special-dates/:dateId', $auth, $superAdmin, $specialDate, [MemberController::class, 'updateSpecialDate']);
    $router->add('DELETE', '/members/:id/special-dates/:dateId', $auth, $superAdmin, [MemberController::class, 'deleteSpecialDate']);
    $router->add('DELETE', '/members/:id', $auth, $superAdmin, [MemberController::class, 'destroy']);

    // Enquiries
    $router->add('POST', '/enquiries', Schema::middleware(Rules::enquiry()), [EnquiryController::class, 'submit']);
    $router->add('GET', '/enquiries', $auth, $superAdmin, [EnquiryController::class, 'index']);
    $router->add('PATCH', '/enquiries/:id/status', $auth, $superAdmin, Schema::middleware(Rules::enquiryStatus()), [EnquiryController::class, 'changeStatus']);

    // Super admins
    $router->add('GET', '/super-admin', $auth, $superAdmin, [SuperAdminController::class, 'index']);
    $router->add('GET', '/super-admin/:id', $auth, $superAdmin, [SuperAdminController::class, 'show']);
    $router->add('POST', '/super-admin/create', $auth, $superAdmin, Schema::middleware(Rules::createSuperAdmin()), [SuperAdminController::class, 'create']);
    $router->add('PATCH', '/super-admin/:id', $auth, $superAdmin, Schema::middleware(Rules::updateSuperAdmin()), [SuperAdminController::class, 'update']);
    $router->add('PATCH', '/super-admin/:id/status', $auth, $superAdmin, [SuperAdminController::class, 'toggleStatus']);
    $router->add('DELETE', '/super-admin/:id', $auth, $superAdmin, [SuperAdminController::class, 'destroy']);
    $router->add('POST', '/super-admin/:id/reset-password', $auth, $superAdmin, Schema::middleware(Rules::resetPassword()), [SuperAdminController::class, 'resetPassword']);
    $router->add('POST', '/super-admin/:id/reveal-password', $auth, $superAdmin, [SuperAdminController::class, 'revealPassword']);

    // Important dates
    $router->add('GET', '/important-dates', $auth, $superAdmin, [ImportantDateController::class, 'index']);

    // Partners
    $partner = Schema::middleware(Rules::createPartner());
    $partnerUpdate = Schema::middleware(Rules::createPartner(true));
    $router->add('POST', '/partners', $auth, $superAdmin, Upload::single('photo'), $partner, [PartnerController::class, 'create']);
    $router->add('GET', '/partners', $auth, $superAdmin, [PartnerController::class, 'index']);
    $router->add('GET', '/partners/public', [PartnerController::class, 'publicIndex']);
    $router->add('GET', '/partners/public/:id', [PartnerController::class, 'publicShow']);
    $router->add('GET', '/partners/member/:memberId', $auth, $superAdmin, [PartnerController::class, 'byMember']);
    $router->add('GET', '/partners/:partnerId', $auth, $superAdmin, [PartnerController::class, 'show']);
    $router->add('PATCH', '/partners/:partnerId', $auth, $superAdmin, Upload::single('photo'), $partnerUpdate, [PartnerController::class, 'update']);
    $router->add('PATCH', '/partners/:partnerId/status', $auth, $superAdmin, [PartnerController::class, 'setStatus']);
    $router->add('PATCH', '/partners/:partnerId/visibility', $auth, $superAdmin, [PartnerController::class, 'setVisibility']);
    $router->add('PATCH', '/partners/:partnerId/renew', $auth, $superAdmin, Schema::middleware(Rules::renewPartner()), [PartnerController::class, 'renew']);
    $router->add('POST', '/partners/:partnerId/special-dates', $auth, $superAdmin, $specialDate, [PartnerController::class, 'addSpecialDate']);
    $router->add('PUT', '/partners/:partnerId/special-dates/:dateId', $auth, $superAdmin, $specialDate, [PartnerController::class, 'updateSpecialDate']);
    $router->add('DELETE', '/partners/:partnerId/special-dates/:dateId', $auth, $superAdmin, [PartnerController::class, 'deleteSpecialDate']);
    $router->add('DELETE', '/partners/:partnerId', $auth, $superAdmin, [PartnerController::class, 'destroy']);

    return $router;
}
