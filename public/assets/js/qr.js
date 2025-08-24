(function() {
    function onReady(fn) { if (document.readyState !== 'loading') { fn(); } else { document.addEventListener('DOMContentLoaded', fn); } }

    function renderThumb(el) {
        try {
            const payload = el.getAttribute('data-payload') || '';
            if (!payload) return;
            el.innerHTML = '';
            const box = document.createElement('div');
            box.style.width = '64px';
            box.style.height = '64px';
            el.appendChild(box);
            if (typeof QRCode !== 'undefined') {
                new QRCode(box, { text: payload, width: 64, height: 64, colorDark: '#4B6BFB', colorLight: getComputedStyle(document.documentElement).getPropertyValue('--surface').trim() || '#ffffff', correctLevel: QRCode.CorrectLevel.L });
            }
        } catch (_) { /* ignore */ }
    }

    function closestAction(target) { return target.closest('[data-action]'); }

    function copy(text) { return navigator.clipboard ? navigator.clipboard.writeText(text) : Promise.resolve(); }

    function post(url, data) { return fetch(url, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: new URLSearchParams(data).toString() }); }

    // Generate simple SVG QR (module squares) from an existing canvas
    function downloadSVGFromCanvas(canvas, filename) {
        try {
            if (!canvas) return;
            const w = canvas.width,
                h = canvas.height;
            const ctx = canvas.getContext('2d');
            const imgData = ctx.getImageData(0, 0, w, h).data;
            const size = 1; // 1px cells
            let rects = '';
            for (let y = 0; y < h; y++) {
                for (let x = 0; x < w; x++) {
                    const i = (y * w + x) * 4;
                    const a = imgData[i + 3];
                    const r = imgData[i],
                        g = imgData[i + 1],
                        b = imgData[i + 2];
                    if (a > 200 && r < 100 && g < 140) {
                        rects += `<rect x="${x}" y="${y}" width="${size}" height="${size}"/>`;
                    }
                }
            }
            const svg = `<?xml version="1.0" encoding="UTF-8"?>\n<svg xmlns="http://www.w3.org/2000/svg" width="${w}" height="${h}" viewBox="0 0 ${w} ${h}" shape-rendering="crispEdges"><g fill="#4B6BFB">${rects}</g></svg>`;
            const blob = new Blob([svg], { type: 'image/svg+xml' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename || 'qr.svg';
            a.click();
            setTimeout(() => URL.revokeObjectURL(url), 1000);
        } catch (_) { /* ignore */ }
    }

    function downloadSVGFromPayload(payload, filename, fg) {
        try {
            const tmp = document.createElement('div');
            const q = new QRCode(tmp, { text: payload, width: 256, height: 256, correctLevel: QRCode.CorrectLevel.L });
            const model = q && q._oQRCode;
            if (!model || typeof model.getModuleCount !== 'function') {
                const canvas = tmp.querySelector('canvas');
                if (canvas) return downloadSVGFromCanvas(canvas, filename);
                return;
            }
            const n = model.getModuleCount();
            let d = '';
            for (let r = 0; r < n; r++) {
                for (let c = 0; c < n; c++) {
                    if (model.isDark(r, c)) d += `M${c} ${r}h1v1h-1z`;
                }
            }
            const color = fg || '#000';
            const svg = `<?xml version="1.0" encoding="UTF-8"?>\n<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${n} ${n}" width="1024" height="1024" shape-rendering="crispEdges"><path fill="${color}" d="${d}"/></svg>`;
            const blob = new Blob([svg], { type: 'image/svg+xml' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename || 'qr.svg';
            a.click();
            setTimeout(() => URL.revokeObjectURL(url), 1000);
        } catch (e) { console.warn('[qr-list] download svg from payload failed', e); }
    }

    onReady(function() {
        const list = document.querySelector('.qr-list');
        if (!list) return;

        // Lazy render with IntersectionObserver
        const targets = Array.from(list.querySelectorAll('.qr-thumb[data-payload]'));
        if ('IntersectionObserver' in window) {
            const io = new IntersectionObserver((entries, obs) => {
                entries.forEach(en => {
                    if (en.isIntersecting) {
                        renderThumb(en.target);
                        obs.unobserve(en.target);
                    }
                });
            }, { rootMargin: '120px 0px', threshold: 0.01 });
            targets.forEach(t => io.observe(t));
        } else {
            targets.forEach(renderThumb);
        }

        document.addEventListener('click', function(e) {
            const btn = e.target.closest('[data-action="menu-toggle"]');
            if (btn) {
                const wrap = btn.closest('.qr-actions__kebab');
                const menu = wrap && wrap.querySelector('.kebab-menu');
                if (menu) {
                    const open = menu.classList.contains('is-open');
                    document.querySelectorAll('.kebab-menu.is-open').forEach(m => m.classList.remove('is-open'));
                    if (!open) {
                        menu.classList.add('is-open');
                        btn.setAttribute('aria-expanded', 'true');
                    }
                    e.preventDefault();
                }
            } else if (!e.target.closest('.kebab-menu')) {
                document.querySelectorAll('.kebab-menu.is-open').forEach(m => m.classList.remove('is-open'));
            }
        });

        document.addEventListener('click', async function(e) {
            // Force navigation for plain view-details links if anything blocks it
            const viewLink = e.target.closest('a.qr-actions__link');
            if (viewLink && !viewLink.hasAttribute('data-action')) {
                e.preventDefault();
                window.location.href = viewLink.href;
                return;
            }
            const a = closestAction(e.target);
            // If click is on a plain anchor without data-action, allow navigation
            if (!a) {
                const link = e.target.closest('a');
                if (link && !link.hasAttribute('data-action')) return;
                return;
            }
            const row = a.closest('tr');
            const id = (row && row.getAttribute('data-qr-id')) || a.getAttribute('data-id');
            const action = a.getAttribute('data-action');
            console.info('[qr-list] action', action, { id });
            switch (action) {
                case 'copy':
                    {
                        e.preventDefault();
                        const link = a.getAttribute('data-link');
                        if (link) await copy(link);
                        break;
                    }
                case 'edit':
                    {
                        window.location.href = '/qr/new?id=' + id;
                        break;
                    }
                case 'download-svg':
                    {
                        const payload = row && row.getAttribute('data-qr-payload');
                        if (payload) return downloadSVGFromPayload(payload, 'qr-' + id + '.svg');
                        const canvas = row ? row.querySelector('.qr-thumb canvas') : null;
                        if (canvas) return downloadSVGFromCanvas(canvas, 'qr-' + id + '.svg');
                        break;
                    }
                case 'delete':
                    {
                        e.preventDefault();
                        if (!confirm('Delete this QR?')) return;
                        const res = await post('/qr/delete.php', { id });
                        if (res.ok) {
                            row && row.parentNode && row.parentNode.removeChild(row);
                        }
                        break;
                    }
            }
        });

        document.addEventListener('keydown', function(e) { if (e.key === 'Escape') { document.querySelectorAll('.kebab-menu.is-open').forEach(m => m.classList.remove('is-open')); } });
    });
})();