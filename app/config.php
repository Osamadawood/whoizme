<?php
return [
  'db' => [
    'host'   => '127.0.0.1',
    'port'   => 8889,
    'name'   => 'whoiz',      // اسم الداتابيز بتاعتك
    'user'   => 'root',
    'pass'   => 'root',
    'charset'=> 'utf8mb4',
    'sock'   => '/Applications/MAMP/tmp/mysql/mysql.sock', // مهم لمامب
  ],

  'base_url' => 'https://whoiz.local:8890',
  'dev'      => true,

  'mail' => [
    'from_email' => 'no-reply@whoiz.me',
    'from_name'  => 'Whoiz.me',
    'host'       => 'sandbox.smtp.mailtrap.io',
    'port'       => 587,
    'username'   => '***',
    'password'   => '***',
    'secure'     => 'tls',
  ],
];