<?php
function trustng_menu_items()
{
    $items = array(
        array('/', 'Dashboard', 'fa-table-columns', 'manage.php', 'Ringkasan'),
        array('setip.php', 'IP Address', 'fa-network-wired', 'setip.php', 'Jaringan'),
        array('setlp.php', 'Lamanlabuh', 'fa-anchor', 'setlp.php', 'Jaringan'),
        array('setclient.php', 'Clients', 'fa-users', 'setclient.php', 'Jaringan'),
        array('forwarder.php', 'Forwarder', 'fa-share-nodes', 'forwarder.php', 'Jaringan'),
        array('hosts.php', 'Hosts File', 'fa-file-lines', 'hosts.php', 'Jaringan'),
        array('options.php', 'Options', 'fa-sliders', 'options.php', 'Proteksi'),
        array('dbtrust.php', 'DB Trust+', 'fa-database', 'dbtrust.php', 'Proteksi'),
        array('setblack.php', 'Blacklist', 'fa-ban', 'setblack.php', 'Proteksi'),
        array('setwhite.php', 'Whitelist', 'fa-shield-halved', 'setwhite.php', 'Proteksi'),
        array('/munin/', 'Graph', 'fa-chart-line', 'munin', 'Monitor', '_blank'),
        array('stats.php', 'Statistics', 'fa-gauge-high', 'stats.php', 'Monitor'),
        array('activity.php', 'Activity', 'fa-clock-rotate-left', 'activity.php', 'Monitor'),
        array('digtest.php', 'DNS Inspector', 'fa-magnifying-glass-chart', 'digtest.php', 'Monitor'),
        array('reqlist.php', 'Request List', 'fa-list', 'reqlist.php', 'Monitor'),
        array('setpwd.php', 'Administration', 'fa-gears', 'setpwd.php', 'Sistem'),
        array('maintenance.php', 'Maintenance', 'fa-screwdriver-wrench', 'maintenance.php', 'Sistem'),
        array('resetstats.php', 'Reset Stats', 'fa-eraser', 'resetstats.php', 'Sistem')
    );

    $model = @file_get_contents('/etc/mymodel');
    if ($model !== false && trim($model) === 'BENGKEL x86 128G') {
        $items[] = array('temp.php', 'Temperature', 'fa-temperature-half', 'temp.php', 'Monitor');
    }

    return $items;
}

function trustng_menu_active_key($active)
{
    $aliases = array(
        'setdigtest.php' => 'digtest.php',
        'hasilcari.php' => 'dbtrust.php',
        'hasilcari2.php' => 'dbtrust.php',
        'reload.php' => 'maintenance.php',
        'reset.php' => 'maintenance.php',
        'reboot.php' => 'maintenance.php',
        'repairmunin.php' => 'maintenance.php',
        'restartunbound.php' => 'maintenance.php',
        'updateblacklist.php' => 'maintenance.php'
    );
    return isset($aliases[$active]) ? $aliases[$active] : $active;
}

function trustng_render_sidebar($active = '')
{
    $active = trustng_menu_active_key($active);
    $groups = array('Ringkasan', 'Jaringan', 'Proteksi', 'Monitor', 'Sistem');
    $items = trustng_menu_items();

    echo '<aside class="app-sidebar" id="appSidebar" aria-label="Navigasi utama">';
    echo '<div class="sidebar-brand-row">';
    echo '<a href="/" class="sidebar-brand" aria-label="TRUST-NG Dashboard">';
    echo '<span class="brand-mark"><img src="img/logo-img/trust-ng.jpg" alt=""></span>';
    echo '<span class="brand-copy"><strong>TRUST-NG</strong><small>DNS Control Plane</small></span>';
    echo '</a>';
    echo '<button type="button" class="icon-button sidebar-collapse" data-sidebar-toggle aria-label="Ciutkan sidebar" aria-controls="appSidebar"><i class="fa-solid fa-angles-left" aria-hidden="true"></i></button>';
    echo '</div>';
    echo '<nav class="sidebar-nav">';

    foreach ($groups as $group) {
        $groupItems = array_values(array_filter($items, function ($item) use ($group) { return $item[4] === $group; }));
        if (count($groupItems) === 0) continue;
        echo '<section class="nav-group" aria-labelledby="nav-' . strtolower($group) . '">';
        echo '<h2 class="nav-group-label" id="nav-' . strtolower($group) . '">' . htmlspecialchars($group, ENT_QUOTES, 'UTF-8') . '</h2>';
        foreach ($groupItems as $item) {
            $isActive = $active === $item[3];
            $target = isset($item[5]) ? ' target="' . htmlspecialchars($item[5], ENT_QUOTES, 'UTF-8') . '" rel="noopener noreferrer"' : '';
            echo '<a class="nav-item' . ($isActive ? ' is-active' : '') . '" href="' . htmlspecialchars($item[0], ENT_QUOTES, 'UTF-8') . '"' . $target . ($isActive ? ' aria-current="page"' : '') . ' title="' . htmlspecialchars($item[1], ENT_QUOTES, 'UTF-8') . '">';
            echo '<span class="nav-icon"><i class="fa-solid ' . htmlspecialchars($item[2], ENT_QUOTES, 'UTF-8') . '" aria-hidden="true"></i></span>';
            echo '<span class="nav-label">' . htmlspecialchars($item[1], ENT_QUOTES, 'UTF-8') . '</span>';
            if (isset($item[5])) echo '<i class="fa-solid fa-arrow-up-right-from-square nav-external" aria-hidden="true"></i>';
            echo '</a>';
        }
        echo '</section>';
    }

    echo '</nav>';
    echo '<div class="sidebar-footer">';
    echo '<div class="sidebar-session"><span class="session-avatar">AD</span><span class="session-copy"><strong>Administrator</strong><small>Sesi aman</small></span></div>';
    echo '<a href="logout.php" class="nav-item nav-logout"><span class="nav-icon"><i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i></span><span class="nav-label">Keluar</span></a>';
    echo '</div>';
    echo '</aside>';
}

function trustng_render_bottom_nav($active = '')
{
    $active = trustng_menu_active_key($active);
    $systemPages = array('setpwd.php', 'resetstats.php', 'maintenance.php');
    $bottomActive = in_array($active, $systemPages, true) ? 'maintenance.php' : $active;
    $items = array(
        array('/', 'Dashboard', 'fa-table-columns', 'manage.php'),
        array('stats.php', 'Stats', 'fa-chart-simple', 'stats.php'),
        array('digtest.php', 'Inspector', 'fa-magnifying-glass-chart', 'digtest.php'),
        array('maintenance.php', 'Sistem', 'fa-screwdriver-wrench', 'maintenance.php')
    );

    echo '<nav class="bottom-nav" aria-label="Navigasi cepat">';
    foreach ($items as $item) {
        echo '<a href="' . htmlspecialchars($item[0], ENT_QUOTES, 'UTF-8') . '" class="bottom-nav-item' . ($bottomActive === $item[3] ? ' is-active' : '') . '"' . ($bottomActive === $item[3] ? ' aria-current="page"' : '') . '>';
        echo '<i class="fa-solid ' . htmlspecialchars($item[2], ENT_QUOTES, 'UTF-8') . '" aria-hidden="true"></i><span>' . htmlspecialchars($item[1], ENT_QUOTES, 'UTF-8') . '</span></a>';
    }
    echo '<button type="button" class="bottom-nav-item" data-sidebar-toggle aria-label="Buka semua menu" aria-controls="appSidebar" aria-expanded="false"><i class="fa-solid fa-bars" aria-hidden="true"></i><span>Menu</span></button>';
    echo '</nav>';
}
?>
