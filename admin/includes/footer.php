<?php
/**
 * Rodapé do painel admin — fecha layout (content, main, layout) e body/html.
 */
?>
      </main>
    </div>
  </div>
  <script>
  (function() {
    var sidebar = document.getElementById('admin-sidebar');
    var toggle = document.getElementById('admin-menu-toggle');
    var overlay = document.getElementById('admin-overlay');
    function openSidebar() {
      if (sidebar) sidebar.classList.add('is-open');
      if (toggle) { toggle.setAttribute('aria-label', 'Fechar menu'); toggle.setAttribute('aria-expanded', 'true'); }
      document.body.classList.add('admin-sidebar-open');
    }
    function closeSidebar() {
      if (sidebar) sidebar.classList.remove('is-open');
      if (toggle) { toggle.setAttribute('aria-label', 'Abrir menu'); toggle.setAttribute('aria-expanded', 'false'); }
      document.body.classList.remove('admin-sidebar-open');
    }
    if (toggle) toggle.addEventListener('click', function() {
      sidebar && sidebar.classList.contains('is-open') ? closeSidebar() : openSidebar();
    });
    if (overlay) overlay.addEventListener('click', closeSidebar);
  })();
  </script>
</body>
</html>
