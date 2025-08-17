<?php
  if (!isset($page_title)) { $page_title = "Whoizme"; }
  if (!isset($page_class)) { $page_class = ""; }
?>
<!doctype html>
<html lang="en" dir="ltr" data-theme="dark">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title><?= htmlspecialchars($page_title) ?></title>

  <!-- App CSS (من SCSS بتاع السيستم) -->
  <link rel="stylesheet" href="/assets/css/app.min.css?v=<?= time() ?>">
</head>
<body class="<?= htmlspecialchars($page_class) ?>">

<header class="landing-header" role="banner">
  <div class="landing-header__bar">
    <a class="landing-header__logo" href="/">
      <img src="/assets/img/logo.svg" alt="" width="28" height="28">
      <span>Whoiz.me</span>
    </a>

    <nav aria-label="Primary" class="nav">
      <a href="/#features">Features</a>
      <a href="/#help">Help</a>
      
    </nav>

    <div class="nav__spacer"></div>
    
    <a href="/login.php" class="landing-header__link">Log in</a>
    <a class="btn btn--cta" href="/register.php">Get started</a>

  </div>
</header>

</header>
