<?php
declare(strict_types=1);
/**
 * Header (no auth logic here)
 * - يفترض إن bootstrap.php تم تضمينه قبل كده
 * - يستخدم BASE_URL و current_user_id() لو متوفرين
 */

// حمايات خفيفة عشان ما نكسرش الصفحة لو اتنسى اللودر
if (!defined('BASE_URL')) { define('BASE_URL', '/'); }
if (!function_exists('current_user_id')) {
    function current_user_id(): int { return (int)($_SESSION['uid'] ?? 0); }
}

$uid = current_user_id();
$base = rtrim(BASE_URL, '/');
?>
<!doctype html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Whoizme</title>

    <!-- CSS الرئيسي بتاعك -->
    <link rel="preload" href="<?= $base ?>/assets/css/app.min.css" as="style">
    <link rel="stylesheet" href="<?= $base ?>/assets/css/app.min.css">

    <!-- أي أيقونات / manifest لو عندك -->
    <link rel="icon" type="image/svg+xml" href="<?= $base ?>/img/logo.png">

    <!-- meta اختيارية لتحسين الـ caching -->
    <meta http-equiv="x-ua-compatible" content="ie=edge">
</head>
<body class="<?= $uid ? 'with-user-sidebar' : 'no-sidebar' ?>">
<header class="user-topbar">
    <div class="topHeader d-flex align-items-center justify-content-between">
        <a class="brand d-inline-flex align-items-center" href="<?= $base ?>/">
            <img src="<?= $base ?>/img/logo.png" alt="Whoizme" width="28" height="28" style="margin-inline-end:.5rem;">
            <strong>Whoizme</strong>
        </a>

        <nav class="d-flex align-items-center gap-2">
            <a class="btn btn-link" href="<?= $base ?>/help.php">Help</a>
            <?php if ($uid): ?>
                <a class="btn btn-primary" href="<?= $base ?>/dashboard.php">Dashboard</a>
                <a class="btn btn-outline" href="<?= $base ?>/logout.php">Logout</a>
            <?php else: ?>
                <a class="btn btn-outline" href="<?= $base ?>/login.php">Login</a>
                <a class="btn btn-primary" href="<?= $base ?>/register.php">Create free account</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main>
<!-- ابدأ محتوى الصفحة هنا -->