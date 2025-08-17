  </div><!-- /.container -->
</main>

<footer class="page-section">
  <div class="container">
    <div class="grid grid--3">
      <div>
        <h4>Whoizme</h4>
        <p class="text-muted">QR codes, short links, and analytics — all in one place.</p>
      </div>
      <div>
        <h6>Product</h6>
        <ul class="stack">
          <li><a href="/#features">Features</a></li>
          <li><a href="/#pricing">Pricing</a></li>
          <li><a href="/terms.php">Terms</a></li>
          <li><a href="/privacy.php">Privacy</a></li>
        </ul>
      </div>
      <div>
        <h6>Support</h6>
        <ul class="stack">
          <li><a href="/help.php">Help Center</a></li>
          <li><a href="mailto:support@whoiz.me">support@whoiz.me</a></li>
        </ul>
      </div>
    </div>

    <p class="mt-6 text-muted">&copy; <?= date('Y') ?> Whoizme. All rights reserved.</p>
  </div>
</footer>

<script>
// تبديل الثيم (اختياري)
document.addEventListener('click', (e) => {
  const t = e.target.closest('[data-toggle-theme]');
  if (!t) return;
  const root = document.documentElement;
  const isLight = root.getAttribute('data-theme') === 'light';
  root.setAttribute('data-theme', isLight ? 'dark' : 'light');
});
</script>
</body>
</html>