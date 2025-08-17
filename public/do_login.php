<?php
declare(strict_types=1);

// معالج دخول: POST فقط
if (!defined('SKIP_AUTH_GUARD')) define('SKIP_AUTH_GUARD', true);
require __DIR__ . '/../includes/bootstrap.php';

// امنع الوصول المباشر بـ GET
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    $ret = (string)($_GET['return'] ?? '/dashboard.php');
    $ret = urldecode($ret);
    $path = (string)(parse_url($ret, PHP_URL_PATH) ?? '');
    $blocked = ['', '/', '/index.php', '/login', '/login.php', '/do_login', '/do_login.php'];
    $safeRet = (!$path || in_array($path, $blocked, true)) ? '/dashboard.php' : $path;
    header('Location: /login.php?return=' . urlencode($safeRet));
    exit;
}

// قراءة البيانات
$email    = strtolower(trim((string)($_POST['email'] ?? '')));
$password = (string)($_POST['password'] ?? '');
$retRaw   = (string)($_POST['return'] ?? '');
$retDec   = $retRaw !== '' ? urldecode($retRaw) : '';
$retPath  = $retDec !== '' ? (string)(parse_url($retDec, PHP_URL_PATH) ?? '') : '';
$blocked  = ['', '/', '/index.php', '/login', '/login.php', '/do_login', '/do_login.php'];
$returnTo = (!$retPath || in_array($retPath, $blocked, true)) ? '/dashboard.php' : $retPath;

// تحقّق أولي
if ($email === '' || $password === '') {
    header('Location: /login.php?err=1&return=' . urlencode($returnTo));
    exit;
}

// تأكد من وجود PDO (bootstrap عادة بيوفره)
$pdo = $pdo ?? ($GLOBALS['pdo'] ?? null);
if (!$pdo instanceof PDO) {
    header('Location: /login.php?err=1&return=' . urlencode($returnTo));
    exit;
}

// جِب المستخدم
$user = null;
try {
    $stmt = $pdo->prepare('SELECT id, email, password FROM users WHERE LOWER(email)=:email LIMIT 1');
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
} catch (Throwable $e) {
    $user = null;
}

// تحقق الباسورد (BCrypt/Argon2)، مع fallback مؤقت لنص عادي لو في داتا قديمة
$ok = false;
if ($user) {
    $hash = (string)($user['password'] ?? '');
    $info = password_get_info($hash);
    if (!empty($info['algo'])) {
        $ok = password_verify($password, $hash);
    } else {
        // مؤقتًا: دعم باسوردات غير مُهشّنة (لو قديمة). يفضّل مهاجرتها لاحقًا.
        $ok = ($hash !== '' && hash_equals($hash, $password));
    }
}

if ($ok && $user) {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_name('whoizme_sess');
        session_start();
    }
    session_regenerate_id(true);
    $_SESSION['uid']   = (int)$user['id'];
    $_SESSION['email'] = (string)$user['email'];

    header('Location: ' . $returnTo);
    exit;
}

// فشل
header('Location: /login.php?err=1&return=' . urlencode($returnTo));
exit;