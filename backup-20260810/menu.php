<?php
function trustng_menu_items()
{
    $items = array(
        array('setip.php',       'IP Address',  'fa-network-wired',         'setip.php'),
        array('setlp.php',       'Lamanlabuh',  'fa-anchor',                'setlp.php'),
        array('setclient.php',   'Clients',     'fa-users',                 'setclient.php'),
        array('options.php',     'Options',     'fa-sliders',               'options.php'),
        array('dbtrust.php',     'DB Trust+',   'fa-database',              'dbtrust.php'),
        array('forwarder.php',   'Forwarder',   'fa-share-nodes',           'forwarder.php'),
        array('hosts.php',       'HostsFile',   'fa-file-lines',            'hosts.php'),
        array('/munin/',         'Graph',       'fa-chart-line',            'munin',      '_blank'),
        array('stats.php',       'Stats',       'fa-gauge-high',            'stats.php'),
        array('reqlist.php',     'Reqlist',     'fa-list-check',            'reqlist.php'),
        array('digtest.php',     'Digtest',     'fa-magnifying-glass-chart','digtest.php'),
        array('setpwd.php',      'Change Password', 'fa-key',                   'setpwd.php'),
        array('setlogo.php',     'Logo',         'fa-image',                 'setlogo.php'),
        array('maintenance.php', 'Maintenance', 'fa-screwdriver-wrench',    'maintenance.php'),
    );

    // Tambah menu Temp khusus model tertentu
    $model = @file_get_contents('/etc/mymodel');
    if ($model !== false && trim($model) === 'BENGKEL x86 128G') {
        $items[] = array('temp.php', 'Temp', 'fa-temperature-half', 'temp.php');
    }

    return $items;
}

/**
 * Render sidebar navigation.
 *
 * CATATAN: Tombol toggle TIDAK dirender di sini.
 * Tombol toggle (#tng-menu-toggle) ada di topbar manage.php agar tidak duplikat.
 * Sidebar ini murni berisi <aside> + nav menu saja.
 *
 * @param string $active  Nama file halaman aktif untuk highlight menu
 */
function trustng_render_sidebar($active = '')
{
    echo '<aside class="dashboard-sidebar" id="dashboardSidebar" role="navigation" aria-label="Main menu">';

    // Header sidebar — logo + label + theme toggle
    echo '<div class="sidebar-header">
        <a href="/" class="sidebar-brand" title="Ke halaman utama">
            <img src="img/logo-img/trust-ng.jpg" class="sidebar-logo" alt="TRUST-NG">
            <div class="sidebar-brand-text">
                <span class="sidebar-brand-name">TRUST-NG</span>
                <span class="sidebar-brand-sub">DNS Services</span>
            </div>
        </a>
        <button id="tng-theme-toggle" title="Ubah Tema (Terang/Gelap)" aria-label="Toggle theme">
            <i class="fa-solid fa-moon" id="theme-toggle-icon"></i>
        </button>
    </div>';

    // Nav items
    echo '<nav class="sidebar-nav" aria-label="DNS services">';

    $groups = array(
        'Jaringan'    => array('setip.php', 'setlp.php', 'setclient.php', 'forwarder.php', 'hosts.php'),
        'Konfigurasi' => array('options.php', 'dbtrust.php'),
        'Monitor'     => array('munin', 'stats.php', 'reqlist.php', 'digtest.php', 'temp.php'),
        'Sistem'      => array('setpwd.php', 'setlogo.php', 'maintenance.php'),
    );

    // Build lookup: key => item data
    $lookup = array();
    foreach (trustng_menu_items() as $item) {
        $lookup[$item[3]] = $item;
    }

    foreach ($groups as $groupLabel => $keys) {
        $hasItems = false;
        foreach ($keys as $k) {
            if (isset($lookup[$k])) { $hasItems = true; break; }
        }
        if (!$hasItems) continue;

        echo '<div class="sidebar-group">';
        echo '<p class="sidebar-group-label">' . htmlspecialchars($groupLabel, ENT_QUOTES, 'UTF-8') . '</p>';

        foreach ($keys as $k) {
            if (!isset($lookup[$k])) continue;
            $item    = $lookup[$k];
            $target  = isset($item[4]) ? ' target="' . htmlspecialchars($item[4], ENT_QUOTES, 'UTF-8') . '" rel="noopener noreferrer"' : '';
            $current = ($active === $item[3]) ? ' active' : '';
            $href    = htmlspecialchars($item[0], ENT_QUOTES, 'UTF-8');
            $icon    = htmlspecialchars($item[2], ENT_QUOTES, 'UTF-8');
            $title   = htmlspecialchars($item[1], ENT_QUOTES, 'UTF-8');

            echo '<a class="sidebar-item' . $current . '" href="' . $href . '"' . $target . '>
                <span class="sidebar-item-icon"><i class="fa-solid ' . $icon . '" aria-hidden="true"></i></span>
                <span class="sidebar-item-label">' . $title . '</span>
            </a>';
        }

        echo '</div>'; // end .sidebar-group
    }

    echo '</nav>';
    echo '</aside>';

    // CSS sidebar — all styles in style.css, no inline overrides needed

    // menu.js tetap dimuat untuk kompatibilitas fungsi lain yang mungkin ada di sana
    echo '<script src="menu.js"></script>';
}
?>
