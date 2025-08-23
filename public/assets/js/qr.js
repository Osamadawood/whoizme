(function(){
  function onReady(fn){ if(document.readyState!=='loading'){ fn(); } else { document.addEventListener('DOMContentLoaded', fn); } }

  function renderThumb(el){
    try{
      const payload = el.getAttribute('data-payload') || '';
      if(!payload) return;
      // clear fallback
      el.innerHTML='';
      // container 80x80 with 8px padding; QR 64x64
      const box = document.createElement('div');
      box.style.width = '64px';
      box.style.height = '64px';
      el.appendChild(box);
      if(typeof QRCode !== 'undefined'){
        new QRCode(box, { text: payload, width: 64, height: 64, colorDark: '#4B6BFB', colorLight: getComputedStyle(document.documentElement).getPropertyValue('--surface').trim()||'#ffffff', correctLevel: QRCode.CorrectLevel.L });
      }
    }catch(_){ /* ignore */ }
  }

  function closestAction(target){ return target.closest('[data-action]'); }

  function copy(text){ return navigator.clipboard ? navigator.clipboard.writeText(text) : Promise.resolve(); }

  function post(url, data){
    return fetch(url, { method:'POST', credentials:'same-origin', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:new URLSearchParams(data).toString() });
  }

  onReady(function(){
    const list = document.querySelector('.qr-list');
    if(!list) return;

    // Render thumbs
    list.querySelectorAll('.qr-thumb[data-payload]').forEach(renderThumb);

    // Kebab open/close
    document.addEventListener('click', function(e){
      const btn = e.target.closest('[data-action="menu"]');
      if(btn){
        const wrap = btn.closest('.qr-actions__kebab');
        if(!wrap) return;
        const menu = wrap.querySelector('.kebab-menu');
        const open = menu.classList.contains('is-open');
        document.querySelectorAll('.kebab-menu.is-open').forEach(m=>m.classList.remove('is-open'));
        if(!open){ menu.classList.add('is-open'); btn.setAttribute('aria-expanded','true'); }
        e.preventDefault();
      } else if(!e.target.closest('.kebab-menu')){
        document.querySelectorAll('.kebab-menu.is-open').forEach(m=>m.classList.remove('is-open'));
      }
    });

    // Actions delegation
    document.addEventListener('click', async function(e){
      const a = closestAction(e.target);
      if(!a) return;
      const id = a.getAttribute('data-id');
      switch(a.getAttribute('data-action')){
        case 'copy':{
          e.preventDefault();
          const link = a.getAttribute('data-link');
          if(link) await copy(link);
          break; }
        case 'edit':{
          window.location.href = '/qr/new.php?id='+id;
          break; }
        case 'download':{
          // Try to download current canvas under this row if present
          const canvas = a.closest('tr')?.querySelector('.qr-thumb canvas');
          if(canvas){
            const url = canvas.toDataURL('image/png');
            const dl = document.createElement('a'); dl.href=url; dl.download='qr-'+id+'.png'; dl.click();
          }
          break; }
        case 'delete':{
          e.preventDefault();
          if(!confirm('Delete this QR?')) return;
          const res = await post('/qr/delete.php', { id });
          if(res.ok){ const tr = a.closest('tr'); tr && tr.parentNode && tr.parentNode.removeChild(tr); }
          break; }
      }
    });

    // Keyboard support
    document.addEventListener('keydown', function(e){ if(e.key==='Escape'){ document.querySelectorAll('.kebab-menu.is-open').forEach(m=>m.classList.remove('is-open')); }});
  });
})();
