<?php
declare(strict_types=1);

/**
 * Public: Login handler (POST only)
 * - لا يوجد أي HTML هنا. توجيهات فقط.
 * - يحترم return الآمن، ويمنع الدورات (login/do_login/index).
 * - يدعم legacy MD5/SHA1 ويُعيد تهشير الباسورد بـ password_hash.
 * - لا يلمس أي CSS/HTML للتصميم.
 */

if (!defined('SKIP_AUTH_GUARD')) {
    // مهم علشان ملف bootstrap أو الحارس ما يوقفنا هنا
    define('SKIP_AUTH_GUARD', true);
}

require dirname(__DIR__) . '/includes/bootstrap.php';

// ---------------- Helpers ----------------

/** احصل على كائن PDO من أي مسمى مستخدم في المشروع */
function _pdo(): ?PDO {
    if (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) return $GLOBALS['pdo'];
    if (isset($GLOBALS['DB'])  && $GLOBALS['DB']  instanceof PDO) return $GLOBALS['DB'];
    if (function_exists('db')) {
        $maybe = db();
        if ($maybe instanceof PDO) return $maybe;
    }
    return null;
}

/** تنظيف return ومنع الدورات */
function _clean_return(?string $raw): string {
    $raw = (string)($raw ?? '');
    $decoded  = $raw !== '' ? urldecode($raw) : '';
    $pathOnly = $decoded !== '' ? (string)(parse_url($decoded, PHP_URL_PATH) ?? '') : '';

    // مسارات لا نعود إليها
    $bad = [
        '', '/', '/index', '/index.php',
        '/login', '/login.php',
        '/do_login', '/do_login.php'
    ];
    if ($pathOnly === '' || in_array($pathOnly, $bad, true)) {
        return '/dashboard.php';
    }
    // احرص إنه يبدأ بـ /
    return $pathOnly[0] === '/' ? $pathOnly : '/dashboard.php';
}

/** إعادة توجيه سريعة ثم exit */
function _go(string $to): void {
    header('Location: ' . $to, true, 302);
    exit;
}

/** التحقق من CSRF لو مستخدم (اختياري) */
function _check_csrf(): bool {
    if (!isset($_POST['_token'])) return true; // لا يوجد توكن، اعتبرها غير مفعلة
    if (!isset($_SESSION['_csrf_login'])) return false;
    return hash_equals((string)$_SESSION['_csrf_login'], (string)$_POST['_token']);
}

// ---------------- Only POST ----------------

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method !== 'POST') {
    $fallback = _clean_return($_GET['return'] ?? $_POST['return'] ?? null);
    _go('/login.php?return=' . urlencode($fallback));
}

// لو فيه سيشن شغالة بالفعل ودورهان
if (function_exists('current_user_id') && current_user_id() > 0) {
    $rt = _clean_return($_POST['return'] ?? null);
    _go($rt);
}

// ---------------- Read input ----------------

$email      = trim((string)($_POST['email']    ?? ''));
$password   = (string)($_POST['password'] ?? '');
$remember   = isset($_POST['remember']) && (string)$_POST['remember'] === '1';
$return_to  = _clean_return($_POST['return'] ?? null);

// مدخلات لازمة
if ($email === '' || $password === '' || !_check_csrf()) {
    _go('/login.php?err=1&return=' . urlencode($return_to));
}

// ---------------- DB ----------------

$pdo = _pdo();
if (!$pdo) {
    // غير متوقع
    _go('/login.php?err=1&return=' . urlencode($return_to));
}

// هات المستخدم – ندعم وجود password أو password_hash
$stmt = $pdo->prepare('SELECT id, email, password, password_hash FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    // لا تفصح إن الإيميل غير موجود
    _go('/login.php?err=1&return=' . urlencode($return_to));
}

// ---------------- Verify password ----------------

$storedHash = '';
if (!empty($user['password_hash'])) {
    $storedHash = (string)$user['password_hash'];
} elseif (!empty($user['password'])) {
    $storedHash = (string)$user['password'];
}

$ok = false;
if ($storedHash !== '') {
    // الحالة الحديثة
    if (strlen($storedHash) > 40) { // على الأغلب password_hash
        $ok = password_verify($password, $storedHash);
    } else {
        // Legacy: MD5 أو SHA1
        if (strlen($storedHash) === 32 && ctype_xdigit($storedHash)) {
            $ok = (md5($password) === strtolower($storedHash));
        } elseif (strlen($storedHash) === 40 && ctype_xdigit($storedHash)) {
            $ok = (sha1($password) === strtolower($storedHash));
        }
        // لو اتقبل legacy، نرقي فورًا لـ password_hash
        if ($ok) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            if ($newHash && is_string($newHash)) {
                try {
                    // إن وجد عمود password_hash استعمله، خلاف ذلك اكتب فوق password
                    if (array_key_exists('password_hash', $user)) {
                        $up = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
                        $up->execute([$newHash, (int)$user['id']]);
                    } else {
                        $up = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
                        $up->execute([$newHash, (int)$user['id']]);
                    }
                } catch (Throwable $e) {
                    // تجاهل — ممكن نسجل لاحقًا
                }
            }
        }
    }
}

// لو فشل التحقق
if (!$ok) {
    _go('/login.php?err=1&return=' . urlencode($return_to));
}

// ---------------- Auth success ----------------

$_SESSION['uid'] = (int)$user['id'];
if (function_exists('session_regenerate_id')) {
    @session_regenerate_id(true);
}

// "Remember me" Cookie (موقّعة)
$secret = $CFG['app_key'] ?? ($CFG['secret'] ?? 'whoizme_fallback_secret_change_me');
if ($remember) {
    $uid = (string)$_SESSION['uid'];
    $sig = hash_hmac('sha256', $uid, (string)$secret);
    $val = base64_encode(json_encode(['u' => $uid, 's' => $sig], JSON_UNESCAPED_SLASHES));
    $exp = time() + 60 * 60 * 24 * 30; // 30 يوم
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    setcookie('whoizme_remember', $val, [
        'expires'  => $exp,
        'path'     => '/',
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
} else {
    // امسح الكوكي لو كانت موجودة
    if (isset($_COOKIE['whoizme_remember'])) {
        setcookie('whoizme_remember', '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}

// تنظيف CSRF الخاص بالفورم (اختياري)
unset($_SESSION['_csrf_login']);

// ---------------- Safe redirect ----------------

_go($return_to);