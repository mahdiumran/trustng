(function () {
  var THEME_KEY = 'trustngTheme';
  var SIDEBAR_KEY = 'trustngSidebarCollapsed';
  var media = window.matchMedia('(prefers-color-scheme: dark)');
  var themeMenu = null;

  function getStored(key, fallback) {
    try { return localStorage.getItem(key) || fallback; } catch (e) { return fallback; }
  }
  function setStored(key, value) {
    try { localStorage.setItem(key, value); } catch (e) {}
  }

  /* ---------- Theme: light | dark | system ---------- */
  function resolvedIsDark() {
    var mode = getStored(THEME_KEY, 'system');
    if (mode === 'dark') return true;
    if (mode === 'light') return false;
    return media.matches;
  }

  function applyTheme() {
    var dark = resolvedIsDark();
    document.documentElement.classList.toggle('dark-mode', dark);
    document.documentElement.dataset.theme = getStored(THEME_KEY, 'system');
    var meta = document.querySelector('meta[name="theme-color"]');
    if (meta) meta.setAttribute('content', dark ? '#111713' : '#f9f9ff');
    syncThemeMenu();
  }

  function syncThemeMenu() {
    var mode = getStored(THEME_KEY, 'system');
    if (!themeMenu) themeMenu = document.getElementById('themeMenu');
    if (themeMenu) {
      themeMenu.querySelectorAll('[data-theme-value]').forEach(function (option) {
        var selected = option.getAttribute('data-theme-value') === mode;
        option.setAttribute('aria-checked', selected ? 'true' : 'false');
        option.classList.toggle('is-selected', selected);
      });
    }
    document.querySelectorAll('.admin-theme-option[data-theme-value]').forEach(function (option) {
      var selected = option.getAttribute('data-theme-value') === mode;
      option.setAttribute('aria-checked', selected ? 'true' : 'false');
    });
  }

  function setTheme(mode) {
    setStored(THEME_KEY, mode);
    applyTheme();
    closeThemeMenu();
    if (typeof window.tngOnThemeChange === 'function') window.tngOnThemeChange();
  }

  media.addEventListener('change', applyTheme);

  /* ---------- Theme menu ---------- */
  function openThemeMenu() {
    if (!themeMenu) return;
    themeMenu.hidden = false;
    document.getElementById('themeMenuButton').setAttribute('aria-expanded', 'true');
    var checked = themeMenu.querySelector('[aria-checked="true"]') || themeMenu.querySelector('button');
    if (checked) checked.focus();
  }
  function closeThemeMenu(restoreFocus) {
    if (!themeMenu || themeMenu.hidden) return;
    themeMenu.hidden = true;
    var button = document.getElementById('themeMenuButton');
    if (button) {
      button.setAttribute('aria-expanded', 'false');
      if (restoreFocus) button.focus();
    }
  }

  /* ---------- Sidebar ---------- */
  function sidebarIsMobile() { return window.innerWidth <= 820; }

  function syncSidebarButtons() {
    var sidebar = document.getElementById('appSidebar');
    if (!sidebar) return;
    var collapsed = sidebar.classList.contains('rail-collapsed');
    var open = sidebar.classList.contains('is-open');
    document.querySelectorAll('[data-sidebar-toggle]').forEach(function (button) {
      button.setAttribute('aria-expanded', sidebarIsMobile() ? (open ? 'true' : 'false') : (collapsed ? 'false' : 'true'));
    });
    var collapseButton = sidebar.querySelector('.sidebar-collapse');
    if (collapseButton) {
      collapseButton.setAttribute('aria-label', collapsed ? 'Bentangkan sidebar' : 'Ciutkan sidebar');
      collapseButton.setAttribute('title', collapsed ? 'Bentangkan sidebar' : 'Ciutkan sidebar');
      collapseButton.innerHTML = '<i class="fa-solid ' + (collapsed ? 'fa-angles-right' : 'fa-angles-left') + '" aria-hidden="true"></i>';
    }
  }

  function applySidebarState() {
    var sidebar = document.getElementById('appSidebar');
    if (!sidebar) return;
    if (sidebarIsMobile()) {
      sidebar.classList.remove('rail-collapsed');
    } else if (getStored(SIDEBAR_KEY, '') === 'yes') {
      sidebar.classList.add('rail-collapsed');
    } else {
      sidebar.classList.remove('rail-collapsed');
    }
    syncSidebarButtons();
  }

  function toggleSidebar() {
    var sidebar = document.getElementById('appSidebar');
    if (!sidebar) return;
    if (sidebarIsMobile()) {
      var open = sidebar.classList.toggle('is-open');
      var overlay = document.getElementById('sidebarOverlay');
      if (overlay) overlay.classList.toggle('is-visible', open);
      document.querySelectorAll('[data-sidebar-toggle]').forEach(function (btn) {
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
      });
      if (open) {
        var first = sidebar.querySelector('.nav-item');
        if (first) first.focus();
      }
      syncSidebarButtons();
    } else {
      var collapsed = sidebar.classList.toggle('rail-collapsed');
      setStored(SIDEBAR_KEY, collapsed ? 'yes' : 'no');
      syncSidebarButtons();
    }
  }

  function closeMobileSidebar() {
    var sidebar = document.getElementById('appSidebar');
    if (!sidebar || !sidebar.classList.contains('is-open')) return;
    sidebar.classList.remove('is-open');
    var overlay = document.getElementById('sidebarOverlay');
    if (overlay) overlay.classList.remove('is-visible');
    syncSidebarButtons();
  }

  function showConfirm(trigger) {
    var message = trigger.getAttribute('data-confirm') || 'Lanjutkan tindakan ini?';
    var danger = trigger.classList.contains('danger') || trigger.classList.contains('button-danger');
    var overlay = document.createElement('div');
    overlay.className = 'confirm-overlay';
    overlay.innerHTML = '<section class="confirm-dialog' + (danger ? ' is-danger' : '') + '" role="alertdialog" aria-modal="true" aria-labelledby="confirmTitle" aria-describedby="confirmMessage">' +
      '<div class="confirm-body"><span class="confirm-icon"><i class="fa-solid ' + (danger ? 'fa-triangle-exclamation' : 'fa-circle-question') + '" aria-hidden="true"></i></span>' +
      '<div class="confirm-copy"><h2 id="confirmTitle">Konfirmasi tindakan</h2><p id="confirmMessage"></p></div></div>' +
      '<div class="confirm-actions"><button type="button" class="button button-secondary" data-confirm-cancel>Batal</button><button type="button" class="button ' + (danger ? 'button-danger' : '') + '" data-confirm-accept>Lanjutkan</button></div></section>';
    overlay.querySelector('#confirmMessage').textContent = message;
    document.body.appendChild(overlay);
    var cancel = overlay.querySelector('[data-confirm-cancel]');
    var accept = overlay.querySelector('[data-confirm-accept]');
    function close() { overlay.remove(); trigger.focus(); }
    cancel.addEventListener('click', close);
    overlay.addEventListener('click', function (event) { if (event.target === overlay) close(); });
    accept.addEventListener('click', function () {
      trigger.setAttribute('data-confirmed', 'true');
      overlay.remove();
      trigger.focus();
      if (trigger.tagName === 'A') window.location.href = trigger.href;
      else if (trigger.form) trigger.form.requestSubmit(trigger);
    });
    overlay.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') { close(); return; }
      if (event.key !== 'Tab') return;
      var focusable = [cancel, accept];
      var current = focusable.indexOf(document.activeElement);
      var next = event.shiftKey ? (current <= 0 ? focusable.length - 1 : current - 1) : (current >= focusable.length - 1 ? 0 : current + 1);
      event.preventDefault();
      focusable[next].focus();
    });
    cancel.focus();
  }

  /* ---------- Toasts ---------- */
  function pushToast(kind, title, body) {
    var region = document.getElementById('toastRegion');
    if (!region) return;
    var icons = { success: 'fa-circle-check', error: 'fa-circle-exclamation', warning: 'fa-triangle-exclamation', info: 'fa-circle-info' };
    var toast = document.createElement('div');
    toast.className = 'toast';
    toast.setAttribute('role', 'status');
    toast.innerHTML = '<i class="fa-solid ' + (icons[kind] || icons.info) + '" aria-hidden="true"></i>' +
      '<div><strong></strong>' + (body ? '<p></p>' : '') + '</div>' +
      '<button type="button" aria-label="Tutup notifikasi"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>';
    toast.querySelector('strong').textContent = title;
    if (body) toast.querySelector('p').textContent = body;
    toast.querySelector('button').addEventListener('click', function () { toast.remove(); });
    region.appendChild(toast);
    window.setTimeout(function () { toast.remove(); }, kind === 'error' ? 12000 : 6000);
  }
  window.tngToast = pushToast;

  function movePageActions() {
    var container = document.querySelector('.page-container');
    var target = document.querySelector('.page-heading [data-page-actions]');
    if (!container || !target) return;
    var source = container.querySelector(':scope > [data-page-actions]');
    if (!source) return;
    while (source.firstChild) target.appendChild(source.firstChild);
    source.remove();
  }

  /* ---------- Countdown (system screens) ---------- */
  function startCountdown() {
    var box = document.querySelector('[data-countdown]');
    if (!box) return;
    var valueNode = document.getElementById('countdownValue');
    var progressNode = document.getElementById('countdownProgress');
    var seconds = parseInt(box.getAttribute('data-countdown'), 10) || 0;
    var target = box.getAttribute('data-target') || '/';
    var left = seconds;
    if (progressNode) progressNode.style.width = '100%';
    var timer = window.setInterval(function () {
      left -= 1;
      if (valueNode) valueNode.textContent = String(Math.max(left, 0));
      if (progressNode) progressNode.style.width = Math.max(left / seconds, 0) * 100 + '%';
      if (left <= 0) { window.clearInterval(timer); window.location.href = target; }
    }, 1000);
  }

  /* ---------- Global listeners ---------- */
  document.addEventListener('click', function (e) {
    var toggle = e.target.closest('[data-sidebar-toggle]');
    if (toggle) { e.preventDefault(); toggleSidebar(); return; }
    var close = e.target.closest('[data-sidebar-close]');
    if (close) { closeMobileSidebar(); return; }
    var confirmable = e.target.closest('[data-confirm]');
    if (confirmable && confirmable.getAttribute('data-confirmed') !== 'true') {
      e.preventDefault();
      showConfirm(confirmable);
      return;
    }
    var themeBtn = e.target.closest('#themeMenuButton');
    if (themeBtn) {
      if (!themeMenu) themeMenu = document.getElementById('themeMenu');
      if (themeMenu && themeMenu.hidden) openThemeMenu(); else closeThemeMenu();
      return;
    }
    var themeOption = e.target.closest('[data-theme-value]');
    if (themeOption) { setTheme(themeOption.getAttribute('data-theme-value')); return; }
    if (themeMenu && !themeMenu.hidden && !e.target.closest('#themeMenu')) closeThemeMenu();
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      closeThemeMenu(document.activeElement && themeMenu && themeMenu.contains(document.activeElement));
      closeMobileSidebar();
    }
  });

  window.addEventListener('resize', function () {
    if (!sidebarIsMobile()) closeMobileSidebar();
    applySidebarState();
  });

  applyTheme();
  applySidebarState();
  document.addEventListener('DOMContentLoaded', function () {
    themeMenu = document.getElementById('themeMenu');
    movePageActions();
    applyTheme();
    applySidebarState();
    startCountdown();
  });
})();
