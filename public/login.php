<?php
  // Page meta
  $page_title = "Sign in";
  $page_class = "page-auth"; // adds .page-auth to <body> for auth layout styles
  // Public page: bypass auth guard and use landing header
  if (!defined('SKIP_AUTH_GUARD')) {
      define('SKIP_AUTH_GUARD', true);
  }
  require __DIR__ . "/partials/landing_header.php"; // injects the <body class="$page_class">
?>

<main class="site-main">

  <section class="auth-grid log-card">
    <!-- Left: Form card -->
    <article class="auth-panel auth-card">
      <h1 class="auth-title">Sign in</h1>
      <p class="auth-sub form-desc">Use your email and password to continue</p>

      <form class="stack log-form" action="/do_login.php" method="post" novalidate>
        <label class="field">
          <span class="label">Email address</span>
          <input class="input" type="email" name="email" placeholder="name@email.com" autocomplete="username" required>
        </label>

        <label class="field">
          <span class="label">Password</span>
          <input class="input" type="password" name="password" placeholder="••••••••" autocomplete="current-password" required>
        </label>

        <div class="row row--between">
          <label class="checkbox remember">
            <input type="checkbox" name="remember" value="1">
            <span>Remember me</span>
          </label>
          <a href="/forgot.php">Forgot password?</a>
        </div>

        <button class="btn btn--primary" type="submit">Login</button>

        <div class="row">
          <div class="auth-muted">Don’t have an account? <a href="/register.php">Create one</a></div>
        </div>
      </form>
    </article>

    <!-- Right: Content side (marketing) -->
    <aside class="auth-side login-side">
      <div class="text-muted">WHOIZ.ME</div>
      <h2 class="big-title">All your links, QR codes & insights — together in one dashboard</h2>
      <p class="lead">Sign in to manage short links, create QR codes, and track performance with clean, privacy-first analytics — all in one place.</p>

      <div class="auth-sidecards">
        <!-- Card 1 -->
        <div class="sidecard">
          <div class="ico" aria-hidden="true">✉</div>
          <div class="meta">
            <div class="title">Contact support</div>
            <div class="muted">We're here to help you</div>
          </div>
          <a class="btn btn--secondary btn--sm action" href="mailto:support@whoiz.me">Email us</a>
        </div>


      </div>
    </aside>
  </section>
</main>

<?php require __DIR__ . "/partials/landing_footer.php"; ?>