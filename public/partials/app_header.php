<?php
declare(strict_types=1);

/**
 * App Header (for authenticated area)
 * - يعتمد على design system من app.css
 * - فيه toggle للثيم (dark / light) يخزّن الاختيار في كوكي
 */

$page_title = $page_title ?? 'Whoizme';
$themeCookie = $_COOKIE['theme'] ?? 'dark';
$theme = in_array($themeCookie, ['dark','light'], true) ? $themeCookie : 'dark';
$meta_description = $meta_description ?? 'Whoizme is a modern link management and QR analytics platform. Create, track, and optimize your links easily.';
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? '';
$path = isset($_SERVER['REQUEST_URI']) ? strtok($_SERVER['REQUEST_URI'], '?') : '/';
$canonical = $host ? $scheme . '://' . $host . $path : '';
?>
<!doctype html>
<html lang="en" dir="ltr" data-theme="<?= htmlspecialchars($theme, ENT_QUOTES) ?>">
<head>
  <meta charset="utf-8"/>
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="robots" content="index, follow">
  <meta name="author" content="Whoizme Team">
  <meta name="description" content="<?= htmlspecialchars($meta_description) ?>">
  <link rel="canonical" href="<?= htmlspecialchars($canonical) ?>">
  <!-- Language alternates (basic) -->
  <link rel="alternate" href="<?= htmlspecialchars($canonical) ?>" hreflang="en">
  <link rel="alternate" href="<?= htmlspecialchars($canonical . (str_contains($canonical, '?') ? '&' : '?') . 'lang=ar') ?>" hreflang="ar">
  <meta http-equiv="content-language" content="en">
  <!-- GEO -->
  <meta name="geo.region" content="EG-C">
  <meta name="geo.placename" content="Cairo">
  <meta name="geo.position" content="30.033333;31.233334">
  <meta name="ICBM" content="30.033333, 31.233334">
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title><?= htmlspecialchars($page_title) ?> · Whoizme</title>

  <!-- Performance: preconnect for Google Fonts (Onest) -->
  <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <!-- Main CSS bundle (SCSS output) -->
  <link rel="stylesheet" href="/assets/css/app.min.css?v=<?= time() ?>">
  <!-- (اختياري) styleguide.css لو الصفحة هي الـ styleguide -->
  <?php if (!empty($include_styleguide_css)): ?>
    <link rel="stylesheet" href="/assets/css/styleguide.css?v=<?= time() ?>">
  <?php endif; ?>

  <!-- أي أيقونات/مانيفست مستقبلًا -->
  <!-- Favicon set -->
  <link rel="apple-touch-icon" sizes="180x180" href="/assets/favicon/apple-icon-180x180.png">
  <link rel="icon" type="image/png" sizes="32x32" href="/assets/favicon/favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="/assets/favicon/favicon-16x16.png">
  <link rel="shortcut icon" href="/assets/favicon/favicon.ico">
  <link rel="manifest" href="/assets/favicon/manifest.json">
  <meta name="msapplication-config" content="/assets/favicon/browserconfig.xml">
  <meta name="msapplication-TileColor" content="#111827">
  <meta name="theme-color" content="#111827">
  <!-- Open Graph -->
  <meta property="og:type" content="website">
  <meta property="og:site_name" content="Whoizme">
  <meta property="og:title" content="<?= htmlspecialchars($page_title) ?> · Whoizme">
  <meta property="og:description" content="<?= htmlspecialchars($meta_description) ?>">
  <meta property="og:url" content="<?= htmlspecialchars($canonical) ?>">
  <meta property="og:image" content="/assets/favicon/apple-icon-180x180.png">
  <meta property="og:image:width" content="180">
  <meta property="og:image:height" content="180">

  <!-- Twitter Card -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= htmlspecialchars($page_title) ?> · Whoizme">
  <meta name="twitter:description" content="<?= htmlspecialchars($meta_description) ?>">
  <meta name="twitter:image" content="/assets/favicon/apple-icon-180x180.png">

  <!-- Structured Data: Organization -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "Organization",
    "name": "Whoizme",
    "url": "<?= htmlspecialchars($canonical) ?>",
    "logo": "/assets/favicon/apple-icon-180x180.png",
    "sameAs": [
      "https://www.linkedin.com/in/osamadawood",
      "https://www.instagram.com/osamadawood",
      "https://twitter.com/osamadawood",
      "https://www.youtube.com/c/osamastudioc"
    ]
  }
  </script>
</head>
<body>
  <!-- App Shell -->
  <div class="app">


      <main class="app-content"></file>