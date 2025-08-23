<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/auth_guard.php';

$uid = current_user_id();
$id  = (int)($_GET['id'] ?? 0);
$prefill = trim($_GET['prefill_url'] ?? '');

$record = [
  'title'   => '',
  'type'    => 'url',
  'payload' => $prefill,
  'code'    => '',
  'is_active' => 1,
];

if ($id) {
  $st = $pdo->prepare("SELECT * FROM qr_codes WHERE id=:id AND user_id=:uid");
  $st->execute([':id'=>$id, ':uid'=>$uid]);
  $row = $st->fetch();
  if ($row) $record = $row;
}

$page      = 'qr';              // used by sidebar to set active item
$page_slug = 'qr';              // secondary safety for older partials
$page_title = $id ? 'Edit QR' : 'Create new QR';

include __DIR__ . '/../partials/app_header.php';
?>
<main class="dashboard">
  <?php include __DIR__ . '/../partials/app_sidebar.php'; ?>
  <div class="container dash-grid" role="region" aria-label="QR editor">
    <div class="container topbar--inset">
      <?php
        $breadcrumbs = [
          ['label' => 'Dashboard', 'href' => '/dashboard'],
          ['label' => 'QR Codes',  'href' => '/qr'],
          ['label' => 'New', 'href' => null],
        ];
        $topbar = [ 'search' => [ 'enabled' => false ] ];
        include __DIR__ . '/../partials/app_topbar.php';
      ?>
    </div>

    <section class="maincol">
      <div class="u-flex u-gap-16">
        
        <!-- Left Column: Form -->
        <div class="u-flex-1">
          <div class="panel">
            <div class="panel__body u-stack-16">
              <h3 class="h3 u-mt-0">Create new QR</h3>
              
              <form action="/qr/save.php" method="post" class="u-stack-16" id="qrForm">
                <input type="hidden" name="id" value="<?= $id ?>">
                
                <!-- Type Selector Tabs -->
                <div class="qr-tabs" role="tablist" aria-label="QR type selector">
                  <button type="button" class="qr-tab is-active" role="tab" aria-selected="true" aria-controls="urlPane" data-type="url">
                    <i class="fi fi-rr-link" aria-hidden="true"></i>
                    <span>URL</span>
                  </button>
                  <button type="button" class="qr-tab" role="tab" aria-selected="false" aria-controls="vcardPane" data-type="vcard">
                    <i class="fi fi-rr-id-badge" aria-hidden="true"></i>
                    <span>vCard</span>
                  </button>
                  <button type="button" class="qr-tab" role="tab" aria-selected="false" aria-controls="textPane" data-type="text">
                    <i class="fi fi-rr-align-left" aria-hidden="true"></i>
                    <span>Text</span>
                  </button>
                  <button type="button" class="qr-tab" role="tab" aria-selected="false" aria-controls="emailPane" data-type="email">
                    <i class="fi fi-rr-envelope" aria-hidden="true"></i>
                    <span>E‑mail</span>
                  </button>
                  <button type="button" class="qr-tab" role="tab" aria-selected="false" aria-controls="wifiPane" data-type="wifi">
                    <i class="fi fi-rr-wifi" aria-hidden="true"></i>
                    <span>Wi‑Fi</span>
                  </button>
                  <button type="button" class="qr-tab" role="tab" aria-selected="false" aria-controls="pdfPane" data-type="pdf">
                    <i class="fi fi-rr-file-pdf" aria-hidden="true"></i>
                    <span>PDF</span>
                  </button>
                  <button type="button" class="qr-tab" role="tab" aria-selected="false" aria-controls="storesPane" data-type="stores">
                    <i class="fi fi-rr-apps" aria-hidden="true"></i>
                    <span>App Stores</span>
                  </button>
                  <button type="button" class="qr-tab" role="tab" aria-selected="false" aria-controls="imagesPane" data-type="images">
                    <i class="fi fi-rr-picture" aria-hidden="true"></i>
                    <span>Images</span>
                  </button>
                </div>
                <input type="hidden" name="type" value="<?= htmlspecialchars($record['type']) ?>" id="typeInput">

                <!-- Shared Fields -->
                <div>
                  <label for="title" class="label">Title <span class="required">*</span></label>
                  <input type="text" id="title" name="title" class="input" required maxlength="120"
                         value="<?= htmlspecialchars($record['title']) ?>" placeholder="My QR Code">
                </div>

                <div>
                  <label class="label">
                    <input type="checkbox" name="is_active" value="1" <?= $record['is_active'] ? 'checked' : '' ?>>
                    <span>Active</span>
                  </label>
                </div>

                <!-- URL Section -->
                <div id="urlPane" class="qr-pane" role="tabpanel" aria-labelledby="url-tab" data-pane="url">
                  <div>
                    <label for="destination_url" class="label">Destination URL <span class="required">*</span></label>
                    <input type="url" id="destination_url" name="destination_url" class="input" 
                           placeholder="qr code for https://www.whoiz.me/"
                           value="<?= $record['type'] === 'url' ? htmlspecialchars($record['payload']) : '' ?>">
                  </div>
                </div>

                <!-- vCard Section -->
                <div id="vcardPane" class="qr-pane is-hidden" role="tabpanel" aria-labelledby="vcard-tab" data-pane="vcard">
                  <div>
                    <label for="fullname" class="label">Full Name <span class="required">*</span></label>
                    <input type="text" id="fullname" name="fullname" class="input" placeholder="John Doe">
                  </div>
                  
                  <div class="u-flex u-gap-8">
                    <div class="u-flex-1">
                      <label for="org" class="label">Organization</label>
                      <input type="text" id="org" name="org" class="input" placeholder="Company Inc.">
                    </div>
                    <div class="u-flex-1">
                      <label for="job_title" class="label">Job Title</label>
                      <input type="text" id="job_title" name="job_title" class="input" placeholder="Manager">
                    </div>
                  </div>
                  
                  <div class="u-flex u-gap-8">
                    <div class="u-flex-1">
                      <label for="email" class="label">Email <span class="required">*</span></label>
                      <input type="email" id="email" name="email" class="input" placeholder="john@example.com">
                    </div>
                    <div class="u-flex-1">
                      <label for="phone" class="label">Phone <span class="required">*</span></label>
                      <input type="tel" id="phone" name="phone" class="input" placeholder="+1234567890">
                    </div>
                  </div>
                  
                  <div>
                    <label for="website" class="label">Website</label>
                    <input type="url" id="website" name="website" class="input" placeholder="https://example.com">
                  </div>
                  
                  <div>
                    <label for="address" class="label">Address</label>
                    <textarea id="address" name="address" class="input" rows="3" placeholder="123 Main St, City, Country"></textarea>
                  </div>
                </div>

                <!-- Text Section -->
                <div id="textPane" class="qr-pane is-hidden" role="tabpanel" aria-labelledby="text-tab" data-pane="text">
                  <div>
                    <label for="text" class="label">Text Content <span class="required">*</span></label>
                    <textarea id="text" name="text" class="input" rows="6" maxlength="1000" 
                              placeholder="Enter your text content here..."></textarea>
                    <div class="char-counter">
                      <span id="charCount">0</span> / 1000 characters
                    </div>
                  </div>
                </div>

                <!-- Email Section -->
                <div id="emailPane" class="qr-pane is-hidden" role="tabpanel" aria-labelledby="email-tab" data-pane="email">
                  <div>
                    <label for="to" class="label">To <span class="required">*</span></label>
                    <input type="email" id="to" name="to" class="input" placeholder="recipient@example.com">
                  </div>
                  
                  <div>
                    <label for="subject" class="label">Subject</label>
                    <input type="text" id="subject" name="subject" class="input" placeholder="Email subject">
                  </div>
                  
                  <div>
                    <label for="body" class="label">Message</label>
                    <textarea id="body" name="body" class="input" rows="4" placeholder="Email message content"></textarea>
                  </div>
                </div>

                <!-- WiFi Section -->
                <div id="wifiPane" class="qr-pane is-hidden" role="tabpanel" aria-labelledby="wifi-tab" data-pane="wifi">
                  <div>
                    <label for="ssid" class="label">Network Name (SSID) <span class="required">*</span></label>
                    <input type="text" id="ssid" name="ssid" class="input" placeholder="MyWiFiNetwork">
                  </div>
                  
                  <div>
                    <label for="password" class="label">Password</label>
                    <input type="password" id="password" name="password" class="input" placeholder="WiFi password">
                  </div>
                  
                  <div class="u-flex u-gap-8">
                    <div class="u-flex-1">
                      <label for="encryption" class="label">Encryption</label>
                      <select id="encryption" name="encryption" class="input">
                        <option value="WPA">WPA/WPA2/WPA3</option>
                        <option value="WEP">WEP</option>
                        <option value="nopass">No Password</option>
                      </select>
                    </div>
                    <div class="u-flex-1">
                      <label class="label">
                        <input type="checkbox" id="hidden" name="hidden" value="1">
                        <span>Hidden Network</span>
                      </label>
                    </div>
                  </div>
                </div>

                <!-- PDF Section -->
                <div id="pdfPane" class="qr-pane is-hidden" role="tabpanel" aria-labelledby="pdf-tab" data-pane="pdf">
                  <div>
                    <label for="pdf_url" class="label">PDF URL <span class="required">*</span></label>
                    <input type="url" id="pdf_url" name="pdf_url" class="input" placeholder="https://example.com/document.pdf">
                  </div>
                </div>

                <!-- App Stores Section -->
                <div id="storesPane" class="qr-pane is-hidden" role="tabpanel" aria-labelledby="stores-tab" data-pane="stores">
                  <div>
                    <label for="ios_url" class="label">iOS App Store URL</label>
                    <input type="url" id="ios_url" name="ios_url" class="input" placeholder="https://apps.apple.com/app/...">
                  </div>
                  
                  <div>
                    <label for="android_url" class="label">Android Play Store URL</label>
                    <input type="url" id="android_url" name="android_url" class="input" placeholder="https://play.google.com/store/apps/...">
                  </div>
                  
                  <div>
                    <label class="label">Preview Platform</label>
                    <div class="u-flex u-gap-8">
                      <label class="label">
                        <input type="radio" id="platform_ios" name="platform_preview" value="ios" checked>
                        <span>iOS</span>
                      </label>
                      <label class="label">
                        <input type="radio" id="platform_android" name="platform_preview" value="android">
                        <span>Android</span>
                      </label>
                    </div>
                  </div>
                </div>

                <!-- Images Section -->
                <div id="imagesPane" class="qr-pane is-hidden" role="tabpanel" aria-labelledby="images-tab" data-pane="images">
                  <div>
                    <label for="image_url" class="label">Image URL <span class="required">*</span></label>
                    <input type="url" id="image_url" name="image_url" class="input" placeholder="https://example.com/image.jpg">
                  </div>
                </div>

                <!-- Buttons -->
                <div class="u-flex u-gap-8">
                  <button type="submit" class="btn btn--primary" id="createBtn">Create QR Code</button>
                  <a href="/qr" class="btn btn--ghost">Cancel</a>
                </div>
              </form>
            </div>
          </div>
        </div>

        <!-- Right Column: Live Preview -->
        <div class="u-flex-1">
          <div class="qr-card" id="qr-preview">
            <div class="qr-card__body">
              <h4 class="h4">Live Preview</h4>
              
              <div class="qr-canvas-wrapper">
                <div id="qr-canvas" aria-label="QR Preview" role="img"></div>
              </div>
              
              <div class="qr-caption" id="qr-caption">
                whoiz.me | URL
              </div>
              
              <!-- Styling Controls -->
              <div class="qr-controls">
                <h5 class="h5">Styling</h5>
                <div class="qr-controls__grid">
                  <div class="qr-control">
                    <label for="qrColor" class="label">Foreground</label>
                    <input type="color" id="qrColor" class="input input--color" value="#4B6BFB">
                  </div>
                  <div class="qr-control">
                    <label for="qrBg" class="label">Background</label>
                    <input type="color" id="qrBg" class="input input--color" value="#ffffff">
                  </div>
                  <div class="qr-control">
                    <label for="qrSize" class="label">Size: <span id="qrSizeValue">256</span>px</label>
                    <input type="range" id="qrSize" class="input input--range" min="128" max="512" step="16" value="256">
                  </div>
                  <div class="qr-control">
                    <label for="qrQuiet" class="label">Margin: <span id="qrQuietValue">16</span>px</label>
                    <input type="range" id="qrQuiet" class="input input--range" min="0" max="32" step="2" value="16">
                  </div>
                  <div class="qr-control qr-control--full">
                    <label class="label">
                      <input type="checkbox" id="qrRounded" checked>
                      <span>Rounded modules</span>
                    </label>
                  </div>
                </div>
              </div>
              
              <!-- Helper Info -->
              <div class="qr-helper">
                <div class="note">
                  <i class="fi fi-rr-info" aria-hidden="true"></i>
                  <span>Short link format: /qrgo.php?q={id} after creation</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
</main>

<!-- Include QR Code library and custom JS -->
<script src="/assets/js/qrcode.min.js"></script>
<script src="/assets/js/qr-new.js" defer></script>

<?php include __DIR__ . '/../partials/app_footer.php'; ?>