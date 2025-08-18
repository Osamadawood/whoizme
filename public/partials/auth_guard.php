<?php
declare(strict_types=1);

// يُفترض أن bootstrap تم تحميله قبل هذا الملف
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('whoizme_sess');
    session_start();
}

// دالة موحدة للمنع ثم التحويل للوج-إن
if (!function_exists('require_login')) {
    function require_login(): void {
        $uid = (int)($_SESSION['user_id'] ?? 0);
        if ($uid <= 0) {
            // ابنِ return من الـ URI الحالي
            $path = $_SERVER['REQUEST_URI'] ?? '/';
            // لو كان الهدف هو login نفسه، رجّع إلى الداشبورد بعد النجاح
            if (preg_match('~^/login\.php~i', $path)) {
                $path = '/dashboard.php';
            }
            header('Location: /login.php?return=' . urlencode($path));
            exit;
        }
    }
}

// استدعِ الحارس فعليًا
require_login();