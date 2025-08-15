<?php
// public/help.php
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Help & FAQ – whoiz.me</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
  body{font-family:ui-sans-serif,system-ui;color:#0f172a}
  .wrap{max-width:900px;margin:0 auto;padding:32px 20px}
  details{border:1px solid #e5e7eb;border-radius:12px;margin:10px 0;background:#fff}
  summary{cursor:pointer;padding:14px 16px;font-weight:600;list-style:none}
  details > div{padding:0 16px 16px 16px;color:#475569}
  .cta{display:inline-flex;gap:8px;align-items:center;border-radius:10px;background:#0d6efd;color:#fff;padding:10px 14px;text-decoration:none}
</style>
</head>
<body>
<header class="wrap" style="display:flex;justify-content:space-between;align-items:center">
  <a href="/" style="text-decoration:none;font-weight:700;color:#0f172a">whoiz.me</a>
  <nav><a href="/templates">Templates</a> · <a href="/pricing">Pricing</a></nav>
</header>

<main class="wrap">
  <h1>Help & FAQ</h1>

  <details open>
    <summary>How do I generate a basic QR without sign‑up?</summary>
    <div>من الصفحة الرئيسية اختر النوع (URL أو Text..)، غيّر اللون والحجم، واضغط Generate ثم نزّل PNG/JPG.</div>
  </details>
  <details>
    <summary>What do I get as a Member?</summary>
    <div>تتبّع مرات المسح، شعارات مخصّصة، قوالب إضافية، أنواع QR (App Stores/Images/vCard الكامل) وملفات SVG للطباعة.</div>
  </details>
  <details>
    <summary>Why is tracking disabled on the free plan?</summary>
    <div>لأن تتبّع المسح يحتاج روابط ديناميكية وتخزين للزيارات. فعّلها بالعضوية عبر <a href="/pricing">Pricing</a>.</div>
  </details>

  <p style="margin-top:22px"><a class="cta" href="/signup">Create a free account</a></p>
</main>

<footer class="wrap" style="margin:40px 0;color:#64748b">
  © <?= date('Y') ?> whoiz.me
</footer>
</body>
</html>