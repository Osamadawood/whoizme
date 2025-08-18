<?php
declare(strict_types=1);

// لود البوتستراب (سيشن + إعدادات + DB + هلفرز)
require_once __DIR__ . '/../includes/bootstrap.php';

// أداة بسيطة لقراءة POST بأمان
function p(string $key, string $default = ''): string {
    return isset($_POST[$key]) ? trim((string)$_POST[$key]) : $default;
}

// CSRF (اختياري لو عندك دالة verify_csrf_token)
// if (!verify_csrf_token($_POST['_csrf'] ?? '')) {
//     header('Location: /login.php?err=csrf'); exit;
// }

// 1) التحقق من الإدخال
$email    = strtolower(p('email'));
$password = p('password');
$remember = isset($_POST['remember']) ? (bool)$_POST['remember'] : false;

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
    header('Location: /login.php?err=badinput&email=' . urlencode($email));
    exit;
}

try {
    // 2) احضر المستخدم
    $stmt = $pdo->prepare('SELECT id, email, password_hash, is_active FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || (int)$user['is_active'] !== 1) {
        header('Location: /login.php?err=nouser&email=' . urlencode($email));
        exit;
    }

    // 3) تحقق من كلمة المرور
    if (!password_verify($password, (string)$user['password_hash'])) {
        header('Location: /login.php?err=badpass&email=' . urlencode($email));
        exit;
    }

    // 4) نجاح: ثبت الجلسة بأمان
    session_regenerate_id(true);
    $_SESSION['user_id']    = (int)$user['id'];
    $_SESSION['user_email'] = (string)$user['email'];

    // لو عندك فلاش ميسج
    if (!function_exists('flash_set')) {
        function flash_set(string $key, string $msg): void { $_SESSION['_flash'][$key] = $msg; }
    }
    flash_set('ok', 'Welcome back!');

    // 5) احترم return إن كان نظيفًا، وإلا اذهب للداشبورد
    $return = $_POST['return'] ?? $_GET['return'] ?? '/dashboard.php';

    // تنظيف الـ return: يسمح فقط بمسارات داخلية تبدأ بـ /
    if (!is_string($return) || $return === '' || $return[0] !== '/') {
        $return = '/dashboard.php';
    }

    // منع حلقة: لو الـ return يشير للوج-إن نفسه، أرسل للداشبورد
    if (preg_match('~^/login\.php~i', $return)) {
        $return = '/dashboard.php';
    }

    header('Location: ' . $return);
    exit;

} catch (Throwable $e) {
    // سجل لو عندك لوج
    error_log('[do_login] ' . $e->getMessage());
    header('Location: /login.php?err=exception&email=' . urlencode($email));
    exit;
}