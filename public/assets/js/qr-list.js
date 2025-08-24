// Scoped controller for QR list tabs + search (no full reloads)
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
        // Only run on the list/table view container
        const root = document.querySelector('#qrTable') || document.querySelector('.qr-list.qr-table');
        const tabsWrap = document.querySelector('.qr-list__tabs');
        if (!root || !tabsWrap) return;

        const tabs = Array.from(tabsWrap.querySelectorAll('.tabs__item'));
        const rows = Array.from(root.querySelectorAll('.qr-row'));
        const search = document.querySelector('[data-qr-search]');

        const url = new URL(location.href);
        const urlType = (url.searchParams.get('type') || 'all').toLowerCase();
        const initialType = urlType === 'stores' ? 'appstores' : urlType;
        const initialQ = (url.searchParams.get('q') || '').toLowerCase();

        const state = { type: initialType, q: initialQ };

        function setTabSelection() {
            tabs.forEach(btn => btn.setAttribute('aria-selected', String(btn.dataset.type === state.type)));
        }

        function render() {
            const q = state.q.trim();
            let any = false;
            rows.forEach(row => {
                const t = (row.dataset.type || '').trim().toLowerCase();
                const text = (row.dataset.q || row.textContent || '').toLowerCase();
                const matchType = state.type === 'all' || t === state.type;
                const matchQ = !q || text.includes(q);
                const vis = matchType && matchQ;
                row.hidden = !vis;
                row.style.display = vis ? '' : 'none';
                if (vis) any = true;
            });
            const empty = root.querySelector('.qr-empty');
            if (empty) empty.hidden = any;
            const tableWrap = root.querySelector('.table-wrapper');
            if (tableWrap) tableWrap.style.display = any ? '' : 'none';
        }

        function syncUrl() {
            const u = new URL(location.href);
            const serverType = state.type === 'appstores' ? 'stores' : state.type;
            if (serverType === 'all') u.searchParams.delete('type');
            else u.searchParams.set('type', serverType);
            if (state.q) u.searchParams.set('q', state.q);
            else u.searchParams.delete('q');
            history.replaceState({}, '', u);
        }

        tabs.forEach(btn => {
            btn.addEventListener('click', () => {
                state.type = btn.dataset.type || 'all';
                setTabSelection();
                syncUrl();
                render();
            }, { passive: true });
        });

        if (search) {
            const onInput = debounce(() => {
                state.q = (search.value || '').toLowerCase();
                syncUrl();
                render();
            }, 200);
            search.addEventListener('input', onInput);
            if (state.q && !search.value) search.value = state.q;
        }

        window.addEventListener('popstate', () => {
            const u = new URL(location.href);
            const t = (u.searchParams.get('type') || 'all').toLowerCase();
            state.type = t === 'stores' ? 'appstores' : t;
            state.q = (u.searchParams.get('q') || '').toLowerCase();
            setTabSelection();
            if (search) search.value = state.q;
            render();
        });

        // initial
        setTabSelection();
        render();
    });
})();