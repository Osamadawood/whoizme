<?php // /includes/session.php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
  $cfg = require INC_PATH . '/config.php';
  session_name($cfg['security']['session_name']);
  session_set_cookie_params([
    'lifetime' => $cfg['security']['cookie_lifetime'],
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
  ]);
  session_start();
}