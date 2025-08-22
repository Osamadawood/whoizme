<?php
// public/partials/app_topbar.php (refined)
// يعتمد فقط على $_SESSION بدون ما يغيّر أي شيء في بقية الصفحات

if (session_status() === PHP_SESSION_NONE) session_start();

// ====== Helpers ======
function wz_first_nonempty(...$vals) {
  foreach ($vals as $v) { if (isset($v) && trim((string)$v) !== '') return trim((string)$v); }
  return '';
}

function wz_current_user() {
    // نجمع أكبر عدد من المفاتيح المحتملة عشان حكاية حرف U
    $userArr = $_SESSION['user'] ?? [];
    $authArr = $_SESSION['auth'] ?? [];
    $profile = $_SESSION['profile'] ?? [];

    $id = wz_first_nonempty($userArr['id'] ?? null, $authArr['id'] ?? null, $profile['id'] ?? null);
    $name = wz_first_nonempty(
      $userArr['name'] ?? null,
      $authArr['name'] ?? null,
      $_SESSION['username'] ?? null,
      $userArr['full_name'] ?? null,
      $authArr['full_name'] ?? null,
      $userArr['display_name'] ?? null,
      $authArr['display_name'] ?? null,
      $userArr['username'] ?? null,
      $authArr['username'] ?? null
    );
    $email  = wz_first_nonempty($userArr['email'] ?? null, $authArr['email'] ?? null, $profile['email'] ?? null);
    $avatar = wz_first_nonempty($userArr['avatar'] ?? null, $authArr['avatar'] ?? null, $profile['avatar'] ?? null);

    return ['id'=>$id, 'name'=>$name, 'email'=>$email, 'avatar'=>$avatar];
}

function wz_initial_from_user(array $u): string {
    // أول حرف من الاسم (بعد إزالة مسافات)، fallback إلى الإيميل، ثم "U"
    $name = trim($u['name'] ?? '');
    if ($name !== '') {
        // لو الاسم مكون من كلمتين ناخد أول حرف من أول كلمة
        $parts = preg_split('/\s+/', $name);
        $first = $parts[0] ?? $name;
        return mb_strtoupper(mb_substr($first, 0, 1, 'UTF-8'), 'UTF-8');
    }
    $email = trim($u['email'] ?? '');
    if ($email !== '' && strpos($email, '@') !== false) {
        return strtoupper(substr($email, 0, 1));
    }
    return 'U';
}

function wz_avatar_tint_class(array $u): string {
    $seed = $u['id'] ?: ($u['email'] ?: ($u['name'] ?: 'seed'));
    $n = (abs(crc32((string)$seed)) % 6) + 1; // avatar--t1..t6 موجودة في SCSS
    return "avatar--t{$n}";
}

function wz_current_locale(): string {
    // لو فيه ?lang=ar|en غير السيشن وارجع لنفس العنوان بدون براميتر
    if (isset($_GET['lang'])) {
        $g = ($_GET['lang'] === 'ar') ? 'ar' : 'en';
        $_SESSION['lang'] = $g;
        $url = strtok($_SERVER['REQUEST_URI'], '?');
        header("Location: {$url}");
        exit;
    }
    return $_SESSION['lang'] ?? 'en';
}

function wz_other_locale(string $cur): string { return $cur === 'ar' ? 'en' : 'ar'; }

// ====== State ======
$user    = wz_current_user();
$initial = wz_initial_from_user($user);
$avatar  = trim($user['avatar'] ?? '');
$locale  = wz_current_locale();
$other   = wz_other_locale($locale);

// ====== Breadcrumb ======
$page_title  = $page_title  ?? 'Dashboard';
$breadcrumbs = $breadcrumbs ?? [];

// إذا الصفحة Dashboard لا نعرض "Home" إطلاقًا
if (strtolower($page_title) === 'dashboard') {
  $breadcrumbs = [['label' => 'Dashboard', 'url' => null]];
} elseif (empty($breadcrumbs)) {
  $breadcrumbs = [
    ['label' => 'Home', 'url' => '/dashboard'],
    ['label' => $page_title, 'url' => null]
  ];
} else {
  // تنظيف التكرار لو أول عنصر Home وبعدين Dashboard
  if (isset($breadcrumbs[0]['label']) && strtolower($breadcrumbs[0]['label']) === 'home'
      && isset($breadcrumbs[1]['label']) && strtolower($breadcrumbs[1]['label']) === 'dashboard') {
    $breadcrumbs = [['label' => 'Dashboard', 'url' => null]];
  }
}
?>

<div class="topbar--contained">
  <div class="app-topbar" role="banner">
    <div class="app-topbar__left">
      <nav class="breadcrumb" aria-label="Breadcrumb">
        <?php foreach ($breadcrumbs as $i => $bc): ?>
          <?php if ($i > 0): ?><span class="breadcrumb__sep" aria-hidden="true"><i class="fi fi-rr-angle-small-right"></i></span><?php endif; ?>
          <?php if (!empty($bc['url'])): ?>
            <a class="breadcrumb__item" href="<?= htmlspecialchars($bc['url']) ?>"><?= htmlspecialchars($bc['label']) ?></a>
          <?php else: ?>
            <span class="breadcrumb__item is-current"><?= htmlspecialchars($bc['label']) ?></span>
          <?php endif; ?>
        <?php endforeach; ?>
      </nav>
    </div>

    <div class="app-topbar__right">
      <form class="topbar-search" action="/search" method="get" role="search">
        <input class="topbar-search__input" type="search" name="q" placeholder="Search links &amp; QR..." />
        <button class="topbar-search__btn" type="button" aria-hidden="true" tabindex="-1">
          <i class="fi fi-rr-search" aria-hidden="true"></i>
        </button>
      </form>

      <!-- Create new (Modal) -->
      <button type="button" class="btn btn--primary topbar__create" data-action="open-create">
        <i class="fi fi-rr-plus" aria-hidden="true"></i>
        <span>Create new</span>
      </button>

      <!-- Theme toggle (LocalStorage) -->
      <!-- <button type="button" class="theme-toggle" id="themeToggle" aria-label="Toggle theme" aria-pressed="false">
        <span class="theme-toggle__icon"></span>
      </button> -->
      <button type="button" class="theme-toggle theme-toggle--pill" id="themeToggle" aria-label="Toggle theme" aria-pressed="false">
        <span class="tt__track" aria-hidden="true">
          <span class="tt__icon tt__icon--sun">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <circle cx="12" cy="12" r="4"/>
              <path d="M12 2v3M12 19v3M4.22 4.22 6.34 6.34M17.66 17.66 19.78 19.78M2 12h3M19 12h3M4.22 19.78 6.34 17.66M17.66 6.34 19.78 4.22"/>
            </svg>
          </span>
          <span class="tt__icon tt__icon--moon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
            </svg>
          </span>
        </span>
      </button>

      <!-- Language pill: بتعرض اللغة الأخرى فقط -->
      <!-- <a class="lang-pill" href="?lang=<?= $other ?>" title="Switch language">
        <?= strtoupper($other) ?>
      </a> -->

      <!-- Account -->
      <div class="account account--topbar" id="accountArea">
        <button type="button" class="avatar <?= $avatar ? '' : 'avatar--initial ' . wz_avatar_tint_class($user) ?>" id="accountBtn" aria-label="Account menu" aria-expanded="false">
          <?php if ($avatar): ?>
            <img src="<?= htmlspecialchars($avatar) ?>" alt="Account avatar">
          <?php else: ?>
            <span><?= htmlspecialchars($initial) ?></span>
          <?php endif; ?>
        </button>

        <div class="account__menu" id="accountMenu" role="menu" aria-hidden="true" hidden>
          <a href="/profile" role="menuitem">
            <i class="fi fi-rr-user" aria-hidden="true"></i>
            <span>View profile</span>
          </a>

          <a href="/settings" role="menuitem">
            <i class="fi fi-rr-settings" aria-hidden="true"></i>
            <span>Settings</span>
          </a>

          <hr class="account__menu-divider" aria-hidden="true">

          <a href="/help/faqs" role="menuitem">
            <!-- <i class="fi fi-rr-info" aria-hidden="true"></i> -->
            <i class="fi fi-rr-interrogation" aria-hidden="true"></i>
            <span>FAQs</span>
          </a>

          <a href="/support" role="menuitem">
            <i class="fi fi-rr-headset" aria-hidden="true"></i>
            <span>Support</span>
          </a>

          <!-- <a href="/logout.php" class="danger" role="menuitem">
            <i class="fi fi-rr-sign-out-alt" aria-hidden="true"></i>
            <span>Log out</span>
          </a> -->

          <?php
            $WZ_CSRF = function_exists('csrf_token') ? csrf_token() : ($_SESSION['csrf_token'] ?? '');
          ?>
          <form action="/logout" class="danger" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($WZ_CSRF, ENT_QUOTES); ?>">
            <i class="fi fi-rr-sign-out-alt" aria-hidden="true"></i>
            <button type="submit" class="dropdown__item">Logout</button>
          </form>
        </div>
        
      </div>
      
    </div>
  </div>
</div>

<!-- Quick Create Modal (Whoizme DS) -->
<div id="createModal" class="create-modal" role="dialog" aria-modal="true" aria-labelledby="createTitle" aria-hidden="true" hidden>
  <div class="create-modal__backdrop" data-close="modal"></div>
  <div class="create-modal__panel create-modal__dialog" role="document">
    <div class="create-modal__header">
      <h3 id="createTitle" class="create-modal__title">What do you want to create?</h3>
      <button type="button" class="create-modal__close" data-close="modal" aria-label="Close">×</button>
    </div>
    <div class="create-modal__grid">
      <a class="create-card" href="/links/create.php">
        <i class="create-card__icon fi fi-rr-link" aria-hidden="true"></i>
        <span class="create-card__title">Shorten a link</span>
        <span class="create-card__kbd">L</span>
      </a>
      <a class="create-card" href="/qr/create.php">
        <i class="create-card__icon fi fi-rr-qrcode" aria-hidden="true"></i>
        <span class="create-card__title">Create a QR Code</span>
        <span class="create-card__kbd">Q</span>
      </a>
      <a class="create-card" href="/templates/create.php">
        <i class="create-card__icon fi fi-rr-browser" aria-hidden="true"></i>
        <span class="create-card__title">Build a landing page</span>
        <span class="create-card__kbd">P</span>
      </a>
    </div>
  </div>
</div>

<script>
// ===== Theme toggle + dir/lang + menus + modal =====
(function () {
  const root = document.documentElement;

  // lang/dir sync
  root.setAttribute('lang', '<?= $locale ?>');
  root.setAttribute('dir', '<?= $locale === 'ar' ? 'rtl' : 'ltr' ?>');

  // Dashboard breadcrumb guard (client-side fallback only)
  try{
    const bc = document.querySelector('.breadcrumb');
    if (bc && <?= json_encode(strtolower($page_title) === 'dashboard') ?>) {
      const links = bc.querySelectorAll('.breadcrumb__item');
      if (links.length > 1) {
        bc.innerHTML = '<span class="breadcrumb__item is-current">Dashboard</span>';
      }
    }
  } catch(_) {}

  // Theme toggle
  const btn  = document.getElementById('themeToggle');
  const key  = 'whoiz.theme';
  const setTheme = (t) => {
    root.setAttribute('data-theme', t);
    btn?.classList.toggle('is-on', t === 'dark');
    btn?.setAttribute('aria-pressed', t === 'dark' ? 'true' : 'false');
    try { localStorage.setItem(key, t); } catch(_) {}
  };
  const saved = (function(){
    try { return localStorage.getItem(key); } catch(_) { return null; }
  })() || (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
  setTheme(saved);
  btn && btn.addEventListener('click', () => setTheme(root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark'));

  // ===== Account dropdown (اجعل كل مساحة الأڤاتار قابلة للنقر) =====
  const accArea = document.getElementById('accountArea');   // <div class="account" id="accountArea">
  const aBtn    = document.getElementById('accountBtn');    // زر الأڤاتار داخلها
  const menu    = document.getElementById('accountMenu');

  function closeAccount(){
    if (!menu) return;
    menu.classList.remove('is-open');
    aBtn?.setAttribute('aria-expanded', 'false');
    menu.setAttribute('aria-hidden', 'true');
    menu.hidden = true;
  }
  function openAccount(){
    if (!menu) return;
    menu.classList.add('is-open');
    aBtn?.setAttribute('aria-expanded', 'true');
    menu.setAttribute('aria-hidden', 'false');
    menu.hidden = false;
  }
  function toggleAccount(){
    if (!menu) return;
    const isOpen = menu.classList.contains('is-open');
    isOpen ? closeAccount() : openAccount();
  }

  // خلي الكليك على أي نقطة داخل الـavatar يشتغل
  accArea && accArea.addEventListener('click', (e) => {
    if (aBtn && aBtn.contains(e.target)) {
      e.preventDefault();
      toggleAccount();
    }
  });

  // اقفل عند الكليك خارج مساحة الحساب كلها (مش بس الزر)
  document.addEventListener('click', (e) => {
    if (accArea && !accArea.contains(e.target)) closeAccount();
  });

  // اقفل بالـ Escape
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeAccount(); });

  // ===== Create modal =====
  const openBtn = document.querySelector('[data-action="open-create"]');
  const modal   = document.getElementById('createModal');
  const backdrop = modal ? modal.querySelector('.create-modal__backdrop') : null;
  const closeEls = modal ? modal.querySelectorAll('[data-close="modal"]') : null;

  function openModal(){
    if (!modal) return;
    modal.classList.add('is-open');
    modal.hidden = false;
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('no-scroll');
  }
  function closeModal(){
    if (!modal) return;
    modal.classList.remove('is-open');
    modal.hidden = true;
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('no-scroll');
  }

  openBtn && openBtn.addEventListener('click', (e)=>{ e.preventDefault(); openModal(); });
  backdrop && backdrop.addEventListener('click', closeModal);
  closeEls && closeEls.forEach(el => el.addEventListener('click', (e)=>{ e.preventDefault(); closeModal(); }));
  document.addEventListener('keydown', (e)=>{ if(e.key === 'Escape') closeModal(); });
})();
</script>