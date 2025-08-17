<?php
// public/do_login.php
declare(strict_types=1);

/**
 * Login endpoint (POST).
 * We deliberately skip the auth guard here, then include the bootstrap for DB + helpers.
 */
if (!defined('SKIP_AUTH_GUARD')) define('SKIP_AUTH_GUARD', true);
require __DIR__ . '/../includes/bootstrap.php';

// Make sure a session is open (bootstrap usually starts it, but be explicit)
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('whoizme_sess');
    session_start();
}

// Helper: safe internal return path (never to auth pages, never to do_login)
function wl_return_path(): string {
    $raw = (string)($_POST['return'] ?? ($_GET['return'] ?? ''));
    if ($raw === '') return '';
    // Decode once ("%2Fdashboard.php" -> "/dashboard.php")
    $val = urldecode($raw);
    $url = parse_url($val);
    $path = (string)($url['path'] ?? '');

    // Block empty/root and any auth endpoints to avoid loops
    $blocked = [
        '', '/', '/index.php',
        '/login', '/login.php',
        '/do_login', '/do_login.php',
        '/register', '/register.php',
        '/forgot', '/forgot.php',
        '/reset', '/reset.php',
    ];
    if ($path === '' || in_array($path, $blocked, true)) {
        return '';
    }

    // Re-append the original query-string (if any)
    $qs = isset($url['query']) && $url['query'] !== '' ? ('?' . $url['query']) : '';
    return $path . $qs;
}

// Only allow POST directly; otherwise bounce to login with a clean `return`
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    $ret = wl_return_path();
    header('Location: /login.php' . ($ret !== '' ? ('?return=' . urlencode($ret)) : ''));
    exit;
}

// --- Read form data ---
$email    = strtolower(trim((string)($_POST['email'] ?? '')));
$password = (string)($_POST['password'] ?? '');
$return   = wl_return_path();

// Quick validation
if ($email === '' || $password === '') {
    $_SESSION['flash_error'] = 'Please enter your email and password.';
    header('Location: /login.php' . ($return !== '' ? ('?return=' . urlencode($return)) : ''));
    exit;
}

// --- DB connection from bootstrap ---
$pdo = $pdo ?? ($GLOBALS['pdo'] ?? null);
if (!$pdo instanceof PDO) {
    $_SESSION['flash_error'] = 'Temporary login issue. Please try again.';
    header('Location: /login.php' . ($return !== '' ? ('?return=' . urlencode($return)) : ''));
    exit;
}

// --- Look up user and verify password ---
$ok = false; $user = null;
try {
    $stmt = $pdo->prepare('SELECT id, email, password FROM users WHERE LOWER(email) = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

    if ($user) {
        $hash = (string)($user['password'] ?? '');
        $info = password_get_info($hash);
        if (!empty($info['algo'])) {
            $ok = password_verify($password, $hash);
        } else {
            // Backwards-compat (plain text) — remove once all rows migrated
            $ok = ($hash !== '' && hash_equals($hash, $password));
        }
    }
} catch (Throwable $e) {
    // Never leak DB details
    $ok = false; $user = null;
}

if ($ok && $user) {
    // Regenerate session ID to prevent fixation, then stamp identity
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_name('whoizme_sess');
        session_start();
    }
    session_regenerate_id(true);
    $_SESSION['uid']   = (int)$user['id'];
    $_SESSION['email'] = (string)$user['email'];

    // Optional: clear any stale flash
    unset($_SESSION['flash_error']);

    // Decide target (dashboard by default)
    $target = $return !== '' ? $return : '/dashboard.php';
    header('Location: ' . $target);
    exit;
}

// Failure
$_SESSION['flash_error'] = 'Invalid email or password.';
header('Location: /login.php' . ($return !== '' ? ('?return=' . urlencode($return)) : ''));
exit;