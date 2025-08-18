<?php
declare(strict_types=1);

if (!function_exists('current_user_id')) {
    function current_user_id(): int { return (int)($_SESSION['uid'] ?? 0); }
}

function guest_only(?string $return = null): void {
    if (current_user_id() > 0) {
        $dest = '/dashboard.php';
        if ($return) {
            $bad = ['', '/', '/index', '/index.php', '/login', '/login.php', '/do_login', '/do_login.php', '/register', '/register.php'];
            $p = (string)(parse_url($return, PHP_URL_PATH) ?? '');
            if ($p && !in_array($p, $bad, true)) $dest = $p;
        }
        header('Location: ' . $dest, true, 302);
        exit;
    }
}