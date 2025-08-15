<?php
$config = require __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/database.php';
require_once __DIR__ . '/../includes/mailer.php';
session_start();
$db = new Database($config['db']);

if (!isset($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['csrf'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && hash_equals($csrf, $_POST['csrf'] ?? '')) {
  $email = trim($_POST['email'] ?? '');
  if ($email !== '') {
    $st = $db->pdo()->prepare("SELECT id, name FROM users WHERE email=? LIMIT 1");
    $st->execute([$email]);
    if ($u = $st->fetch()) {
      $raw = bin2hex(random_bytes(32));
      $hash = hash('sha256', $raw);
      $expires = (new DateTime('+1 hour'))->format('Y-m-d H:i:s');
      $db->pdo()->prepare("INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?,?,?)")
        ->execute([(int)$u['id'], $hash, $expires]);

      $link = rtrim($config['base_url'],'/').'/reset.php?token='.$raw;
      $html = '<p>Hello '.htmlspecialchars($u['name']).',</p>
               <p>Reset your password:</p><p><a href="'.$link.'">'.$link.'</a></p>';
      send_app_mail($config, $email, 'Reset your password', $html);
    }
    $_SESSION['flash'] = 'If the email exists, a reset link has been sent.';
    header('Location: /forgot.php'); exit;
  }
}
$flash = $_SESSION['flash'] ?? ''; unset($_SESSION['flash']);
?>
<!doctype html><html lang="en"><meta charset="utf-8">
<title>Forgot password</title><meta name="viewport" content="width=device-width,initial-scale=1">
<body style="font-family:system-ui;max-width:480px;margin:40px auto;line-height:1.7">
  <h2>Forgot password</h2>
  <?php if($flash): ?><div style="background:#eef8ff;border:1px solid #cfe8ff;padding:10px;border-radius:10px"><?= htmlspecialchars($flash) ?></div><?php endif; ?>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= $csrf ?>">
    <label>Email<br><input type="email" name="email" required style="width:100%"></label><br><br>
    <button>Send reset link</button>
  </form>
</body></html>