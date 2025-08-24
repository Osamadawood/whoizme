<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../includes/helpers_links.php';

$page_title = 'Create link';
include __DIR__ . '/../partials/app_header.php';
?>
<main class="dashboard">
  <?php include __DIR__ . '/../partials/app_sidebar.php'; ?>
  <div class="container dash-grid">
    <div class="container topbar--inset">
      <?php
        $breadcrumbs = [ ['label'=>'Dashboard','href'=>'/dashboard'], ['label'=>'Links','href'=>'/links'], ['label'=>'Create','href'=>null] ];
        $topbar = [ 'search'=>['enabled'=>false] ];
        include __DIR__ . '/../partials/app_topbar.php';
      ?>
    </div>
    <section class="maincol">
      <div class="panel"><div class="panel__body">
        <div class="panel__title">Create link</div>
        <form action="/links/save.php" method="post" class="form">
          <div class="form__row"><label>Title</label><input type="text" name="title" required placeholder="My link"></div>
          <div class="form__row"><label>Destination URL</label><input type="url" name="destination_url" required placeholder="https://example.com"></div>
          <div class="form__row"><label>UTM (JSON, optional)</label><textarea name="utm_json" rows="3" placeholder='{"utm_source":"whoiz"}'></textarea></div>
          <div class="form__row"><label><input type="checkbox" name="is_active" value="1" checked> Active</label></div>
          <div class="u-mt-12"><button class="btn btn--primary" type="submit">Save</button> <a class="btn btn--ghost" href="/links">Cancel</a></div>
        </form>
      </div></div>
    </section>
  </div>
</main>
<?php include __DIR__ . '/../partials/app_footer.php'; ?>


