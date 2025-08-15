<?php require __DIR__ . "/../_bootstrap.php"; ?>
<?php
require __DIR__ . '/../../includes/admin_auth.php';
require __DIR__ . '/../../includes/admin_header.php';

// flash helpers
$flash = function(string $key) {
  if (!empty($_SESSION[$key])) { $m = $_SESSION[$key]; unset($_SESSION[$key]); return $m; }
  return null;
};

// roles
$role = $_SESSION['admin_role'] ?? 'viewer';
$can_edit = in_array($role, ['manager','super'], true);

// CSRF
if (empty($_SESSION['csrf_admin'])) {
  $_SESSION['csrf_admin'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf_admin'];

function get_setting(PDO $pdo, string $key, string $default='') {
  $st = $pdo->prepare("SELECT v FROM settings WHERE k=?");
  $st->execute([$key]);
  $val = $st->fetchColumn();
  return $val !== false ? (string)$val : $default;
}

/** حفظ مجموعة مفاتيح بدون مسح الباقي */
function save_settings(PDO $pdo, array $items): void {
  if (!$items) return;
  $pdo->beginTransaction();
  try {
    $up = $pdo->prepare("INSERT INTO settings (k,v) VALUES (?,?) ON DUPLICATE KEY UPDATE v=VALUES(v)");
    foreach ($items as $k=>$v) {
      $up->execute([$k, $v]);
    }
    $pdo->commit();
  } catch (Throwable $e) {
    $pdo->rollBack();
    throw $e;
  }
}

/** تحميل كل القيم الحالية */
function load_current(PDO $pdo): array {
  return [
    // Branding
    'site_name'            => get_setting($pdo, 'site_name', 'whoiz.me'),
    'brand_url'            => get_setting($pdo, 'brand_url', 'https://whoiz.me'),
    'logo_url'             => get_setting($pdo, 'logo_url', ''),
    'favicon_url'          => get_setting($pdo, 'favicon_url', ''),
    'primary_color'        => get_setting($pdo, 'primary_color', '#0d6efd'),
    'timezone'             => get_setting($pdo, 'timezone', 'Africa/Cairo'),

    // Emails (basic; SMTP to be wired later)
    'from_email'           => get_setting($pdo, 'from_email', 'no-reply@whoiz.me'),
    'support_email'        => get_setting($pdo, 'support_email', 'support@whoiz.me'),
    'smtp_host'            => get_setting($pdo, 'smtp_host', ''),
    'smtp_port'            => get_setting($pdo, 'smtp_port', ''),
    'smtp_user'            => get_setting($pdo, 'smtp_user', ''),
    'smtp_secure'          => get_setting($pdo, 'smtp_secure', 'tls'), // tls|ssl|none

    // Users & Links
    'allow_registration'   => get_setting($pdo, 'allow_registration', '1'),
    'default_user_active'  => get_setting($pdo, 'default_user_active', '1'),
    'default_link_domain'  => get_setting($pdo, 'default_link_domain', 'whoiz.me'),

    // QR & Redirects
    'redirect_type'        => get_setting($pdo, 'redirect_type', '302'), // 301/302
    'qr_ec_level'          => get_setting($pdo, 'qr_ec_level', 'M'),     // L/M/Q/H
    'qr_size'              => get_setting($pdo, 'qr_size', '256'),       // px
    'qr_margin'            => get_setting($pdo, 'qr_margin', '2'),       // modules

    // Security
    'session_timeout'      => get_setting($pdo, 'session_timeout', '60'), // minutes
    'login_rate_limit'     => get_setting($pdo, 'login_rate_limit', '5'), // attempts per 15m (placeholder)

    // Uploads
    'uploads_convert_webp' => get_setting($pdo, 'uploads_convert_webp', '1'),
    'uploads_webp_quality' => get_setting($pdo, 'uploads_webp_quality', '86'),
    'max_upload_mb'        => get_setting($pdo, 'max_upload_mb', '10'),

    // Analytics
    'analytics_enabled'    => get_setting($pdo, 'analytics_enabled', '1'),
    'anonymize_ip'         => get_setting($pdo, 'anonymize_ip', '1'),
    'retention_days'       => get_setting($pdo, 'retention_days', '365'),
  ];
}

$ok = $err = null;

// --- معالجة الحفظ ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!$can_edit) {
    $err = 'You do not have permission to edit settings.';
  } elseif (!hash_equals($csrf, $_POST['csrf'] ?? '')) {
    $err = 'Invalid CSRF token.';
  } else {
    $scope = $_POST['scope'] ?? 'branding'; // tab scope
    $pdo   = $db->pdo();
    $cur   = load_current($pdo);
    $updates = [];

    // مسارات الرفع تحت public
    $publicDir = realpath(__DIR__.'/../');
    $uploadDir = $publicDir . '/uploads/branding';
    if (!is_dir($uploadDir)) { @mkdir($uploadDir, 0775, true); }

    $isValidColor = function(string $c): bool {
      return (bool)preg_match('/^#?[0-9a-fA-F]{3}([0-9a-fA-F]{3})?$/', $c);
    };

    $saveImageWebp = function(string $tmp, string $prefix) use ($uploadDir): ?string {
      $info = @getimagesize($tmp);
      if (!$info) return null;
      $mime = $info['mime'] ?? '';
      $fn   = $prefix . '-' . time();
      $dst  = $uploadDir . '/' . $fn;

      if ($mime === 'image/jpeg' || $mime === 'image/png' || $mime === 'image/webp') {
        if (function_exists('imagewebp')) {
          if ($mime === 'image/jpeg' && function_exists('imagecreatefromjpeg')) {
            $im = @imagecreatefromjpeg($tmp);
          } elseif ($mime === 'image/png' && function_exists('imagecreatefrompng')) {
            $im = @imagecreatefrompng($tmp); if ($im) @imagepalettetotruecolor($im);
          } elseif ($mime === 'image/webp' && function_exists('imagecreatefromwebp')) {
            $im = @imagecreatefromwebp($tmp);
          } else { $im = null; }
          if ($im) {
            $dstPath = $dst . '.webp';
            @imagewebp($im, $dstPath, 86);
            @chmod($dstPath, 0644);
            @imagedestroy($im);
            return '/uploads/branding/' . basename($dstPath);
          }
        }
        // fallback copy
        $ext = $mime === 'image/png' ? '.png' : ($mime === 'image/webp' ? '.webp' : '.jpg');
        $dstPath = $dst . $ext; @copy($tmp, $dstPath); @chmod($dstPath, 0644);
        return '/uploads/branding/' . basename($dstPath);
      }
      if ($mime === 'image/x-icon' || $mime === 'image/vnd.microsoft.icon') {
        $dstPath = $dst . '.ico'; @copy($tmp, $dstPath); @chmod($dstPath, 0644);
        return '/uploads/branding/' . basename($dstPath);
      }
      return null;
    };

    try {
      // أزرار الإزالة العالمية (branding remove)
      if (($__act = $_POST['action'] ?? '') === 'remove_logo') {
        $updates['logo_url'] = '';
        $_SESSION['admin_ok'] = 'Logo removed.';
        save_settings($pdo, $updates); header('Location: /admin/settings.php#branding'); exit;
      }
      if (($__act = $_POST['action'] ?? '') === 'remove_favicon') {
        $updates['favicon_url'] = '';
        $_SESSION['admin_ok'] = 'Favicon removed.';
        save_settings($pdo, $updates); header('Location: /admin/settings.php#branding'); exit;
      }

      if ($scope === 'branding') {
        $site_name     = trim((string)($_POST['site_name'] ?? $cur['site_name']));
        $brand_url     = trim((string)($_POST['brand_url'] ?? $cur['brand_url']));
        $primary_color = trim((string)($_POST['primary_color'] ?? ltrim($cur['primary_color'], '#')));
        $timezone      = trim((string)($_POST['timezone'] ?? $cur['timezone']));

        if ($site_name === '') throw new RuntimeException('Site name is required.');
        if ($brand_url !== '' && !filter_var($brand_url, FILTER_VALIDATE_URL)) throw new RuntimeException('Invalid Brand URL.');
        if ($primary_color !== '' && !$isValidColor($primary_color)) throw new RuntimeException('Invalid color value. Use HEX like #0d6efd.');
        if ($timezone !== '') {
          $tzlist = timezone_identifiers_list();
          if (!in_array($timezone, $tzlist, true)) throw new RuntimeException('Invalid timezone.');
        }
        if ($primary_color !== '' && $primary_color[0] !== '#') $primary_color = '#'.$primary_color;

        $updates['site_name']     = $site_name;
        $updates['brand_url']     = $brand_url;
        $updates['primary_color'] = $primary_color;
        $updates['timezone']      = $timezone;

        if (isset($_FILES['logo_file']) && ($_FILES['logo_file']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
          $p = $saveImageWebp($_FILES['logo_file']['tmp_name'], 'logo'); if ($p) $updates['logo_url'] = $p;
        }
        if (isset($_FILES['favicon_file']) && ($_FILES['favicon_file']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
          $p = $saveImageWebp($_FILES['favicon_file']['tmp_name'], 'favicon'); if ($p) $updates['favicon_url'] = $p;
        }

      } elseif ($scope === 'emails') {
        $from_email    = trim((string)($_POST['from_email'] ?? $cur['from_email']));
        $support_email = trim((string)($_POST['support_email'] ?? $cur['support_email']));
        $smtp_host     = trim((string)($_POST['smtp_host'] ?? $cur['smtp_host']));
        $smtp_port     = trim((string)($_POST['smtp_port'] ?? $cur['smtp_port']));
        $smtp_user     = trim((string)($_POST['smtp_user'] ?? $cur['smtp_user']));
        $smtp_secure   = in_array($_POST['smtp_secure'] ?? $cur['smtp_secure'], ['tls','ssl','none'], true) ? ($_POST['smtp_secure'] ?? $cur['smtp_secure']) : 'tls';

        if ($from_email !== '' && !filter_var($from_email, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Invalid From email.');
        if ($support_email !== '' && !filter_var($support_email, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Invalid Support email.');
        if ($smtp_port !== '' && !preg_match('/^\d+$/', $smtp_port)) throw new RuntimeException('SMTP port must be numeric.');

        $updates['from_email']    = $from_email;
        $updates['support_email'] = $support_email;
        $updates['smtp_host']     = $smtp_host;
        $updates['smtp_port']     = $smtp_port;
        $updates['smtp_user']     = $smtp_user;
        $updates['smtp_secure']   = $smtp_secure;

      } elseif ($scope === 'users_links') {
        $allow_registration  = isset($_POST['allow_registration']) ? '1' : '0';
        $default_user_active = isset($_POST['default_user_active']) ? '1' : '0';
        $default_link_domain = trim((string)($_POST['default_link_domain'] ?? $cur['default_link_domain']));
        if ($default_link_domain !== '' && !preg_match('/^[a-z0-9.-]+$/i', $default_link_domain)) throw new RuntimeException('Invalid default link domain.');
        $updates['allow_registration']  = $allow_registration;
        $updates['default_user_active'] = $default_user_active;
        $updates['default_link_domain'] = $default_link_domain;

      } elseif ($scope === 'qr') {
        $redirect_type = in_array(($_POST['redirect_type'] ?? $cur['redirect_type']), ['301','302'], true) ? $_POST['redirect_type'] : '302';
        $qr_ec_level   = in_array(($_POST['qr_ec_level'] ?? $cur['qr_ec_level']), ['L','M','Q','H'], true) ? $_POST['qr_ec_level'] : 'M';
        $qr_size       = (string)max(128, min(1024, (int)($_POST['qr_size'] ?? $cur['qr_size'])));
        $qr_margin     = (string)max(0, min(16, (int)($_POST['qr_margin'] ?? $cur['qr_margin'])));
        $updates['redirect_type'] = $redirect_type;
        $updates['qr_ec_level']   = $qr_ec_level;
        $updates['qr_size']       = $qr_size;
        $updates['qr_margin']     = $qr_margin;

      } elseif ($scope === 'security') {
        $session_timeout  = (string)max(5, min(1440, (int)($_POST['session_timeout'] ?? $cur['session_timeout'])));
        $login_rate_limit = (string)max(3, min(50,   (int)($_POST['login_rate_limit'] ?? $cur['login_rate_limit'])));
        $updates['session_timeout']  = $session_timeout;
        $updates['login_rate_limit'] = $login_rate_limit;

      } elseif ($scope === 'uploads') {
        $uploads_convert_webp = isset($_POST['uploads_convert_webp']) ? '1' : '0';
        $uploads_webp_quality = (string)max(50, min(100, (int)($_POST['uploads_webp_quality'] ?? $cur['uploads_webp_quality'])));
        $max_upload_mb        = (string)max(1,  min(100, (int)($_POST['max_upload_mb'] ?? $cur['max_upload_mb'])));
        $updates['uploads_convert_webp'] = $uploads_convert_webp;
        $updates['uploads_webp_quality'] = $uploads_webp_quality;
        $updates['max_upload_mb']        = $max_upload_mb;

      } elseif ($scope === 'analytics') {
        $analytics_enabled = isset($_POST['analytics_enabled']) ? '1' : '0';
        $anonymize_ip      = isset($_POST['anonymize_ip']) ? '1' : '0';
        $retention_days    = (string)max(30, min(3650, (int)($_POST['retention_days'] ?? $cur['retention_days'])));
        $updates['analytics_enabled'] = $analytics_enabled;
        $updates['anonymize_ip']      = $anonymize_ip;
        $updates['retention_days']    = $retention_days;
      }

      save_settings($pdo, $updates);
      $_SESSION['admin_ok'] = 'Settings saved.';
      header('Location: /admin/settings.php#'.$scope);
      exit;

    } catch (Throwable $e) {
      $err = $e->getMessage();
    }
  }
}

// تحميل القيم للعرض
$cur = load_current($db->pdo());
extract($cur, EXTR_OVERWRITE);

// helpers for checked/selected
$checked = fn(bool $b) => $b ? 'checked' : '';
$sel = fn(string $a, string $b) => ($a===$b ? 'selected' : '');
?>
<div class="d-flex align-items-center justify-content-between mb-3">
  <h2 class="mb-0">Settings</h2>
  <a class="btn btn-outline-secondary" href="/admin/dashboard.php">← Back to Dashboard</a>
</div>

<?php if ($m = $flash('admin_ok')): ?>
  <div class="alert alert-success"><?= htmlspecialchars($m) ?></div>
<?php endif; ?>
<?php if ($err): ?><div class="alert alert-danger"><?= htmlspecialchars($err) ?></div><?php endif; ?>

<!-- Tabs -->
<ul class="nav nav-tabs" id="settingsTabs" role="tablist">
  <li class="nav-item" role="presentation"><button class="nav-link active" id="branding-tab" data-bs-toggle="tab" data-bs-target="#branding" type="button" role="tab">Branding</button></li>
  <li class="nav-item" role="presentation"><button class="nav-link" id="qr-tab" data-bs-toggle="tab" data-bs-target="#qr" type="button" role="tab">QR & Redirects</button></li>
  <li class="nav-item" role="presentation"><button class="nav-link" id="emails-tab" data-bs-toggle="tab" data-bs-target="#emails" type="button" role="tab">Emails</button></li>
  <li class="nav-item" role="presentation"><button class="nav-link" id="users-links-tab" data-bs-toggle="tab" data-bs-target="#users_links" type="button" role="tab">Users & Links</button></li>
  <li class="nav-item" role="presentation"><button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab">Security</button></li>
  <li class="nav-item" role="presentation"><button class="nav-link" id="uploads-tab" data-bs-toggle="tab" data-bs-target="#uploads" type="button" role="tab">Uploads</button></li>
  <li class="nav-item" role="presentation"><button class="nav-link" id="analytics-tab" data-bs-toggle="tab" data-bs-target="#analytics" type="button" role="tab">Analytics</button></li>
</ul>

<div class="tab-content pt-3">
  <!-- BRANDING -->
  <div class="tab-pane fade show active" id="branding" role="tabpanel">
    <div class="row g-3">
      <div class="col-12 col-xl-8">
        <div class="card border-0 shadow-sm mb-3">
          <div class="card-header bg-light fw-semibold">Branding</div>
          <div class="card-body">
            <form method="post" enctype="multipart/form-data" class="row g-3">
              <input type="hidden" name="csrf"  value="<?= $csrf ?>">
              <input type="hidden" name="scope" value="branding">

              <div class="col-12 col-md-6">
                <label class="form-label">Site name</label>
                <input type="text" name="site_name" class="form-control" value="<?= htmlspecialchars($site_name) ?>" required <?= $can_edit?'':'disabled' ?>>
              </div>
              <div class="col-12 col-md-6">
                <label class="form-label">Brand URL</label>
                <input type="url" name="brand_url" class="form-control" value="<?= htmlspecialchars($brand_url) ?>" placeholder="https://..." <?= $can_edit?'':'disabled' ?>>
              </div>

              <div class="col-12 col-md-8">
                <label class="form-label">Logo file</label>
                <input type="file" name="logo_file" class="form-control" accept="image/png,image/jpeg,image/webp" <?= $can_edit?'':'disabled' ?>>
                <?php if ($logo_url): ?><div class="form-text">Current: <code><?= htmlspecialchars($logo_url) ?></code></div><?php endif; ?>
              </div>
              <div class="col-12 col-md-4 d-flex align-items-end gap-2">
                <?php if ($logo_url): ?>
                  <img src="<?= htmlspecialchars($logo_url) ?>" alt="logo" class="img-fluid border rounded" style="max-height:48px">
                  <form method="post" onsubmit="return confirm('Remove logo?')">
                    <input type="hidden" name="csrf" value="<?= $csrf ?>">
                    <input type="hidden" name="action" value="remove_logo">
                    <button class="btn btn-outline-danger btn-sm" <?= $can_edit?'':'disabled' ?>>Remove</button>
                  </form>
                <?php else: ?>
                  <div class="text-muted small">No logo</div>
                <?php endif; ?>
              </div>

              <div class="col-12 col-md-8">
                <label class="form-label">Favicon file</label>
                <input type="file" name="favicon_file" class="form-control" accept="image/x-icon,image/png,image/jpeg,image/webp" <?= $can_edit?'':'disabled' ?>>
                <?php if ($favicon_url): ?><div class="form-text">Current: <code><?= htmlspecialchars($favicon_url) ?></code></div><?php endif; ?>
              </div>
              <div class="col-12 col-md-4 d-flex align-items-end gap-2">
                <?php if ($favicon_url): ?>
                  <img src="<?= htmlspecialchars($favicon_url) ?>" alt="favicon" class="img-fluid border rounded" style="max-height:32px">
                  <form method="post" onsubmit="return confirm('Remove favicon?')">
                    <input type="hidden" name="csrf" value="<?= $csrf ?>">
                    <input type="hidden" name="action" value="remove_favicon">
                    <button class="btn btn-outline-danger btn-sm" <?= $can_edit?'':'disabled' ?>>Remove</button>
                  </form>
                <?php else: ?>
                  <div class="text-muted small">No favicon</div>
                <?php endif; ?>
              </div>

              <div class="col-12 col-md-4">
                <label class="form-label">Primary color</label>
                <div class="input-group">
                  <span class="input-group-text">#</span>
                  <input type="text" name="primary_color" class="form-control" value="<?= htmlspecialchars(ltrim($primary_color,'#')) ?>" placeholder="0d6efd" <?= $can_edit?'':'disabled' ?>>
                  <span class="input-group-text" style="background: <?= htmlspecialchars($primary_color) ?>; width: 32px;"></span>
                </div>
                <div class="form-text">HEX color, e.g. #0d6efd</div>
              </div>

              <div class="col-12 col-md-4">
                <label class="form-label">Timezone</label>
                <input type="text" name="timezone" class="form-control" value="<?= htmlspecialchars($timezone) ?>" placeholder="Africa/Cairo" <?= $can_edit?'':'disabled' ?>>
              </div>

              <div class="col-12">
                <button class="btn btn-primary" <?= $can_edit?'':'disabled' ?>>Save</button>
                <?php if (!$can_edit): ?><span class="text-muted ms-2">Read‑only for your role.</span><?php endif; ?>
              </div>
            </form>
          </div>
        </div>
      </div>

      <div class="col-12 col-xl-4">
        <div class="card border-0 shadow-sm mb-3">
          <div class="card-header bg-light fw-semibold">Preview</div>
          <div class="card-body">
            <div class="d-flex align-items-center gap-3 mb-3">
              <div style="width:48px;height:48px;border-radius:8px;background: <?= htmlspecialchars($primary_color) ?>;"></div>
              <div>
                <div class="fw-semibold mb-1" style="line-height:1"><?= htmlspecialchars($site_name) ?></div>
                <div class="text-muted small">Brand URL: <?= htmlspecialchars($brand_url) ?></div>
              </div>
            </div>
            <div class="d-flex align-items-center gap-2">
              <?php if ($logo_url): ?><img src="<?= htmlspecialchars($logo_url) ?>" alt="logo" style="max-height:40px" class="border rounded"><?php endif; ?>
              <?php if ($favicon_url): ?><img src="<?= htmlspecialchars($favicon_url) ?>" alt="favicon" style="max-height:24px" class="border rounded"><?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- QR & Redirects -->
  <div class="tab-pane fade" id="qr" role="tabpanel">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-light fw-semibold">Defaults</div>
      <div class="card-body">
        <form method="post" class="row g-3">
          <input type="hidden" name="csrf" value="<?= $csrf ?>">
          <input type="hidden" name="scope" value="qr">

          <div class="col-12 col-md-4">
            <label class="form-label">Redirect type</label>
            <select name="redirect_type" class="form-select">
              <option value="302" <?= $sel($redirect_type,'302') ?>>302 (Temporary)</option>
              <option value="301" <?= $sel($redirect_type,'301') ?>>301 (Permanent)</option>
            </select>
            <div class="form-text">يؤثر على SEO والمتصفحات.</div>
          </div>

          <div class="col-12 col-md-4">
            <label class="form-label">QR error correction</label>
            <select name="qr_ec_level" class="form-select">
              <option value="L" <?= $sel($qr_ec_level,'L') ?>>L (7%)</option>
              <option value="M" <?= $sel($qr_ec_level,'M') ?>>M (15%)</option>
              <option value="Q" <?= $sel($qr_ec_level,'Q') ?>>Q (25%)</option>
              <option value="H" <?= $sel($qr_ec_level,'H') ?>>H (30%)</option>
            </select>
          </div>

          <div class="col-12 col-md-2">
            <label class="form-label">Size (px)</label>
            <input type="number" name="qr_size" class="form-control" value="<?= htmlspecialchars($qr_size) ?>" min="128" max="1024">
          </div>
          <div class="col-12 col-md-2">
            <label class="form-label">Margin</label>
            <input type="number" name="qr_margin" class="form-control" value="<?= htmlspecialchars($qr_margin) ?>" min="0" max="16">
          </div>

          <div class="col-12">
            <button class="btn btn-primary">Save</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Emails -->
  <div class="tab-pane fade" id="emails" role="tabpanel">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-light fw-semibold">Emails & SMTP</div>
      <div class="card-body">
        <form method="post" class="row g-3">
          <input type="hidden" name="csrf" value="<?= $csrf ?>">
          <input type="hidden" name="scope" value="emails">

          <div class="col-12 col-md-6">
            <label class="form-label">From email (system)</label>
            <input type="email" name="from_email" class="form-control" value="<?= htmlspecialchars($from_email) ?>">
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label">Support email</label>
            <input type="email" name="support_email" class="form-control" value="<?= htmlspecialchars($support_email) ?>">
          </div>

          <div class="col-12 col-md-4">
            <label class="form-label">SMTP host</label>
            <input type="text" name="smtp_host" class="form-control" value="<?= htmlspecialchars($smtp_host) ?>" placeholder="smtp.example.com">
          </div>
          <div class="col-12 col-md-2">
            <label class="form-label">Port</label>
            <input type="number" name="smtp_port" class="form-control" value="<?= htmlspecialchars($smtp_port) ?>" placeholder="587">
          </div>
          <div class="col-12 col-md-3">
            <label class="form-label">Security</label>
            <select name="smtp_secure" class="form-select">
              <option value="tls"  <?= $sel($smtp_secure,'tls')  ?>>TLS</option>
              <option value="ssl"  <?= $sel($smtp_secure,'ssl')  ?>>SSL</option>
              <option value="none" <?= $sel($smtp_secure,'none') ?>>None</option>
            </select>
          </div>
          <div class="col-12 col-md-3">
            <label class="form-label">SMTP user</label>
            <input type="text" name="smtp_user" class="form-control" value="<?= htmlspecialchars($smtp_user) ?>" placeholder="username">
          </div>

          <div class="col-12">
            <button class="btn btn-primary">Save</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Users & Links -->
  <div class="tab-pane fade" id="users_links" role="tabpanel">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-light fw-semibold">Users & Links</div>
      <div class="card-body">
        <form method="post" class="row g-3">
          <input type="hidden" name="csrf" value="<?= $csrf ?>">
          <input type="hidden" name="scope" value="users_links">

          <div class="col-12 col-md-6">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="allow_registration" name="allow_registration" value="1" <?= $allow_registration==='1'?'checked':'' ?>>
              <label class="form-check-label" for="allow_registration">Allow user self‑registration</label>
            </div>
          </div>
          <div class="col-12 col-md-6">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="default_user_active" name="default_user_active" value="1" <?= $default_user_active==='1'?'checked':'' ?>>
              <label class="form-check-label" for="default_user_active">New users are active by default</label>
            </div>
          </div>

          <div class="col-12 col-md-6">
            <label class="form-label">Default link domain</label>
            <input type="text" name="default_link_domain" class="form-control" value="<?= htmlspecialchars($default_link_domain) ?>" placeholder="whoiz.me">
          </div>

          <div class="col-12">
            <button class="btn btn-primary">Save</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Security -->
  <div class="tab-pane fade" id="security" role="tabpanel">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-light fw-semibold">Security</div>
      <div class="card-body">
        <form method="post" class="row g-3">
          <input type="hidden" name="csrf" value="<?= $csrf ?>">
          <input type="hidden" name="scope" value="security">

          <div class="col-12 col-md-4">
            <label class="form-label">Session timeout (minutes)</label>
            <input type="number" name="session_timeout" class="form-control" value="<?= htmlspecialchars($session_timeout) ?>" min="5" max="1440">
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label">Login rate limit (attempts / 15m)</label>
            <input type="number" name="login_rate_limit" class="form-control" value="<?= htmlspecialchars($login_rate_limit) ?>" min="3" max="50">
          </div>

          <div class="col-12">
            <button class="btn btn-primary">Save</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Uploads -->
  <div class="tab-pane fade" id="uploads" role="tabpanel">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-light fw-semibold">Uploads</div>
      <div class="card-body">
        <form method="post" class="row g-3">
          <input type="hidden" name="csrf" value="<?= $csrf ?>">
          <input type="hidden" name="scope" value="uploads">

          <div class="col-12 col-md-4">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="uploads_convert_webp" name="uploads_convert_webp" value="1" <?= $uploads_convert_webp==='1'?'checked':'' ?>>
              <label class="form-check-label" for="uploads_convert_webp">Convert images to WebP</label>
            </div>
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label">WebP quality (50–100)</label>
            <input type="number" name="uploads_webp_quality" class="form-control" value="<?= htmlspecialchars($uploads_webp_quality) ?>" min="50" max="100">
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label">Max upload (MB)</label>
            <input type="number" name="max_upload_mb" class="form-control" value="<?= htmlspecialchars($max_upload_mb) ?>" min="1" max="100">
          </div>

          <div class="col-12">
            <button class="btn btn-primary">Save</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Analytics -->
  <div class="tab-pane fade" id="analytics" role="tabpanel">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-light fw-semibold">Analytics</div>
      <div class="card-body">
        <form method="post" class="row g-3">
          <input type="hidden" name="csrf" value="<?= $csrf ?>">
          <input type="hidden" name="scope" value="analytics">

          <div class="col-12 col-md-4">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="analytics_enabled" name="analytics_enabled" value="1" <?= $analytics_enabled==='1'?'checked':'' ?>>
              <label class="form-check-label" for="analytics_enabled">Enable analytics</label>
            </div>
          </div>
          <div class="col-12 col-md-4">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="anonymize_ip" name="anonymize_ip" value="1" <?= $anonymize_ip==='1'?'checked':'' ?>>
              <label class="form-check-label" for="anonymize_ip">Anonymize IPs</label>
            </div>
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label">Retention (days)</label>
            <input type="number" name="retention_days" class="form-control" value="<?= htmlspecialchars($retention_days) ?>" min="30" max="3650">
          </div>

          <div class="col-12">
            <button class="btn btn-primary">Save</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../../includes/admin_footer.php'; ?>