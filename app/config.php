<?php
return [
  'db' => [
    'host'    => '127.0.0.1',   // الأفضل TCP بدل socket: استخدم 127.0.0.1
    'port'    => 8889,          // MAMP MySQL port
    'name'    => 'whoiz',       // اسم قاعدة البيانات
    'user'    => 'root',
    'pass'    => 'root',
    'charset' => 'utf8mb4',
    'socket' => '/Applications/MAMP/tmp/mysql/mysql.sock',
  ],

  'base_url' => 'https://whoiz.local:8890',
  'dev'      => true, // أثناء التطوير
  'mail'     => [
    'from_email' => 'no-reply@whoiz.me',
    'from_name'  => 'Whoiz.me',
    // Mailtrap (اختياري)
    'host'       => 'sandbox.smtp.mailtrap.io',
    'port'       => 587,
    'username'   => '1a69401e36192e',
    'password'   => 'c1a136639719b9',
    'secure'     => 'tls',
  ],
];