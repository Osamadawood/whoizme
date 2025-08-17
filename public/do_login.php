<?php
declare(strict_types=1);

// ده هاندلر فقط، ما نحتاجش جارد إعادة التوجيه هنا
if (!defined('SKIP_AUTH_GUARD')) {
  define('SKIP_AUTH_GUARD', true);
}

// البوتستراب: بيشغّل السيشن ويلمّ الـ helpers (ومايطبعش HTML)
require dirname(__DIR__) . '/includes/bootstrap.php';

/**
 * نحافظ على إن الهاندلر مايطبعش أي حاجة إطلاقًا
 * علشان الـ header('Location') يشتغل.
 */
@ini_set('display_errors', '0');

/** فلتر آمن لمسار الرجوع */
function safe_return_path(?string $raw): string {
  $raw      = (string)($raw ?? '');
  $decoded  = urldecode($raw);
  $pathOnly = parse_url($decoded, PHP_URL_PATH) ?: '';
  if ($pathOnly === '' || $pathOnly === '/' || $pathOnly === '/login.php' || $pathOnly === '/do_login.php') {
    return '/dashboard.php';
  }
  // نسمح بالمسارات الداخلية فقط
  if ($pathOnly[0] !== '/') {
    return '/dashboard.php';
  }
  return $pathOnly;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method !== 'POST') {
  $ret = safe_return_path($_GET['return'] ?? '');
  header('Location: /login.php?return=' . rawurlencode($ret), true, 302);
  exit;
}

$email    = trim((string)($_POST['email'] ?? ''));
$password = (string)($_POST['password'] ?? '');
$remember = isset($_POST['remember']) ? 1 : 0;
$returnTo = safe_return_path($_POST['return'] ?? '');

// تحقق سريع من المدخلات
if ($email === '' || $password === '') {
  header('Location: /login.php?return=' . rawurlencode($returnTo), true, 302);
  exit;
}

// ===== تحقق المستخدم من قاعدة البيانات =====
// توقع الأعمدة: id, email, password_hash (BCrypt/Argon2)
// عدّل الأسماء لو سكيمتك مختلفة
try {
  /** @var PDO $pdo */
  if (!isset($pdo)) {
    // لو البوتستراب عندك بيوفر دالة، استخدمها:
    if (function_exists('db')) {
      $pdo = db();
    } else {
      throw new RuntimeException('DB handle not available');
    }
  }

  $stmt = $pdo->prepare('SELECT id, email, password_hash FROM users WHERE email = :email LIMIT 1');
  $stmt->execute([':email' => $email]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);

  $ok = false;
  if ($row) {
    // لو عندك عمود اسمه password بدلاً من password_hash بدّله هنا
    $hash = $row['password_hash'] ?? '';
    if ($hash !== '' && password_verify($password, $hash)) {
      $ok = true;
    }
  }

  if (!$ok) {
    // فشل: ارجع لنفس صفحة اللوجين من غير ما نكسر الديزاين
    header('Location: /login.php?return=' . rawurlencode($returnTo), true, 302);
    exit;
  }

  // نجاح: سجّل المستخدم في السيشن
  if (function_exists('login_user')) {
    login_user((int)$row['id'], $remember === 1);
  } else {
    // fallback بسيط لو ماعندكش helper
    $_SESSION['uid'] = (int)$row['id'];
    if ($remember === 1) {
      // تقدر هنا تعمل remember-me token لو موجود عندك جدول tokens
      // حالياً نكتفي بسيشن عادي
    }
  }

  // توجيه أخير
  header('Location: ' . $returnTo, true, 302);
  exit;

} catch (Throwable $e) {
  // في حالة خطأ غير متوقع، ما نطبعش حاجة — نرجّع المستخدم للّوجين
  header('Location: /login.php?return=' . rawurlencode($returnTo), true, 302);
  exit;
}