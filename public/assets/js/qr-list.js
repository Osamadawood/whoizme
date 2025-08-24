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
        let root = document.querySelector('#qrTable') || document.querySelector('.qr-list.qr-table');
        const tabsWrap = document.querySelector('.qr-list__tabs');
        if (!root || !tabsWrap) return;

        const tabs = Array.from(tabsWrap.querySelectorAll('[data-type]'));
        // rows may change after pagination; always query fresh inside render
        const search = document.querySelector('[data-qr-search]');

        const url = new URL(location.href);
        const normalize = (s) => {
            s = (s || '').toLowerCase();
            if (s === 'stores' || s === 'app') return 'appstores';
            if (s === 'image') return 'images';
            return s || 'all';
        };
        const initialType = normalize(url.searchParams.get('type') || 'all');
        const initialQ = (url.searchParams.get('q') || '').toLowerCase();

        const state = { type: initialType, q: initialQ };

        function setTabSelection() {
            tabs.forEach(btn => {
                const on = (btn.dataset.type || 'all') === state.type;
                btn.setAttribute('aria-selected', String(on));
                btn.classList.toggle('is-active', on);
            });
        }

        function render() {
            const q = state.q.trim();
            let any = false;
            const currentRoot = document.querySelector('#qrTable') || root;
            const rowsNow = Array.from(currentRoot.querySelectorAll('.qr-row'));
            rowsNow.forEach(row => {
                const t = normalize((row.dataset.type || '').trim());
                const text = (row.dataset.q || row.textContent || '').toLowerCase();
                const matchType = state.type === 'all' || t === state.type;
                const matchQ = !q || text.includes(q);
                const vis = matchType && matchQ;
                row.hidden = !vis;
                row.style.display = vis ? '' : 'none';
                if (vis) any = true;
            });
            const empty = (document.querySelector('#qrTable') || root).querySelector('.qr-empty');
            if (empty) empty.hidden = any;
            const tableWrap = (document.querySelector('#qrTable') || root).querySelector('.table-wrapper');
            if (tableWrap) tableWrap.style.display = any ? '' : 'none';
        }

        // Progressive-enhancement: AJAX pagination (no full reload)
        function attachPagerClick() {
            const pager = document.querySelector('.qr-list__body .pagination');
            if (!pager) return;
            pager.addEventListener('click', onPagerClick);
        }

        function onPagerClick(e) {
            const a = e.target.closest('a');
            if (!a || !a.href) return;
            e.preventDefault();
            loadPage(a.href);
        }

        async function loadPage(href) {
            try {
                const res = await fetch(href, { headers: { 'X-Requested-With': 'fetch' } });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const html = await res.text();
                const doc = new DOMParser().parseFromString(html, 'text/html');
                const incomingTable = doc.querySelector('#qrTable');
                const incomingPager = doc.querySelector('.pagination-wrapper');
                const currentTable = document.querySelector('#qrTable');
                if (incomingTable && currentTable) currentTable.replaceWith(incomingTable);
                if (incomingPager) {
                    const currentPager = document.querySelector('.qr-list__body .pagination-wrapper');
                    if (currentPager) currentPager.replaceWith(incomingPager);
                    else document.querySelector('.qr-list__body').appendChild(incomingPager);
                }
                // Clean URL: drop page/type; keep search only
                const clean = new URL(location.href);
                clean.searchParams.delete('page');
                clean.searchParams.delete('type');
                if (state.q) clean.searchParams.set('q', state.q);
                else clean.searchParams.delete('q');
                history.replaceState({}, '', clean);
                // Update cached root
                root = document.querySelector('#qrTable') || root;
                attachPagerClick();
                render();
            } catch (err) {
                console.error('AJAX pagination failed; falling back', err);
                location.href = href;
            }
        }

        function syncUrl() {
            const u = new URL(location.href);
            // Do not expose type in the URL
            u.searchParams.delete('type');
            // Keep search only
            if (state.q) u.searchParams.set('q', state.q);
            else u.searchParams.delete('q');
            // Remove page param when filtering/searching client-side
            u.searchParams.delete('page');
            history.replaceState({}, '', u);
        }

        // Robust delegation: click anywhere inside the tabs wrap on an element carrying data-type
        function onTabClick(e) {
            const el = e.target.closest('[data-type]');
            if (!el || !tabsWrap.contains(el)) return;
            // Stop the default anchor behavior (which was adding a `#` to the URL)
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            state.type = normalize(el.dataset.type || 'all');
            setTabSelection();
            syncUrl();
            render();
        }
        tabsWrap.addEventListener('click', onTabClick, true);
        // Cancel default on mousedown to avoid hash-jumps in some browsers
        tabsWrap.addEventListener('mousedown', function(e) {
            const el = e.target.closest('[data-type]');
            if (!el) return;
            e.preventDefault();
        }, true);

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
            const t = normalize(u.searchParams.get('type') || 'all');
            state.type = t;
            state.q = (u.searchParams.get('q') || '').toLowerCase();
            setTabSelection();
            if (search) search.value = state.q;
            render();
        });

        // initial
        setTabSelection();
        render();
        attachPagerClick();
    });
})();