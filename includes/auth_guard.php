<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
require_once __DIR__.'/helpers.php';
require_once __DIR__.'/auth.php';

if (defined('SKIP_AUTH_GUARD') && SKIP_AUTH_GUARD === true) {
    return;
}

if (!function_exists('auth_is_logged_in') || !auth_is_logged_in()) {
    $here = $_SERVER['REQUEST_URI'] ?? '/dashboard';
    $here = preg_replace('~\.php($|\?)~i', '$1', $here);
    // only a single return param; avoid nested encodings by not re-appending if already present
    $qpos = strpos($here, '?');
    $cleanHere = $qpos === false ? $here : substr($here, 0, $qpos);
    $loginUrl = wz_url('/login', ['return' => $cleanHere]);
    header('Location: ' . $loginUrl, true, 302);
    exit();
}