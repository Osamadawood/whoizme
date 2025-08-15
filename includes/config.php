<?php
declare(strict_types=1);

return [
    'dev'        => true,
    'base_url'   => 'https://whoiz.local:8890',
    'auto_guard' => true,

    'db' => [
        'host'    => 'localhost',
        'port'    => 8889,
        'name'    => 'whoiz',
        'user'    => 'root',
        'pass'    => 'root',
        'charset' => 'utf8mb4',
    ],

    'smtp' => [
        'host'       => 'sandbox.smtp.mailtrap.io',
        'port'       => 2525,
        'user'       => '',
        'pass'       => '',
        'secure'     => null,
        'from_email' => 'noreply@whoiz.me',
        'from_name'  => 'whoiz.me',
    ],
];