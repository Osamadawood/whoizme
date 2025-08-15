<?php
$root = dirname(__DIR__);
$config = require $root.'/app/config.php';
$pdo = new PDO(
  "mysql:host={$config['db']['host']};port={$config['db']['port']};dbname={$config['db']['name']};charset={$config['db']['charset']}",
  $config['db']['user'],$config['db']['pass'],
  [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]
);
$pdo->exec("DELETE FROM password_resets WHERE used_at IS NOT NULL OR expires_at < NOW()");
echo "Cleanup done\n";