<?php
declare(strict_types=1);
require_once __DIR__ . '/../_bootstrap.php';
require_once __DIR__ . '/../../includes/helpers.php';

// Build clean pretty URL to profile handler without .php and with original query
$path = '/u';
// Prefer ?u=username when provided
$username = isset($_GET['u']) ? trim((string)$_GET['u']) : '';
if ($username === '' && !empty($_SERVER['PATH_INFO'])) {
    $username = trim((string)$_SERVER['PATH_INFO'], '/');
}
if ($username !== '') {
    $path .= '/' . rawurlencode($username);
}

// Preserve other query params (excluding u)
$qsParams = [];
if (!empty($_GET)) {
    $qsParams = $_GET; unset($qsParams['u']);
}
$url = wz_url($path, $qsParams);

header('Location: ' . $url, true, 301);
exit;