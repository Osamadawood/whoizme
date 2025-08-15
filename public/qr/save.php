<?php require_once __DIR__ . "/../_bootstrap.php"; ?>
require __DIR__ . '/../inc/db.php';
require __DIR__ . '/../inc/auth.php';

$uid   = current_user_id();
$id    = (int)($_POST['id'] ?? 0);
$title = trim($_POST['title'] ?? '');
$type  = trim($_POST['type'] ?? 'url');
$payload = trim($_POST['payload'] ?? '');

if ($title === '' || $payload === '') {
    header('Location: /qr/new.php?err=missing'); exit;
}

if ($id) {
    $sql = "UPDATE qr_codes
            SET title=:title, type=:type, payload=:payload, updated_at=NOW()
            WHERE id=:id AND user_id=:uid";
    $st = $pdo->prepare($sql);
    $st->execute([
        ':title'=>$title, ':type'=>$type, ':payload'=>$payload,
        ':id'=>$id, ':uid'=>$uid
    ]);
} else {
    $sql = "INSERT INTO qr_codes (user_id, title, type, payload)
            VALUES (:uid, :title, :type, :payload)";
    $st = $pdo->prepare($sql);
    $st->execute([
        ':uid'=>$uid, ':title'=>$title, ':type'=>$type, ':payload'=>$payload
    ]);
    $id = (int)$pdo->lastInsertId();
}

header('Location: /qr/view.php?id=' . $id);