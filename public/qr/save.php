<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/auth_guard.php';

/** @var PDO $pdo */
$uid = (int)($_SESSION['user_id'] ?? 0);
if ($uid <= 0) { http_response_code(403); exit('Forbidden'); }

$id      = (int)($_POST['id'] ?? 0);
$title   = trim((string)($_POST['title'] ?? ''));
$type    = trim((string)($_POST['type'] ?? 'url'));
$payload = trim((string)($_POST['payload'] ?? ''));

if ($title === '' || $payload === '') {
    header('Location: /qr/new.php?err=missing');
    exit;
}

if ($id > 0) {
    $sql = "UPDATE qr_codes
            SET title=:title, type=:type, payload=:payload, updated_at=NOW()
            WHERE id=:id AND user_id=:uid";
    $st = $pdo->prepare($sql);
    $st->execute([
        ':title' => $title,
        ':type' => $type,
        ':payload' => $payload,
        ':id' => $id,
        ':uid' => $uid,
    ]);
} else {
    $sql = "INSERT INTO qr_codes (user_id, title, type, payload, created_at)
            VALUES (:uid, :title, :type, :payload, NOW())";
    $st = $pdo->prepare($sql);
    $st->execute([
        ':uid' => $uid,
        ':title' => $title,
        ':type' => $type,
        ':payload' => $payload,
    ]);
    $id = (int)$pdo->lastInsertId();
}

header('Location: /qr/view.php?id=' . $id);
exit;