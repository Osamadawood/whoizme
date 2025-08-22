<?php
declare(strict_types=1);

// Includes
$INC = __DIR__ . '/../includes';
@require_once $INC . '/bootstrap.php'; // لازم يفعّل session + CSRF token
@require_once $INC . '/auth.php';
@require_once $INC . '/flash.php';

// نضمن إن في Session
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

// لازم POST (AR/EN)
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
  http_response_code(405);
  exit(isset($_SESSION['lang']) && $_SESSION['lang']==='ar' ? 'الطريقة غير مسموحة' : 'Method Not Allowed');
}

// جِب التوكن (من دالة csrf_token() لو موجودة أو من السيشن)
$posted = $_POST['csrf_token'] ?? '';
$valid  = false;
if (function_exists('csrf_verify')) {
  $valid = csrf_verify((string)$posted);
} else {
  $valid = is_string($posted) && hash_equals($_SESSION['csrf_token'] ?? '', $posted);
}
if (!$valid) {
  if (function_exists('flash')) {
    $msg = (isset($_SESSION['lang']) && $_SESSION['lang']==='ar')
      ? 'رمز الأمان غير صالح. حاول مرة أخرى.'
      : 'Security token invalid. Please try again.';
    flash('error', $msg);
  }
  header('Location: /dashboard.php');
  exit;
}

// احتفظ بتفضيلات الـ UI (لو مستخدمين كوكيز للثيم/اللغة)
$keepTheme = $_COOKIE['theme'] ?? null;
$keepLang  = $_COOKIE['lang']  ?? null;

// لو عندك دالة logout_user في auth.php خلّيها تنظّف أي remember-me
if (function_exists('logout_user')) {
  logout_user();
}

// امسح السيشن كويس
$_SESSION = [];
if (ini_get('session.use_cookies')) {
  $p = session_get_cookie_params();
  setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();

// امسح أي كوكيز محتملة للأوث
$secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
setcookie('remember_token', '', time() - 3600, '/', '', $secure, true);
setcookie('auth', '', time() - 3600, '/', '', $secure, true);

// ارجع التفضيلات (لو موجودة) لمدة سنة
if ($keepTheme) {
  setcookie('theme', (string)$keepTheme, time() + 31536000, '/', '', $secure, false);
}
if ($keepLang) {
  setcookie('lang', (string)$keepLang, time() + 31536000, '/', '', $secure, false);
}

// رسالة نجاح (اختياري)
if (function_exists('flash')) {
  $msg = (isset($_SESSION['lang']) && $_SESSION['lang']==='ar')
    ? 'تم تسجيل خروجك.'
    : 'You have been logged out.';
  flash('success', $msg);
}

require_once __DIR__ . '/../includes/helpers.php';
wz_redirect('/login');