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
              <li><a href="/">Home</a></li>
              <li><a href="/features">Features</a></li>
              <li><a href="/qr-codes">Create QR</a></li>
              <li><a href="/links">Create Links</a></li>
              <li><a href="/landing-pages">Landing Pages</a> <span class="soon">soon</span></li>
            </ul>
          </div>

          <div class="col">
            <h4 class="h6">Product</h4>
            <ul class="link-list">
              <li><a href="/login">Sign in</a></li>
              <li><a href="/register">Create account</a></li>
              <li><a href="/forgot-password">Forgot password</a></li>
            </ul>
          </div>

          <div class="col">
            <h4 class="h6">Company</h4>
            <ul class="link-list">
              <li><a href="/terms">Terms</a></li>
              <li><a href="/privacy">Privacy</a></li>
              <li><a href="/faq">FAQs</a></li>
              <li><a href="/contact-us">Contact Us</a></li>
            </ul>
          </div>
        </div>

        <hr class="divider" />
        <div class="mb-12"></div>

        <div class="brand-row">
          <a class="brand" href="/">
            <img class="brand-mark" src="/assets/img/logo.png" alt="" loading="lazy" />
            <span class="brand-name">Whoiz.me</span>
          </a>

          <p class="copy">© <?php echo date('Y'); ?> Whoiz.me. All rights reserved.</p>
        </div>
      </section>

      <!-- صندوق الـ CTA على اليمين -->
      <aside class="cta-card card">
        <div class="cta-inner">
          <h3 class="cta-title">Get started with Whoiz.me today</h3>

          <div class="cta-actions">
            <a class="btn btn--sm btn--primary" href="/register">Get started</a>
            <a class="btn btn--sm btn--ghost" href="/features">Explore features</a>
          </div>

        </div>
      </aside>
    </div>
    
  </div>
</footer>