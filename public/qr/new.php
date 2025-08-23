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
                
                <!-- Type Selector -->
                <div>
                  <label class="label">Type</label>
                  <div class="seg-switch" role="tablist" aria-label="QR type selector">
                    <button type="button" class="seg-btn is-active" role="tab" aria-selected="true" data-type="url">
                      <i class="fi fi-rr-link" aria-hidden="true"></i>
                      <span>URL</span>
                    </button>
                    <button type="button" class="seg-btn" role="tab" aria-selected="false" data-type="vcard">
                      <i class="fi fi-rr-id-badge" aria-hidden="true"></i>
                      <span>vCard</span>
                    </button>
                    <button type="button" class="seg-btn" role="tab" aria-selected="false" data-type="text">
                      <i class="fi fi-rr-align-left" aria-hidden="true"></i>
                      <span>Text</span>
                    </button>
                  </div>
                  <input type="hidden" name="type" value="<?= htmlspecialchars($record['type']) ?>" id="typeInput">
                </div>

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
                <div id="urlSection" class="type-section">
                  <div>
                    <label for="url" class="label">Destination URL <span class="required">*</span></label>
                    <input type="url" id="url" name="url" class="input" placeholder="https://example.com"
                           value="<?= $record['type'] === 'url' ? htmlspecialchars($record['payload']) : '' ?>">
                  </div>
                  
                  <details class="utm-builder">
                    <summary class="label">UTM Parameters (optional)</summary>
                    <div class="u-stack-12 u-mt-8">
                      <div class="u-flex u-gap-8">
                        <div class="u-flex-1">
                          <label for="utm_source" class="label">Source</label>
                          <input type="text" id="utm_source" name="utm_source" class="input" placeholder="google">
                        </div>
                        <div class="u-flex-1">
                          <label for="utm_medium" class="label">Medium</label>
                          <input type="text" id="utm_medium" name="utm_medium" class="input" placeholder="cpc">
                        </div>
                      </div>
                      <div class="u-flex u-gap-8">
                        <div class="u-flex-1">
                          <label for="utm_campaign" class="label">Campaign</label>
                          <input type="text" id="utm_campaign" name="utm_campaign" class="input" placeholder="summer2024">
                        </div>
                        <div class="u-flex-1">
                          <label for="utm_term" class="label">Term</label>
                          <input type="text" id="utm_term" name="utm_term" class="input" placeholder="running+shoes">
                        </div>
                      </div>
                      <div>
                        <label for="utm_content" class="label">Content</label>
                        <input type="text" id="utm_content" name="utm_content" class="input" placeholder="logolink">
                      </div>
                    </div>
                  </details>
                  
                  <div id="urlPreview" class="url-preview" style="display: none;">
                    <label class="label">Final URL</label>
                    <div class="input input--readonly" id="finalUrl"></div>
                  </div>
                </div>

                <!-- vCard Section -->
                <div id="vcardSection" class="type-section" style="display: none;">
                  <div>
                    <label for="vcard_name" class="label">Full Name <span class="required">*</span></label>
                    <input type="text" id="vcard_name" name="vcard_name" class="input" placeholder="John Doe">
                  </div>
                  
                  <div class="u-flex u-gap-8">
                    <div class="u-flex-1">
                      <label for="vcard_org" class="label">Organization</label>
                      <input type="text" id="vcard_org" name="vcard_org" class="input" placeholder="Company Inc.">
                    </div>
                    <div class="u-flex-1">
                      <label for="vcard_title" class="label">Job Title</label>
                      <input type="text" id="vcard_title" name="vcard_title" class="input" placeholder="Manager">
                    </div>
                  </div>
                  
                  <div class="u-flex u-gap-8">
                    <div class="u-flex-1">
                      <label for="vcard_email" class="label">Email</label>
                      <input type="email" id="vcard_email" name="vcard_email" class="input" placeholder="john@example.com">
                    </div>
                    <div class="u-flex-1">
                      <label for="vcard_phone" class="label">Phone</label>
                      <input type="tel" id="vcard_phone" name="vcard_phone" class="input" placeholder="+1234567890">
                    </div>
                  </div>
                  
                  <div>
                    <label for="vcard_website" class="label">Website</label>
                    <input type="url" id="vcard_website" name="vcard_website" class="input" placeholder="https://example.com">
                  </div>
                  
                  <div>
                    <label for="vcard_address" class="label">Address</label>
                    <textarea id="vcard_address" name="vcard_address" class="input" rows="3" placeholder="123 Main St, City, Country"></textarea>
                  </div>
                  
                  <div>
                    <label for="vcard_notes" class="label">Notes</label>
                    <textarea id="vcard_notes" name="vcard_notes" class="input" rows="3" placeholder="Additional information"></textarea>
                  </div>
                </div>

                <!-- Text Section -->
                <div id="textSection" class="type-section" style="display: none;">
                  <div>
                    <label for="text_content" class="label">Text Content <span class="required">*</span></label>
                    <textarea id="text_content" name="text_content" class="input" rows="6" maxlength="1000" 
                              placeholder="Enter your text content here..."></textarea>
                    <div class="char-counter">
                      <span id="charCount">0</span> / 1000 characters
                    </div>
                  </div>
                </div>

                <!-- Buttons -->
                <div class="u-flex u-gap-8">
                  <button type="submit" class="btn btn--primary">Create QR Code</button>
                  <a href="/qr" class="btn btn--ghost">Cancel</a>
                </div>
              </form>
            </div>
          </div>
        </div>

        <!-- Right Column: Preview -->
        <div class="u-flex-1">
          <div class="panel">
            <div class="panel__body u-stack-16">
              <h4 class="h4">Live Preview</h4>
              
              <div class="qr-preview">
                <div class="qr-placeholder">
                  <i class="fi fi-rr-qr-code" aria-hidden="true"></i>
                  <span>QR Preview</span>
                </div>
              </div>
              
              <div class="payload-preview">
                <label class="label">Payload</label>
                <div class="input input--readonly" id="payloadPreview">
                  <span class="muted">Select a type and fill the form to see preview</span>
                </div>
              </div>
              
              <div class="meta-notes">
                <div class="note">
                  <i class="fi fi-rr-info" aria-hidden="true"></i>
                  <span>Will appear in QR table and grid</span>
                </div>
                <div class="note">
                  <i class="fi fi-rr-link" aria-hidden="true"></i>
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

<style>
/* Type selector styles */
.seg-switch {
  display: flex;
  gap: 4px;
  padding: 4px;
  background: var(--surface-800);
  border: 1px solid var(--border-700);
  border-radius: var(--radius-default);
}

.seg-btn {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 8px 12px;
  border: none;
  background: transparent;
  color: var(--text-300);
  border-radius: var(--radius-default);
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 14px;
  font-weight: 500;
}

.seg-btn:hover {
  background: var(--surface-700);
  color: var(--text-200);
}

.seg-btn.is-active {
  background: var(--primary);
  color: var(--text-inverse);
}

.seg-btn i {
  font-size: 16px;
}

/* Type sections */
.type-section {
  border: 1px solid var(--border-700);
  border-radius: var(--radius-default);
  padding: 16px;
  background: var(--surface-800);
}

/* UTM builder */
.utm-builder {
  margin-top: 16px;
}

.utm-builder summary {
  cursor: pointer;
  color: var(--text-200);
  font-weight: 500;
}

.utm-builder summary:hover {
  color: var(--text-100);
}

/* URL preview */
.url-preview {
  margin-top: 16px;
  padding: 12px;
  background: var(--surface-900);
  border: 1px solid var(--border-600);
  border-radius: var(--radius-default);
}

.input--readonly {
  background: var(--surface-900);
  color: var(--text-200);
  cursor: not-allowed;
}

/* Character counter */
.char-counter {
  margin-top: 4px;
  font-size: 12px;
  color: var(--text-400);
  text-align: right;
}

/* QR preview */
.qr-preview {
  display: flex;
  justify-content: center;
  padding: 32px;
}

.qr-placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  padding: 24px;
  background: var(--surface-800);
  border: 2px dashed var(--border-600);
  border-radius: var(--radius-default);
  color: var(--text-400);
}

.qr-placeholder i {
  font-size: 48px;
}

/* Payload preview */
.payload-preview {
  margin-top: 16px;
}

/* Meta notes */
.meta-notes {
  margin-top: 16px;
}

.note {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 8px;
  font-size: 14px;
  color: var(--text-400);
}

.note i {
  font-size: 16px;
  color: var(--text-500);
}

/* Required indicator */
.required {
  color: var(--danger);
}

/* Mobile responsive */
@media (max-width: 768px) {
  .u-flex {
    flex-direction: column;
  }
  
  .seg-switch {
    flex-direction: column;
  }
  
  .seg-btn {
    justify-content: flex-start;
  }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const typeBtns = document.querySelectorAll('.seg-btn');
  const typeInput = document.getElementById('typeInput');
  const sections = document.querySelectorAll('.type-section');
  const payloadPreview = document.getElementById('payloadPreview');
  const charCount = document.getElementById('charCount');
  const textContent = document.getElementById('text_content');
  
  // Type switching
  typeBtns.forEach(btn => {
    btn.addEventListener('click', function() {
      const type = this.dataset.type;
      
      // Update active state
      typeBtns.forEach(b => {
        b.classList.remove('is-active');
        b.setAttribute('aria-selected', 'false');
      });
      this.classList.add('is-active');
      this.setAttribute('aria-selected', 'true');
      
      // Update hidden input
      typeInput.value = type;
      
      // Show/hide sections
      sections.forEach(section => {
        section.style.display = 'none';
      });
      document.getElementById(type + 'Section').style.display = 'block';
      
      // Update preview
      updatePreview();
    });
  });
  
  // Character counter for text
  if (textContent) {
    textContent.addEventListener('input', function() {
      const count = this.value.length;
      charCount.textContent = count;
      
      if (count > 900) {
        charCount.style.color = 'var(--warning)';
      } else {
        charCount.style.color = 'var(--text-400)';
      }
    });
  }
  
  // URL preview with UTM
  const urlInput = document.getElementById('url');
  const utmInputs = document.querySelectorAll('[id^="utm_"]');
  const urlPreview = document.getElementById('urlPreview');
  const finalUrl = document.getElementById('finalUrl');
  
  function updateUrlPreview() {
    const baseUrl = urlInput.value.trim();
    if (!baseUrl) {
      urlPreview.style.display = 'none';
      return;
    }
    
    const utmParams = [];
    utmInputs.forEach(input => {
      if (input.value.trim()) {
        utmParams.push(input.name + '=' + encodeURIComponent(input.value.trim()));
      }
    });
    
    if (utmParams.length > 0) {
      const separator = baseUrl.includes('?') ? '&' : '?';
      const finalUrlStr = baseUrl + separator + utmParams.join('&');
      finalUrl.textContent = finalUrlStr;
      urlPreview.style.display = 'block';
    } else {
      urlPreview.style.display = 'none';
    }
  }
  
  urlInput.addEventListener('input', updateUrlPreview);
  utmInputs.forEach(input => input.addEventListener('input', updateUrlPreview));
  
  // Live preview update
  function updatePreview() {
    const type = typeInput.value;
    let preview = '';
    
    switch(type) {
      case 'url':
        const url = urlInput.value.trim();
        if (url) {
          const utmParams = [];
          utmInputs.forEach(input => {
            if (input.value.trim()) {
              utmParams.push(input.name + '=' + encodeURIComponent(input.value.trim()));
            }
          });
          if (utmParams.length > 0) {
            const separator = url.includes('?') ? '&' : '?';
            preview = url + separator + utmParams.join('&');
          } else {
            preview = url;
          }
        }
        break;
        
      case 'vcard':
        const name = document.getElementById('vcard_name').value.trim();
        if (name) {
          preview = 'vCard: ' + name;
        }
        break;
        
      case 'text':
        const text = textContent.value.trim();
        if (text) {
          preview = text.length > 50 ? text.substring(0, 50) + '...' : text;
        }
        break;
    }
    
    if (preview) {
      payloadPreview.innerHTML = `<span>${preview}</span>`;
    } else {
      payloadPreview.innerHTML = '<span class="muted">Select a type and fill the form to see preview</span>';
    }
  }
  
  // Add input listeners for live preview
  const allInputs = document.querySelectorAll('input, textarea');
  allInputs.forEach(input => {
    input.addEventListener('input', updatePreview);
  });
  
  // Form validation
  const form = document.getElementById('qrForm');
  form.addEventListener('submit', function(e) {
    const type = typeInput.value;
    let isValid = true;
    
    // Clear previous errors
    document.querySelectorAll('.error').forEach(el => el.remove());
    
    // Validate title
    const title = document.getElementById('title').value.trim();
    if (!title) {
      showError('title', 'Title is required');
      isValid = false;
    }
    
    // Validate type-specific fields
    switch(type) {
      case 'url':
        const url = urlInput.value.trim();
        if (!url) {
          showError('url', 'URL is required');
          isValid = false;
        } else if (!isValidUrl(url)) {
          showError('url', 'Please enter a valid URL (starting with http:// or https://)');
          isValid = false;
        }
        break;
        
      case 'vcard':
        const vcardName = document.getElementById('vcard_name').value.trim();
        if (!vcardName) {
          showError('vcard_name', 'Full name is required');
          isValid = false;
        }
        break;
        
      case 'text':
        const text = textContent.value.trim();
        if (!text) {
          showError('text_content', 'Text content is required');
          isValid = false;
        }
        break;
    }
    
    if (!isValid) {
      e.preventDefault();
    }
  });
  
  function showError(fieldId, message) {
    const field = document.getElementById(fieldId);
    const error = document.createElement('div');
    error.className = 'error';
    error.style.color = 'var(--danger)';
    error.style.fontSize = '12px';
    error.style.marginTop = '4px';
    error.textContent = message;
    field.parentNode.appendChild(error);
  }
  
  function isValidUrl(string) {
    try {
      new URL(string);
      return true;
    } catch (_) {
      return false;
    }
  }
  
  // Initialize with current type
  const currentType = typeInput.value;
  if (currentType) {
    document.querySelector(`[data-type="${currentType}"]`).click();
  }
});
</script>

<?php include __DIR__ . '/../partials/app_footer.php'; ?>