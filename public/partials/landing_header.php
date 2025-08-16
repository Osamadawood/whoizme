<?php
declare(strict_types=1);

// عنوان الصفحة (اختياري)
$title = $title ?? 'Whoizme — QR & Short Links';

// ثيم افتراضي (dark). حابب تفعّل light؟ غير القيمة
$theme = $theme ?? 'dark';

// مسار CSS النهائي (لو بتخرج CSS في مسار مختلف عدّله)
$APP_CSS = '/assets/css/app.min.css';

// هل المستخدم مسجّل؟ (لو عندك دالة جاهزة)
$__uid = function_exists('current_user_id') ? (int)current_user_id() : 0;
?>
<!doctype html>
<html lang="en" dir="ltr" data-theme="<?= htmlspecialchars($theme) ?>">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title><?= htmlspecialchars($title) ?></title>

  <!-- Fonts + App CSS -->
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= $APP_CSS ?>?v=<?= time() ?>"/>
</head>
<body>

<header class="topbar">
  <div class="topbar__inner">
    <a class="brand" href="/"><span class="sr-only">Whoizme</span>Whoizme</a>

    <nav class="nav">
      <a href="/#features">Features</a>
      <a href="/#pricing">Pricing</a>
      <a href="/help.php">Help</a>

      <?php if ($__uid): ?>
        <a class="btn btn--primary" href="/dashboard">Go to Dashboard</a>
      <?php else: ?>
        <a class="btn btn--ghost" href="/login">Log in</a>
        <a class="btn btn--primary" href="/register">Get Started</a>
      <?php endif; ?>
    </nav>
  </div>
</header>

<main class="page-section">
  <div class="container">