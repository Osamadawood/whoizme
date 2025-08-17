<?php
// public/partials/auth_guard.php
// -------------------------------------------------
// حارس الدخول (بدون أي HTML/CSS)
// يعتمد على جلسة $_SESSION['uid'] التي يستخدمها المشروع
// -------------------------------------------------

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

const AUTH_SESSION_KEY = 'uid';           // مهم: المشروع بيستخدم uid
const PATH_LOGIN       = '/login.php';
const PATH_DASHBOARD   = '/dashboard.php';

/** هل المستخدم مسجّل دخول؟ */
function auth_is_logged_in(): bool {
    return !empty($_SESSION[AUTH_SESSION_KEY]);
}

/** المسار المطلوب حاليًا */
function auth_current_path(): string {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    return $path ?: '/';
}

/** إعادة توجيه سريعة */
function auth_redirect(string $to): void {
    header('Location: ' . $to);
    exit;
}

/** استدعِها في هيدر صفحات التطبيق (المحمية) */
function auth_require_login(): void {
    if (auth_is_logged_in()) return;

    $ret = auth_current_path();
    if (!empty($_SERVER['QUERY_STRING'])) {
        $ret .= '?' . $_SERVER['QUERY_STRING'];
    }
    auth_redirect(PATH_LOGIN . '?return=' . urlencode($ret));
}

/** استدعِها في هيدر الصفحات العامة (لانديج/لوج إن/ريجستر) */
function auth_redirect_if_logged_in(): void {
    if (auth_is_logged_in()) {
        auth_redirect(PATH_DASHBOARD);
    }
}