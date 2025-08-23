
<footer class="site-footer" role="contentinfo"></footer>
</body>
<script>
// Remove global preloader when the whole page (all assets) has finished loading.
(function(){
  var done=false; function hide(){ if(done) return; done=true; var p=document.getElementById('preload'); if(!p) return; p.style.opacity='0'; setTimeout(function(){ if(p&&p.parentNode){ p.parentNode.removeChild(p); } },140); }
  // Strict: wait until all items on the page are loaded
  if (document.readyState === 'complete') hide();
  else window.addEventListener('load', hide, { once:true });
  // Last-resort safety in case load never fires due to third‑party/network issues
  setTimeout(hide, 5000);
})();
</script>
</html>