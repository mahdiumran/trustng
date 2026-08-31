(function () {
  var KEY = 'trustngSidebarCollapsed';
  var THEME_KEY = 'trustngTheme';
  var CHROME_THEME_KEY = 'trustngChromeTheme';

  function getStoredValue(key, fallback) {
    try {
      return localStorage.getItem(key) || fallback;
    } catch (e) {
      return fallback;
    }
  }

  function storeValue(key, value) {
    try {
      localStorage.setItem(key, value);
    } catch (e) {}
  }

  function applyChromeTheme() {
    var theme = getStoredValue(CHROME_THEME_KEY, 'default');
    if (theme !== 'darker') theme = 'default';
    document.documentElement.classList.toggle('chrome-darker', theme === 'darker');
    document.querySelectorAll('input[name="chrome-theme"]').forEach(function (option) {
      option.checked = option.value === theme;
    });
  }

  // --- Theme Toggle (Light / Dark Mode) ---
  function applyTheme() {
    var currentTheme = getStoredValue(THEME_KEY, 'dark');
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
    storeValue(THEME_KEY, isLight ? 'dark' : 'light');
    applyTheme();
  }

  // Apply themes immediately to reduce visual flashing.
  applyTheme();
  applyChromeTheme();

  // --- Sidebar Toggle ---
  function applySidebarState() {
    var isMobile = window.innerWidth <= 768;
    if (isMobile) {
      document.body.classList.add('sidebar-collapsed');
    } else {
      var stored = getStoredValue(KEY, '');
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
    var toggle = document.getElementById('tng-menu-toggle');
    if (toggle) toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
    storeValue(KEY, collapsed ? 'yes' : 'no');
  }

  applySidebarState();

  // --- Event Listeners ---
  document.addEventListener('DOMContentLoaded', function() {
    applyTheme(); // Re-apply to bind elements correctly once DOM is ready
    applyChromeTheme();

    document.querySelectorAll('input[name="chrome-theme"]').forEach(function (option) {
      option.addEventListener('change', function () {
        if (!option.checked) return;
        storeValue(CHROME_THEME_KEY, option.value);
        applyChromeTheme();
      });
    });

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
    storeValue(KEY, 'yes');
  });

  /* Escape key */
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      document.body.classList.add('sidebar-collapsed');
      storeValue(KEY, 'yes');
    }
  });
})();
