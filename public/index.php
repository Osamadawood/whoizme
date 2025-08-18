
<?php
require_once __DIR__ . '/_bootstrap.php';

// Determine login state from existing session (bootstrap defines current_user_id())
$uid = function_exists('current_user_id')
    ? (int) current_user_id()
    : (int) ($_SESSION['uid'] ?? $_SESSION['user_id'] ?? 0);

$IS_LOGGED_IN = $uid > 0;

// If logged in, send user to dashboard (unless explicitly previewing landing)
if ($IS_LOGGED_IN && empty($_GET['preview'])) {
    header('Location: /dashboard');
    exit;
}
?>
<?php define('PUBLIC_PAGE', true); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Whoizme — Free QR Code Generator</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet" />
<style>
  :root{--brand:#0d6efd;--ink:#0f172a}
  body{font-family:system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,"Noto Sans";
       background:#f7f9fc;color:#0f172a}
  .navbar-brand{font-weight:700}
  /* Builder */
  #builderWrap{padding:42px 0 56px}
  .builder-card{border:0;background:#fff;border-radius:14px;box-shadow:0 10px 30px rgba(13,110,253,.08)}
  .pill-tabs .nav-link{border:1px solid #e7eaf3;margin-right:.5rem;border-radius:999px;padding:.4rem .9rem;font-weight:600;color:#334155}
  .pill-tabs .nav-link.active{background:var(--brand);color:#fff;border-color:var(--brand)}
  #previewBox{background:#fff;border-radius:14px;box-shadow:0 10px 30px rgba(2,6,23,.08)}
  #previewStage{background:#fff;border-radius:12px;padding:12px;display:inline-block}
  .acc-btn{width:100%;text-align:left}
  .disabled-soft{opacity:.55;cursor:not-allowed}
  footer{background:#0b1220;color:#a7b1c2}
  footer a{color:#cbd5e1;text-decoration:none}
  .floating-cta{position:fixed;bottom:18px;right:18px;z-index:1080}
  /* Cards + surface */
  .builder-card, #previewBox{
    border-radius: 16px;
    box-shadow: 0 12px 30px rgba(2,6,23,.06), 0 2px 8px rgba(2,6,23,.04);
  }
  #builderWrap{ padding: 40px 0 64px }

  /* Section titles */
  #builderWrap h1.h3 { letter-spacing: .2px }

  /* Form cosmetics */
  .form-control-lg { border-radius: 12px }
  .form-control, .form-select { border-radius: 12px }
  .form-control:focus, .form-select:focus {
    box-shadow: 0 0 0 .2rem rgba(13,110,253,.15);
  }

  /* CTA row */
  #btnGenerate { padding:.7rem 1.1rem; border-radius: 12px }
  #btnDownloadPNG, #btnDownloadJPG, #btnDownloadSVG { border-radius: 12px }

  /* Tabs: حجم أيقونة أوضح */
  .pill-tabs .nav-link i { font-size: 1rem; opacity:.95; }

  /* Range nicer */
  input[type=range]::-webkit-slider-thumb{ width:18px;height:18px;border-radius:50% }

  /* Checkerboard under the canvas */
  #previewStage{
    background:
      linear-gradient(45deg,#f5f7fb 25%,transparent 25%) 0 0/20px 20px,
      linear-gradient(45deg,transparent 75%,#f5f7fb 75%) 0 0/20px 20px,
      linear-gradient(45deg,transparent 25%,#f5f7fb 25%) 10px 10px/20px 20px,
      linear-gradient(45deg,#f5f7fb 75%,transparent 75%) 10px 10px/20px 20px;
    padding: 16px;
    border-radius: 14px;
    border: 1px solid #e9edf3;
  }
  #qrCanvas { display:block; image-rendering: crisp-edges; }
  @media (min-width: 992px){
    #previewBox{ position: sticky; top: 88px }
  }

  /* --- UX tweaks (swatches, disabled hints, pulse) --- */
  .swatch-row{ display:flex; align-items:center; gap:.4rem; margin-top:.35rem }
  .swatch{ width:24px; height:24px; border-radius:6px; border:1px solid #e5e9f2; cursor:pointer; padding:0; background-clip:padding-box }
  .swatch:is(:hover,:focus){ outline:2px solid rgba(13,110,253,.25) }
  .swatch[aria-pressed="true"]{ outline:2px solid rgba(13,110,253,.5) }
  .btn[disabled]{ opacity:.55; cursor:not-allowed }
  @keyframes pulseStage{
    from{ box-shadow:0 0 0 0 rgba(13,110,253,.25) }
    to  { box-shadow:0 0 0 18px rgba(13,110,253,0) }
  }
  #previewStage.pulse{ animation:pulseStage .6s ease-out }
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center" href="/">
      <img src="/uploads/branding/logo-1755010621.webp" height="24" class="me-2" alt="" onerror="this.style.display='none'">
      Whoizme
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain"><span class="navbar-toggler-icon"></span></button>
    <div id="navMain" class="collapse navbar-collapse justify-content-end align-items-center">
      <ul class="navbar-nav align-items-lg-center">
        <li class="nav-item"><a class="nav-link" href="#builderWrap">Generator</a></li>
        <li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
        <li class="nav-item"><a class="nav-link" href="#plans">Plans</a></li>
      </ul>
      <div class="ms-lg-3 d-flex gap-2">
        <?php if(!$IS_LOGGED_IN): ?>
          <a href="login.php" class="btn btn-outline-secondary btn-sm">Login</a>
          <a href="login.php?mode=signup" class="btn btn-primary btn-sm">Create free account</a>
        <?php else: ?>
          <a href="dashboard.php" class="btn btn-primary btn-sm">Go to Dashboard</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>

<!-- Builder -->
<section id="builderWrap">
  <div class="container">
    <div class="row g-4">
      <!-- Left -->
      <div class="col-lg-7">
        <div class="builder-card p-4">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <h1 class="h3 mb-0">Create your QR</h1>
            <span class="text-muted small">No sign-up needed for basic QR</span>
          </div>

          <!-- Tabs (order as requested) -->
          <ul class="nav pill-tabs mb-3" id="qrTabs" role="tablist">
            <li class="nav-item"><button class="nav-link active" id="t-url"   data-bs-toggle="tab" data-bs-target="#p-url"   type="button" role="tab"><i class="bi bi-globe2 me-1"></i>URL</button></li>
            <li class="nav-item"><button class="nav-link"         id="t-vc"    data-bs-toggle="tab" data-bs-target="#p-vc"    type="button" role="tab"><i class="bi bi-person-vcard me-1"></i>vCard</button></li>
            <li class="nav-item"><button class="nav-link"         id="t-text"  data-bs-toggle="tab" data-bs-target="#p-text"  type="button" role="tab"><i class="bi bi-chat-left-text me-1"></i>Text</button></li>
            <li class="nav-item"><button class="nav-link"         id="t-email" data-bs-toggle="tab" data-bs-target="#p-email" type="button" role="tab"><i class="bi bi-envelope-at me-1"></i>E‑mail</button></li>
            <li class="nav-item"><button class="nav-link"         id="t-wifi"  data-bs-toggle="tab" data-bs-target="#p-wifi"  type="button" role="tab"><i class="bi bi-wifi me-1"></i>Wi‑Fi</button></li>
            <li class="nav-item"><button class="nav-link"         id="t-pdf"   data-bs-toggle="tab" data-bs-target="#p-pdf"   type="button" role="tab"><i class="bi bi-filetype-pdf me-1"></i>PDF</button></li>
            <li class="nav-item">
              <button class="nav-link" id="t-app"
                      data-bs-toggle="tab" data-bs-target="#p-app" type="button" role="tab">
                <i class="bi bi-app me-1"></i>App Stores
              </button>
            </li>
            <li class="nav-item">
              <button class="nav-link" id="t-img"
                      data-bs-toggle="tab" data-bs-target="#p-img" type="button" role="tab">
                <i class="bi bi-image me-1"></i>Images
              </button>
            </li>
          </ul>

          <div class="tab-content">
            <!-- URL -->
            <div class="tab-pane fade show active" id="p-url" role="tabpanel">
              <label class="form-label">Enter your website</label>
              <input type="url" id="f_url" class="form-control form-control-lg" placeholder="Ex: http://www.whoiz.me">
              <div class="form-text">Your QR code will be generated automatically.</div>
            </div>

            <!-- vCard -->
            <div class="tab-pane fade" id="p-vc" role="tabpanel">
              <div class="row g-3">
                <div class="col-md-6"><label class="form-label">First name</label><input id="vc_first" class="form-control"></div>
                <div class="col-md-6"><label class="form-label">Last name</label><input id="vc_last" class="form-control"></div>

                <div class="col-md-6"><label class="form-label">Mobile</label><input id="vc_mobile" class="form-control"></div>
                <div class="col-md-6"><label class="form-label">Fax</label><input id="vc_fax" class="form-control"></div>

                <div class="col-md-6"><label class="form-label">Phone</label><input id="vc_phone" class="form-control"></div>
                <div class="col-md-6"><label class="form-label">Email</label><input id="vc_email" type="email" class="form-control"></div>

                <div class="col-md-6"><label class="form-label">Company</label><input id="vc_company" class="form-control"></div>
                <div class="col-md-6"><label class="form-label">Job title</label><input id="vc_job" class="form-control"></div>

                <div class="col-12"><label class="form-label">Street</label><input id="vc_street" class="form-control"></div>

                <div class="col-md-6"><label class="form-label">City</label><input id="vc_city" class="form-control"></div>
                <div class="col-md-6"><label class="form-label">ZIP</label><input id="vc_zip" class="form-control"></div>

                <div class="col-md-6"><label class="form-label">State</label><input id="vc_state" class="form-control"></div>
                <div class="col-md-6"><label class="form-label">Country</label><input id="vc_country" class="form-control"></div>

                <div class="col-12"><label class="form-label">Website</label><input id="vc_url" type="url" class="form-control"></div>
              </div>
            </div>

            <!-- TEXT -->
            <div class="tab-pane fade" id="p-text" role="tabpanel">
              <label class="form-label">Text content</label>
              <textarea id="f_text" rows="3" class="form-control" placeholder="Write any text"></textarea>
            </div>

            <!-- EMAIL -->
            <div class="tab-pane fade" id="p-email" role="tabpanel">
              <div class="row g-3">
                <div class="col-md-6"><label class="form-label">To</label><input id="em_to" type="email" class="form-control"></div>
                <div class="col-md-6"><label class="form-label">Subject</label><input id="em_subject" class="form-control"></div>
                <div class="col-12"><label class="form-label">Body</label><textarea id="em_body" class="form-control" rows="3"></textarea></div>
              </div>
            </div>

            <!-- WIFI -->
            <div class="tab-pane fade" id="p-wifi" role="tabpanel">
              <div class="row g-3">
                <div class="col-md-6"><label class="form-label">SSID</label><input id="wf_ssid" class="form-control"></div>
                <div class="col-md-6"><label class="form-label">Password</label><input id="wf_pwd" class="form-control"></div>
                <div class="col-md-6">
                  <label class="form-label">Security</label>
                  <select id="wf_auth" class="form-select"><option value="WPA">WPA/WPA2</option><option value="WEP">WEP</option><option value="nopass">None</option></select>
                </div>
                <div class="col-md-6 d-flex align-items-end">
                  <div class="form-check"><input class="form-check-input" id="wf_hidden" type="checkbox"><label class="form-check-label" for="wf_hidden">Hidden network</label></div>
                </div>
              </div>
            </div>

            <!-- PDF -->
            <div class="tab-pane fade" id="p-pdf" role="tabpanel">
              <div class="row g-3">
                <div class="col-md-8">
                  <label class="form-label">PDF link</label>
                  <input type="url" id="pdf_url" class="form-control" placeholder="https://…/file.pdf">
                  <div class="form-text">Paste a public link OR upload a PDF below.</div>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                  <div class="w-100">
                    <label class="form-label">Upload PDF</label>
                    <input type="file" id="pdf_file" class="form-control" accept="application/pdf">
                    <div class="form-text">Uploaded locally for preview/QR only.</div>
                  </div>
                </div>
              </div>
            </div>

            <!-- App Stores -->
            <div class="tab-pane fade" id="p-app" role="tabpanel">
              <?php if(!$IS_LOGGED_IN): ?>
                <div class="p-4 border rounded text-center">
                  <div class="mb-2 display-6 text-warning"><i class="bi bi-lock"></i></div>
                  <p class="mb-3">App Stores is a members feature. Create a free account to use it.</p>
                  <a href="login.php?mode=signup" class="btn btn-primary btn-sm">Create free account</a>
                  <a href="login.php" class="btn btn-link btn-sm">Login</a>
                </div>
              <?php else: ?>
                <div class="row g-3">
                  <div class="col-md-6"><label class="form-label">App Store (iOS)</label><input id="app_ios" type="url" class="form-control" placeholder="https://apps.apple.com/…"></div>
                  <div class="col-md-6"><label class="form-label">Google Play (Android)</label><input id="app_android" type="url" class="form-control" placeholder="https://play.google.com/store/apps/details?id=…"></div>
                </div>
              <?php endif; ?>
            </div>

            <!-- Images -->
            <div class="tab-pane fade" id="p-img" role="tabpanel">
              <?php if(!$IS_LOGGED_IN): ?>
                <div class="p-4 border rounded text-center">
                  <div class="mb-2 display-6 text-warning"><i class="bi bi-lock"></i></div>
                  <p class="mb-3">Images QR is a members feature. Create a free account to use it.</p>
                  <a href="login.php?mode=signup" class="btn btn-primary btn-sm">Create free account</a>
                  <a href="login.php" class="btn btn-link btn-sm">Login</a>
                </div>
              <?php else: ?>
                <label class="form-label">Image URL</label>
                <input id="img_url" type="url" class="form-control" placeholder="https://…/image.jpg">
              <?php endif; ?>
            </div>
          </div>

          <div class="mt-4">
            <a href="#" id="uploadAny" class="small text-decoration-none">
              <i class="bi bi-upload me-1"></i>Upload any file <span class="text-muted">( .jpg, .pdf, .mp3, .docx, .pptx )</span>
            </a>
          </div>

          <hr class="my-4"/>

          <!-- Controls moved to preview; keep only Generate here -->
          <div class="mt-3 d-flex gap-2 align-items-center">
            <button id="btnGenerate" class="btn btn-primary"><i class="bi bi-magic me-1"></i>Generate</button>
            <?php if ($IS_LOGGED_IN): ?>
              <button id="btnSaveQR" class="btn btn-success"><i class="bi bi-bookmark-plus me-1"></i>Save to my QR</button>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Right: preview + frames / shape / logo -->
      <div class="col-lg-5">
        <div id="previewBox" class="p-4">
          <div class="d-flex align-items-center justify-content-between">
            <h6 class="text-muted mb-3">Live preview</h6>
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" id="trackSwitch">
              <label class="form-check-label" for="trackSwitch">Scan tracking</label>
            </div>
          </div>

          <div class="text-center mb-3">
            <div id="previewStage"><canvas id="qrCanvas" width="160" height="160"></canvas></div>
          </div>

          <!-- Instant controls -->
          <div class="row g-3 align-items-end mb-3">
            <div class="col-md-4">
              <label class="form-label">Size</label>
              <input type="range" min="120" max="600" value="160" id="opt_size" class="form-range">
            </div>
            <div class="col-md-4">
              <label class="form-label">Color</label>
              <div class="d-flex flex-column">
                <input type="color" id="opt_colorDark" value="#000000" class="form-control form-control-color">
                <div class="swatch-row">
                  <button type="button" class="swatch" style="background:#000000" data-swatch-dark="#000000" aria-pressed="true"></button>
                  <button type="button" class="swatch" style="background:#0d6efd" data-swatch-dark="#0d6efd"></button>
                  <button type="button" class="swatch" style="background:#10b981" data-swatch-dark="#10b981"></button>
                  <button type="button" class="swatch" style="background:#ef4444" data-swatch-dark="#ef4444"></button>
                  <button type="button" class="swatch" style="background:#f59e0b" data-swatch-dark="#f59e0b"></button>
                </div>
              </div>
            </div>
            <div class="col-md-4">
              <label class="form-label">Background</label>
              <div class="d-flex flex-column">
                <input type="color" id="opt_colorLight" value="#ffffff" class="form-control form-control-color">
                <div class="swatch-row">
                  <button type="button" class="swatch" style="background:#ffffff" data-swatch-light="#ffffff" aria-pressed="true"></button>
                  <button type="button" class="swatch" style="background:#f8fafc" data-swatch-light="#f8fafc"></button>
                  <button type="button" class="swatch" style="background:#f1f5f9" data-swatch-light="#f1f5f9"></button>
                  <button type="button" class="swatch" style="background:#e2e8f0" data-swatch-light="#e2e8f0"></button>
                  <button type="button" class="swatch" style="background:#000000" data-swatch-light="#000000" title="Transparent look? keep white for contrast"></button>
                </div>
              </div>
            </div>
          </div>

          <!-- Design templates (always visible) -->
          <div class="border-top pt-3">
            <div class="row g-3">
              <div class="col-12">
                <label class="form-label fw-semibold mb-1">Templates</label>
                <div class="d-flex flex-wrap gap-2">
                  <button type="button" class="btn btn-outline-secondary btn-sm temp-btn" data-temp="minimal">Minimal</button>
                  <button type="button" class="btn btn-outline-secondary btn-sm temp-btn" data-temp="badge">Scan&nbsp;Badge</button>
                  <button type="button" class="btn btn-outline-secondary btn-sm temp-btn" data-temp="ribbon">Ribbon&nbsp;Black</button>
                  <button type="button" class="btn btn-outline-secondary btn-sm temp-btn" data-temp="brand">Brand&nbsp;Blue</button>
                </div>
                <div class="form-text">Pick a starting style; you can fine‑tune colors above.</div>
              </div>
            </div>
            <div class="d-flex flex-wrap gap-2 mt-2">
              <button id="btnDownloadPNG" class="btn btn-outline-secondary" disabled>Download PNG</button>
              <button id="btnDownloadJPG" class="btn btn-outline-secondary" disabled>Download JPG</button>
              <button id="btnDownloadSVG" class="btn btn-outline-secondary" disabled>Print quality (SVG)</button>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</section>

<!-- (مختصر) Features / Pricing / Footer كما هي -->
<section id="features" class="py-5">
  <div class="container">
    <h2 class="text-center fw-bold mb-4">Why Whoizme</h2>
    <div class="row text-center">
      <div class="col-md-4 mb-4"><div class="display-6 text-primary"><i class="bi bi-graph-up-arrow"></i></div><h5>Analytics</h5><p class="text-muted">Track scans with your account.</p></div>
      <div class="col-md-4 mb-4"><div class="display-6 text-primary"><i class="bi bi-palette2"></i></div><h5>Custom designs</h5><p class="text-muted">Pick colors, frames and add your logo.</p></div>
      <div class="col-md-4 mb-4"><div class="display-6 text-primary"><i class="bi bi-rocket"></i></div><h5>Fast & free</h5><p class="text-muted">No sign‑up for basic codes. Upgrade anytime.</p></div>
    </div>
  </div>
</section>

<section id="plans" class="py-5 bg-white border-top">
  <div class="container">
    <h2 class="text-center fw-bold mb-4">Plans & Features</h2>
    <p class="text-center text-muted mb-4">No prices for now — just a quick comparison between guests and members.</p>

    <div class="table-responsive">
      <table class="table align-middle">
        <thead>
          <tr>
            <th style="min-width:220px">Feature</th>
            <th class="text-center">Guest (no account)</th>
            <th class="text-center">Member (free account)</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>Generate basic QR (URL / Text / E‑mail / Wi‑Fi / PDF upload or link)</td>
            <td class="text-center"><span class="text-success">✔</span></td>
            <td class="text-center"><span class="text-success">✔</span></td>
          </tr>
          <tr>
            <td>Color & Background + quick templates</td>
            <td class="text-center"><span class="text-success">✔</span> (limited)</td>
            <td class="text-center"><span class="text-success">✔</span></td>
          </tr>
          <tr>
            <td>Frames / Badges</td>
            <td class="text-center"><span class="text-success">✔</span> (basic)</td>
            <td class="text-center"><span class="text-success">✔</span> (all)</td>
          </tr>
          <tr>
            <td>Download PNG / JPG</td>
            <td class="text-center"><span class="text-success">✔</span></td>
            <td class="text-center"><span class="text-success">✔</span></td>
          </tr>
          <tr>
            <td>Print‑quality SVG</td>
            <td class="text-center"><span class="text-danger">✖</span></td>
            <td class="text-center"><span class="text-success">✔</span></td>
          </tr>
          <tr>
            <td>Upload Logo overlay</td>
            <td class="text-center"><span class="text-danger">✖</span></td>
            <td class="text-center"><span class="text-success">✔</span></td>
          </tr>
          <tr>
            <td>Scan tracking & Analytics</td>
            <td class="text-center"><span class="text-danger">✖</span></td>
            <td class="text-center"><span class="text-success">✔</span></td>
          </tr>
          <tr>
            <td>Save to dashboard & manage history</td>
            <td class="text-center"><span class="text-danger">✖</span></td>
            <td class="text-center"><span class="text-success">✔</span></td>
          </tr>
          <tr>
            <td>App Stores / Images QR types</td>
            <td class="text-center"><span class="text-danger">✖</span></td>
            <td class="text-center"><span class="text-success">✔</span></td>
          </tr>
          <tr>
            <td>vCard (full fields)</td>
            <td class="text-center"><span class="text-success">✔</span> (basic)</td>
            <td class="text-center"><span class="text-success">✔</span></td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="d-flex flex-column flex-md-row justify-content-center gap-3 mt-3">
      <a href="#builderWrap" class="btn btn-outline-primary">Continue as guest</a>
      <a href="login.php?mode=signup" class="btn btn-primary">Create free account</a>
    </div>
  </div>
</section>

<footer class="py-4 mt-5">
  <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center">
    <div class="small">
      <a href="#builderWrap" class="me-3">Generator</a>
      <a href="#features" class="me-3">Features</a>
      <a href="#plans" class="me-3">Plans</a>
      <a href="login.php" class="me-3">Login</a>
      <a href="login.php?mode=signup">Signup</a>
    </div>
    <div class="small">&copy; <?php echo date('Y'); ?> Whoizme</div>
  </div>
</footer>

<!-- Upsell modal -->
<div class="modal fade" id="proUpsell" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
    <div class="modal-body p-4">
      <div class="d-flex">
        <div class="me-3 display-6 text-primary"><i class="bi bi-stars"></i></div>
        <div>
          <h5 class="mb-1">Unlock more features</h5>
          <p class="mb-2 text-muted">Logo upload, SVG export and scan tracking need an account.</p>
          <a href="login.php?mode=signup" class="btn btn-primary">Create free account</a>
          <a href="login.php" class="btn btn-link">I have an account</a>
        </div>
      </div>
    </div>
  </div></div>
</div>

<button class="btn btn-primary d-lg-none floating-cta" onclick="window.location='#builderWrap'"><i class="bi bi-qr-code-scan me-1"></i>Generate</button>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/qrcode/build/qrcode.min.js"></script>
<script>
  const IS_LOGGED_IN = <?php echo $IS_LOGGED_IN ? 'true' : 'false'; ?>;

  // Elements
  const canvas = document.getElementById('qrCanvas');
  const ctx = canvas.getContext('2d');
  const el = {
    size: document.getElementById('opt_size'),
    colorDark: document.getElementById('opt_colorDark'),
    colorLight: document.getElementById('opt_colorLight'),
    track: document.getElementById('trackSwitch'),
    url: document.getElementById('f_url'),
    text: document.getElementById('f_text'),
  };
  const stageBox = document.getElementById('previewStage');
  let pdfObjectURL = '';

  // Frame selection (compact)
  let activeFrame = 'none';

  // Swatch quick pick
  function bindSwatches(attr, input){
    document.querySelectorAll(`[${attr}]`).forEach(btn=>{
      btn.addEventListener('click', ()=>{
        const val = btn.getAttribute(attr);
        input.value = val;
        document.querySelectorAll(`[${attr}]`).forEach(b=>b.setAttribute('aria-pressed','false'));
        btn.setAttribute('aria-pressed','true');
        drawQR();
      });
    });
  }
  bindSwatches('data-swatch-dark', el.colorDark);
  bindSwatches('data-swatch-light', el.colorLight);

  // Build payload according to active tab
  function activePayload(){
    const active = document.querySelector('#qrTabs .nav-link.active')?.id || 't-url';

    if(active==='t-text')  return el.text.value || '';

    if(active==='t-vc'){
      const get=id=>(document.getElementById(id)?.value||'');
      const first=get('vc_first'), last=get('vc_last');
      const mobile=get('vc_mobile'), fax=get('vc_fax'), phone=get('vc_phone');
      const email=get('vc_email'), company=get('vc_company'), job=get('vc_job');
      const street=get('vc_street'), city=get('vc_city'), zip=get('vc_zip'), state=get('vc_state'), country=get('vc_country');
      const site=get('vc_url');

      const lines=[
        'BEGIN:VCARD','VERSION:3.0',
        `N:${last};${first};;;`, `FN:${[first,last].filter(Boolean).join(' ')}`,
        mobile?`TEL;TYPE=CELL:${mobile}`:'',
        phone?`TEL;TYPE=VOICE:${phone}`:'',
        fax?`TEL;TYPE=FAX:${fax}`:'',
        email?`EMAIL:${email}`:'',
        company?`ORG:${company}`:'',
        job?`TITLE:${job}`:'',
        (street||city||state||zip||country)?`ADR;TYPE=HOME:;;${street};${city};${state};${zip};${country}`:'',
        site?`URL:${site}`:'',
        'END:VCARD'
      ].filter(Boolean);
      return lines.join('\n');
    }

    if(active==='t-email'){
      const to=document.getElementById('em_to').value||'';
      const subject=encodeURIComponent(document.getElementById('em_subject').value||'');
      const body=encodeURIComponent(document.getElementById('em_body').value||'');
      return `mailto:${to}?subject=${subject}&body=${body}`;
    }

    if(active==='t-wifi'){
      const ssid=document.getElementById('wf_ssid').value||'';
      const pwd=document.getElementById('wf_pwd').value||'';
      const auth=document.getElementById('wf_auth').value||'WPA';
      const hid=document.getElementById('wf_hidden').checked?'true':'false';
      return `WIFI:T:${auth};S:${ssid};P:${pwd};H:${hid};`;
    }

    if(active==='t-pdf'){
      // Prefer uploaded file object URL if present, else use URL field
      if(pdfObjectURL) return pdfObjectURL;
      return document.getElementById('pdf_url').value||'';
    }

    if(active==='t-app'){
      const ios=document.getElementById('app_ios').value||'';
      const and=document.getElementById('app_android').value||'';
      return [ios,and].filter(Boolean).join('\n');
    }

    if(active==='t-img'){
      return document.getElementById('img_url').value||'';
    }

    // URL default
    return el.url.value || '';
  }

  // Draw simple frames over QR
  function drawFrameOverlay(size, color) {
    const g = ctx;
    g.save();
    g.lineWidth = Math.max(3, Math.round(size*0.02));
    g.strokeStyle = color;
    g.fillStyle   = color;

    if(activeFrame === 'scan-1'){ // solid border
      const pad = Math.round(size*0.035);
      g.beginPath();
      g.roundRect(pad, pad, size-pad*2, size-pad*2, Math.round(size*0.05));
      g.stroke();
    }
    else if(activeFrame === 'scan-2'){ // dashed outline
      const pad = Math.round(size*0.035);
      g.setLineDash([8,6]);
      g.lineDashOffset = 0;
      g.beginPath();
      g.roundRect(pad, pad, size-pad*2, size-pad*2, Math.round(size*0.05));
      g.stroke();
      g.setLineDash([]);
    }
    else if(activeFrame === 'badge'){ // small pill at bottom
      const pillW = Math.round(size*0.44), pillH = Math.round(size*0.13);
      const x = Math.round((size-pillW)/2), y = Math.round(size - pillH - size*0.04);
      // white backing for contrast
      g.fillStyle = '#ffffff';
      g.roundRect(x-6, y-6, pillW+12, pillH+12, pillH);
      g.fill();
      // main pill
      g.fillStyle = color;
      g.roundRect(x, y, pillW, pillH, pillH);
      g.fill();
      // text
      g.fillStyle = '#ffffff';
      g.font = `${Math.round(pillH*0.42)}px system-ui, -apple-system, Segoe UI, Roboto, Arial`;
      g.textAlign='center'; g.textBaseline='middle';
      g.fillText('SCAN ME', Math.round(size/2), Math.round(y+pillH/2)+1);
    }
    else if(activeFrame === 'ribbon'){ // corner ribbon
      const tri = Math.round(size*0.22);
      g.fillStyle = color;
      g.beginPath();
      g.moveTo(size, 0);
      g.lineTo(size, tri);
      g.lineTo(size-tri, 0);
      g.closePath();
      g.fill();
    }
    g.restore();
  }

  // Draw QR (ECC ثابت داخليًا = M)
  async function drawQR(){
    const data = activePayload();
    const size = parseInt(el.size.value,10)||160;
    canvas.width = canvas.height = size;
    ctx.clearRect(0,0,size,size);
    if(!data){
        // clear pulse when placeholder
        stageBox.classList.remove('pulse');

        // Draw placeholder (semi‑transparent)
        const tryImage = new Image();
        tryImage.onload = function(){
          let w = tryImage.width, h = tryImage.height;
          let scale = Math.min(size / w, size / h);
          let dispW = w * scale, dispH = h * scale;
          let x = (size - dispW) / 2, y = (size - dispH) / 2;
          ctx.save();
          ctx.clearRect(0,0,size,size);
          ctx.globalAlpha = 0.22; // subtle
          ctx.drawImage(tryImage, x, y, dispW, dispH);
          ctx.restore();
        };
        tryImage.onerror = function(){
          // Fallback: draw three finder squares + light modules
          ctx.clearRect(0,0,size,size);
          ctx.save();
          const g = ctx;
          g.globalAlpha = 0.18;
          g.fillStyle = '#0f172a';
          const s = Math.floor(size/6); // finder size
          const pad = Math.floor(size/18);
          function finder(x,y){
            g.fillRect(x,y,s,s);
            g.clearRect(x+pad,y+pad,s-2*pad,s-2*pad);
            g.fillRect(x+2*pad,y+2*pad,s-4*pad,s-4*pad);
          }
          finder(pad,pad);
          finder(size-s-pad,pad);
          finder(pad,size-s-pad);
          const m = Math.floor(size/20);
          for(let i=0;i<22;i++){
            const rx = Math.floor(size/2 - s/2 + (Math.random()-0.5)*size/3);
            const ry = Math.floor(size/2 - s/2 + (Math.random()-0.5)*size/3);
            g.fillRect(rx, ry, m, m);
          }
          g.restore();
        };
        tryImage.src = '/img/qr-placeholder.png';

        // Disable download buttons while no data
        document.getElementById('btnDownloadPNG').disabled=true;
        document.getElementById('btnDownloadJPG').disabled=true;
        document.getElementById('btnDownloadSVG').disabled=true;
        return;
    }

    const opts = { width:size, margin:2, color:{dark:el.colorDark.value, light:el.colorLight.value}, errorCorrectionLevel:'M' };
    const tmp = document.createElement('canvas');
    await new Promise((res,rej)=>QRCode.toCanvas(tmp, data, opts, err=>err?rej(err):res()));
    ctx.clearRect(0,0,size,size);
    ctx.drawImage(tmp,0,0);

    // Logo
    if(false){
      // Logo feature removed
    }

    // Frame overlay
    drawFrameOverlay(size, el.colorDark.value);

    document.getElementById('btnDownloadPNG').disabled=false;
    document.getElementById('btnDownloadJPG').disabled=false;
    document.getElementById('btnDownloadSVG').disabled=false;

    // Pulse to confirm update
    stageBox.classList.remove('pulse'); void stageBox.offsetWidth; stageBox.classList.add('pulse');
  }

  // Generate + auto update on typing
  document.getElementById('btnGenerate').addEventListener('click', drawQR);

  // Disabled download hints
  ['btnDownloadPNG','btnDownloadJPG','btnDownloadSVG'].forEach(id=>{
    const b=document.getElementById(id);
    if(b){ b.setAttribute('title','Generate a QR first'); }
  });

  [
    'f_url','f_text','vc_first','vc_last','vc_mobile','vc_fax','vc_phone','vc_email','vc_company','vc_job','vc_street',
    'vc_city','vc_zip','vc_state','vc_country','vc_url','em_to','em_subject','em_body','wf_ssid','wf_pwd',
    'pdf_url','app_ios','app_android','img_url','pdf_file'
  ].forEach(id=>{ const e=document.getElementById(id); if(e){ e.addEventListener('input', drawQR); } });

  const pdfFileInput = document.getElementById('pdf_file');
  if(pdfFileInput){
    pdfFileInput.addEventListener('change', ()=>{
      pdfObjectURL = '';
      if(pdfFileInput.files && pdfFileInput.files[0]){
        pdfObjectURL = URL.createObjectURL(pdfFileInput.files[0]);
      }
      drawQR();
    });
  }

  // Downloads
  function dl(uri,name){ const a=document.createElement('a'); a.href=uri; a.download=name; document.body.appendChild(a); a.click(); a.remove(); }
  document.getElementById('btnDownloadPNG').addEventListener('click',()=>dl(canvas.toDataURL('image/png'),'qr.png'));
  document.getElementById('btnDownloadJPG').addEventListener('click',()=>{
    const c2=document.createElement('canvas'); c2.width=c2.height=canvas.width; const g=c2.getContext('2d');
    g.fillStyle='#fff'; g.fillRect(0,0,c2.width,c2.height); g.drawImage(canvas,0,0);
    dl(c2.toDataURL('image/jpeg',.92),'qr.jpg');
  });
  document.getElementById('btnDownloadSVG').addEventListener('click', async ()=>{
    const svg = await new Promise((res,rej)=>QRCode.toString(activePayload(),{type:'svg',color:{dark:el.colorDark.value,light:el.colorLight.value},errorCorrectionLevel:'M'},(e,s)=>e?rej(e):res(s)));
    const blob=new Blob([svg],{type:'image/svg+xml'}); const url=URL.createObjectURL(blob); dl(url,'qr.svg'); URL.revokeObjectURL(url);
  });

  // Tracking toggle upsell
  const proModal = new bootstrap.Modal('#proUpsell');
  document.getElementById('trackSwitch').addEventListener('change', function(){
    if(!IS_LOGGED_IN && this.checked){ this.checked=false; proModal.show(); }
  });

  // Upload any: placeholder (لا يوجد Backend هنا)
  document.getElementById('uploadAny').addEventListener('click', function(e){
    e.preventDefault();
    alert('Coming soon: upload a file and share via QR.\nFor now, paste a public URL to your file to generate a QR.');
  });

  // ===== Templates =====
  function applyTemplate(name){
    const presets = {
      minimal: { dark:'#000000', light:'#ffffff', frame:'none' },
      badge:   { dark:'#0f172a', light:'#ffffff', frame:'badge' },
      ribbon:  { dark:'#000000', light:'#ffffff', frame:'ribbon' },
      brand:   { dark:'#0d6efd', light:'#ffffff', frame:'scan-1' }
    };
    const p = presets[name] || presets.minimal;

    if (el.colorDark)  el.colorDark.value  = p.dark;
    if (el.colorLight) el.colorLight.value = p.light;

    activeFrame = p.frame;

    if (name==='badge' || name==='ribbon'){
      const s = parseInt(el.size.value, 10) || 160;
      if (s < 220) el.size.value = 220;
    }

    drawQR();
  }

  document.querySelectorAll('.temp-btn').forEach(btn=>{
    btn.addEventListener('click', ()=>applyTemplate(btn.getAttribute('data-temp')));
  });

  // --- Save QR helper functions ---
  function currentType(){
    const id = document.querySelector('#qrTabs .nav-link.active')?.id || 't-url';
    return ({
      't-url':'url','t-vc':'vcard','t-text':'text','t-email':'email',
      't-wifi':'wifi','t-pdf':'pdf','t-app':'app','t-img':'image'
    })[id] || 'url';
  }

  function buildStyleJSON(){
    const style = {
      size: parseInt(document.getElementById('opt_size')?.value||'160',10),
      colorDark: document.getElementById('opt_colorDark')?.value || '#000000',
      colorLight: document.getElementById('opt_colorLight')?.value || '#ffffff',
      template: (typeof activeFrame!=='undefined' ? activeFrame : 'none')
    };
    return JSON.stringify(style);
  }

  function isDynamicEnabled(){
    const sw = document.getElementById('trackSwitch');
    return !!(sw && sw.checked);
  }

  async function saveQR(){
    const payload = activePayload();
    if(!payload){ alert('Please enter content first.'); return; }

    const body = new URLSearchParams();
    body.append('type', currentType());
    body.append('payload', payload);
    body.append('style_json', buildStyleJSON());
    body.append('is_dynamic', isDynamicEnabled() ? '1' : '0');

    try{
      const resp = await fetch('/qr/save.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body
      });
      const data = await resp.json();
      if(data.ok){
        alert('Saved!' + (data.short_url ? ('\nShort URL: '+data.short_url) : ''));
      }else{
        alert('Couldn\'t save: ' + (data.error || 'Unknown error'));
      }
    }catch(e){
      alert('Network error while saving.');
    }
  }

  const btnSave = document.getElementById('btnSaveQR');
  if(btnSave){ btnSave.addEventListener('click', saveQR); }

  // Initial placeholder (small QR)
  drawQR();
</script>
</body>
</html>