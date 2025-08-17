<?php
declare(strict_types=1);

/**
 * Auth utilities – safe to include alongside bootstrap without redeclare errors.
 * Relies on includes/bootstrap.php for session setup and constants.
 */

// Provide a single source of truth for current user id.
// If bootstrap already declared current_user_id(), we do NOT redeclare it.
if (!function_exists('current_user_id')) {
    function current_user_id(): int {
        // Accept both new and legacy session keys
        return (int)($_SESSION['user_id'] ?? $_SESSION['uid'] ?? 0);
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
            $current = $_SERVER['REQUEST_URI'] ?? '/';
            header('Location: /login.php?return=' . rawurlencode($current), true, 302);
            exit;
        }
    }
}

if (!function_exists('redirect_if_logged_in')) {
    function redirect_if_logged_in(string $to = '/dashboard.php'): void {
        if (is_logged_in()) {
            header('Location: ' . $to, true, 302);
            exit;
        }
    }
}