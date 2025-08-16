<?php
// لا تعمل session_start هنا — اللودر بيتكفّل
declare(strict_types=1);

/** 
 * متغيرات بسيطة
 * $title: عنوان الصفحة (اختياري)
 * $active: اسم اللينك النشط في الناف (اختياري) [dashboard|links|qr|help]
 */
$title  = $title  ?? 'Whoizme';
$active = $active ?? '';

$base   = rtrim(BASE_URL ?? '/', '/');              // من includes/bootstrap.php
$asset  = $base . '/assets';                        // مجلد الأصول
$nav    = [
  ['href' => $base . '/dashboard',   'key' => 'dashboard', 'label' => 'Dashboard'],
  ['href' => $base . '/links',       'key' => 'links',     'label' => 'Links'],
  ['href' => $base . '/qr-codes',    'key' => 'qr',        'label' => 'QR Codes'],
  ['href' => $base . '/help',        'key' => 'help',      'label' => 'Help'],
];
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title><?= htmlspecialchars($title) ?></title>

  <!-- ملف CSS موحّد (بدون هاردكود للدومين) -->
  <link rel="preload" href="<?= $asset ?>/css/app.min.css" as="style">
  <link rel="stylesheet" href="<?= $asset ?>/css/app.min.css">

  <!-- أي ميتا/أيقونات مستقبلًا -->
</head>
<body class="app">
  <header class="topbar">
    <div class="container topbar__inner">
      <a class="brand" href="<?= $base ?>/">Whoizme</a>

      <nav class="mainnav" aria-label="Primary">
        <?php foreach ($nav as $item): ?>
          <a 
            class="mainnav__link <?= $active === $item['key'] ? 'is-active' : '' ?>" 
            href="<?= $item['href'] ?>"
          ><?= htmlspecialchars($item['label']) ?></a>
        <?php endforeach; ?>
      </nav>

      <div class="usernav">
        <a class="btn btn-ghost" href="<?= $base ?>/logout">Logout</a>
      </div>
    </div>
  </header>

  <div class="container page">