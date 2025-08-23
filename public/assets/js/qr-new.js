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

        // Re-render QR
        this.scheduleRender();
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
        // Listen to all form inputs for live updates
        const inputs = document.querySelectorAll('input, textarea, select');
        inputs.forEach(input => {
            input.addEventListener('input', () => this.scheduleRender());
            input.addEventListener('change', () => this.scheduleRender());
        });
    }

    setupStylingControls() {
        // Color pickers
        if (this.elements.qrColor) {
            this.elements.qrColor.addEventListener('input', (e) => {
                this.state.fg = e.target.value;
                this.renderQR();
            });
        }

        if (this.elements.qrBg) {
            this.elements.qrBg.addEventListener('input', (e) => {
                this.state.bg = e.target.value;
                this.renderQR();
            });
        }

        // Size slider
        if (this.elements.qrSize && this.elements.qrSizeValue) {
            this.elements.qrSize.addEventListener('input', (e) => {
                this.state.size = parseInt(e.target.value);
                this.elements.qrSizeValue.textContent = this.state.size;
                this.renderQR();
            });
        }

        // Quiet zone slider
        if (this.elements.qrQuiet && this.elements.qrQuietValue) {
            this.elements.qrQuiet.addEventListener('input', (e) => {
                this.state.quiet = parseInt(e.target.value);
                this.elements.qrQuietValue.textContent = this.state.quiet;
                this.renderQR();
            });
        }

        // Rounded modules toggle
        if (this.elements.qrRounded) {
            this.elements.qrRounded.addEventListener('change', (e) => {
                this.state.rounded = e.target.checked;
                this.renderQR();
            });
        }

        // Platform preview for App Stores
        document.querySelectorAll('input[name="platform_preview"]').forEach(radio => {
            radio.addEventListener('change', (e) => {
                this.state.platformPreview = e.target.value;
                this.scheduleRender();
            });
        });
    }

    setupCharacterCounter() {
        const textArea = document.getElementById('text');
        const charCount = document.getElementById('charCount');

        if (textArea && charCount) {
            textArea.addEventListener('input', () => {
                const count = textArea.value.length;
                charCount.textContent = count;

                if (count > 900) {
                    charCount.style.color = 'var(--warning)';
                } else {
                    charCount.style.color = 'var(--text-muted)';
                }
            });
        }
    }

    setupKeyboardNavigation() {
        // Focus management for tab panels
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Tab') {
                const activePane = document.querySelector('.qr-pane:not(.is-hidden)');
                if (activePane) {
                    const focusableElements = activePane.querySelectorAll('input, textarea, select, button');
                    if (focusableElements.length > 0) {
                        // Ensure focus stays within the active pane
                        const firstElement = focusableElements[0];
                        const lastElement = focusableElements[focusableElements.length - 1];

                        if (e.shiftKey && document.activeElement === firstElement) {
                            e.preventDefault();
                            lastElement.focus();
                        } else if (!e.shiftKey && document.activeElement === lastElement) {
                            e.preventDefault();
                            firstElement.focus();
                        }
                    }
                }
            }
        });
    }

    scheduleRender() {
        clearTimeout(this.debounceTimer);
        this.debounceTimer = setTimeout(() => {
            this.renderQR();
        }, 60);
    }

    renderDefaultQR() {
        // Set default URL if empty
        const urlInput = document.getElementById('destination_url');
        if (urlInput && !urlInput.value.trim()) {
            urlInput.value = this.DEFAULT_URL;
        }

        this.renderQR();
    }

    renderQR() {
        const payload = this.buildPayload(this.state.type);

        if (!payload) {
            this.showEmptyState();
            this.updateCreateButton(false);
            return;
        }

        this.state.payload = payload;
        this.updateCreateButton(true);
        this.generateQRCode(payload);
        this.updateCaption(this.state.type, payload);
    }

    buildPayload(type) {
        switch (type) {
            case 'url':
                return this.buildUrl();
            case 'vcard':
                return this.buildVCard();
            case 'text':
                return this.buildText();
            case 'email':
                return this.buildEmail();
            case 'wifi':
                return this.buildWifi();
            case 'pdf':
                return this.buildPdf();
            case 'stores':
                return this.buildAppStores();
            case 'images':
                return this.buildImage();
            default:
                return '';
        }
    }

    buildUrl() {
        const url = document.getElementById('destination_url')?.value.trim();
        if (!url) return '';

        // Ensure URL has protocol
        if (url && !url.match(/^https?:\/\//)) {
            return `https://${url}`;
        }
        return url;
    }

    buildVCard() {
        const fullname = document.getElementById('fullname')?.value.trim();
        if (!fullname) return '';

        const org = document.getElementById('org')?.value.trim() || '';
        const title = document.getElementById('job_title')?.value.trim() || '';
        const phone = document.getElementById('phone')?.value.trim() || '';
        const email = document.getElementById('email')?.value.trim() || '';
        const website = document.getElementById('website')?.value.trim() || '';
        const address = document.getElementById('address')?.value.trim() || '';

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
        const text = document.getElementById('text')?.value.trim();
        return text || '';
    }

    buildEmail() {
        const to = document.getElementById('to')?.value.trim();
        if (!to) return '';

        const subject = document.getElementById('subject')?.value.trim() || '';
        const body = document.getElementById('body')?.value.trim() || '';

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
        const ssid = document.getElementById('ssid')?.value.trim();
        if (!ssid) return '';

        const password = document.getElementById('password')?.value.trim() || '';
        const encryption = document.getElementById('encryption')?.value || 'WPA';
        const hidden = document.getElementById('hidden')?.checked || false;

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
        const url = document.getElementById('pdf_url')?.value.trim();
        return url || '';
    }

    buildAppStores() {
        const ios = document.getElementById('ios_url')?.value.trim() || '';
        const android = document.getElementById('android_url')?.value.trim() || '';

        if (!ios && !android) return '';

        // Return the URL for the selected platform
        if (this.state.platformPreview === 'ios' && ios) {
            return ios;
        } else if (this.state.platformPreview === 'android' && android) {
            return android;
        }

        // Fallback to whichever URL is available
        return ios || android;
    }

    buildImage() {
        const url = document.getElementById('image_url')?.value.trim();
        return url || '';
    }

    generateQRCode(payload) {
        if (!this.elements.qrCanvas) {
            console.error('QR canvas not found');
            return;
        }

        // Clear existing QR
        this.elements.qrCanvas.innerHTML = '';

        // Create QR code with styling options
        this.currentQR = new QRCode(this.elements.qrCanvas, {
            text: payload,
            width: this.state.size,
            height: this.state.size,
            colorDark: this.state.fg,
            colorLight: this.state.bg === 'transparent' ? '#ffffff' : this.state.bg,
            correctLevel: QRCode.CorrectLevel.M
        });

        // Apply rounded modules if enabled
        if (this.state.rounded) {
            const qrImage = this.elements.qrCanvas.querySelector('img');
            if (qrImage) {
                qrImage.style.borderRadius = '4px';
            }
        }

        // Apply quiet zone (margin)
        if (this.state.quiet > 0) {
            this.elements.qrCanvas.style.padding = `${this.state.quiet}px`;
        }

        // Update accessibility
        this.elements.qrCanvas.setAttribute('aria-label', `QR code preview for ${this.truncateText(payload, 50)}`);
    }

    showEmptyState() {
        if (!this.elements.qrCanvas) return;

        this.elements.qrCanvas.innerHTML = `
            <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: ${this.state.size}px; color: var(--text-muted);">
                <i class="fi fi-rr-qr-code" style="font-size: 48px; margin-bottom: 16px;"></i>
                <span>Fill the form to see preview</span>
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
                const name = document.getElementById('fullname')?.value.trim() || 'Contact';
                caption = `${name} | vCard`;
                break;
            case 'text':
                caption = `${this.truncateText(payload, 30)} | Text`;
                break;
            case 'email':
                const to = document.getElementById('to')?.value.trim() || 'Email';
                caption = `${to} | Email`;
                break;
            case 'wifi':
                const ssid = document.getElementById('ssid')?.value.trim() || 'WiFi';
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
