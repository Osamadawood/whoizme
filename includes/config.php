<?php
declare(strict_types=1);

return [
    // فعل عرض الأخطاء محلياً
    'dev'       => true,

    // عنوان مشروعك المحلي
    'base_url'  => 'https://whoiz.local:8890',

    // يخلّي اللودر يمنع الصفحات الخاصة لو المستخدم مش لوج إن
    'auto_guard' => true,

    // إعدادات قاعدة البيانات (MAMP)
    'db' => [
        'driver'   => 'mysql',
        'host'     => 'localhost',      // هيتم تجاهلها لو استخدمنا socket
        'port'     => 8889,
        'dbname'   => 'whoiz',
        'username' => 'root',
        'password' => 'root',
        'charset'  => 'utf8mb4',

        // مهم: سوكت MAMP (أسرع وأكثر ثباتاً)
        'socket'   => '/Applications/MAMP/tmp/mysql/mysql.sock',
    ],

    // (اختياري) إعدادات الإيميل
    'smtp' => [
        'host' => 'sandbox.smtp.mailtrap.io',
        'port' => 2525,
        'user' => '',
        'pass' => '',
        'secure' => null,
        'from_email' => 'noreply@whoiz.me',
        'from_name'  => 'whoiz.me',
    ],
];