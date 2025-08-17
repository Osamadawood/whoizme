<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

// لو بالفعل عامل لوج إن حوله عَ اللي جاي من return أو للداشبورد
if (function_exists('is_logged_in') && is_logged_in()) {
    $to = (string)($_GET['return'] ?? $_POST['return'] ?? '/dashboard.php');
    if ($to === '' || $to[0] !== '/') { $to = '/dashboard.php'; }
    header('Location: ' . $to);
    exit;
}

// لازم POST
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    $ret = (string)($_GET['return'] ?? '/dashboard.php');
    if ($ret === '' || $ret[0] !== '/') { $ret = '/dashboard.php'; }
    header('Location: /login.php?err=1&return=' . urlencode($ret));
    exit;
}

$email  = trim((string)($_POST['email'] ?? ''));
$pass   = (string)($_POST['password'] ?? '');
$return = (string)($_POST['return'] ?? '/dashboard.php');
if ($return === '' || $return[0] !== '/') { $return = '/dashboard.php'; }

if ($email === '' || $pass === '') {
    header('Location: /login.php?err=1&return=' . urlencode($return));
    exit;
}

/** @var PDO|null $pdo */
$pdo = $pdo ?? null;
if (!$pdo instanceof PDO) {
    error_log('[auth] PDO missing in do_login.php');
    header('Location: /login.php?err=1&return=' . urlencode($return));
    exit;
}

// جرّب أسماء أعمدة/جداول شائعة
$queries = [
    // users(password_hash)
    ['sql' => 'SELECT id, email, password_hash, name FROM users WHERE email = :email LIMIT 1', 'pw' => 'password_hash', 'name' => 'name'],
    // users(password)
    ['sql' => 'SELECT id, email, password, name FROM users WHERE email = :email LIMIT 1', 'pw' => 'password', 'name' => 'name'],
    // admins(password_hash)
    ['sql' => 'SELECT id, email, password_hash, full_name AS name FROM admins WHERE email = :email LIMIT 1', 'pw' => 'password_hash', 'name' => 'name'],
];

$user = null;
$hashField = 'password_hash';
$nameField = 'name';

foreach ($queries as $q) {
    $stmt = $pdo->prepare($q['sql']);
    $stmt->execute([':email' => $email]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $user = $row;
        $hashField = $q['pw'];
        $nameField = $q['name'];
        break;
    }
}

$ok = false;
if ($user) {
    $hash = (string)($user[$hashField] ?? '');
    // password_hash؟
    $info = password_get_info($hash);
    if (!empty($info['algo'])) {
        $ok = password_verify($pass, $hash);
    } else {
        // fallback قديم md5/sha1
        if (strlen($hash) === 32 && ctype_xdigit($hash)) {
            $ok = (md5($pass) === strtolower($hash));
        } elseif (strlen($hash) === 40 && ctype_xdigit($hash)) {
            $ok = (sha1($pass) === strtolower($hash));
        }
    }
}

if (!$ok) {
    header('Location: /login.php?err=1&return=' . urlencode($return));
    exit;
}

// نجاح
session_regenerate_id(true);
$_SESSION['uid']   = (int)$user['id'];
$_SESSION['email'] = (string)$user['email'];
$_SESSION['name']  = (string)($user[$nameField] ?? '');

header('Location: ' . $return);
exit;