<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

// بدون أي auth_guard هنا — الهيدر عام
if (!isset($page_title)) { $page_title = 'Whoiz.me'; }
if (!isset($page_class)) { $page_class = ''; }
$meta_description = $meta_description ?? 'Whoizme — create, manage and track short links with powerful QR analytics. Start for free today.';
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? '';
$path = isset($_SERVER['REQUEST_URI']) ? strtok($_SERVER['REQUEST_URI'], '?') : '/';
$canonical = $host ? $scheme . '://' . $host . $path : '';
?>
<!doctype html>
<html lang="en" dir="ltr" data-theme="dark">
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
  <title><?= htmlspecialchars($page_title) ?></title>
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
  <!-- Performance: preconnect for Google Fonts (Onest) -->
  <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="/assets/css/app.min.css?v=<?= time() ?>">
</head>
<body class="<?= htmlspecialchars($page_class) ?>">

<header class="landing-header" role="banner">
  <div class="landing-header__bar">
    <a class="landing-header__logo" href="/">
      <img src="/assets/img/logo.png" alt="<?= htmlspecialchars($page_title) ?>">
      <span>Whoiz.me</span>
    </a>

    <nav aria-label="Primary" class="nav">
      <a href="/#features">Features</a>
      <a href="/#help">Help</a>
    </nav>

    <div class="nav__spacer"></div>

    <a href="/login" class="landing-header__link">Log in</a>
    <a class="btn btn--cta" href="/register">Get started</a>
  </div>
</header>