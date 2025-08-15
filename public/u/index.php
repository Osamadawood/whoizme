<?php
// --- Track visit for profile page /u/{username} ---
try {
    // 1) اتصال PDO (لو عندك $pdo جاهز من bootstrap استبدل البلوك ده باستخدامه)
    $pdo = $pdo ?? new PDO(
        'mysql:host=localhost;port=8889;dbname=whoiz;charset=utf8mb4',
        'root', 'root',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // 2) استنتاج الكود المرتبط بصفحة /u/{username}
    // لو عندك $username متاح، استخدمه. لو لا، ناخده من REQUEST_URI
    $username = $username ?? '';
    if ($username === '') {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
        // يتوقع مسار بالشكل /u/osama أو /u/osama/
        if (preg_match('~^/u/([^/]+)/?~i', $path, $m)) {
            $username = $m[1];
        }
    }

    if ($username !== '') {
        // 3) نجيب الكود من جدول short_links بناءً على target_url اللي بتشير لـ /u/{username}
        // غيّر النمط لو target_url عندك مختلف
        $st = $pdo->prepare('SELECT code FROM short_links WHERE target_url LIKE :url LIMIT 1');
        $st->execute([':url' => "%/u/{$username}%"]);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        if ($row && !empty($row['code'])) {
            $ins = $pdo->prepare('INSERT INTO short_link_hits (code, created_at, ip, ua, ref) VALUES (:c, NOW(), :ip, :ua, :ref)');
            $ins->execute([
                ':c'   => $row['code'],
                ':ip'  => $_SERVER['REMOTE_ADDR'] ?? '',
                ':ua'  => $_SERVER['HTTP_USER_AGENT'] ?? '',
                ':ref' => $_SERVER['HTTP_REFERER'] ?? '',
            ]);
        }
    }
} catch (Throwable $e) {
    if (isset($_GET['debug'])) { echo "\n[profile-hit error] ".$e->getMessage(); }
}
// --- End track block ---

