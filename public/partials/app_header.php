<?php
// public/partials/app_header.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/auth_guard.php'; // يحمي صفحات الداشبورد

$lang  = $_GET['lang'] ?? 'en';
$dir   = ($lang === 'ar') ? 'rtl' : 'ltr';

// عنوان ووصف الصفحة (يُمكن للصفحة تغييره قبل include)
$page_title = $page_title ?? 'Dashboard';
$meta_title = $meta_title ?? ($page_title . ' · Whoizme');
$meta_desc  = $meta_desc  ?? 'Whoizme — short links, QR codes & analytics.';

// تفضيل الثيم محفوظ بالكوكي (dark افتراضي)
$theme = $_COOKIE['whoizme_theme'] ?? 'dark';
?>
<!doctype html>
<html lang="<?= htmlspecialchars($lang) ?>" dir="<?= $dir ?>" data-theme="<?= htmlspecialchars($theme) ?>">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= htmlspecialchars($meta_title) ?></title>
    <meta name="description" content="<?= htmlspecialchars($meta_desc) ?>">
    <meta name="theme-color" content="<?= $theme === 'dark' ? '#111827' : '#ffffff' ?>">

    <!-- Favicon pack -->
    <link rel="icon" href="/assets/favicon/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/favicon/apple-icon.png">
    <link rel="manifest" href="/assets/favicon/manifest.json">

    <!-- CSS (بدون inline) -->
    <link rel="preload" href="/assets/css/app.min.css" as="style" />
    <link rel="stylesheet" href="/assets/css/app.min.css" />

    <!-- CSRF helper (لو محتاج) -->
    <?php
      $csrf_token = '';
      if (file_exists(__DIR__ . '/../../includes/csrf.php')) {
        require_once __DIR__ . '/../../includes/csrf.php';
        if (function_exists('csrf_token')) $csrf_token = csrf_token();
      }
    ?>
  </head>
  <body data-theme="<?= htmlspecialchars($theme) ?>">
    <a class="skip-link" href="#main">Skip to content</a>
    <div class="app-shell">
      <!-- Grid يتنفّذ داخل كل صفحة: Sidebar + Main -->