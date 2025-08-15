<?php require __DIR__ . "/_bootstrap.php"; ?>
<?php require __DIR__ . '/../includes/auth.php'; ?>
<?php
ini_set('display_errors',1); error_reporting(E_ALL);

$config = require __DIR__ . '/../app/config.php';
require __DIR__ . '/../app/database.php';
$db = new Database($config['db']);
$uid = (int)$_SESSION['uid'];

$st = $db->pdo()->prepare("SELECT username FROM profiles WHERE user_id=? LIMIT 1");
$st->execute([$uid]);
$p = $st->fetch();
if (!$p || empty($p['username'])) {
  echo "<p style='font-family:system-ui;max-width:720px;margin:40px auto;color:#b00'>Complete profile first. <a href='/profile_edit.php'>Edit profile</a></p>";
  exit;
}
$defaultTarget = rtrim($config['base_url'],'/').'/u/'.$p['username'];

$st = $db->pdo()->prepare("SELECT * FROM short_links WHERE user_id=? LIMIT 1");
$st->execute([$uid]);
$link = $st->fetch();

$errors=[]; $ok=false;
function random_code($len=6){ $c='ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; $s=''; for($i=0;$i<$len;$i++) $s.=$c[random_int(0,strlen($c)-1)]; return $s; }

if ($_SERVER['REQUEST_METHOD']==='POST') {
  $label = trim($_POST['label'] ?? 'My profile QR');
  $target = trim($_POST['target_url'] ?? $defaultTarget);
  if (!filter_var($target, FILTER_VALIDATE_URL)) $errors[]='Invalid target URL';

  if (!$errors) {
    if ($link) {
      $db->pdo()->prepare("UPDATE short_links SET label=?, target_url=? WHERE id=? AND user_id=?")
        ->execute([$label,$target,$link['id'],$uid]);
    } else {
      do {
        $code = random_code(6);
        $chk = $db->pdo()->prepare("SELECT id FROM short_links WHERE code=? LIMIT 1");
        $chk->execute([$code]);
      } while ($chk->fetch());
      $db->pdo()->prepare("INSERT INTO short_links (user_id, code, target_url, label) VALUES (?,?,?,?)")
        ->execute([$uid,$code,$target,$label]);

      $st = $db->pdo()->prepare("SELECT * FROM short_links WHERE user_id=? LIMIT 1");
      $st->execute([$uid]);
      $link = $st->fetch();
    }
    $ok=true;
  }
}

$code = $link['code'] ?? null;
$shortUrl = $code ? rtrim($config['base_url'],'/').'/r/'.$code : null;

require_once __DIR__ . '/../includes/bootstrap.php';
if (auth_role() === 'admin') {
  include INC_PATH . '/partials/admin_header.php';
} else {
  include INC_PATH . '/partials/user_header.php';
}
?>
<h2><?php echo __t('dynamic_qr'); ?></h2>

<?php if($ok): ?><p style="color:green"><?= __t('saved_success'); ?> ✅</p><?php endif; ?>
<?php if($errors): ?><ul style="color:#b00"><?php foreach($errors as $e) echo "<li>".htmlspecialchars($e)."</li>"; ?></ul><?php endif; ?>

<form method="post">
  <label>Label<br><input name="label" value="<?= htmlspecialchars($link['label'] ?? 'My profile QR') ?>"></label><br><br>
  <label>Target URL<br><input name="target_url" style="width:100%" value="<?= htmlspecialchars($link['target_url'] ?? $defaultTarget) ?>"></label><br><br>
  <button><?php echo __t('save'); ?></button>
  &nbsp; <a href="/dashboard.php"><?php echo __t('go_back'); ?></a>
</form>

<?php if($shortUrl): ?>
<hr>
<p>Short link: <a target="_blank" href="<?= htmlspecialchars($shortUrl) ?>"><?= htmlspecialchars($shortUrl) ?></a></p>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>