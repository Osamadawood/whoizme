<?php
declare(strict_types=1);

if (!function_exists('is_logged_in')) {
    function is_logged_in(): bool {
        return !empty($_SESSION['uid']);
    }
}
if (!function_exists('require_login')) {
    function require_login(): void {
        if (!is_logged_in()) {
            $ret = $_SERVER['REQUEST_URI'] ?? '/dashboard.php';
            if ($ret === '' || $ret[0] !== '/') { $ret = '/dashboard.php'; }
            header('Location: /login.php?return=' . urlencode($ret));
            exit;
        }
    }
}
if (!function_exists('require_guest')) {
    function require_guest(): void {
        if (is_logged_in()) {
            header('Location: /dashboard.php');
            exit;
        }
    }
}
if (!function_exists('logout_user')) {
    function logout_user(): void {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }
}