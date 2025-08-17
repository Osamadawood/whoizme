  </div><!-- /.container -->
</div><!-- /.page-section -->

<footer class="page-section">
  <div class="container">
    <div class="row justify-between items-center">
      <p class="text-muted">&copy; <?= date('Y') ?> Whoizme</p>
      <div class="row gap-4">
        <a class="text-muted" href="/terms">Terms</a>
        <a class="text-muted" href="/privacy">Privacy</a>
        <button class="btn btn--ghost" type="button" data-toggle-theme>Toggle theme</button>
      </div>
    </div>
  </div>
</footer>

<script>
document.addEventListener('click', (e) => {
  const t = e.target.closest('[data-toggle-theme]');
  if (!t) return;
  const root = document.documentElement;
  root.setAttribute('data-theme', root.getAttribute('data-theme') === 'light' ? 'dark' : 'light');
});
</script>
</body>
</html>