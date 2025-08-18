<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

function flash_set(string $key, string $msg, string $type = 'info'): void {
    $_SESSION['flash'][$key] = ['m'=>$msg, 't'=>$type, 'ts'=>time()];
}
function flash_get(string $key): ?array {
    if (!empty($_SESSION['flash'][$key])) {
        $v = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $v;
    }
    return null;
}