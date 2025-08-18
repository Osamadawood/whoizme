<?php
declare(strict_types=1);

/**
 * POST /do_login.php
 * يعتمد على includes/bootstrap.php لتجهيز الـPDO والسيشن والدوال.
 */
require dirname(__DIR__) . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /login.php', true, 302);
    exit;
}

// helpers
function only_internal_path(string $path, string $fallback = '/dashboard.php'): string {
    $path = trim($path);
    if ($path === '' || $path[0] !== '/') return $fallback;     // لازم يبدأ بـ /
    // امنع البروتوكولات والروابط المطلقة
    if (preg_match('~^\s*(https?:)?//~i', $path)) return $fallback;
    // امنع أي new lines
    $path = str_replace(["\r", "\n"], '', $path);
    return $path;
}

function redirect_login(string $reason, string $return = '/dashboard.php', string $email = ''): void {
    $qs = http_build_query([
        'err'    => $reason,
        'return' => $return,
        'email'  => $email !== '' ? $email : null,
    ]);
    header('Location: /login.php?' . $qs, true, 302);
    exit;
}

// read inputs
$email    = isset($_POST['email']) ? trim((string)$_POST['email']) : '';
$password = isset($_POST['password']) ? (string)$_POST['password'] : '';
$remember = isset($_POST['remember']) ? (bool)$_POST['remember'] : false;
$return   = only_internal_path((string)($_POST['return'] ?? '/dashboard.php'));

if ($email === '' || $password === '') {
    redirect_login('badpass', $return);
}

try {
    /** @var PDO $pdo */
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        throw new RuntimeException('DB not ready');
    }

    // نحاول قراءة أي من العمودين password_hash أو pass_hash
    // نجيب السطر مرة واحدة بعمودين ونستعمل أول واحد موجود
    $sql = <<<SQL
        SELECT id, name, email, is_active,
               password_hash, pass_hash
        FROM users
        WHERE email = :email
        LIMIT 1
    SQL;

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // إيميل مش موجود
        redirect_login('badpass', $return, $email);
    }

    if (isset($user['is_active']) && (int)$user['is_active'] === 0) {
        redirect_login('inactive', $return, $email);
    }

    // اختَر الهاش الفعّال
    $hash = '';
    if (!empty($user['password_hash'])) {
        $hash = (string)$user['password_hash'];
    } elseif (!empty($user['pass_hash'])) {
        $hash = (string)$user['pass_hash'];
    }

    if ($hash === '' || !password_verify($password, $hash)) {
        redirect_login('badpass', $return, $email);
    }

    // نجاح: فعّل السيشن
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    session_regenerate_id(true);

    $_SESSION['user_id']   = (int)$user['id'];
    $_SESSION['user_name'] = (string)($user['name'] ?? '');
    $_SESSION['user_email']= (string)$user['email'];

    // Remember me (كوكي اختيارية لمدة 30 يوم)
    if ($remember) {
        // token بسيط للآن (ممكن نطوّره لاحقاً)
        $token = bin2hex(random_bytes(32));
        setcookie('whoizme_remember', $token, [
            'expires'  => time() + 60*60*24*30,
            'path'     => '/',
            'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        // يمكن تخزينه في جدول لاحقاً؛ لسه مش مطلوب للـ MVP
    }

    // إلى الداشبورد أو الـreturn
    header('Location: ' . $return, true, 302);
    exit;

} catch (Throwable $e) {
    // بدلاً من كراش نرجّع رسالة exception المؤقتة
    redirect_login('exception', $return, $email);
}