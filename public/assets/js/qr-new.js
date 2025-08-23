/**
 * Live QR Code Generator for Whoiz.me
 * Enhanced with styling controls and instant preview
 */

class QRGenerator {
    constructor() {
        this.DEFAULT_URL = 'https://www.whoiz.me/';
        this.state = {
            type: 'url',
            payload: this.DEFAULT_URL,
            fg: getComputedStyle(document.documentElement).getPropertyValue('--brand').trim() || '#4B6BFB',
            bg: 'transparent',
            size: 256,
            quiet: 16,
            rounded: true,
            platformPreview: 'ios'
        };

        this.elements = {
            qrCanvas: document.getElementById('qr-canvas'),
            qrCaption: document.getElementById('qr-caption'),
            typeInput: document.getElementById('typeInput'),
            createBtn: document.getElementById('createBtn'),
            qrColor: document.getElementById('qrColor'),
            qrBg: document.getElementById('qrBg'),
            qrSize: document.getElementById('qrSize'),
            qrSizeValue: document.getElementById('qrSizeValue'),
            qrQuiet: document.getElementById('qrQuiet'),
            qrQuietValue: document.getElementById('qrQuietValue'),
            qrRounded: document.getElementById('qrRounded')
        };

        this.currentQR = null;
        this.debounceTimer = null;
        this.reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        this.init();
    }

    init() {
        this.setupTabs();
        this.setupInputListeners();
        this.setupStylingControls();
        this.setupCharacterCounter();
        this.setupKeyboardNavigation();
        this.setupFormSubmission();
        this.renderDefaultQR();
    }

    setupTabs() {
        const tabs = document.querySelectorAll('.qr-tab');
        console.log('Setting up', tabs.length, 'tabs');

        tabs.forEach((tab, index) => {
            console.log('Setting up tab', index, ':', tab.dataset.type);
            tab.addEventListener('click', (e) => {
                e.preventDefault();
                console.log('Tab clicked:', tab.dataset.type);
                this.switchTab(tab);
            });
            tab.addEventListener('keydown', (e) => this.handleTabKeydown(e, tabs, index));
        });
    }

    switchTab(activeTab) {
        const type = activeTab.dataset.type;

        // Update tab states
        document.querySelectorAll('.qr-tab').forEach(tab => {
            tab.classList.remove('is-active');
            tab.setAttribute('aria-selected', 'false');
        });
        activeTab.classList.add('is-active');
        activeTab.setAttribute('aria-selected', 'true');

        // Update hidden input
        if (this.elements.typeInput) {
            this.elements.typeInput.value = type;
        }
        this.state.type = type;

        // Show/hide panes
        document.querySelectorAll('.qr-pane').forEach(pane => {
            pane.classList.add('is-hidden');
        });

        const targetPane = document.getElementById(type + 'Pane');
        if (targetPane) {
            targetPane.classList.remove('is-hidden');
        }

        // Re-render QR immediately when switching tabs
        this.renderQR();
    }

    handleTabKeydown(e, tabs, currentIndex) {
        let newIndex = currentIndex;

        switch (e.key) {
            case 'ArrowLeft':
                newIndex = currentIndex > 0 ? currentIndex - 1 : tabs.length - 1;
                break;
            case 'ArrowRight':
                newIndex = currentIndex < tabs.length - 1 ? currentIndex + 1 : 0;
                break;
            case 'Home':
                newIndex = 0;
                break;
            case 'End':
                newIndex = tabs.length - 1;
                break;
            default:
                return;
        }

        e.preventDefault();
        tabs[newIndex].focus();
        this.switchTab(tabs[newIndex]);
    }

    setupInputListeners() {
        // Listen to all input fields for live preview
        const inputs = document.querySelectorAll('.qr-pane input, .qr-pane textarea');
        inputs.forEach(input => {
            ['input', 'change', 'keyup'].forEach(eventType => {
                input.addEventListener(eventType, () => this.scheduleRender());
            });
        });

        // Platform preview toggle for App Stores
        const platformRadios = document.querySelectorAll('input[name="platform_preview"]');
        platformRadios.forEach(radio => {
            radio.addEventListener('change', () => {
                this.state.platformPreview = radio.value;
                this.scheduleRender();
            });
        });
    }

    setupStylingControls() {
        if (this.elements.qrColor) {
            this.elements.qrColor.addEventListener('change', (e) => {
                this.state.fg = e.target.value;
                this.renderQR();
            });
        }

        if (this.elements.qrBg) {
            this.elements.qrBg.addEventListener('change', (e) => {
                this.state.bg = e.target.value;
                this.renderQR();
            });
        }

        if (this.elements.qrSize) {
            this.elements.qrSize.addEventListener('input', (e) => {
                this.state.size = parseInt(e.target.value);
                if (this.elements.qrSizeValue) {
                    this.elements.qrSizeValue.textContent = this.state.size;
                }
                this.renderQR();
            });
        }

        if (this.elements.qrQuiet) {
            this.elements.qrQuiet.addEventListener('input', (e) => {
                this.state.quiet = parseInt(e.target.value);
                if (this.elements.qrQuietValue) {
                    this.elements.qrQuietValue.textContent = this.state.quiet;
                }
                this.renderQR();
            });
        }

        if (this.elements.qrRounded) {
            this.elements.qrRounded.addEventListener('change', (e) => {
                this.state.rounded = e.target.checked;
                this.renderQR();
            });
        }
    }

    setupCharacterCounter() {
        const textArea = document.getElementById('text');
        if (textArea) {
            const counter = document.createElement('div');
            counter.className = 'char-counter';
            textArea.parentNode.appendChild(counter);

            const updateCounter = () => {
                const count = textArea.value.length;
                counter.textContent = `${count}/1000 characters`;
            };

            textArea.addEventListener('input', updateCounter);
            updateCounter();
        }
    }

    setupKeyboardNavigation() {
        // Add keyboard navigation to form fields
        const formFields = document.querySelectorAll('.qr-pane input, .qr-pane textarea');
        formFields.forEach((field, index) => {
            field.addEventListener('keydown', (e) => {
                if (e.key === 'Tab') {
                    // Let default tab behavior work
                    return;
                }

                if (e.key === 'Enter' && e.target.tagName === 'INPUT') {
                    e.preventDefault();
                    const nextField = formFields[index + 1];
                    if (nextField) {
                        nextField.focus();
                    }
                }
            });
        });
    }

    setupFormSubmission() {
        const form = document.getElementById('qrForm');
        if (!form) return;

        form.addEventListener('submit', (e) => {
            e.preventDefault();
            this.saveQR();
        });
    }

    scheduleRender() {
        if (this.debounceTimer) {
            clearTimeout(this.debounceTimer);
        }

        this.debounceTimer = setTimeout(() => {
            this.renderQR();
        }, 150);
    }

    renderDefaultQR() {
        if (!this.elements.qrCanvas) return;

        this.generateQRCode(this.DEFAULT_URL);
        this.updateCaption('url', this.DEFAULT_URL);
        this.elements.qrCanvas.setAttribute('aria-label', 'QR code preview - fill form to generate');
    }

    renderQR() {
        const payload = this.buildPayload();

        if (!payload) {
            this.showEmptyState();
            this.updateCreateButton(false);
            return;
        }

        this.generateQRCode(payload);
        this.updateCaption(this.state.type, payload);
        this.updateCreateButton(true);
    }

    buildPayload() {
        let payload = '';

        switch (this.state.type) {
            case 'url':
                payload = this.buildUrl();
                break;
            case 'vcard':
                payload = this.buildVCard();
                break;
            case 'text':
                payload = this.buildText();
                break;
            case 'email':
                payload = this.buildEmail();
                break;
            case 'wifi':
                payload = this.buildWifi();
                break;
            case 'pdf':
                payload = this.buildPdf();
                break;
            case 'stores':
                payload = this.buildAppStores();
                break;
            case 'images':
                payload = this.buildImage();
                break;
            default:
                payload = '';
        }

        // If no payload is generated, return default URL
        if (!payload && this.state.type === 'url') {
            return this.DEFAULT_URL;
        }

        return payload;
    }

    buildUrl() {
        const url = document.getElementById('destination_url') ?.value.trim();
        if (!url) return '';

        // Ensure URL has protocol
        if (url && !url.match(/^https?:\/\//)) {
            return `https://${url}`;
        }
        return url;
    }

    buildVCard() {
        const fullname = document.getElementById('fullname') ?.value.trim();
        if (!fullname) return '';

        const org = document.getElementById('org') ?.value.trim() || '';
        const title = document.getElementById('job_title') ?.value.trim() || '';
        const phone = document.getElementById('phone') ?.value.trim() || '';
        const email = document.getElementById('email') ?.value.trim() || '';
        const website = document.getElementById('website') ?.value.trim() || '';
        const address = document.getElementById('address') ?.value.trim() || '';

        let vcard = `BEGIN:VCARD\r\nVERSION:3.0\r\n`;
        vcard += `N:;${this.escapeVCardValue(fullname)};;;\r\n`;
        vcard += `FN:${this.escapeVCardValue(fullname)}\r\n`;

        if (org) vcard += `ORG:${this.escapeVCardValue(org)}\r\n`;
        if (title) vcard += `TITLE:${this.escapeVCardValue(title)}\r\n`;
        if (phone) vcard += `TEL;TYPE=CELL:${this.escapeVCardValue(phone)}\r\n`;
        if (email) vcard += `EMAIL:${this.escapeVCardValue(email)}\r\n`;
        if (website) vcard += `URL:${this.escapeVCardValue(website)}\r\n`;
        if (address) vcard += `ADR;TYPE=HOME:${this.escapeVCardValue(address)}\r\n`;

        vcard += `END:VCARD`;
        return vcard;
    }

    escapeVCardValue(value) {
        return value.replace(/[\\;,]/g, '\\$&');
    }

    buildText() {
        const text = document.getElementById('text') ?.value.trim();
        return text || '';
    }

    buildEmail() {
        const to = document.getElementById('to') ?.value.trim();
        if (!to) return '';

        const subject = document.getElementById('subject') ?.value.trim() || '';
        const body = document.getElementById('body') ?.value.trim() || '';

        let mailto = `mailto:${encodeURIComponent(to)}`;
        const params = [];

        if (subject) params.push(`subject=${encodeURIComponent(subject)}`);
        if (body) params.push(`body=${encodeURIComponent(body)}`);

        if (params.length > 0) {
            mailto += `?${params.join('&')}`;
        }

        return mailto;
    }

    buildWifi() {
        const ssid = document.getElementById('ssid') ?.value.trim();
        if (!ssid) return '';

        const password = document.getElementById('password') ?.value.trim() || '';
        const encryption = document.getElementById('encryption') ?.value || 'WPA';
        const hidden = document.getElementById('hidden') ?.checked || false;

        let wifi = `WIFI:T:${encryption};S:${this.escapeWifiValue(ssid)};`;

        if (password && encryption !== 'nopass') {
            wifi += `P:${this.escapeWifiValue(password)};`;
        }

        wifi += `H:${hidden ? 'true' : 'false'};;`;
        return wifi;
    }

    escapeWifiValue(value) {
        return value.replace(/[;:,]/g, '\\$&');
    }

    buildPdf() {
        const url = document.getElementById('pdf_url') ?.value.trim();
        return url || '';
    }

    buildAppStores() {
        const ios = document.getElementById('ios_url') ?.value.trim() || '';
        const android = document.getElementById('android_url') ?.value.trim() || '';

        if (!ios && !android) return '';

        // Return the URL for the selected platform
        if (this.state.platformPreview === 'ios' && ios) {
            return ios;
        } else if (this.state.platformPreview === 'android' && android) {
            return android;
        }

        return ios || android;
    }

    buildImage() {
        const url = document.getElementById('image_url') ?.value.trim();
        return url || '';
    }

    generateQRCode(payload) {
        if (!this.elements.qrCanvas) return;

        // Clear previous QR
        this.elements.qrCanvas.innerHTML = '';

        try {
            // Create QR code using qrcode.min.js
            new QRCode(this.elements.qrCanvas, {
                text: payload,
                width: this.state.size,
                height: this.state.size,
                colorDark: this.state.fg,
                colorLight: this.state.bg,
                correctLevel: QRCode.CorrectLevel.L
            });

            // Apply rounded corners if enabled
            if (this.state.rounded) {
                const qrImage = this.elements.qrCanvas.querySelector('img');
                if (qrImage) {
                    qrImage.style.borderRadius = '8px';
                }
            }

            this.elements.qrCanvas.setAttribute('aria-label', `QR code for ${this.truncateText(payload, 50)}`);
        } catch (error) {
            console.error('QR generation error:', error);
            this.showEmptyState();
        }
    }

    showEmptyState() {
        if (!this.elements.qrCanvas) return;

        this.elements.qrCanvas.innerHTML = `
            <div style="display: flex; align-items: center; justify-content: center; height: 100%; color: var(--text-muted); font-size: 0.9rem;">
                <div style="text-align: center;">
                    <i class="fi fi-rr-qr-code" style="font-size: 2rem; margin-bottom: 8px; opacity: 0.5;"></i>
                    <div>Fill in the form to generate QR code</div>
                </div>
            </div>
        `;
        this.elements.qrCanvas.setAttribute('aria-label', 'QR code preview - fill form to generate');
    }

    updateCaption(type, payload) {
        if (!this.elements.qrCaption) return;

        let caption = '';

        switch (type) {
            case 'url':
                try {
                    const url = new URL(payload);
                    caption = `${url.hostname} | URL`;
                } catch {
                    caption = 'URL | QR';
                }
                break;
            case 'vcard':
                const name = document.getElementById('fullname') ?.value.trim() || 'Contact';
                caption = `${name} | vCard`;
                break;
            case 'text':
                caption = `${this.truncateText(payload, 30)} | Text`;
                break;
            case 'email':
                const to = document.getElementById('to') ?.value.trim() || 'Email';
                caption = `${to} | Email`;
                break;
            case 'wifi':
                const ssid = document.getElementById('ssid') ?.value.trim() || 'WiFi';
                caption = `${ssid} | WiFi`;
                break;
            case 'pdf':
                try {
                    const pdfUrl = new URL(payload);
                    caption = `${pdfUrl.hostname} | PDF`;
                } catch {
                    caption = 'PDF | QR';
                }
                break;
            case 'stores':
                caption = `${this.state.platformPreview.toUpperCase()} | App Store`;
                break;
            case 'images':
                try {
                    const imgUrl = new URL(payload);
                    caption = `${imgUrl.hostname} | Image`;
                } catch {
                    caption = 'Image | QR';
                }
                break;
            default:
                caption = 'whoiz.me | QR';
        }

        this.elements.qrCaption.textContent = this.truncateText(caption, 48);
    }

    truncateText(text, maxLength) {
        if (text.length <= maxLength) return text;
        return text.substring(0, maxLength - 3) + '...';
    }

    updateCreateButton(enabled) {
        if (this.elements.createBtn) {
            this.elements.createBtn.disabled = !enabled;
            this.elements.createBtn.style.opacity = enabled ? '1' : '0.5';
            this.elements.createBtn.style.cursor = enabled ? 'pointer' : 'not-allowed';
        }
    }

    async saveQR() {
        try {
            // Disable button and show loading state
            this.setSaveButtonState(true, 'Saving...');

            // Collect form data
            const formData = new FormData();

            // Get the current payload
            const payload = this.buildPayload();
            if (!payload) {
                throw new Error('Please fill in the required fields');
            }

            // Add form fields
            formData.append('id', document.querySelector('input[name="id"]') ?.value || '');
            formData.append('title', document.getElementById('title') ?.value.trim() || '');
            formData.append('type', this.state.type);
            formData.append('payload', payload);

            // Add styling data
            formData.append('fg', this.state.fg);
            formData.append('bg', this.state.bg);
            formData.append('size', this.state.size.toString());
            formData.append('quiet', this.state.quiet.toString());
            formData.append('rounded', this.state.rounded.toString());

            // Send to API
            const response = await fetch('/api/qr/save', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                this.showAlert('success', result.message);

                // Update form with new ID if it's a new QR
                if (!document.querySelector('input[name="id"]') ?.value && result.id) {
                    const idInput = document.createElement('input');
                    idInput.type = 'hidden';
                    idInput.name = 'id';
                    idInput.value = result.id;
                    const form = document.getElementById('qrForm');
                    if (form) {
                        form.appendChild(idInput);
                    }
                }

                // Update helper text with short code if available
                if (result.short_code) {
                    this.updateHelperText(result.short_code);
                }

            } else {
                throw new Error(result.error || 'Failed to save QR code');
            }

        } catch (error) {
            console.error('Save error:', error);
            this.showAlert('error', error.message);
        } finally {
            // Re-enable button
            this.setSaveButtonState(false, 'Create QR Code');
        }
    }

    setSaveButtonState(loading, text) {
        if (this.elements.createBtn) {
            this.elements.createBtn.disabled = loading;
            this.elements.createBtn.textContent = text;

            if (loading) {
                this.elements.createBtn.classList.add('btn--loading');
            } else {
                this.elements.createBtn.classList.remove('btn--loading');
            }
        }
    }

    showAlert(type, message) {
        // Remove existing alerts
        const existingAlert = document.querySelector('.qr-alert');
        if (existingAlert) {
            existingAlert.remove();
        }

        // Create alert element
        const alert = document.createElement('div');
        alert.className = `qr-alert qr-alert--${type}`;
        alert.innerHTML = `
            <div class="qr-alert__content">
                <i class="fi fi-rr-${type === 'success' ? 'check' : 'cross'}" aria-hidden="true"></i>
                <span>${message}</span>
            </div>
            <button type="button" class="qr-alert__close" aria-label="Close alert">
                <i class="fi fi-rr-cross" aria-hidden="true"></i>
            </button>
        `;

        // Add close functionality
        const closeBtn = alert.querySelector('.qr-alert__close');
        closeBtn.addEventListener('click', () => alert.remove());

        // Auto-remove after 5 seconds for success
        if (type === 'success') {
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.remove();
                }
            }, 5000);
        }

        // Insert at the top of the form
        const form = document.getElementById('qrForm');
        if (form) {
            form.insertBefore(alert, form.firstChild);
        }
    }

    updateHelperText(shortCode) {
        const helper = document.querySelector('.qr-helper .note span');
        if (helper) {
            helper.textContent = `Short link: /qrgo.php?q=${shortCode}`;
        }
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    console.log('QR Generator initializing...');

    // Check if required elements exist
    const qrCanvas = document.getElementById('qr-canvas');
    const tabs = document.querySelectorAll('.qr-tab');

    if (!qrCanvas) {
        console.error('QR canvas not found');
        return;
    }

    if (tabs.length === 0) {
        console.error('No tabs found');
        return;
    }

    console.log('Found', tabs.length, 'tabs and QR canvas');

    try {
        new QRGenerator();
        console.log('QR Generator initialized successfully');
    } catch (error) {
        console.error('Error initializing QR Generator:', error);
    }
});