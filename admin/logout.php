<?php
/* ==========================================================================
   ADMIN LOGOUT (admin/logout.php)
   Only accepts a signed POST with a valid CSRF token, then bounces back to
   the login screen. Signing out here never touches a normal user session.
   ========================================================================== */
require_once __DIR__ . '/includes/boot.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && af_csrf_ok($_POST['csrf'] ?? '')) {
    af_admin_logout();
}
af_admin_redirect('admin/login.php');
