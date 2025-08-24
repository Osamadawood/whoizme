<?php
declare(strict_types=1);

function wz_link_normalize_url(string $u): string {
    $u = trim($u);
    if ($u === '') return '';
    if (!preg_match('~^https?://~i', $u)) {
        $u = 'https://' . $u;
    }
    return $u;
}

function wz_generate_slug(int $len = 6): string {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    $out = '';
    for ($i=0; $i<$len; $i++) $out .= $chars[random_int(0, strlen($chars)-1)];
    return $out;
}

function wz_links_build_filters_from_query(): array {
    $q = trim($_GET['q'] ?? '');
    $active = $_GET['active'] ?? '1';
    $page = max(1, (int)($_GET['page'] ?? 1));
    $perAllowed = [10,25,50];
    $perReq = (int)($_GET['per'] ?? 10);
    $per = in_array($perReq, $perAllowed, true) ? $perReq : 10;
    return ['q'=>$q, 'active'=>$active, 'page'=>$page, 'per'=>$per];
}

function wz_links_user_can(PDO $pdo, int $userId, int $linkId): bool {
    $st = $pdo->prepare('SELECT 1 FROM links WHERE id=:id AND user_id=:u LIMIT 1');
    $st->execute([':id'=>$linkId, ':u'=>$userId]);
    return (bool)$st->fetchColumn();
}


