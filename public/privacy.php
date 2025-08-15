<?php require __DIR__ . "/_bootstrap.php"; ?>
<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$page_title = 'Privacy · Whoiz.me';
require __DIR__ . '/partials/landing_header.php'; ?>
<div class="card" style="max-width:820px;margin:0 auto;">
  <h1>Privacy Policy</h1>
  <p class="muted">Placeholder privacy policy. Replace with your real policy.</p>
  <ul>
    <li>We collect minimal data needed to operate the service.</li>
    <li>We do not sell your personal information.</li>
    <li>Contact us for data access or deletion requests.</li>
  </ul>
</div>
<?php require __DIR__ . '/partials/landing_footer.php';