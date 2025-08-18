<?php require __DIR__ . "/_bootstrap.php"; ?>
<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require __DIR__.'/../app/includes/layout.php';

require __DIR__ . '/../includes/guest_guard.php';
guest_only($_GET['return'] ?? null);

$pdo   = pdo_conn($config);
$now   = new DateTime('now', new DateTimeZone('UTC'));
$token = trim($_GET['token'] ?? '');
$errors=[]; $notice=''; $email='';

// التحقق من التوكن لو موجود
$validTokenRow=null;
if ($token!==''){
  $st=$pdo->prepare('SELECT pr.*, u.id AS uid FROM password_resets pr JOIN users u ON u.id=pr.user_id WHERE pr.token=:t LIMIT 1');
  $st->execute([':t'=>$token]);
  $validTokenRow=$st->fetch();
  if(!$validTokenRow) $errors[]='Invalid or expired reset link.';
  else{
    if($validTokenRow['used_at']!==null) $errors[]='This reset link has already been used.';
    elseif(new DateTime($validTokenRow['expires_at'],new DateTimeZone('UTC'))<$now) $errors[]='This reset link has expired.';
  }
}

// طلب إرسال لينك
if ($_SERVER['REQUEST_METHOD']==='POST' && $token===''){
  csrf_check();
  $email=trim(strtolower($_POST['email']??''));
  if(!$email || !filter_var($email,FILTER_VALIDATE_EMAIL)) $errors[]='Please enter a valid email address.';
  if(!$errors){
    $st=$pdo->prepare('SELECT id,email FROM users WHERE email=:e LIMIT 1');
    $st->execute([':e'=>$email]); $u=$st->fetch();
    if(!$u) $errors[]='No account found for that email.';
    else{
      // تنظيف أي توكن سابق
      $pdo->prepare('DELETE FROM password_resets WHERE user_id=:uid AND used_at IS NULL')->execute([':uid'=>$u['id']]);
      $plain=bin2hex(random_bytes(32));
      $exp=(clone $now)->modify('+60 minutes')->format('Y-m-d H:i:s');
      $pdo->prepare('INSERT INTO password_resets(user_id,token,expires_at) VALUES(:uid,:t,:e)')
          ->execute([':uid'=>$u['id'], ':t'=>$plain, ':e'=>$exp]);
      $url=base_url($config).'/reset.php?token='.urlencode($plain);

      // إرسال أو fallback
      $subject='Reset your Whoiz.me password';
      $body="Click the link to reset your password:\n\n{$url}\n\nIf you didn't request this, ignore.";
      // dev: سجل في اللوج كفاية
      $logDir=dirname(__DIR__).'/storage/logs'; if(!is_dir($logDir)) @mkdir($logDir,0777,true);
      @file_put_contents($logDir.'/mail.log',"[".date('c')."] To: {$u['email']}\n{$subject}\n{$body}\n\n",FILE_APPEND);

      if (!empty($config['dev'])) {
        $notice='A reset link has been sent to your email.';
      } else {
        // جرّب SMTP بسيط (لو فشل نعرض fallback)
        $ok=false;
        try {
          $transport = ($config['mail']['secure']??'tls')==='ssl' ? 'ssl://' : '';
          $fp=@stream_socket_client(($transport.$config['mail']['host']).':'.(int)$config['mail']['port'], $errno,$err,15);
          if($fp){ fclose($fp); $ok=true; } // smoke test فقط
        } catch(Throwable $e){ $ok=false; }
        if($ok) { $notice='A reset link has been sent to your email.'; }
        else {
          $notice='Email sending seems unavailable. Use this link to reset your password: '
            . '<br><input type="text" readonly style="width:100%;margin-top:8px;padding:10px;border:1px solid #e2e5f0;border-radius:8px" value="'.h($url).'">'
            . '<div style="margin-top:8px;font-size:13px;color:#666">This is shown because SMTP is not delivering right now. The token still expires in 60 minutes.</div>';
        }
      }
      $email='';
    }
  }
}

// تعيين كلمة مرور جديدة
if ($token!=='' && !empty($validTokenRow) && empty($errors) && $_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['new_password'])){
  csrf_check();
  $p1=(string)($_POST['new_password']??''); $p2=(string)($_POST['confirm_password']??'');
  if(strlen($p1)<8) $errors[]='Password must be at least 8 characters.';
  elseif($p1!==$p2) $errors[]='Passwords do not match.';
  else{
    $hash=password_hash($p1,PASSWORD_DEFAULT);
    $pdo->prepare('UPDATE users SET password_hash=:h, must_change_password=0 WHERE id=:uid')
        ->execute([':h'=>$hash,':uid'=>$validTokenRow['user_id']]);
    $pdo->prepare('UPDATE password_resets SET used_at=NOW() WHERE id=:id')
        ->execute([':id'=>$validTokenRow['id']]);
    flash('ok','Your password has been changed successfully.');
    header('Location: /login.php'); exit;
  }
}

render_header($token===''?'Reset password':'Set a new password'); ?>
<div class="card">
  <h1><?= $token===''?'Reset password':'Set a new password' ?></h1>
  <?php if($msg=flash('ok')): ?><div class="alert ok"><?=h($msg)?></div><?php endif; ?>
  <?php if($errors): ?><div class="alert error"><?=h(implode(' ',$errors))?></div><?php endif; ?>
  <?php if($notice && !$errors): ?><div class="alert ok"><?=$notice?></div><?php endif; ?>

  <?php if($token===''): ?>
    <form method="post" action="/reset.php">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES) ?>">
      <input type="hidden" name="token" value="<?= htmlspecialchars($_GET['token'] ?? '', ENT_QUOTES) ?>">
      <?php csrf_field(); ?>
      <label>Email</label>
      <input type="email" name="email" value="<?=h($email)?>" placeholder="you@example.com" required>
      <div class="button-row"><button type="submit">Send reset link</button></div>
    </form>
  <?php elseif(!empty($validTokenRow) && empty($errors)): ?>
    <form method="post" action="/reset.php?token=<?=urlencode($token)?>">
      <?php csrf_field(); ?>
      <label>New password</label>
      <input type="password" name="new_password" placeholder="At least 8 characters" required>
      <label>Confirm password</label>
      <input type="password" name="confirm_password" required>
      <div class="button-row"><button type="submit">Update password</button></div>
    </form>
  <?php endif; ?>
</div>
<?php render_footer();