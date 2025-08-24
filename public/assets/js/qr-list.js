(function() {
    function ready(fn) {
        if (document.readyState !== 'loading') fn();
        else document.addEventListener('DOMContentLoaded', fn);
    }

    function debounce(fn, ms) {
        let t;
        return (...a) => {
            clearTimeout(t);
            t = setTimeout(() => fn(...a), ms);
        };
    }

    ready(function() {
        const tabs = document.querySelectorAll('.qr-tabs .qr-tab');
        const rows = document.querySelectorAll('.qr-list .qr-row');
        const search = document.querySelector('[data-qr-search]');
        if (!rows.length || !tabs.length) return; // nothing to do

        const url = new URL(window.location.href);
        let type = (document.querySelector('.qr-tabs') ? .getAttribute('data-current-type') || url.searchParams.get('type') || 'all').toLowerCase();
        const state = { type, q: (search ? .value || '').trim().toLowerCase() };

        function setActiveTab(slug) {
            tabs.forEach(t => {
                const on = t.dataset.type === slug;
                t.classList.toggle('is-active', on);
                t.setAttribute('aria-selected', on ? 'true' : 'false');
            });
        }

        function applyFilter() {
            const q = state.q;
            rows.forEach(r => {
                const t = (r.dataset.type || '').toLowerCase();
                const matchesType = (state.type === 'all' || t === state.type);
                const matchesText = !q || r.textContent.toLowerCase().includes(q);
                r.hidden = !(matchesType && matchesText);
            });
        }

        tabs.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                state.type = btn.dataset.type;
                setActiveTab(state.type);
                applyFilter();
                // update URL without reload
                const u = new URL(window.location.href);
                if (state.type === 'all') u.searchParams.delete('type');
                else u.searchParams.set('type', state.type);
                window.history.replaceState({}, '', u);
            });
        });

        if (search) {
            search.addEventListener('input', debounce(() => {
                state.q = (search.value || '').trim().toLowerCase();
                applyFilter();
            }, 200));
        }

        // Initial
        setActiveTab(state.type);
        applyFilter();
    });
})();