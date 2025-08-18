<?php
declare(strict_types=1);

// Auth helpers only (no route guard here)
require_once __DIR__ . '/bootstrap.php';

if (!function_exists('current_user_id')) {
    function current_user_id(): int {
        return (int)($_SESSION['uid'] ?? 0);
    }
}

if (!function_exists('is_logged_in')) {
    function is_logged_in(): bool {
        return current_user_id() > 0;
    }
}

if (!function_exists('require_login')) {
    function require_login(): void {
        if (!is_logged_in()) {
            $ret = $_SERVER['REQUEST_URI'] ?? '/';
            // keep it on-site only
            if (!str_starts_with($ret, '/')) { $ret = '/'; }
            header('Location: /login.php?return=' . rawurlencode($ret));
            exit;
        }
    }
}

if (!function_exists('logout_user')) {
    function logout_user(): void {
        // Keep session name consistent with bootstrap
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }
}