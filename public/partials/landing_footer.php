<?php
// Landing footer (public pages footer)
// يعتمد على متغيرات الـ CSS من الثيم: --surface, --card, --text, --muted, --border, --radius-*, --shadow-*
?>
<footer class="landing-footer">
  <div class="wrap">

    <div class="footer-grid">
      <!-- العمود الكبير (روابط) -->
      <section class="links-card card">

        <div class="cols">
          <div class="col">
            <h4 class="h6">Main pages</h4>
            <ul class="link-list">
              <li><a href="/index.php">Home</a></li>
              <li><a href="/features.php">Features</a></li>
              <li><a href="/qr.php">Create QR</a></li>
              <li><a href="/links.php">Create Links</a></li>
              <li><a href="/landing-pages.php">Landing Pages</a> <span class="soon">soon</span></li>
            </ul>
          </div>

          <div class="col">
            <h4 class="h6">Product</h4>
            <ul class="link-list">
              <li><a href="/login.php">Sign in</a></li>
              <li><a href="/register.php">Create account</a></li>
              <li><a href="/forgot.php">Forgot password</a></li>
            </ul>
          </div>

          <div class="col">
            <h4 class="h6">Company</h4>
            <ul class="link-list">
              <li><a href="/terms.php">Terms</a></li>
              <li><a href="/privacy.php">Privacy</a></li>
              <li><a href="/faq.php">FAQs</a></li>
              <li><a href="/contact-us.php">Contact Us</a></li>
            </ul>
          </div>
        </div>

        <hr class="divider" />
        <div class="mb-12"></div>

        <div class="brand-row">
          <a class="brand" href="/index.php">
            <img class="brand-mark" src="/assets/img/logo.svg" alt="" loading="lazy" />
            <span class="brand-name">Whoizme</span>
          </a>

          <p class="copy">© <?php echo date('Y'); ?> Whoizme. All rights reserved.</p>
        </div>
      </section>

      <!-- صندوق الـ CTA على اليمين -->
      <aside class="cta-card card">
        <div class="cta-inner">
          <h3 class="cta-title">Get started with Whoizme today</h3>

          <div class="cta-actions">
            <a class="btn btn--sm btn--primary" href="/register.php">Get started</a>
            <a class="btn btn--sm btn--ghost" href="/features.php">Explore features</a>
          </div>

        </div>
      </aside>
    </div>
    
  </div>
</footer>