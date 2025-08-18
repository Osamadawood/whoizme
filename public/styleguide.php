<?php
/**
 * Whoizme – Public Styleguide (no auth)
 * Robust version: optional includes, no fatals if files are missing.
 */
declare(strict_types=1);

// Public page flags so guards (if any) will skip auth
if (!defined('PUBLIC_PAGE')) { define('PUBLIC_PAGE', true); }
if (!defined('SKIP_AUTH_GUARD')) { define('SKIP_AUTH_GUARD', true); }

// Try to include a lightweight bootstrap if available (but don't die if not)
$tryBoot = [
  __DIR__.'/_bootstrap.php',
  __DIR__.'/bootstrap.php',
  dirname(__DIR__).'/includes/bootstrap.php',
  dirname(__DIR__).'/includes/_bootstrap.php',
];
foreach ($tryBoot as $b) { if (is_file($b)) { @require_once $b; break; } }

// Title & meta
$title = 'Whoizme · Styles & Components';
?>
<!doctype html>
<html lang="en" dir="ltr" data-theme="dark">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= htmlspecialchars($title) ?></title>

  <!-- Favicon (use same assets used by register/login); guard if missing -->
  <?php if (is_file(__DIR__.'/assets/img/favicon.svg')): ?>
    <link rel="icon" href="/assets/img/favicon.svg" type="image/svg+xml" />
  <?php elseif (is_file(__DIR__.'/favicon.ico')): ?>
    <link rel="icon" href="/favicon.ico" />
  <?php endif; ?>

  <!-- Design system CSS; fall back to app.css if styleguide.css is absent -->
  <?php if (is_file(__DIR__.'/assets/css/styleguide.css')): ?>
    <link rel="stylesheet" href="/assets/css/styleguide.css?v=<?= time() ?>" />
  <?php else: ?>
    <link rel="stylesheet" href="/assets/css/app.css?v=<?= time() ?>" />
  <?php endif; ?>
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
      <a href="#links">Links &amp; Lists</a>
      <a href="#cards">Cards</a>
      <a href="#avatars">Avatars</a>
      <a href="#states">State messages</a>
      <a href="#tooltips">Tooltips</a>
      <a href="#tabs">Tabs</a>
      <a href="#accordions">Accordions</a>
      <a href="#notifications">Notifications</a>
      <a href="#popups">Popups</a>
      <a href="#breadcrumbs">Breadcrumbs</a>
      <a href="#pagination">Pagination</a>
      <a href="#prose">Rich text</a>
      <a href="#spacers">Spacers</a>
      <a href="#icons">Icons</a>
      <a href="#iconfont">Icon font</a>
      <a href="#logo">Logo</a>
      <a href="#shadows">Shadows</a>
    </nav>
  </aside>

  <!-- Main -->
  <div class="sg-main">
    <header class="sg-topbar">
      <div class="sg-topbar__in">
        <strong>Styles &amp; Components</strong>
        <span class="sg-muted">Reference</span>
        <div class="sg-topbar__spacer"></div>
        <label class="sg-toggle" title="Toggle theme">
          <input id="themeToggle" type="checkbox" />
          <span>Light</span>
        </label>
      </div>
    </header>

    <section class="sg-hero">
      <div class="sg-hero__in">
        <h1 class="sg-title">Design System</h1>
        <p class="sg-sub">Tokens, type scale, controls and layout blocks used across Whoizme. Built with our SCSS system; this page is public for quick QA.</p>
      </div>
    </section>

    <main class="sg-content">
      <!-- ==== Sections (unchanged) ==== -->
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

      <!-- Links &amp; Lists -->
      <section id="links" class="sg-section">
        <h2 class="sg-h">Links &amp; Lists</h2>
        <div class="sg-grid cols-2">
          <div class="sg-card stack">
            <h3 class="sg-h sg-h--sm">Links</h3>
            <p><a class="btn btn--link" href="#">Inline link</a> inside text &mdash; plus a <a class="btn btn--link" href="#">second link</a> to show spacing.</p>
            <div class="sg-row">
              <a class="btn btn--link" href="#">Link button</a>
              <a class="btn btn--link" href="#">Another</a>
            </div>
          </div>
          <div class="sg-card stack">
            <h3 class="sg-h sg-h--sm">Lists</h3>
            <ul class="stack u-pl-4">
              <li>Simple list item</li>
              <li>Another list item</li>
              <li>Third list item</li>
            </ul>
            <ol class="stack u-pl-4">
              <li>Ordered one</li>
              <li>Ordered two</li>
              <li>Ordered three</li>
            </ol>
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

      <!-- Avatars -->
      <section id="avatars" class="sg-section">
        <h2 class="sg-h">Avatars</h2>
        <div class="sg-card sg-row">
          <div class="avatar"><img src="/img/qr-placeholder.png" alt=""></div>
          <div class="avatar avatar--sm"><img src="/img/qr-placeholder.png" alt=""></div>
          <div class="avatar avatar--lg"><img src="/img/qr-placeholder.png" alt=""></div>
          <div class="avatar">
            <span class="avatar__fallback">OD</span>
            <span class="avatar__status" title="online"></span>
          </div>
        </div>
      </section>

      <!-- State messages -->
      <section id="states" class="sg-section">
        <h2 class="sg-h">State messages</h2>
        <div class="sg-card stack">
          <div class="note note--success">Everything looks good. Success state.</div>
          <div class="note note--warning">Heads up! Something needs your attention.</div>
          <div class="note note--danger">There was a problem processing your request.</div>
        </div>
      </section>

      <!-- Tooltips -->
      <section id="tooltips" class="sg-section">
        <h2 class="sg-h">Tooltips</h2>
        <div class="sg-card sg-row">
          <button class="btn" data-tip="Default tooltip">Hover me</button>
          <button class="btn btn--primary" data-tip="Primary action">Primary</button>
          <span class="badge" data-tip="Badge tip">Badge</span>
        </div>
      </section>

      <!-- Tabs -->
      <section id="tabs" class="sg-section">
        <h2 class="sg-h">Tabs</h2>
        <div class="sg-card">
          <div class="tabs" data-tabs>
            <div class="tabs__list">
              <button class="tabs__tab is-active" data-tab="one">Overview</button>
              <button class="tabs__tab" data-tab="two">Details</button>
              <button class="tabs__tab" data-tab="three">More</button>
            </div>
            <div class="tabs__panel is-active" data-panel="one">
              <p class="sg-muted">Overview content.</p>
            </div>
            <div class="tabs__panel" data-panel="two">
              <p class="sg-muted">Details content.</p>
            </div>
            <div class="tabs__panel" data-panel="three">
              <p class="sg-muted">More content.</p>
            </div>
          </div>
        </div>
      </section>

      <!-- Accordions -->
      <section id="accordions" class="sg-section">
        <h2 class="sg-h">Accordions</h2>
        <div class="sg-card">
          <details class="acc" open>
            <summary class="acc__head">Accordion item #1</summary>
            <div class="acc__body">Body of item #1.</div>
          </details>
          <details class="acc">
            <summary class="acc__head">Accordion item #2</summary>
            <div class="acc__body">Body of item #2.</div>
          </details>
        </div>
      </section>

      <!-- Notifications -->
      <section id="notifications" class="sg-section">
        <h2 class="sg-h">Notifications</h2>
        <div class="sg-card sg-row">
          <div class="toast">Saved successfully</div>
          <div class="toast toast--warn">Check your inputs</div>
          <div class="toast toast--error">Something went wrong</div>
        </div>
      </section>

      <!-- Popups -->
      <section id="popups" class="sg-section">
        <h2 class="sg-h">Popups</h2>
        <div class="sg-card">
          <button class="btn btn--primary" id="openDemoModal">Open modal</button>
          <dialog id="demoModal" class="modal">
            <div class="modal__card">
              <h3 class="sg-h sg-h--sm">Demo modal</h3>
              <p class="sg-muted">This is a native dialog styled by our tokens.</p>
              <div class="sg-row">
                <button class="btn btn--ghost" id="closeDemoModal">Close</button>
                <button class="btn btn--primary">Confirm</button>
              </div>
            </div>
          </dialog>
        </div>
      </section>

      <!-- Breadcrumbs -->
      <section id="breadcrumbs" class="sg-section">
        <h2 class="sg-h">Breadcrumbs</h2>
        <div class="sg-card">
          <nav class="crumbs" aria-label="Breadcrumb">
            <a href="#">Home</a>
            <span>/</span>
            <a href="#">Library</a>
            <span>/</span>
            <span aria-current="page" class="sg-muted">Data</span>
          </nav>
        </div>
      </section>

      <!-- Pagination -->
      <section id="pagination" class="sg-section">
        <h2 class="sg-h">Pagination</h2>
        <div class="sg-card">
          <nav class="pagi" role="navigation" aria-label="Pagination">
            <a class="pagi__btn is-disabled" href="#" aria-disabled="true">Prev</a>
            <a class="pagi__num is-active" href="#">1</a>
            <a class="pagi__num" href="#">2</a>
            <a class="pagi__num" href="#">3</a>
            <span class="pagi__sep">…</span>
            <a class="pagi__num" href="#">9</a>
            <a class="pagi__btn" href="#">Next</a>
          </nav>
        </div>
      </section>

      <!-- Rich text / Prose -->
      <section id="prose" class="sg-section">
        <h2 class="sg-h">Rich text</h2>
        <article class="prose">
          <h3>Heading inside prose</h3>
          <p>This is a paragraph with <a href="#">a link</a>, <strong>strong text</strong>, and <em>emphasis</em>.</p>
          <blockquote>Blockquote example using muted border and color tokens.</blockquote>
          <pre><code>code { color: var(--muted); }</code></pre>
          <ul><li>One</li><li>Two</li></ul>
        </article>
      </section>

      <!-- Spacers -->
      <section id="spacers" class="sg-section">
        <h2 class="sg-h">Spacers</h2>
        <div class="sg-card">
          <div class="sg-row"><span class="badge">.u-mt-6</span><span class="badge">.u-mb-6</span><span class="badge">.u-p-6</span></div>
          <div class="u-mt-6"></div>
          <div class="u-mb-6"></div>
        </div>
      </section>

      <!-- Icons -->
      <section id="icons" class="sg-section">
        <h2 class="sg-h">Icons</h2>
        <div class="sg-card sg-row">
          <span class="icon-btn"><span class="icon">★</span></span>
          <span class="icon-btn"><span class="icon">☆</span></span>
          <span class="icon-btn"><span class="icon">✚</span></span>
        </div>
      </section>

      <!-- Icon font -->
      <section id="iconfont" class="sg-section">
        <h2 class="sg-h">Icon font</h2>
        <div class="sg-card sg-row">
          <i class="if if-home"></i>
          <i class="if if-user"></i>
          <i class="if if-bell"></i>
          <i class="if if-settings"></i>
        </div>
      </section>

      <!-- Logo -->
      <section id="logo" class="sg-section">
        <h2 class="sg-h">Logo</h2>
        <div class="sg-card sg-row">
          <div class="logo">Whoizme</div>
          <div class="logo logo--badge"><span>WZ</span></div>
        </div>
      </section>

      <!-- Shadows -->
      <section id="shadows" class="sg-section">
        <h2 class="sg-h">Shadows</h2>
        <div class="sg-grid cols-3">
          <div class="sg-card u-center" style="box-shadow: var(--shadow-sm)">shadow-sm</div>
          <div class="sg-card u-center" style="box-shadow: var(--shadow-md)">shadow-md</div>
          <div class="sg-card u-center" style="box-shadow: var(--shadow-lg)">shadow-lg</div>
        </div>
      </section>

      <p class="sg-muted u-center">© <?= date('Y') ?> Whoizme · Design System Reference</p>
    </main>
  </div>

</div>

<script>
(()=>{
  // ===== Theme toggle =====
  const root = document.documentElement;
  const toggle = document.getElementById('themeToggle');
  const KEY = 'whoizme_theme';
  const saved = localStorage.getItem(KEY);
  if (saved === 'light' || saved === 'dark') {
    root.setAttribute('data-theme', saved);
  }
  if (toggle) {
    toggle.checked = (root.getAttribute('data-theme') === 'light');
    toggle.addEventListener('change', () => {
      const mode = toggle.checked ? 'light' : 'dark';
      root.setAttribute('data-theme', mode);
      try { localStorage.setItem(KEY, mode); } catch(e) {}
    });
  }

  // ===== Smooth scroll + active link (no # in URL) =====
  const nav = document.querySelector('.sg-nav');
  const links = nav ? Array.from(nav.querySelectorAll('a[href^="#"]')) : [];
  const sections = links.map(a => document.querySelector(a.getAttribute('href'))).filter(Boolean);

  const topbar = document.querySelector('.sg-topbar');
  function getOffset(){ return (topbar?.offsetHeight || 80) + 16; }
  function setActive(id){ links.forEach(a => a.classList.toggle('is-active', a.getAttribute('href') === '#' + id)); }
  function scrollToId(id){
    const el = document.getElementById(id);
    if (!el) return;
    const y = el.getBoundingClientRect().top + window.pageYOffset - getOffset();
    window.scrollTo({ top: y, behavior: 'smooth' });
    setActive(id);
    if (history.replaceState) history.replaceState(null, '', location.pathname);
  }
  links.forEach(a => {
    a.addEventListener('click', (e) => { e.preventDefault(); scrollToId(a.getAttribute('href').slice(1)); }, { passive: false });
  });
  let ticking = false;
  function onScroll(){
    if (ticking) return; ticking = true;
    requestAnimationFrame(() => {
      let current = sections[0]?.id; const offset = getOffset();
      for (const sec of sections){ if (sec.getBoundingClientRect().top - offset <= 0) current = sec.id; }
      if (current) setActive(current); ticking = false;
    });
  }
  document.addEventListener('scroll', onScroll, { passive: true });
  window.addEventListener('resize', onScroll, { passive: true });

  // Tabs
  const tabs = document.querySelector('[data-tabs]');
  if (tabs){
    const btns = Array.from(tabs.querySelectorAll('.tabs__tab'));
    const panels = Array.from(tabs.querySelectorAll('.tabs__panel'));
    btns.forEach(btn=>{
      btn.addEventListener('click', ()=>{
        const id = btn.getAttribute('data-tab');
        btns.forEach(b => b.classList.toggle('is-active', b===btn));
        panels.forEach(p => p.classList.toggle('is-active', p.getAttribute('data-panel')===id));
      });
    });
  }

  // Modal
  const modal = document.getElementById('demoModal');
  const openBtn = document.getElementById('openDemoModal');
  const closeBtn = document.getElementById('closeDemoModal');
  if (modal && openBtn){
    openBtn.addEventListener('click', ()=> modal.showModal());
    if (closeBtn) closeBtn.addEventListener('click', ()=> modal.close());
    modal.addEventListener('click', (e)=> { if (e.target === modal) modal.close(); });
  }

  // Tooltips (title fallback)
  document.addEventListener('mouseover', (e)=>{
    const t = e.target.closest('[data-tip]'); if (!t) return; t.setAttribute('title', t.getAttribute('data-tip'));
  }, {passive:true});

  if (location.hash) { const id = location.hash.slice(1); setTimeout(()=> scrollToId(id), 0); } else { onScroll(); }
})();
</script>
</body>
</html>
