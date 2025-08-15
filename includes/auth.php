<?php
declare(strict_types=1);

/**
 * Auth / Guard
 * - يفترض إن bootstrap.php عمل session_start() وعرّف BASE_URL وredirect() و current_user_id()
 * - يدعم SKIP_AUTH_GUARD لتعطيل الحارس في صفحات معينة (health.php, _selfcheck.php …إلخ)
 */

// لو الصفحة عرّفت SKIP_AUTH_GUARD خلاص نخرج
if (defined('SKIP_AUTH_GUARD') && SKIP_AUTH_GUARD) {
    return;
}

// حمايات خفيفة لو الدوال مش متاحة (في حالات نادرة)
if (!function_exists('current_user_id')) {
    function current_user_id(): int {
        return (int)($_SESSION['uid'] ?? 0);
    }
}
if (!function_exists('redirect')) {
    function redirect(string $to): never {
        if (str_starts_with($to, 'http')) {
            header('Location: '.$to);
        } else {
            $base = rtrim((defined('BASE_URL') ? BASE_URL : ''), '/');
            header('Location: '.$base.'/'.ltrim($to, '/'));
        }
        exit;
    }
}
if (!defined('BASE_URL')) {
    define('BASE_URL', '/');
}

// حدّد اسم الملف الحالي
$CURRENT = basename($_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? '');

// صفحات عامة (لا تحتاج تسجيل دخول)
$PUBLIC_PAGES = [
    '', // في حال السيرفر بيرجع اسم فارغ للـ index
    'index.php',
    'login.php',
    'register.php',
    'forgot.php',
    'reset.php',
    'privacy.php',
    'terms.php',
    'help.php',
    'health.php',
    '_selfcheck.php',
    'r.php',   // redirector
    'u.php',   // public profile entry
];

// حالة المستخدم
$isLoggedIn = current_user_id() > 0;
$isPublic   = in_array($CURRENT, $PUBLIC_PAGES, true);

// 1) لو الصفحة ليست عامة والمستخدم غير مسجل → ودّيه لصفحة الدخول
if (!$isPublic && !$isLoggedIn) {
    // احتفظ بالعودة بعد اللوج إن إن حبيت (اختياري)
    $return = rawurlencode($_SERVER['REQUEST_URI'] ?? '/');
    redirect('/login.php?return='.$return);
}

// 2) لو هو بالفعل مسجل ودخل صفحات login/register/forgot/reset رجّعه للداشبورد
if ($isLoggedIn && in_array($CURRENT, ['login.php', 'register.php', 'forgot.php', 'reset.php'], true)) {
    redirect('/dashboard.php');
}

/**
 * helper اختياري: استدعِه داخل الصفحات الخاصة لو حبيت
 */
if (!function_exists('require_login')) {
    function require_login(): void {
        if (current_user_id() <= 0) {
            $return = rawurlencode($_SERVER['REQUEST_URI'] ?? '/');
            redirect('/login.php?return='.$return);
        }
    }
}

/**
 * helper اختياري: دالة تسجيل الدخول البسيطة
 * - هنا بس مثال: انت اربطها بجدول users حسب لوجيكك
 */
if (!function_exists('auth_login')) {
    function auth_login(int $uid): void {
        $_SESSION['uid'] = $uid;
        session_regenerate_id(true);
    }
}
if (!function_exists('auth_logout')) {
    function auth_logout(): void {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }
}