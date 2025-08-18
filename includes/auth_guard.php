<?php
declare(strict_types=1);

/**
 * حارس الصفحات المحمية.
 * لو الصفحة عامة لازم تعرف PAGE_PUBLIC قبل تضمين bootstrap.
 * هذا الحارس يدعم أيضًا PUBLIC_PAGE لضمان التوافق مع الكود القديم.
 */

if (defined('PAGE_PUBLIC') || defined('PUBLIC_PAGE')) {
    // صفحة عامة: لا شيء
    return;
}

// نفّذ حماية الدخول
if (!function_exists('require_login')) {
    // نضمن وجود الدالة من bootstrap/includes/auth.php
    function require_login(): void {
        $current = $_SERVER['REQUEST_URI'] ?? '/';
        header('Location: /login.php?return=' . rawurlencode($current), true, 302);
        exit;
    }
}

require_login();