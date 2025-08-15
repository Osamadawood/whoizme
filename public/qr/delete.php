<?php require_once __DIR__ . "/../_bootstrap.php"; ?>
require __DIR__ . '/../inc/db.php';
require __DIR__ . '/../inc/auth.php';
$uid = current_user_id();
$id  = (int)($_GET['id'] ?? 0);

$st = $pdo->prepare("DELETE FROM qr_codes WHERE id=:id AND user_id=:uid");
$st->execute([':id'=>$id, ':uid'=>$uid]);

header('Location: /qr/');