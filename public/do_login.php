<?php
declare(strict_types=1);

/**
 * POST /do_login.php
 * - منطق فقط ثم Redirect (لا HTML).
 * - sanitization للـ return، ومنع open redirect / الدوران.
 * - يدعم أعمدة hash مختلفة (password_hash / pass_hash) وأنواع هاش قديمة.
 * - إضافة لوج تشخيصي آمن في /tmp/whoizme_login.log
 */

if (!defined('SKIP_AUTH_GUARD')) {
    define('SKIP_AUTH_GUARD', true);
}

require dirname(__DIR__) . '/includes/bootstrap.php';

/* ---------- polyfills / helpers ---------- */

// polyfill بسيط بديل str_starts_with لنسخ PHP قبل 8.0
if (!function_exists('whoizme_starts_with')) {
    function whoizme_starts_with(string $haystack, string $needle): bool {
        return $needle === '' || strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}

function log_diag(string $msg, array $ctx = []): void {
    // لا تسجل كلمات سر. ده لبيئة التطوير فقط.
    $line = '[' . date('c') . "] do_login | $msg";
    if ($ctx) {
        $safe = [];
        foreach ($ctx as $k => $v) {
            if (in_array($k, ['password', 'pwd'], true)) continue;
            if (is_bool($v)) $v = $v ? 'true' : 'false';
            $safe[$k] = (string)$v;
        }
        $line .= ' | ' . json_encode($safe, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
    @file_put_contents('/tmp/whoizme_login.log', $line . PHP_EOL, FILE_APPEND);
}

function clean_return(?string $raw): string {
    $raw     = (string)($raw ?? '');
    $decoded = $raw !== '' ? urldecode($raw) : '';
    $path    = $decoded !== '' ? (string)(parse_url($decoded, PHP_URL_PATH) ?? '') : '';

    // امنع الدوران / الرجوع لنفس الصفحات
    $bad = ['', '/', '/index', '/index.php', '/login', '/login.php', '/do_login', '/do_login.php'];
    if ($path === '' || in_array($path, $bad, true)) {
        return '/dashboard.php';
    }
    return $path[0] === '/' ? $path : '/dashboard.php';
}

function verify_password_safely(string $plain, string $stored): bool {
    if ($stored === '') return false;

    // Bcrypt / Argon2 prefixes بدون str_starts_with (متوافق مع PHP7)
    if (
        whoizme_starts_with($stored, '$2y$') ||
        whoizme_starts_with($stored, '$2a$') ||
        whoizme_starts_with($stored, '$argon2')
    ) {
        return password_verify($plain, $stored);
    }

    $len = strlen($stored);
    if ($len === 40 && ctype_xdigit($stored)) return hash_equals($stored, sha1($plain));
    if ($len === 32 && ctype_xdigit($stored)) return hash_equals($stored, md5($plain));

    // نص صريح (حالات انتقالية فقط)
    return hash_equals($stored, $plain);
}

function pick_hash(string ...$candidates): string {
    foreach ($candidates as $h) {
        if ($h !== '' && $h !== null) return $h;
    }
    return '';
}

/* ---------- inputs ---------- */

$return_to = clean_return($_POST['return'] ?? '');
$email     = isset($_POST['email']) ? trim(strtolower((string)$_POST['email'])) : '';
$password  = isset($_POST['password']) ? (string)$_POST['password'] : '';
$remember  = isset($_POST['remember']) && $_POST['remember'] == '1';

if ($email === '' || $password === '') {
    log_diag('empty_fields', ['email_len' => strlen($email), 'pwd_len' => strlen($password)]);
    header('Location: /login.php?err=empty&return=' . rawurlencode($return_to), true, 302);
    exit;
}

try {
    // نجيب المستخدم – لاحظ الأعمدة الموجودة عندك (email, password_hash, pass_hash)
    $sql = "SELECT id, email, password_hash, pass_hash
            FROM users
            WHERE email = :email
            LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        log_diag('user_not_found', ['email' => $email]);
        header('Location: /login.php?err=notfound&return=' . rawurlencode($return_to), true, 302);
        exit;
    }

    // استخدم أي عمود متوفر
    $stored = pick_hash(
        (string)($user['password_hash'] ?? ''),
        (string)($user['pass_hash'] ?? '')
    );

    $ok = verify_password_safely($password, $stored);
    log_diag('verify_done', [
        'uid' => (string)($user['id'] ?? ''),
        'email' => (string)($user['email'] ?? ''),
        'hash_len' => strlen($stored),
        'hash_prefix' => substr($stored, 0, 7),
        'verify' => $ok ? '1' : '0'
    ]);

    if (!$ok) {
        header('Location: /login.php?err=badpass&return=' . rawurlencode($return_to), true, 302);
        exit;
    }

    // success
    $_SESSION['user_id']    = (int)$user['id'];
    $_SESSION['user_email'] = (string)$user['email'];

    if ($remember) {
        $token = bin2hex(random_bytes(32));
        setcookie('whoizme_rm', $token, [
            'expires'  => time() + 60 * 60 * 24 * 30,
            'path'     => '/',
            'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        $_SESSION['whoizme_rm'] = $token;
    }

    log_diag('login_ok_redirect', ['to' => $return_to]);
    header('Location: ' . $return_to, true, 302);
    exit;

} catch (Throwable $e) {
    log_diag('exception', ['msg' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile()]);
    header('Location: /login.php?err=exception&return=' . rawurlencode($return_to), true, 302);
    exit;
}