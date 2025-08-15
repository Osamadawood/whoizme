<?php
// includes/db.php

// حمِّل الكونفِج (bridge يقرأ من app/config.php)
if (!isset($CFG)) {
    $CFG = require __DIR__ . '/config.php';
}

/** احصل على PDO واحد (Singleton) */
function db(): PDO {
    static $pdo;
    if ($pdo instanceof PDO) return $pdo;

    $cfg     = $GLOBALS['CFG']['db'] ?? [];
    $charset = $cfg['charset'] ?? 'utf8mb4';
    $opts    = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    // 1) جرّب Socket (MAMP)
    if (!empty($cfg['sock']) && file_exists($cfg['sock'])) {
        $dsn = "mysql:unix_socket={$cfg['sock']};dbname={$cfg['name']};charset={$charset}";
        $pdo = new PDO($dsn, $cfg['user'] ?? 'root', $cfg['pass'] ?? '', $opts);
        return $pdo;
    }

    // 2) لو Socket مش موجود، استخدم TCP
    $host = $cfg['host'] ?? '127.0.0.1';
    $port = $cfg['port'] ?? 3306;
    $dsn  = "mysql:host={$host};port={$port};dbname={$cfg['name']};charset={$charset}";
    $pdo  = new PDO($dsn, $cfg['user'] ?? 'root', $cfg['pass'] ?? '', $opts);
    return $pdo;
}