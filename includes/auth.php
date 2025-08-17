<?php
declare(strict_types=1);

// دوال مساعدة فقط
if (!function_exists('current_user_id')) {
    function current_user_id(): int { return (int)($_SESSION['uid'] ?? 0); }
}
if (!function_exists('is_logged_in')) {
    function is_logged_in(): bool { return current_user_id() > 0; }
}