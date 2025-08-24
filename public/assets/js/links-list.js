(function() {
    function ready(fn) {
        if (document.readyState !== 'loading') fn();
        else document.addEventListener('DOMContentLoaded', fn);
    }
    ready(function() {
        const root = document.getElementById('linksTable');
        if (!root) return;

        function attachPager() {
            const pager = document.querySelector('.pagination-wrapper .pagination');
            if (!pager) return;
            pager.addEventListener('click', async(e) => {
                const a = e.target.closest('a');
                if (!a) return;
                e.preventDefault();
                try {
                    const res = await fetch(a.href, { headers: { 'X-Requested-With': 'fetch' } });
                    const html = await res.text();
                    const doc = new DOMParser().parseFromString(html, 'text/html');
                    const nt = doc.querySelector('#linksTable');
                    const np = doc.querySelector('.pagination-wrapper');
                    if (nt) root.replaceWith(nt);
                    if (np) { const cp = document.querySelector('.pagination-wrapper'); if (cp) cp.replaceWith(np); }
                    history.replaceState({}, '', a.href);
                    // Rebind handlers after DOM swap
                    setTimeout(() => { location.reload(); }, 0);
                } catch { location.href = a.href; }
            });
        }
        document.addEventListener('click', async(e) => {
            const b = e.target.closest('[data-action="copy-link"]');
            if (!b) return;
            const slug = b.getAttribute('data-slug');
            if (!slug) return;
            const url = `${location.origin}/lgo.php?c=${slug}`;
            try {
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    await navigator.clipboard.writeText(url);
                } else {
                    const ta = document.createElement('textarea');
                    ta.value = url;
                    document.body.appendChild(ta);
                    ta.select();
                    document.execCommand('copy');
                    document.body.removeChild(ta);
                }
                b.textContent = 'Copied!';
            } catch (_) {
                alert('Copy failed. You can copy this URL:\n' + url);
            }
            setTimeout(() => b.textContent = 'Copy short link', 1200);
        });
        attachPager();
    });
})();