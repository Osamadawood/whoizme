<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
require_once __DIR__.'/auth.php';

if (defined('SKIP_AUTH_GUARD') && SKIP_AUTH_GUARD === true) {
    return;
}

if (!function_exists('auth_is_logged_in') || !auth_is_logged_in()) {
    $ret = rawurlencode($_SERVER['REQUEST_URI'] ?? '/dashboard.php');
    header('Location: /login.php?return=' . $ret);
    exit();
}