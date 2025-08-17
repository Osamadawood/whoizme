<?php
// public/logout.php
$AUTH_PAGE = true;
require __DIR__ . '/partials/app_header.php';

// لو عندك منطق تسجيل خروج فعلي (مسح السيشن) خليه يتم هنا قبل الريندر
// session_start(); session_destroy();
?>
<!doctype html>
<html lang="en" dir="ltr" data-theme="dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Logged out · Whoizme</title>
  <link rel="stylesheet" href="/assets/css/app.min.css?v=<?php echo time(); ?>">
</head>
<body class="page-auth">
  <section class="auth-hero" aria-hidden="true"></section>

  <main class="auth-wrap">
    <header class="auth-head">
      <h1 class="auth-title">You’re logged out</h1>
      <p class="auth-lead">Thanks for using Whoizme. See you soon!</p>
    </header>

    <section class="auth-card">
      <div class="stack">
        <p class="auth-muted">Your session has ended safely.</p>
        <a class="btn btn--primary" href="/login.php">Back to sign in</a>
      </div>
    </section>

    <footer class="auth-muted u-mt-6">© <?php echo date('Y'); ?> Whoizme</footer>
  </main>
</body>
</html>