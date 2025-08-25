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

    let trendChart, devicesChart, refsChart;

    async function loadAll(ctrl) {
        const p = new URLSearchParams({ from, to, scope });
        const [s, d, r] = await Promise.all([
            fetch(`/api/analytics/series.php?${p}&interval=day`, { signal: ctrl.signal }).then(x => x.json()),
            fetch(`/api/analytics/devices.php?${p}`, { signal: ctrl.signal }).then(x => x.json()),
            fetch(`/api/analytics/referrers.php?${p}`, { signal: ctrl.signal }).then(x => x.json()),
        ]);
        renderTrend(s);
        renderDevices(d);
        renderRefs(r);
    }

    function fmtDate(dt) { return dt.toISOString().slice(0, 10); }

    function setRange(kind) {
        const now = new Date();
        if (kind === 'today') { from = fmtDate(now);
            to = fmtDate(now); } else if (kind === '7d') { const f = new Date(now.getTime() - 6 * 864e5);
            from = fmtDate(f);
            to = fmtDate(now); } else if (kind === '30d') { const f = new Date(now.getTime() - 29 * 864e5);
            from = fmtDate(f);
            to = fmtDate(now); } else if (kind === 'custom') {
            const fIn = prompt('From (YYYY-MM-DD):', from) || from;
            const tIn = prompt('To (YYYY-MM-DD):', to) || to;
            const re = /^\d{4}-\d{2}-\d{2}$/;
            if (re.test(fIn) && re.test(tIn)) { from = fIn;
                to = tIn; }
        }
        root.setAttribute('data-from', from);
        root.setAttribute('data-to', to);
        // update UI active state
        document.querySelectorAll('[data-range]').forEach(el => el.classList.remove('is-active'));
        const active = document.querySelector(`[data-range="${kind}"]`);
        if (active) active.classList.add('is-active');
        const ctrl = new AbortController();
        loadAll(ctrl).catch((e) => console.error('[analytics]', e));
    }

    function renderTrend(j) {
        if (!j || !j.ok || !Array.isArray(j.labels)) return;
        if (!j.total || j.total === 0 || j.labels.length === 0) { emptyCard($trend, 'No data yet'); return; }
        showCanvas($trend);
        const data = { labels: j.labels, datasets: [{ label: 'Engagements', data: j.counts || [], borderColor: palette.primary, backgroundColor: palette.primary + '26', fill: true }] };
        const opts = { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { grid: { display: false }, ticks: { color: palette.text, reverse: isRTL() } }, y: { beginAtZero: true, grid: { color: palette.grid }, ticks: { color: palette.text } } } };
        if (!trendChart) { trendChart = new Chart($trend.getContext('2d'), { type: 'line', data, options: opts }); } else {
            trendChart.data = data;
            trendChart.update();
        }
    }

    function renderDevices(j) {
        if (!j || !j.ok || !Array.isArray(j.series)) return;
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
        if (!j || !j.ok || !Array.isArray(j.series)) return;
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
        const controller = new AbortController();
        loadAll(controller).catch((e) => console.error('[analytics]', e));
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
})();