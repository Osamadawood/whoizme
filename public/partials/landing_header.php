<?php if (session_status() !== PHP_SESSION_ACTIVE) session_start(); ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($page_title ?? 'Whoiz.me', ENT_QUOTES, 'UTF-8') ?></title>
<link rel="stylesheet" href="/assets/landing.css">
<style>
  :root{--brand:$brand;--muted:#6b7280;--border:#e5e7eb;background:#f9fafb}
  body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,"Helvetica Neue",sans-serif;color:#111}
  .container{max-width:1100px;margin:0 auto;padding:0 16px}
  .navbar{display:flex;align-items:center;justify-content:space-between;padding:14px 0}
  .logo a{font-weight:700;color:#111;text-decoration:none}
  .nav a{color:#111;text-decoration:none;margin-left:16px}
  .hero-pad{padding-top:16px}
  .card{background:#fff;border:1px solid var(--border);border-radius:14px;padding:22px}
  .grid{display:grid;grid-template-columns:1fr 1fr;gap:24px}
  @media (max-width:900px){ .grid{grid-template-columns:1fr} }
  h1,h2,h3{margin:0 0 8px}
  .muted{color:var(--muted)}
  .btn{display:inline-flex;align-items:center;gap:8px;border:none;border-radius:10px;background:var(--brand);color:#fff;padding:12px 16px;cursor:pointer}
  .btn-secondary{background:#111}
  .field{margin:12px 0}
  input[type=email],input[type=password],input[type=text]{width:100%;padding:12px;border:1px solid var(--border);border-radius:10px;background:#fff}
  .tabs{display:flex;gap:12px;margin-bottom:16px}
  .tablink{padding:8px 12px;border-radius:8px;text-decoration:none;color:#111;background:transparent}
  .tablink.active{background:var(--brand);color:#fff}
  .alert{padding:10px 12px;border-radius:8px;margin:8px 0}
  .error{background:#fee2e2;color:#991b1b;border:1px solid #fecaca}
  .ok{background:#dcfce7;color:#166534;border:1px solid #bbf7d0}
  .field-error{font-size:13px;color:#b91c1c;margin-top:6px}
  .checkbox{display:flex;gap:8px;align-items:center}
  a{color:var(--brand);text-decoration:none}
</style>
</head>
<body>
<header class="container navbar">
  <div class="logo"><a href="/">Whoiz.me</a></div>
  <nav class="nav">
    <?php if (!empty($_SESSION['is_logged_in'])): ?>
      <a href="/dashboard.php">Dashboard</a>
      <a href="/logout.php">Logout</a>
    <?php else: ?>
      <a href="/login.php">Login</a>
      <a href="/login.php?mode=signup" class="btn" style="padding:8px 12px">Create account</a>
    <?php endif; ?>
  </nav>
</header>
<main class="container hero-pad">