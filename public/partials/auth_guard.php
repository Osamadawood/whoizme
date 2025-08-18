<?php
// Auth guard for landing pages: don't force login; just bounce logged-in users away.
require_once __DIR__ . '/auth_guard.php';
if (function_exists('auth_redirect_if_logged_in')) {
    auth_redirect_if_logged_in();
}
?>