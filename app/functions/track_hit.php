<?php
function track_hit(PDO $pdo, string $code): void {
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO short_link_hits (code, created_at, ip, ua, ref)
             VALUES (:c, NOW(), :ip, :ua, :ref)'
        );
        $stmt->execute([
            ':c'   => $code,
            ':ip'  => $_SERVER['REMOTE_ADDR'] ?? '',
            ':ua'  => $_SERVER['HTTP_USER_AGENT'] ?? '',
            ':ref' => $_SERVER['HTTP_REFERER'] ?? '',
        ]);
    } catch (Throwable $e) {
        if (isset($_GET['debug'])) {
            echo "\n(hit-log error) " . $e->getMessage() . "\n";
        }
    }
}