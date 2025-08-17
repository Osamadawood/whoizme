<?php
declare(strict_types=1);

/**
 * Auth helpers – تُضمَّن من bootstrap.php
 * بتوفر: is_logged_in(), current_user(), login(), logout(), require_login()
 */

# حارس بسيط علشان نتأكد إنه مش هيتحمّل مرتين
if (function_exists('is_logged_in')) {
    return;
}

function is_logged_in(): bool {
    return isset($_SESSION['user']) && is_array($_SESSION['user']);
}

function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}

/**
 * استدعِها أعلى أي صفحة محمية (لوحة التحكم مثلًا)
 */
function require_login(): void {
    if (!is_logged_in()) {
        $return = $_SERVER['REQUEST_URI'] ?? '/';
        header('Location: ' . url('/login.php', ['return' => $return]));
        exit;
    }
}

/**
 * $user: مصفوفة بيانات المستخدم (على الأقل ['id'=>...,'email'=>...])
 */
function login(array $user, bool $remember = false): void {
    $_SESSION['user'] = [
        'id'    => $user['id']    ?? null,
        'email' => $user['email'] ?? null,
        'name'  => $user['name']  ?? null,
    ];

    if ($remember && !empty($user['id'])) {
        // تذكرة "تذكرني" بسيطة (يفضّل استخدام توكن حقيقي في DB)
        setcookie('remember_user', (string)$user['id'], [
            'expires'  => time() + 60 * 60 * 24 * 30, // 30 يوم
            'path'     => '/',
            'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}

function logout(): void {
    // ازالة تذكرة remember إن وجدت
    if (isset($_COOKIE['remember_user'])) {
        setcookie('remember_user', '', time() - 3600, '/');
    }

    // مسح السيشن
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}