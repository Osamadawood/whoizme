(function() {
    function dsPalette() {
        const s = getComputedStyle(document.documentElement);
        return {
            text: s.getPropertyValue('--text').trim() || '#334155',
            grid: s.getPropertyValue('--muted').trim() || '#D1D5DB',
            border: s.getPropertyValue('--border').trim() || '#E5E7EB',
            primary: s.getPropertyValue('--primary').trim() || '#4B6BFB',
            accent: s.getPropertyValue('--accent').trim() || '#A855F7',
            yellow: (s.getPropertyValue('--brand-yellow-500') || '').trim() || '#FACC15',
            orange: (s.getPropertyValue('--brand-orange-500') || '').trim() || '#FB923C',
        };
    }

    function isRTL() { return document.documentElement.dir === 'rtl'; }

    function applyChartDefaults() {
        const p = dsPalette();
        if (!window.Chart) return;
        Chart.defaults.color = p.text;
        Chart.defaults.font.family = 'Inter, Cairo, ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial';
        Chart.defaults.plugins.legend.position = 'bottom';
        Chart.defaults.plugins.tooltip.mode = 'index';
        Chart.defaults.plugins.tooltip.intersect = false;
        Chart.defaults.elements.line.tension = 0.3;
        Chart.defaults.elements.line.borderWidth = 2;
        Chart.defaults.elements.point.radius = 0;
        Chart.defaults.scale.grid.color = p.grid;
        Chart.defaults.scale.border.color = p.border;
        Chart.defaults.layout.padding = 8;
    }
    applyChartDefaults();
    const root = document.getElementById('analytics-charts');
    if (!root) return;

    let from = root.getAttribute('data-from') || new Date(Date.now() - 30 * 864e5).toISOString().slice(0, 10);
    let to = root.getAttribute('data-to') || new Date().toISOString().slice(0, 10);
    const scope = root.getAttribute('data-scope') || 'all';

    const $trend = document.getElementById('chart-trend');
    const $devices = document.getElementById('chart-devices');
    const $refs = document.getElementById('chart-referrers');

    function emptyCard(canvas, msg) {
        if (!canvas) return;
        const wrap = canvas.parentElement;
        if (!wrap) return;
        const d = document.createElement('div');
        d.className = 'empty';
        d.textContent = msg || 'No data yet';
        wrap.innerHTML = '';
        wrap.appendChild(d);
    }

    function showCanvas(canvas) {
        if (!canvas) return;
        const wrap = canvas.parentElement;
        if (!wrap) return;
        // ensure canvas visible
        wrap.innerHTML = '';
        wrap.appendChild(canvas);
        canvas.style.display = '';
    }

    let palette = dsPalette();

    // Filter state
    const FilterState = { scope: 'all', devices: [], ref: '' };

    let trendChart, devicesChart, refsChart;

    function getFiltersFromURL() {
        const u = new URL(location.href);
        const s = (u.searchParams.get('scope') || '').toLowerCase();
        if (['all', 'links', 'qrs'].includes(s)) FilterState.scope = s;
        else FilterState.scope = 'all';
        const d = (u.searchParams.get('device') || '').toLowerCase();
        FilterState.devices = d ? d.split(',').map(x => x.trim()).filter(Boolean) : [];
        const r = (u.searchParams.get('ref') || '').trim();
        FilterState.ref = r;
        const f = u.searchParams.get('from');
        if (f) from = f;
        const t = u.searchParams.get('to');
        if (t) to = t;
    }

    function getFiltersFromStorage() {
        try { const raw = localStorage.getItem('analytics:filters:v1'); if (!raw) return; const f = JSON.parse(raw); if (!f || typeof f !== 'object') return; if (['all', 'links', 'qrs'].includes(f.scope)) FilterState.scope = f.scope; if (Array.isArray(f.devices)) FilterState.devices = f.devices; if (typeof f.ref === 'string') FilterState.ref = f.ref; if (typeof f.from === 'string') from = f.from; if (typeof f.to === 'string') to = f.to; } catch (_) {}
    }

    function persistFilters() {
        try { localStorage.setItem('analytics:filters:v1', JSON.stringify({ scope: FilterState.scope, devices: FilterState.devices, ref: FilterState.ref, from, to })); } catch (_) {}
        const u = new URL(location.href);
        const set = (k, v) => { if (v && ((Array.isArray(v) && v.length > 0) || (!Array.isArray(v) && v !== ''))) { u.searchParams.set(k, Array.isArray(v) ? v.join(',') : v); } else { u.searchParams.delete(k); } };
        set('scope', FilterState.scope !== 'all' ? FilterState.scope : '');
        set('device', FilterState.devices);
        set('ref', FilterState.ref);
        set('from', from);
        set('to', to);
        history.replaceState({}, '', u.toString());
    }

    async function loadAll(ctrl) {
        const p = new URLSearchParams({ from, to, scope: FilterState.scope });
        if (FilterState.devices.length) p.set('device', FilterState.devices.join(','));
        if (FilterState.ref) p.set('ref', FilterState.ref);
        showLoading($trend);
        showLoading($devices);
        showLoading($refs);
        const [s, d, r] = await Promise.all([
            fetch(`/api/analytics/series.php?${p}&interval=day`, { signal: ctrl.signal }).then(x => x.json()).catch(() => ({ ok: false })),
            fetch(`/api/analytics/devices.php?${p}`, { signal: ctrl.signal }).then(x => x.json()).catch(() => ({ ok: false })),
            fetch(`/api/analytics/referrers.php?${p}`, { signal: ctrl.signal }).then(x => x.json()).catch(() => ({ ok: false })),
        ]);
        renderTrend(s);
        renderDevices(d);
        renderRefs(r);
    }

    function fmtDate(dt) { return dt.toISOString().slice(0, 10); }

    function writeDetails() {
        const el = document.getElementById('range-details');
        if (!el) return;
        try {
            const f = new Date((from || '').replace(/-/g, '/'));
            const t = new Date((to || '').replace(/-/g, '/'));
            const loc = document.dir === 'rtl' ? 'ar' : 'en';
            const fmt = { month: 'short', day: 'numeric' };
            el.textContent = `${f.toLocaleDateString(loc,fmt)} → ${t.toLocaleDateString(loc,fmt)} (${FilterState.scope})`;
        } catch (_) { /* ignore */ }
    }

    function setRange(kind) {
        const now = new Date();
        if (kind === 'today') {
            from = fmtDate(now);
            to = fmtDate(now);
        } else if (kind === '7d') {
            const f = new Date(now.getTime() - 6 * 864e5);
            from = fmtDate(f);
            to = fmtDate(now);
        } else if (kind === '30d') {
            const f = new Date(now.getTime() - 29 * 864e5);
            from = fmtDate(f);
            to = fmtDate(now);
        } else if (kind === 'custom') {
            const modal = document.getElementById('dr-modal');
            const iFrom = document.getElementById('dr-from');
            const iTo = document.getElementById('dr-to');
            const iCancel = document.getElementById('dr-cancel');
            const form = document.getElementById('dr-form');
            if (!modal || !iFrom || !iTo || !form) return;
            iFrom.value = from;
            iTo.value = to;
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
            const onOverlay = (e) => { if (e.target && e.target.hasAttribute && e.target.hasAttribute('data-modal-close')) close(); };
            const onCancel = () => close();

            function close() {
                modal.classList.remove('is-open');
                modal.setAttribute('aria-hidden', 'true');
                cleanup();
            }

            function onSubmit(e) {
                e.preventDefault();
                const f = iFrom.value,
                    t = iTo.value;
                const re = /^\d{4}-\d{2}-\d{2}$/;
                const err = document.getElementById('dr-error');
                if (!re.test(f) || !re.test(t)) {
                    if (err) {
                        err.textContent = 'Please enter valid dates (YYYY-MM-DD).';
                        err.hidden = false;
                    }
                    return;
                }
                if (new Date(f) > new Date(t)) {
                    if (err) {
                        err.textContent = 'From must be before To.';
                        err.hidden = false;
                    }
                    return;
                }
                if (err) err.hidden = true;
                from = f;
                to = t;
                close();
                // update details and reload
                const det = document.getElementById('range-details');
                if (det) {
                    const loc = document.dir === 'rtl' ? 'ar' : 'en';
                    det.textContent = new Date(from.replace(/-/g, '/')).toLocaleDateString(loc, { month: 'short', day: 'numeric' }) + ' → ' + new Date(to.replace(/-/g, '/')).toLocaleDateString(loc, { month: 'short', day: 'numeric' }) + ` (${scope})`;
                }
                rerun();
            }

            function cleanup() {
                form.removeEventListener('submit', onSubmit);
                if (iCancel) iCancel.removeEventListener('click', onCancel);
                modal.removeEventListener('click', onOverlay);
            }

            function rerun() {
                root.setAttribute('data-from', from);
                root.setAttribute('data-to', to);
                const ctrl = new AbortController();
                loadAll(ctrl).catch((e) => console.error('[analytics]', e));
            }
            form.addEventListener('submit', onSubmit);
            if (iCancel) iCancel.addEventListener('click', onCancel);
            modal.addEventListener('click', onOverlay);
            return;
        }
        root.setAttribute('data-from', from);
        root.setAttribute('data-to', to);
        // update UI active state
        document.querySelectorAll('[data-range]').forEach(el => el.classList.remove('is-active'));
        const active = document.querySelector(`[data-range="${kind}"]`);
        if (active) active.classList.add('is-active');
        writeDetails();
        const ctrl = new AbortController();
        loadAll(ctrl).catch((e) => console.error('[analytics]', e));
    }

    // Loading state (no skeleton: keep canvas mounted but hidden)
    function showLoading(canvas) {
        if (!canvas) return;
        const wrap = canvas.parentElement;
        if (!wrap) return;
        // Keep canvas in place; just clear any placeholder content
        wrap.innerHTML = '';
        wrap.appendChild(canvas);
        canvas.style.display = 'none';
    }

    function renderTrend(j) {
        if (!j || !j.ok || !Array.isArray(j.labels)) { emptyCard($trend, 'No data yet'); return; }
        if (!j.total || j.total === 0 || j.labels.length === 0) { emptyCard($trend, 'No data yet'); return; }
        showCanvas($trend);
        const ctx = $trend.getContext('2d');
        const grad = ctx.createLinearGradient(0, 0, 0, $trend.height || 260);
        grad.addColorStop(0, palette.primary + '33');
        grad.addColorStop(1, 'rgba(0,0,0,0)');
        const fmtLbl = (s) => {
            if (/^\d{4}-\d{2}-\d{2}$/.test(s)) {
                try { const d = new Date(s.replace(/-/g, '/')); return d.toLocaleDateString(document.dir === 'rtl' ? 'ar' : 'en', { month: 'short', day: 'numeric' }); } catch (_) { return s; }
            }
            if (/^\d{4}-\d{2}$/.test(s)) {
                try { const d = new Date((s + '-01').replace(/-/g, '/')); return d.toLocaleDateString(document.dir === 'rtl' ? 'ar' : 'en', { month: 'short', year: 'numeric' }); } catch (_) { return s; }
            }
            return s;
        };
        const labels = (j.labels || []).map(fmtLbl);
        const data = {
            labels,
            datasets: [{
                label: 'Engagements',
                data: j.counts || [],
                borderColor: palette.primary,
                backgroundColor: grad,
                fill: true,
                tension: 0.35,
                pointRadius: 2.5,
                pointHoverRadius: 4,
                pointBackgroundColor: palette.primary,
                pointBorderColor: palette.primary,
                borderWidth: 2
            }]
        };
        const opts = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            interaction: { mode: 'index', intersect: false },
            scales: {
                x: { grid: { display: false }, ticks: { color: palette.text, reverse: isRTL(), maxRotation: 0, autoSkip: true } },
                y: { beginAtZero: true, grid: { color: palette.grid, borderDash: [4, 4], drawBorder: false }, ticks: { color: palette.text } }
            }
        };
        if (!trendChart) { trendChart = new Chart(ctx, { type: 'line', data, options: opts }); } else {
            trendChart.data = data;
            trendChart.update();
        }
    }

    function renderDevices(j) {
        if (!j || !j.ok || !Array.isArray(j.series)) { emptyCard($devices, 'No data yet'); return; }
        if (!j.total || j.total === 0) { emptyCard($devices, 'No data yet'); return; }
        showCanvas($devices);
        const labels = j.series.map(x => x[0]);
        const values = j.series.map(x => x[1]);
        const colors = [palette.primary, palette.accent, palette.yellow, palette.orange];
        const data = { labels, datasets: [{ data: values, backgroundColor: colors }] };
        const opts = { responsive: true, plugins: { legend: { position: 'bottom', labels: { color: palette.text } } } };
        if (!devicesChart) { devicesChart = new Chart($devices.getContext('2d'), { type: 'doughnut', data, options: opts }); } else {
            devicesChart.data = data;
            devicesChart.update();
        }
    }

    function renderRefs(j) {
        if (!j || !j.ok || !Array.isArray(j.series)) { emptyCard($refs, 'No data yet'); return; }
        const labels = j.series.map(x => x[0]);
        const values = j.series.map(x => x[1]);
        const any = values.some(v => (v || 0) > 0);
        if (!any) { emptyCard($refs, 'No data yet'); return; }
        showCanvas($refs);
        const data = { labels, datasets: [{ label: 'Referrers', data: values, backgroundColor: palette.accent, borderRadius: 6 }] };
        const opts = { indexAxis: 'y', responsive: true, plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, grid: { color: palette.grid }, ticks: { color: palette.text, reverse: isRTL() } }, y: { ticks: { color: palette.text } } } };
        if (!refsChart) { refsChart = new Chart($refs.getContext('2d'), { type: 'bar', data, options: opts }); } else {
            refsChart.data = data;
            refsChart.update();
        }
    }

    function ensureChartJsThen(run) {
        if (typeof Chart !== 'undefined') {
            applyChartDefaults();
            run();
            return;
        }
        var s = document.createElement('script');
        s.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js';
        s.defer = true;
        s.onload = function() {
            applyChartDefaults();
            run();
        };
        s.onerror = function() { console.error('[analytics] Failed to load Chart.js'); };
        document.head.appendChild(s);
    }

    function boot() {
        // Hydrate filters: URL → storage → defaults
        getFiltersFromStorage();
        getFiltersFromURL();
        // Sync UI
        const sel = document.getElementById('flt-scope');
        if (sel) {
            sel.value = FilterState.scope;
            sel.addEventListener('change', () => {
                FilterState.scope = sel.value;
                persistFilters();
                debounceFetch();
            });
        }
        const chipWrap = document.getElementById('flt-device');
        if (chipWrap) {
            const setActive = (btn, on) => { on ? btn.classList.add('is-active') : btn.classList.remove('is-active'); };
            chipWrap.querySelectorAll('[data-device]').forEach(btn => {
                const v = btn.getAttribute('data-device');
                setActive(btn, FilterState.devices.includes(v));
                btn.addEventListener('click', () => {
                    const val = btn.getAttribute('data-device');
                    const i = FilterState.devices.indexOf(val);
                    if (i >= 0) FilterState.devices.splice(i, 1);
                    else FilterState.devices.push(val);
                    setActive(btn, FilterState.devices.includes(val));
                    persistFilters();
                    debounceFetch();
                });
            });
        }
        const refInput = document.getElementById('flt-ref');
        const refList = document.getElementById('flt-ref-list');
        if (refInput) {
            refInput.value = FilterState.ref || '';
            let sugAbort;
            refInput.addEventListener('input', async() => {
                const q = refInput.value.trim();
                if (refList) {
                    if (!q) {
                        refList.hidden = true;
                        refList.innerHTML = '';
                        return;
                    }
                    refList.hidden = false;
                    refList.innerHTML = '<li class="u-text-muted">Loading…</li>';
                }
                const p = new URLSearchParams({ from, to, scope: FilterState.scope, limit: '20', list: '1' });
                if (FilterState.devices.length) p.set('device', FilterState.devices.join(','));
                try {
                    if (sugAbort) sugAbort.abort();
                    const ac = new AbortController();
                    sugAbort = ac;
                    const r = await fetch(`/api/analytics/referrers.php?${p.toString()}`, { signal: ac.signal });
                    const j = await r.json();
                    if (Array.isArray(j.hosts) && refList) { refList.innerHTML = j.hosts.filter(h => h.toLowerCase().includes(q.toLowerCase())).slice(0, 8).map(h => `<li data-host="${h}">${h}</li>`).join('') || '<li class="u-text-muted">No matches</li>'; }
                } catch (_) {
                    if (refList) {
                        refList.innerHTML = '';
                        refList.hidden = true;
                    }
                }
            });
            refList && refList.addEventListener('click', (e) => {
                const li = e.target.closest('li[data-host]');
                if (!li) return;
                refInput.value = li.getAttribute('data-host');
                FilterState.ref = refInput.value.trim();
                refList.hidden = true;
                persistFilters();
                debounceFetch();
            });
            refInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    FilterState.ref = refInput.value.trim();
                    persistFilters();
                    debounceFetch();
                }
            });
        }
        const controller = new AbortController();
        loadAll(controller).catch((e) => console.error('[analytics]', e));
        writeDetails();
    }
    ensureChartJsThen(boot);

    // repaint on theme/dir changes
    window.__analyticsRepaint = () => {
        palette = dsPalette();
        applyChartDefaults();
        if (trendChart) trendChart.destroy();
        if (devicesChart) devicesChart.destroy();
        if (refsChart) refsChart.destroy();
        const ctrl = new AbortController();
        loadAll(ctrl).catch(() => {});
    };
    const mo = new MutationObserver(() => window.__analyticsRepaint && window.__analyticsRepaint());
    mo.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme', 'dir', 'class'] });

    // Range pills (Today/7D/30D/Custom)
    document.addEventListener('click', function(e) {
        const b = e.target && e.target.closest && e.target.closest('[data-range]');
        if (!b) return;
        e.preventDefault();
        const kind = b.getAttribute('data-range');
        if (kind) setRange(kind);
    });

    // Reset range button (back to Today)
    const resetBtn = document.getElementById('range-reset');
    if (resetBtn) {
        resetBtn.addEventListener('click', function() {
            const today = new Date();
            from = fmtDate(today);
            to = fmtDate(today);
            root.setAttribute('data-from', from);
            root.setAttribute('data-to', to);
            document.querySelectorAll('[data-range]').forEach(el => el.classList.remove('is-active'));
            const pill = document.querySelector('[data-range="today"]');
            if (pill) pill.classList.add('is-active');
            writeDetails();
            const ctrl = new AbortController();
            loadAll(ctrl).catch((e) => console.error('[analytics]', e));
        });
    }

    // Debounced fetch helper + persist
    let debTimer, currentController;

    function debounceFetch() {
        clearTimeout(debTimer);
        debTimer = setTimeout(() => {
            persistFilters();
            if (currentController) currentController.abort();
            const ac = new AbortController();
            currentController = ac;
            loadAll(ac).catch(() => {});
        }, 300);
    }
})();