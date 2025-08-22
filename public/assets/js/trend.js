(function() {
    const root = document.querySelector('[data-trend-root]');
    if (!root) return;

    const el = document.getElementById('trend-chart');
    const tabs = root.querySelector('[data-trend-tabs]');
    let chart;

    const css = getComputedStyle(document.documentElement);
    const COLOR_PRIMARY = css.getPropertyValue('--primary').trim() || '#6E93FF';
    const COLOR_MUTED = css.getPropertyValue('--text-muted').trim() || 'rgba(148,163,184,.6)';
    const SURFACE = css.getPropertyValue('--surface').trim() || '#0F172A';

    const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function toLocale(dstr) {
        const d = new Date(dstr.replace(/-/g, '/'));
        return d.toLocaleDateString(document.dir === 'rtl' ? 'ar' : 'en', { year: 'numeric', month: 'short', day: 'numeric' });
    }

    async function load(period) {
        const res = await fetch(`/api/analytics/trend.php?p=${encodeURIComponent(period)}`, { credentials: 'same-origin' });
        const json = await res.json();
        let labels = (json.days || []).map(d => d.date);
        let values = (json.days || []).map(d => Number(d.total || 0));

        // Ensure visible height even if CSS hasn't loaded
        if (el && (!el.style.height || parseInt(getComputedStyle(el).height, 10) < 100)) {
            el.style.minHeight = '320px';
        }

        // Build placeholder spine when API returns empty (avoid blank card)
        if (!labels.length) {
            const days = period === '30d' ? 30 : (period === '90d' ? 90 : 7);
            const today = new Date();
            const arr = [];
            for (let i = days - 1; i >= 0; i--) {
                const d = new Date(today);
                d.setDate(today.getDate() - i);
                arr.push(d);
            }
            labels = arr.map(d => d.toISOString().slice(0, 10));
            values = new Array(labels.length).fill(0);
        }

        const options = {
            chart: {
                type: 'area',
                height: 320,
                toolbar: { show: false },
                animations: { enabled: !prefersReduced, easing: 'easeinout', speed: 700 },
                foreColor: COLOR_MUTED,
                parentHeightOffset: 0
            },
            series: [{ name: 'Total', data: values }],
            stroke: { curve: 'smooth', width: 3, colors: [COLOR_PRIMARY] },
            fill: { type: 'gradient', gradient: { shade: 'dark', shadeIntensity: .3, opacityFrom: .28, opacityTo: 0, stops: [0, 60, 100] }, colors: [COLOR_PRIMARY] },
            dataLabels: { enabled: false },
            markers: { size: 3, strokeWidth: 2, colors: [SURFACE], strokeColors: [COLOR_PRIMARY] },
            grid: { borderColor: 'rgba(148,163,184,.15)', strokeDashArray: 4 },
            xaxis: {
                type: 'category',
                categories: labels,
                labels: {
                    rotate: -20,
                    formatter: (value, _ts, opts) => {
                        let v = value;
                        if (!v && opts && typeof opts.dataPointIndex !== 'undefined') {
                            v = labels[opts.dataPointIndex] || '';
                        }
                        if (!v) return '';
                        const d = new Date(String(v).replace(/-/g, '/'));
                        if (isNaN(d)) return String(v);
                        return d.toLocaleDateString(document.dir === 'rtl' ? 'ar' : 'en', { month: 'short', day: '2-digit' });
                    }
                },
                axisBorder: { color: 'transparent' },
                axisTicks: { color: 'transparent' }
            },
            yaxis: { min: 0, forceNiceScale: true, labels: { formatter: (v) => Math.round(v).toString() } },
            tooltip: { theme: 'dark', x: { formatter: (v) => v ? toLocale(v) : '' }, y: { formatter: (v) => `${Math.round(v)}` }, style: { fontSize: '14px' } }
        };

        if (chart) { chart.destroy(); }
        chart = new ApexCharts(el, options);
        chart.render();
    }

    function setActive(btn) {
        tabs.querySelectorAll('button').forEach(b => b.classList.remove('is-active'));
        btn.classList.add('is-active');
    }

    tabs.addEventListener('click', (e) => {
        const btn = e.target.closest('button[data-p]');
        if (!btn) return;
        const p = btn.dataset.p;
        setActive(btn);
        load(p);
        const url = new URL(window.location);
        url.searchParams.set('p', p);
        history.replaceState({}, '', url);
    });

    const initP = new URLSearchParams(location.search).get('p') || '7d';
    const initBtn = tabs.querySelector(`button[data-p="${initP}"]`) || tabs.querySelector('button[data-p="7d"]');
    if (initBtn) setActive(initBtn);
    load(initP);
})();