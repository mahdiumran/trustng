(function () {
  var KEY = 'trustngSidebarCollapsed';
  var THEME_KEY = 'trustngTheme';

  // --- Theme Toggle (Light / Dark Mode) ---
  function applyTheme() {
    var currentTheme = localStorage.getItem(THEME_KEY) || 'dark';
    var icon = document.getElementById('theme-toggle-icon');
    if (currentTheme === 'light') {
      document.documentElement.classList.add('light-mode');
      document.body.classList.add('light-mode');
      if (icon) {
        icon.className = 'fa-solid fa-sun';
        icon.style.color = '#e0a030';
      }
    } else {
      document.documentElement.classList.remove('light-mode');
      document.body.classList.remove('light-mode');
      if (icon) {
        icon.className = 'fa-solid fa-moon';
        icon.style.color = '';
      }
    }
  }

  function toggleTheme() {
    var isLight = document.documentElement.classList.contains('light-mode');
    localStorage.setItem(THEME_KEY, isLight ? 'dark' : 'light');
    applyTheme();
  }

  // Apply theme immediately to avoid flash of dark mode
  applyTheme();

  // --- Sidebar Toggle ---
  function applySidebarState() {
    var isMobile = window.innerWidth <= 768;
    if (isMobile) {
      document.body.classList.add('sidebar-collapsed');
    } else {
      var stored = localStorage.getItem(KEY);
      if (stored === 'yes') {
        document.body.classList.add('sidebar-collapsed');
      } else if (stored === 'no') {
        document.body.classList.remove('sidebar-collapsed');
      } else {
        // Default on desktop: expanded
        document.body.classList.remove('sidebar-collapsed');
      }
    }
  }

  function toggleSidebar() {
    var collapsed = document.body.classList.toggle('sidebar-collapsed');
    localStorage.setItem(KEY, collapsed ? 'yes' : 'no');
  }

  applySidebarState();

  // --- Event Listeners ---
  document.addEventListener('DOMContentLoaded', function() {
    applyTheme(); // Re-apply to bind elements correctly once DOM is ready
    
    var themeBtn = document.getElementById('tng-theme-toggle');
    if (themeBtn) {
      themeBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        toggleTheme();
      });
    }
  });

  document.addEventListener('click', function (e) {
    /* Tangkap klik dari tombol toggle manapun */
    var btn = e.target.closest('.sidebar-toggle, #tng-menu-toggle, .tng-topbar-toggle');
    if (!btn) return;
    toggleSidebar();
  });

  /* Klik overlay mobile untuk tutup sidebar */
  document.addEventListener('click', function (e) {
    var ov = e.target.closest('#sidebar-overlay');
    if (!ov) return;
    document.body.classList.add('sidebar-collapsed');
    localStorage.setItem(KEY, 'yes');
  });

  /* Escape key */
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      document.body.classList.add('sidebar-collapsed');
      localStorage.setItem(KEY, 'yes');
    }
  });
})();
