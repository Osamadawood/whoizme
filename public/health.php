<?php require_once __DIR__ . '/_bootstrap.php';
echo "DB OK: " . (int)db()->query("SELECT 1")->fetchColumn() . "<br>";
echo "UID: " . (int)($_SESSION['uid'] ?? 0);