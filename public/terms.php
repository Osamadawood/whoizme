<?php require __DIR__ . "/_bootstrap.php"; ?>
<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$page_title = 'Terms · Whoiz.me';
require __DIR__ . '/partials/landing_header.php'; ?>
<div class="card" style="max-width:820px;margin:0 auto;">
  <h1>Terms of Service</h1>
  <p class="muted">These are placeholder terms. Replace with your real terms.</p>
  <ul>
    <li>Use must comply with applicable laws.</li>
    <li>No abusive or illegal content.</li>
    <li>We may update these terms from time to time.</li>
  </ul>
</div>
<?php require __DIR__ . '/partials/landing_footer.php';