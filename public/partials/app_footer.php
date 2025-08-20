<?php // public/partials/app_footer.php ?>
      </div> <!-- /.app-shell -->
      <script>
        // ===== Theme toggle (cookie + data-attr + meta theme-color) =====
        (function(){
          function setCookie(n,v,d){var t=new Date();t.setTime(t.getTime()+d*24*60*60*1e3);document.cookie=n+'='+v+'; expires='+t.toUTCString()+'; path=/';}
          function getCookie(n){var m=document.cookie.match('(?:^|; )'+n.replace(/([.$?*|{}()\\[\\]\\\\\\/\\+^])/g,'\\$1')+'=([^;]*)');return m?decodeURIComponent(m[1]):null;}
          function applyTheme(t){
            document.documentElement.setAttribute('data-theme',t);
            if(document.body) document.body.setAttribute('data-theme',t);
            setCookie('whoizme_theme',t,365);
            var meta=document.querySelector('meta[name="theme-color"]');
            if(meta) meta.setAttribute('content', t==='dark'?'#111827':'#ffffff');
            // toggle visual state
            document.querySelectorAll('.theme-switch').forEach(function(b){
              b.classList.toggle('is-dark', t==='dark');
              b.setAttribute('aria-pressed', String(t==='dark'));
            });
          }
          function currentTheme(){
            return getCookie('whoizme_theme')
              || document.documentElement.getAttribute('data-theme')
              || (window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches ? 'light':'dark');
          }
          applyTheme(currentTheme());
          document.addEventListener('click', function(e){
            var t=e.target.closest('#themeToggle, #themeToggle2, .theme-switch'); if(!t) return;
            e.preventDefault(); applyTheme(currentTheme()==='dark'?'light':'dark');
          }, true);
        })();

        // ===== Account dropdown (hover/click + outside close) =====
        (function(){
          var root=document.getElementById('account'); if(!root) return;
          var btn=document.getElementById('accountBtn'); var menu=document.getElementById('accountMenu');
          function open(){root.classList.add('is-open'); btn.setAttribute('aria-expanded','true');}
          function close(){root.classList.remove('is-open'); btn.setAttribute('aria-expanded','false');}
          btn.addEventListener('click', function(e){e.stopPropagation(); root.classList.toggle('is-open'); btn.setAttribute('aria-expanded', String(root.classList.contains('is-open')));});
          root.addEventListener('mouseenter', open); root.addEventListener('mouseleave', close);
          document.addEventListener('click', function(e){ if(!root.contains(e.target)) close(); });
        })();

        // ===== Quick Create modal (openers + keyboard shortcuts) =====
        (function(){
          var modal=document.getElementById('quickCreate'); if(!modal) return;
          var openers=[].slice.call(document.querySelectorAll('#qcOpen, #qcOpen2'));
          var overlay=modal.querySelector('.modal__overlay'); var closeBtn=document.getElementById('qcClose');
          var dialog=modal.querySelector('.modal__dialog'); var focusable='a[href],button:not([disabled]),[tabindex="0"]'; var lastFocus;
          function open(){ lastFocus=document.activeElement; modal.setAttribute('aria-hidden','false'); document.body.classList.add('is-modal-open'); setTimeout(function(){ (dialog.querySelector(focusable)||closeBtn||dialog).focus(); }, 10); }
          function close(){ modal.setAttribute('aria-hidden','true'); document.body.classList.remove('is-modal-open'); if(lastFocus) lastFocus.focus(); }
          openers.forEach(function(b){ b.addEventListener('click', function(e){ e.preventDefault(); open(); }); });
          if(overlay) overlay.addEventListener('click', close);
          if(closeBtn) closeBtn.addEventListener('click', close);
          // Esc + shortcuts L/Q/P (خارج الحقول فقط)
          document.addEventListener('keydown', function(e){
            if(e.key==='Escape' && modal.getAttribute('aria-hidden')==='false'){ e.preventDefault(); close(); }
            if(e.target && (e.target.tagName==='INPUT' || e.target.tagName==='TEXTAREA')) return;
            var href=null;
            if(e.key==='l' || e.key==='L') href='/links.php?action=new';
            if(e.key==='q' || e.key==='Q') href='/qr.php?action=new';
            if(e.key==='p' || e.key==='P') href='/templates.php?action=new';
            if(href){ e.preventDefault(); window.location.href=href; }
          });
          // trap focus داخل المودال
          modal.addEventListener('keydown', function(e){
            if(e.key!=='Tab') return;
            var nodes=modal.querySelectorAll(focusable); if(!nodes.length) return;
            var first=nodes[0], last=nodes[nodes.length-1];
            if(e.shiftKey && document.activeElement===first){ e.preventDefault(); last.focus(); }
            else if(!e.shiftKey && document.activeElement===last){ e.preventDefault(); first.focus(); }
          });
        })();
      </script>
  </body>
</html>