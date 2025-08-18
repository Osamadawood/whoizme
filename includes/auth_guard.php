<?php
declare(strict_types=1);

/**
 * Central auth route-guard for Whoizme.
 * - Honors PUBLIC_PAGE on any script.
 * - Whitelists landing/auth/static pages.
 * - Lets assets through.
 * - Redirects logged-in users away from login/register.
 * - Protects everything else.
 */

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/auth.php';

// Normalize current path
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$uri = '/' . ltrim($uri, '/');

// 1) If the script declared itself PUBLIC_PAGE, allow it
if (defined('PUBLIC_PAGE') && PUBLIC_PAGE === true) {
    return;
}

// 2) Always allow static assets
$assetPrefixes = [
    '/assets/', '/public/assets/', '/uploads/', '/img/', '/images/', '/favicon', '/robots.txt',
    '/manifest', '/apple-touch-icon', '/favicon.ico', '/sitemap.xml',
];
foreach ($assetPrefixes as $pfx) {
    if (str_starts_with($uri, $pfx)) {
        return;
    }
}

// 3) Public routes (landing + auth + legal + utilities)
$publicRoutes = [
    '/', '/index.php',
    '/login.php', '/register.php', '/forgot.php', '/do_login.php', '/logout.php',
    '/terms.php', '/privacy.php',
    '/styleguide.php', '/health.php', '/_selfcheck.php',
];

// If the current path is public, allow — with a small UX nicety:
if (in_array($uri, $publicRoutes, true)) {
    // If user is logged in and visiting login/register, bounce to dashboard
    if (is_logged_in() && in_array($uri, ['/login.php','/register.php'], true)) {
        header('Location: /dashboard.php', true, 302);
        exit;
    }
    return;
}

// 4) Everything else must be logged-in
if (!is_logged_in()) {
    $ret = $_SERVER['REQUEST_URI'] ?? '/';
    if (!str_starts_with($ret, '/')) { $ret = '/'; } // on-site only
    header('Location: /login.php?return=' . rawurlencode($ret), true, 302);
    exit;
}