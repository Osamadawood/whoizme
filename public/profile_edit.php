<?php require __DIR__ . "/_bootstrap.php"; ?>
<?php require __DIR__ . '/../includes/auth.php'; ?>
<?php
ini_set('display_errors',1); error_reporting(E_ALL);

$config = require __DIR__ . '/../app/config.php';
require __DIR__ . '/../app/database.php';
$db = new Database($config['db']);

$uid = (int)$_SESSION['uid'];
$errors=[]; $ok=false;

/* fetch current profile */
$stmt = $db->pdo()->prepare("SELECT * FROM profiles WHERE user_id=? LIMIT 1");
$stmt->execute([$uid]);
$profile = $stmt->fetch();

/* handle submit */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username     = trim($_POST['username'] ?? '');
  $display_name = trim($_POST['display_name'] ?? '');
  $bio          = trim($_POST['bio'] ?? '');
  $avatar_url   = trim($_POST['avatar_url'] ?? '');
  $website      = trim($_POST['website'] ?? '');
  $whatsapp     = trim($_POST['whatsapp'] ?? '');
  $instagram    = trim($_POST['instagram'] ?? '');
  $twitter      = trim($_POST['twitter'] ?? '');
  $linkedin     = trim($_POST['linkedin'] ?? '');

  if (isset($_FILES['avatar_file']) && $_FILES['avatar_file']['error'] === UPLOAD_ERR_OK) {
      $ext = strtolower(pathinfo($_FILES['avatar_file']['name'], PATHINFO_EXTENSION));
      $allowed = ['jpg','jpeg','png','gif','webp'];
      if (in_array($ext, $allowed)) {
          $uploadDir = __DIR__ . '/../uploads/avatars/';
          if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
          $newName = 'avatar_'.$uid.'_'.time().'.'.$ext;
          $target = $uploadDir . $newName;
          if (move_uploaded_file($_FILES['avatar_file']['tmp_name'], $target)) {
              $avatar_url = $newName; // نخزن فقط اسم الملف
          } else {
              $errors[] = 'Failed to upload avatar';
          }
      } else {
          $errors[] = 'Invalid avatar file type';
      }
  }

  if ($username === '')        $errors[] = 'Username is required';
  if ($display_name === '')    $errors[] = 'Display name is required';
  if ($username && !preg_match('~^[a-z0-9_-]{3,30}$~i', $username))
    $errors[] = 'Username must be 3–30 chars (letters, numbers, - or _)';

  if (!$errors) {
    $st = $db->pdo()->prepare("SELECT id FROM profiles WHERE username=? AND user_id<>? LIMIT 1");
    $st->execute([$username,$uid]);
    if ($st->fetch()) $errors[] = 'Username is already taken';
  }

  if (!$errors) {
    if ($profile) {
      $sql="UPDATE profiles SET username=?, display_name=?, bio=?, avatar_url=?, website=?, whatsapp=?, instagram=?, twitter=?, linkedin=? WHERE user_id=?";
      $db->pdo()->prepare($sql)->execute([$username,$display_name,$bio,$avatar_url,$website,$whatsapp,$instagram,$twitter,$linkedin,$uid]);
    } else {
      $sql="INSERT INTO profiles (user_id,username,display_name,bio,avatar_url,website,whatsapp,instagram,twitter,linkedin)
            VALUES (?,?,?,?,?,?,?,?,?,?)";
      $db->pdo()->prepare($sql)->execute([$uid,$username,$display_name,$bio,$avatar_url,$website,$whatsapp,$instagram,$twitter,$linkedin]);
    }
    $ok=true;
    $stmt = $db->pdo()->prepare("SELECT * FROM profiles WHERE user_id=? LIMIT 1");
    $stmt->execute([$uid]);
    $profile = $stmt->fetch();
  }
}

/* helpers */
$f = fn($k)=>htmlspecialchars($profile[$k] ?? ($_POST[$k] ?? ''), ENT_QUOTES);
$publicBase = rtrim($config['base_url'], '/').'/u/';
require_once __DIR__ . '/../includes/bootstrap.php';
if (auth_role() === 'admin') {
  include INC_PATH . '/partials/admin_header.php';
} else {
  include INC_PATH . '/partials/user_header.php';
}
?>
<h2>Edit Profile</h2>

<?php if ($ok): ?><p style="color:green">Saved successfully ✅</p><?php endif; ?>
<?php if ($errors): ?><ul style="color:#b00"><?php foreach($errors as $e) echo "<li>".htmlspecialchars($e)."</li>"; ?></ul><?php endif; ?>

<form method="post" enctype="multipart/form-data">
  <label>Username<br>
    <input id="username" name="username" required value="<?= $f('username') ?>" pattern="[A-Za-z0-9_-]{3,30}">
  </label>

  <!-- public link preview -->
  <div style="margin:6px 0 18px; color:#555; font-size:.95em; display:flex; align-items:center; gap:8px; flex-wrap:wrap">
    <span>Your public link:</span>
    <code id="linkPreview" style="background:#f6f7f9; padding:4px 8px; border-radius:6px; display:inline-block">
      <?= htmlspecialchars($publicBase.($profile['username'] ?? ($_POST['username'] ?? ''))) ?>
    </code>
    <button type="button" id="copyLinkBtn" style="padding:4px 10px; border:1px solid #ddd; border-radius:6px; cursor:pointer">Copy</button>
    <span id="copyStatus" style="font-size:.9em; color:green; display:none">Copied ✓</span>
  </div>

  <label>Display name<br>
    <input name="display_name" required value="<?= $f('display_name') ?>">
  </label><br><br>

  <label>Bio<br>
    <textarea name="bio" rows="4" style="width:100%"><?= $f('bio') ?></textarea>
  </label><br><br>

  <label>Upload Profile Picture<br>
    <input type="file" name="avatar_file" accept="image/*">
  </label><br><br>

  <label>Website<br>
    <input name="website" value="<?= $f('website') ?>" placeholder="https://example.com">
  </label><br><br>

  <fieldset style="border:1px solid #ddd;padding:12px">
    <legend>Social Links</legend>
    <label>WhatsApp <input name="whatsapp" value="<?= $f('whatsapp') ?>" placeholder="+20..."></label><br>
    <label>Instagram <input name="instagram" value="<?= $f('instagram') ?>" placeholder="username"></label><br>
    <label>Twitter/X <input name="twitter" value="<?= $f('twitter') ?>" placeholder="username"></label><br>
    <label>LinkedIn <input name="linkedin" value="<?= $f('linkedin') ?>" placeholder="username or full URL"></label>
  </fieldset><br>

  <button>Save</button>
  &nbsp; <a href="/dashboard.php">Go back</a>
</form>

<script>
  (function(){
    const base   = <?= json_encode($publicBase) ?>;
    const input  = document.getElementById('username');
    const out    = document.getElementById('linkPreview');
    const copyBtn= document.getElementById('copyLinkBtn');
    const status = document.getElementById('copyStatus');

    const currentLink = () => base + (input.value || '');
    const update = () => { out.textContent = currentLink(); };

    input.addEventListener('input', update);
    update(); // initial sync

    function copyText(txt){
      if (navigator.clipboard && navigator.clipboard.writeText) {
        return navigator.clipboard.writeText(txt);
      }
      // Fallback for older browsers
      return new Promise((resolve, reject) => {
        try {
          const ta = document.createElement('textarea');
          ta.value = txt;
          ta.style.position = 'fixed';
          ta.style.top = '-1000px';
          document.body.appendChild(ta);
          ta.focus();
          ta.select();
          const ok = document.execCommand('copy');
          document.body.removeChild(ta);
          ok ? resolve() : reject(new Error('copy failed'));
        } catch (e) { reject(e); }
      });
    }

    copyBtn.addEventListener('click', () => {
      copyText(currentLink())
        .then(() => { status.style.display = 'inline'; setTimeout(() => status.style.display = 'none', 1500); })
        .catch(() => { copyBtn.textContent = 'Copy failed'; setTimeout(() => copyBtn.textContent = 'Copy', 1500); });
    });
  })();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>