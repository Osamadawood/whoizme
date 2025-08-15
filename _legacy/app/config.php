<?php // /includes/config.php
declare(strict_types=1);

return [
  'env' => 'local', // local | staging | production
  'db'  => [
    'dsn'  => 'mysql:host=127.0.0.1;port=8889;dbname=whoiz;charset=utf8mb4',
    'user' => 'root',
    'pass' => 'root',
    'options' => [
      PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES   => false,
    ],
  ],
  'security' => [
    'session_name' => 'whoizme_sess',
    'cookie_lifetime' => 60 * 60 * 24 * 14, // 14 يوم
  ],
];