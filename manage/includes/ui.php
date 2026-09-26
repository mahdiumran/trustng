<?php
require_once __DIR__ . '/../menu.php';

function tng_e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function tng_ui_head($title, $bodyClass = '')
{
    $safeTitle = tng_e($title);
    $safeBodyClass = tng_e($bodyClass);
    echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<meta name="color-scheme" content="light dark"><meta name="theme-color" content="#f9f9ff">';
    echo '<script>(function(){try{var m=localStorage.getItem("trustngTheme")||"system";var d=m==="dark"||(m==="system"&&matchMedia("(prefers-color-scheme:dark)").matches);document.documentElement.classList.toggle("dark-mode",d);document.documentElement.dataset.theme=m}catch(e){}})()</script>';
    echo '<link rel="icon" href="data:,">';
    echo '<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
    echo '<link href="https://fonts.googleapis.com/css2?family=Inter:wght@500;600;700&family=JetBrains+Mono:wght@400;500;600&family=Space+Grotesk:wght@400;500;600&display=swap" rel="stylesheet">';
    echo '<link rel="stylesheet" href="style.css"><title>' . $safeTitle . ' · TRUST-NG</title></head><body class="' . $safeBodyClass . '">';
}

function tng_ui_theme_menu()
{
    echo '<div class="theme-control">';
    echo '<button type="button" class="icon-button" id="themeMenuButton" aria-label="Pilih tema" aria-haspopup="menu" aria-expanded="false"><i class="fa-solid fa-circle-half-stroke" aria-hidden="true"></i></button>';
    echo '<div class="theme-menu" id="themeMenu" role="menu" hidden>';
    echo '<button type="button" role="menuitemradio" aria-checked="false" data-theme-value="light"><i class="fa-regular fa-sun" aria-hidden="true"></i><span>Terang</span><i class="fa-solid fa-check theme-check" aria-hidden="true"></i></button>';
    echo '<button type="button" role="menuitemradio" aria-checked="false" data-theme-value="dark"><i class="fa-regular fa-moon" aria-hidden="true"></i><span>Gelap</span><i class="fa-solid fa-check theme-check" aria-hidden="true"></i></button>';
    echo '<button type="button" role="menuitemradio" aria-checked="false" data-theme-value="system"><i class="fa-solid fa-desktop" aria-hidden="true"></i><span>Sistem</span><i class="fa-solid fa-check theme-check" aria-hidden="true"></i></button>';
    echo '</div></div>';
}

function tng_ui_page_start($active, $title, $description = '', $kicker = 'DNS CONTROL')
{
    tng_ui_head($title, 'with-sidebar');
    echo '<div class="sidebar-overlay" id="sidebarOverlay" data-sidebar-close></div><div class="app-shell">';
    trustng_render_sidebar($active);
    echo '<div class="app-main">';
    echo '<header class="app-topbar">';
    echo '<div class="topbar-left"><button type="button" class="icon-button mobile-menu-button" data-sidebar-toggle aria-label="Buka navigasi" aria-controls="appSidebar" aria-expanded="false"><i class="fa-solid fa-bars" aria-hidden="true"></i></button>';
    echo '<div class="topbar-title"><span>TRUST-NG</span><strong>' . tng_e($title) . '</strong></div></div>';
    echo '<div class="topbar-actions"><span class="health-chip"><span class="status-dot success"></span>Sesi aktif</span>';
    tng_ui_theme_menu();
    echo '<a href="setpwd.php" class="profile-chip" aria-label="Administrasi akun"><span>AD</span><strong>Admin</strong></a>';
    echo '</div></header>';
    echo '<main class="page-main"><div class="page-container">';
    echo '<header class="page-heading"><div><span class="kicker">' . tng_e($kicker) . '</span><h1>' . tng_e($title) . '</h1>';
    if ($description !== '') echo '<p>' . tng_e($description) . '</p>';
    echo '</div><div class="page-heading-actions" data-page-actions></div></header>';
}

function tng_ui_page_end($active = '')
{
    echo '</div></main><footer class="app-footer"><span>&copy; 2024 Kominfo</span><span>TRUST-NG DNS Services</span></footer></div></div>';
    trustng_render_bottom_nav($active);
    echo '<div class="toast-region" id="toastRegion" aria-live="polite" aria-atomic="true"></div>';
    echo '<script src="menu.js"></script></body></html>';
}

function tng_ui_card_start($title = '', $description = '', $class = '')
{
    echo '<section class="card ' . tng_e($class) . '">';
    if ($title !== '' || $description !== '') {
        echo '<header class="card-header"><div>';
        if ($title !== '') echo '<h2>' . tng_e($title) . '</h2>';
        if ($description !== '') echo '<p>' . tng_e($description) . '</p>';
        echo '</div></header>';
    }
    echo '<div class="card-body">';
}

function tng_ui_card_end()
{
    echo '</div></section>';
}

function tng_ui_notice($tone, $title, $message)
{
    $icons = array('success' => 'fa-circle-check', 'warning' => 'fa-triangle-exclamation', 'critical' => 'fa-circle-exclamation', 'info' => 'fa-circle-info');
    $icon = isset($icons[$tone]) ? $icons[$tone] : $icons['info'];
    echo '<div class="notice notice-' . tng_e($tone) . '" role="status"><i class="fa-solid ' . $icon . '" aria-hidden="true"></i><div><strong>' . tng_e($title) . '</strong><p>' . tng_e($message) . '</p></div></div>';
}

function tng_ui_system_state($title, $message, $tone = 'info', $countdown = null, $target = '/')
{
    tng_ui_head($title, 'system-state-page');
    echo '<main class="system-state"><section class="system-state-card">';
    echo '<span class="system-state-icon tone-' . tng_e($tone) . '"><i class="fa-solid fa-server" aria-hidden="true"></i></span>';
    echo '<span class="kicker">SYSTEM ACTION</span><h1>' . tng_e($title) . '</h1><p>' . tng_e($message) . '</p>';
    if ($countdown !== null) {
        echo '<div class="countdown" data-countdown="' . (int) $countdown . '" data-target="' . tng_e($target) . '"><strong id="countdownValue">' . (int) $countdown . '</strong><span>detik</span></div><div class="progress"><span id="countdownProgress"></span></div>';
    }
    echo '<a class="button button-secondary" href="' . tng_e($target) . '">Kembali ke dashboard</a>';
    echo '</section></main><script src="menu.js"></script></body></html>';
}
?>
