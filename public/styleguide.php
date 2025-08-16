<?php
declare(strict_types=1);

/**
 * Style Guide (public, no auth) – Darkware-like, pixel-consistent
 */
if (!defined('SKIP_AUTH_GUARD')) {
    define('SKIP_AUTH_GUARD', true);
}

// حاول نلاقي أي bootstrap خفيف لو متاح
$boot = [
  __DIR__.'/_bootstrap.php',
  __DIR__.'/bootstrap.php',
  dirname(__DIR__).'/includes/bootstrap.php',
  dirname(__DIR__).'/includes/_bootstrap.php',
];
foreach ($boot as $b) { if (is_file($b)) { require_once $b; break; } }

$title = 'Whoizme · Styles & Components';
?>
<!doctype html>
<html lang="en" dir="ltr" data-theme="dark">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title><?= htmlspecialchars($title) ?></title>

  <!-- App CSS -->
  
  <link rel="stylesheet" href="/assets/css/styleguide.css?v=<?= time() ?>">
</head>
<body class="sg">

<div class="sg-wrap">
  <!-- Sidebar -->
  <aside class="sg-aside">
    <div class="sg-aside__logo">
      <span class="sg-badge"></span>
      <span class="sg-brand">Whoizme UI</span>
    </div>
    <nav class="sg-nav" aria-label="Sections">
      <a href="#colors" class="is-active">Colors</a>
      <a href="#type">Typography</a>
      <a href="#buttons">Buttons</a>
      <a href="#forms">Forms</a>
      <a href="#badges">Badges</a>
      <a href="#cards">Cards</a>
      <a href="#utilities">Utilities</a>
    </nav>
  </aside>

  <!-- Main -->
  <div class="sg-main">
    <header class="sg-topbar">
      <div class="sg-topbar__in">
        <strong>Styles & Components</strong>
        <span class="sg-muted">Reference</span>
        <div class="sg-topbar__spacer"></div>
        <label class="sg-toggle" title="Toggle theme">
          <input id="themeToggle" type="checkbox"/>
          <span>Light</span>
        </label>
      </div>
    </header>

    <section class="sg-hero">
      <div class="sg-hero__in">
        <h1 class="sg-title">Design System</h1>
        <p class="sg-sub">Color tokens, type scale, controls and layout blocks that mirror the Darkware guide. This page is public and uses the same CSS tokens from our SCSS design system.</p>
      </div>
    </section>

    <main class="sg-content">
      <!-- Colors -->
      <section id="colors" class="sg-section">
        <h2 class="sg-h">Colors</h2>
        <div class="sg-grid cols-4">
          <div class="sg-card">
            <h3 class="sg-h sg-h--sm">Base</h3>
            <div class="stack">
              <div class="swatch" style="background:var(--page-bg)">--page-bg</div>
              <div class="swatch" style="background:var(--surface)">--surface</div>
              <div class="swatch" style="background:color-mix(in oklab,var(--surface),var(--text)/10%)">muted-surface</div>
            </div>
          </div>
          <div class="sg-card">
            <h3 class="sg-h sg-h--sm">Brand</h3>
            <div class="stack">
              <div class="swatch" style="background:var(--brand);color:#fff">--brand</div>
              <div class="swatch" style="background:color-mix(in oklab,var(--brand),#000 10%);color:#fff">brand-700</div>
              <div class="swatch" style="background:color-mix(in oklab,var(--brand),#fff 20%);">brand-300</div>
            </div>
          </div>
          <div class="sg-card">
            <h3 class="sg-h sg-h--sm">Text</h3>
            <div class="stack">
              <div class="swatch" style="background:var(--text);color:var(--page-bg)">--text</div>
              <div class="swatch" style="background:var(--muted);color:var(--page-bg)">--muted</div>
              <div class="swatch" style="background:#fff;color:#111">white</div>
            </div>
          </div>
          <div class="sg-card">
            <h3 class="sg-h sg-h--sm">States</h3>
            <div class="stack">
              <div class="swatch" style="background:#16a34a;color:#fff">success</div>
              <div class="swatch" style="background:#f97316;color:#111">warning</div>
              <div class="swatch" style="background:#ef4444;color:#fff">danger</div>
            </div>
          </div>
        </div>
      </section>

      <!-- Typography -->
      <section id="type" class="sg-section">
        <h2 class="sg-h">Typography</h2>
        <div class="sg-grid cols-2">
          <div class="sg-card stack">
            <h1>Display / H1</h1>
            <h2>Heading / H2</h2>
            <h3>Heading / H3</h3>
            <p>Body text. This paragraph uses the system font sizes and weights from our tokens.</p>
            <p class="sg-muted">Muted paragraph for secondary information.</p>
          </div>
          <div class="sg-card stack">
            <div class="badge">Label</div>
            <div class="badge badge--brand">Brand</div>
            <div class="badge badge--muted">Muted</div>
            <a class="btn btn--link" href="#">Inline link</a>
          </div>
        </div>
      </section>

      <!-- Buttons -->
      <section id="buttons" class="sg-section">
        <h2 class="sg-h">Buttons</h2>
        <div class="sg-card">
          <div class="sg-row">
            <button class="btn">Default</button>
            <button class="btn btn--primary">Primary</button>
            <button class="btn btn--ghost">Ghost</button>
            <button class="btn btn--danger">Danger</button>
            <button class="btn" disabled>Disabled</button>
            <button class="btn btn--primary btn--sm">Small</button>
            <button class="btn btn--primary btn--lg">Large</button>
          </div>
        </div>
      </section>

      <!-- Forms -->
      <section id="forms" class="sg-section">
        <h2 class="sg-h">Forms</h2>
        <div class="sg-grid cols-2">
          <div class="sg-card">
            <form class="stack" action="#" method="post" onsubmit="return false">
              <label class="field">
                <span class="label">Full name</span>
                <input class="input" type="text" placeholder="John Carter">
              </label>
              <label class="field">
                <span class="label">Email</span>
                <input class="input" type="email" placeholder="name@email.com">
              </label>
              <label class="field">
                <span class="label">Password</span>
                <input class="input" type="password" placeholder="••••••••">
              </label>
              <div class="row">
                <label class="checkbox"><input type="checkbox"> <span>Checkbox</span></label>
                <label class="radio"><input name="r" type="radio"> <span>Radio</span></label>
                <label class="switch"><input type="checkbox" checked> <span>Toggle</span></label>
              </div>
              <div class="row">
                <button class="btn btn--primary" type="submit">Submit</button>
                <button class="btn btn--ghost" type="button">Cancel</button>
              </div>
            </form>
          </div>
          <div class="sg-card stack">
            <div class="label">Upload</div>
            <div class="drop">
              <div class="ico"></div>
              <div class="sg-muted u-mb-6">Select a file to upload<br/>or drag it here</div>
              <button class="btn btn--primary btn--sm" type="button">Upload file</button>
            </div>
          </div>
        </div>
      </section>

      <!-- Badges -->
      <section id="badges" class="sg-section">
        <h2 class="sg-h">Badges</h2>
        <div class="sg-card">
          <div class="sg-row">
            <span class="badge">Default</span>
            <span class="badge badge--brand">Brand</span>
            <span class="badge badge--muted">Muted</span>
            <span class="badge">Small</span>
            <span class="badge">Outline</span>
          </div>
        </div>
      </section>

      <!-- Cards -->
      <section id="cards" class="sg-section">
        <h2 class="sg-h">Cards</h2>
        <div class="sg-grid cols-3">
          <article class="sg-card">
            <h3 class="sg-h sg-h--sm">Basic Card</h3>
            <p class="sg-muted">Neutral surface using tokens.</p>
            <button class="btn btn--primary btn--sm">Action</button>
          </article>
          <article class="sg-card">
            <h3 class="sg-h sg-h--sm">Stats</h3>
            <div class="stat-num">12,680</div>
            <div class="sg-muted">Monthly scans</div>
          </article>
          <article class="sg-card">
            <h3 class="sg-h sg-h--sm">List</h3>
            <ul class="stack u-pl-4">
              <li>Item one</li><li>Item two</li><li>Item three</li>
            </ul>
          </article>
        </div>
      </section>

      <!-- Utilities -->
      <section id="utilities" class="sg-section">
        <h2 class="sg-h">Utilities</h2>
        <div class="sg-card sg-row">
          <span class="badge">.mt-6</span>
          <span class="badge">.rounded</span>
          <span class="badge">.text-center</span>
          <span class="badge">.flex .items-center .gap-4</span>
        </div>
      </section>

      <p class="sg-muted" style="text-align:center">© <?= date('Y') ?> Whoizme · Design System Reference</p>
    </main>
  </div>
</div>

<script>
(()=>{
  // ===== Theme toggle =====
  const root = document.documentElement;
  const toggle = document.getElementById('themeToggle');
  if (toggle) {
    toggle.checked = (root.getAttribute('data-theme') === 'light');
    toggle.addEventListener('change', () => {
      root.setAttribute('data-theme', toggle.checked ? 'light' : 'dark');
    });
  }

  // ===== Smooth scroll + active link (no # in URL) =====
  const nav = document.querySelector('.sg-nav');
  const links = nav ? Array.from(nav.querySelectorAll('a[href^="#"]')) : [];
  const sections = links
    .map(a => document.querySelector(a.getAttribute('href')))
    .filter(Boolean);

  const topbar = document.querySelector('.sg-topbar');
  function getOffset(){ return (topbar?.offsetHeight || 80) + 16; }

  function setActive(id){
    links.forEach(a => a.classList.toggle('is-active', a.getAttribute('href') === '#' + id));
  }

  function scrollToId(id){
    const el = document.getElementById(id);
    if (!el) return;
    const y = el.getBoundingClientRect().top + window.pageYOffset - getOffset();
    window.scrollTo({ top: y, behavior: 'smooth' });
    setActive(id);
    // Remove hash without reloading
    if (history.replaceState) history.replaceState(null, '', location.pathname);
  }

  // Click -> smooth scroll, no hash in URL
  links.forEach(a => {
    a.addEventListener('click', (e) => {
      e.preventDefault();
      const id = a.getAttribute('href').slice(1);
      scrollToId(id);
    }, { passive: false });
  });

  // Scroll spy: update active as you scroll
  let ticking = false;
  function onScroll(){
    if (ticking) return; ticking = true;
    requestAnimationFrame(() => {
      let current = sections[0]?.id;
      const offset = getOffset();
      for (const sec of sections){
        const top = sec.getBoundingClientRect().top;
        if (top - offset <= 0) current = sec.id;
      }
      if (current) setActive(current);
      ticking = false;
    });
  }
  document.addEventListener('scroll', onScroll, { passive: true });
  window.addEventListener('resize', onScroll, { passive: true });

  // If page loads with a hash, scroll to it then clean URL
  if (location.hash) {
    const id = location.hash.slice(1);
    setTimeout(() => scrollToId(id), 0);
  } else {
    onScroll();
  }
})();
</script>
</body>
</html>