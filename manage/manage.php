<?php
error_reporting(0);

if (!isset($index) || $index !== 'yes') exit(0);

include_once 'menu.php';

// --- Data gathering dengan safe fallback ---
$myip        = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '';
$myv         = @file_get_contents('/etc/myversion');
$myv         = ($myv !== false) ? trim($myv) : 'unknown';
$ipaddr      = @shell_exec("ifconfig eth0 2>/dev/null | grep netmask | sed 's/ .*inet //;s/ .*//'");
$ipaddr      = ($ipaddr !== null) ? trim($ipaddr) : '';
$ip6         = @shell_exec("ifconfig eth0 2>/dev/null | grep inet6 | grep global | head -1 | sed 's/.*inet6 //;s/ .*//'");
$ip6         = ($ip6 !== null) ? trim($ip6) : '';
$mystatus = '';
$unbound_status_cmd = @shell_exec("systemctl is-active unbound 2>/dev/null");
if ($unbound_status_cmd !== null && trim($unbound_status_cmd) !== '') {
    $mystatus = trim($unbound_status_cmd);
} else {
    $unbound_pid = @shell_exec("pgrep unbound");
    $mystatus = ($unbound_pid !== null && trim($unbound_pid) !== '') ? 'active' : 'inactive';
}

$lp1 = @file_get_contents('lp1.ip');
$lp1 = ($lp1 !== false) ? trim($lp1) : '103.181.142.196';
$dig_res = @shell_exec("dig @127.0.0.1 nekopoi.care +short +timeout=1 +tries=1 2>/dev/null");
if ($dig_res !== null && trim($dig_res) !== '') {
    $dig_ip = trim($dig_res);
} else {
    $dig_ip = '';
}
if (!empty($dig_ip) && strpos($dig_ip, $lp1) !== false) {
    $truststatus = 'active';
} else {
    $truststatus = 'inactive';
}

$extip = @file_get_contents('/run/extip');
$extip = ($extip !== false) ? trim($extip) : '';
if (empty($extip)) {
    $extip = @shell_exec("curl -s -m 1.5 icanhazip.com 2>/dev/null");
    $extip = ($extip !== null) ? trim($extip) : '';
    if (empty($extip)) {
        $extip = $ipaddr;
    }
}

$uptime      = @shell_exec("uptime 2>/dev/null");
$uptime      = ($uptime !== null) ? trim($uptime) : '';
$model       = @file_get_contents('/etc/mymodel');
$model       = ($model !== false) ? trim($model) : '';

$statusText = function($value) {
    $value = trim((string)$value);
    return $value === '' ? 'Tidak tersedia' : htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
};

$resourceItems = array();

// --- Real-time System Resource Gathering (Linux native / proc fallback) ---
$meminfo = @file("/proc/meminfo");
if ($meminfo !== false) {
    $ramtotal = 0;
    $ramfree = 0;
    $ramcached = 0;
    $rambuffers = 0;
    foreach ($meminfo as $line) {
        if (preg_match('/^MemTotal:\s+(\d+)/', $line, $m)) $ramtotal = intval($m[1]);
        if (preg_match('/^MemFree:\s+(\d+)/', $line, $m)) $ramfree = intval($m[1]);
        if (preg_match('/^Cached:\s+(\d+)/', $line, $m)) $ramcached = intval($m[1]);
        if (preg_match('/^Buffers:\s+(\d+)/', $line, $m)) $rambuffers = intval($m[1]);
    }
    if ($ramtotal > 0) {
        $ramused = $ramtotal - $ramfree - $ramcached - $rambuffers;
        $geram = ($ramused / $ramtotal) * 100;
    } else {
        $geram = 0;
    }
} else {
    $geram = 0;
}

$stat1 = @file_get_contents('/proc/stat');
if ($stat1 !== false) {
    usleep(20000); // 20ms delta
    $stat2 = @file_get_contents('/proc/stat');
    $cpu1_parts = preg_split('/\s+/', trim(explode("\n", $stat1)[0]));
    $cpu2_parts = preg_split('/\s+/', trim(explode("\n", $stat2)[0]));
    if (count($cpu1_parts) > 5 && count($cpu2_parts) > 5) {
        $total1 = array_sum(array_slice($cpu1_parts, 1));
        $idle1 = floatval($cpu1_parts[4]);
        $iowait1 = floatval($cpu1_parts[5]);
        $total2 = array_sum(array_slice($cpu2_parts, 1));
        $idle2 = floatval($cpu2_parts[4]);
        $iowait2 = floatval($cpu2_parts[5]);
        $total_delta = $total2 - $total1;
        $idle_delta = $idle2 - $idle1;
        $iowait_delta = $iowait2 - $iowait1;
        if ($total_delta > 0) {
            $gcpu = (1 - ($idle_delta / $total_delta)) * 100;
            $iowait = ($iowait_delta / $total_delta) * 100;
        } else {
            $gcpu = 0;
            $iowait = 0;
        }
    } else {
        $gcpu = 0;
        $iowait = 0;
    }
} else {
    $gcpu = 0;
    $iowait = 0;
}

$loadavg = @file_get_contents('/proc/loadavg');
if ($loadavg !== false) {
    $parts = explode(' ', $loadavg);
    $load1 = floatval($parts[0]);
    $nproc = intval(@shell_exec('nproc 2>/dev/null'));
    if ($nproc <= 0) $nproc = 1;
    $gload = ($load1 * 100) / $nproc;
} else {
    $gload = 0;
}

$disk_total = @disk_total_space('/');
$disk_free = @disk_free_space('/');
if ($disk_total > 0) {
    $gdisk = (($disk_total - $disk_free) / $disk_total) * 100;
} else {
    $gdisk = 0;
}

if ($geram > 0 || $gcpu > 0) {
    $resourceItems[] = array('RAM', min(100, max(0, floatval($geram))));
    $resourceItems[] = array('CPU', min(100, max(0, floatval($gcpu))));
    $resourceItems[] = array('Load', min(100, max(0, floatval($gload))));
    $resourceItems[] = array('Disk', min(100, max(0, floatval($gdisk))));
    $resourceItems[] = array('Iowait', min(100, max(0, floatval($iowait))));
} else {
    // Fallback to gauge.dat
    $gaugeData = @file_get_contents('gauge.dat');
    if ($gaugeData !== false && preg_match_all("/\\['([^']+)'\\s*,\\s*([0-9.]+)\\]/", $gaugeData, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            if ($match[1] === 'Label') continue;
            $resourceItems[] = array($match[1], min(100, max(0, floatval($match[2]))));
        }
    }
}

$resourceIcon = function($label) {
    $icons = array(
        'RAM'    => 'fa-memory',
        'CPU'    => 'fa-microchip',
        'Load'   => 'fa-wave-square',
        'Disk'   => 'fa-hard-drive',
        'Iowait' => 'fa-hourglass-half'
    );
    return isset($icons[$label]) ? $icons[$label] : 'fa-server';
};

$resourceState = function($value) {
    if ($value >= 75) return 'critical';
    if ($value >= 50) return 'warning';
    return 'normal';
};

$resourceTitle = function($label) {
    $titles = array(
        'RAM'    => 'Memori Tersedia',
        'CPU'    => 'CPU Load',
        'Load'   => 'System Load',
        'Disk'   => 'Penggunaan Disk',
        'Iowait' => 'IO Wait'
    );
    return isset($titles[$label]) ? $titles[$label] : $label;
};

$resourceUnit = function($label) {
    return ($label === 'Load') ? '' : '%';
};

$statusBadge = function($val) {
    $v = strtolower(trim($val));
    if ($v === '' || $v === 'tidak tersedia') return 'badge-unknown';
    if (strpos($v, 'ok') !== false || strpos($v, 'up') !== false || strpos($v, 'active') !== false || strpos($v, 'running') !== false) return 'badge-ok';
    if (strpos($v, 'error') !== false || strpos($v, 'fail') !== false || strpos($v, 'down') !== false) return 'badge-err';
    return 'badge-warn';
};

// --- DNS Statistics ---
// Cumulative totals from Unbound stats (dn stats_noreset)
$unbound_stats = @shell_exec("dn stats_noreset 2>/dev/null");
if (!$unbound_stats) {
    $unbound_stats = @shell_exec("unbound-control stats_noreset 2>/dev/null");
}
$totalQueries = 0;
$blockedQueries = 0;
$cacheHits = 0;
if ($unbound_stats !== null) {
    // Use total.* aggregate lines
    if (preg_match('/^total\.num\.queries\s*=\s*(\d+)/m', $unbound_stats, $m)) {
        $totalQueries = intval($m[1]);
    }
    if (preg_match('/^total\.num\.blacklist\s*=\s*(\d+)/m', $unbound_stats, $m2)) {
        $blockedQueries = intval($m2[1]);
    }
    if (preg_match('/^total\.num\.cachehits\s*=\s*(\d+)/m', $unbound_stats, $m3)) {
        $cacheHits = intval($m3[1]);
    }
    // Fallback: sum thread values
    if ($totalQueries === 0) {
        if (preg_match_all('/thread\d+\.num\.queries\s*=\s*(\d+)/i', $unbound_stats, $matches)) {
            foreach ($matches[1] as $v) $totalQueries += intval($v);
        }
    }
    if ($blockedQueries === 0) {
        if (preg_match_all('/thread\d+\.num\.blacklist\s*=\s*(\d+)/i', $unbound_stats, $matches)) {
            foreach ($matches[1] as $v) $blockedQueries += intval($v);
        }
    }
}

// Trust+ blocklist entries count
$trustCount = @file_get_contents('/etc/unbound/db/trust.count');
$trustCount = ($trustCount !== false && trim($trustCount) !== '') ? trim($trustCount) : '0';

// Format numbers for display
$totalQueriesFmt = number_format($totalQueries);
$blockedQueriesFmt = number_format($blockedQueries);
$trustCountFmt = number_format(intval($trustCount));
$blockRate = ($totalQueries > 0) ? round(($blockedQueries / $totalQueries) * 100, 1) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1"/>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" type="text/css" href="style.css"/>
<title>DNS Dashboard</title>
<script src="/jquery.min.js"></script>
<script type="text/javascript" src="loader.js"></script>
</head>
<body>
<main class="manage-shell hosting-dashboard" id="mainShell">

<!-- Overlay mobile -->
<div id="sidebar-overlay"></div>

<!-- Sidebar dari menu.php, dibungkus wrapper -->
<div class="sidebar-wrapper" id="sidebarWrapper">
<?php trustng_render_sidebar('manage.php'); ?>
</div>

<!-- KONTEN UTAMA -->
<section class="dashboard-main">

  <!-- TOPBAR â€” menggantikan hero panel -->
  <div class="tng-topbar">

    <!-- Tombol toggle SATU-SATUNYA â€” tidak ada lagi di menu.php -->
    <button type="button" id="tng-menu-toggle" title="Toggle Sidebar" aria-label="Toggle menu" aria-controls="dashboardSidebar" aria-expanded="true">
      <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
        <rect x="2" y="4.5" width="16" height="2" rx="1" fill="currentColor"/>
        <rect x="2" y="9"   width="16" height="2" rx="1" fill="currentColor"/>
        <rect x="2" y="13.5" width="16" height="2" rx="1" fill="currentColor"/>
      </svg>
    </button>

    <a href="/" class="tng-topbar-brand">
      <img src="img/logo-img/trust-ng.jpg" alt="TRUST-NG">
      <div>
        <div class="brand-name">TRUST-NG</div>
        <div class="brand-ver">v<?php echo htmlspecialchars($myv, ENT_QUOTES, 'UTF-8'); ?> &nbsp;Â·&nbsp; DNS Control Panel</div>
      </div>
    </a>

    <div class="tng-topbar-spacer"></div>

    <div class="tng-topbar-pills">
      <?php if ($ipaddr): ?>
      <span class="tng-topbar-pill">IPv4 <?php echo $statusText($ipaddr); ?></span>
      <?php endif; ?>
      <?php if ($ip6): ?>
      <span class="tng-topbar-pill">IPv6 <?php echo $statusText($ip6); ?></span>
      <?php endif; ?>
      <?php if ($model): ?>
      <span class="tng-topbar-pill"><?php echo $statusText($model); ?></span>
      <?php endif; ?>
      <span class="live-pill">LIVE</span>
    </div>
  </div><!-- end .tng-topbar -->

  <div class="tng-content">

    <!-- ======================================================
         HERO STATS ROW
         ====================================================== -->
    <div class="tng-hero-row">
      <div class="tng-hero-card">
        <div class="tng-hero-icon"><i class="fa-solid fa-globe"></i></div>
        <div class="tng-hero-info">
          <span class="tng-hero-label">Total Queries</span>
          <span class="tng-hero-value" id="heroTotal"><?php echo htmlspecialchars($totalQueriesFmt, ENT_QUOTES, 'UTF-8'); ?></span>
          <span class="tng-hero-unit">total handled</span>
        </div>
      </div>
      <div class="tng-hero-card hero-blocked">
        <div class="tng-hero-icon"><i class="fa-solid fa-shield-halved"></i></div>
        <div class="tng-hero-info">
          <span class="tng-hero-label">Blocked Queries</span>
          <span class="tng-hero-value" id="heroBlocked"><?php echo htmlspecialchars($blockedQueriesFmt, ENT_QUOTES, 'UTF-8'); ?></span>
          <span class="tng-hero-unit">blocked by Trust+</span>
        </div>
      </div>
      <div class="tng-hero-card hero-rate">
        <div class="tng-hero-icon"><i class="fa-solid fa-percent"></i></div>
        <div class="tng-hero-info">
          <span class="tng-hero-label">Block Rate</span>
          <span class="tng-hero-value" id="heroRate">—</span>
          <span class="tng-hero-unit">% of total</span>
        </div>
      </div>
      <div class="tng-hero-card">
        <div class="tng-hero-icon"><i class="fa-solid fa-database"></i></div>
        <div class="tng-hero-info">
          <span class="tng-hero-label">Blocklist Entries</span>
          <span class="tng-hero-value"><?php echo htmlspecialchars($trustCountFmt, ENT_QUOTES, 'UTF-8'); ?></span>
          <span class="tng-hero-unit">domains</span>
        </div>
      </div>
    </div>

    <!-- ======================================================
         STATUS SERVICES
         ====================================================== -->
    <div>
      <p class="tng-section-label">Status Layanan</p>
      <div class="tng-status-strip">
        <div class="tng-status-card">
          <div class="tng-status-icon">UN</div>
          <div class="tng-status-info">
            <span class="tng-status-name">Unbound</span>
            <span class="tng-status-val <?php echo $statusBadge($mystatus); ?>"><?php echo $statusText($mystatus); ?></span>
          </div>
        </div>
        <div class="tng-status-card">
          <div class="tng-status-icon">TR</div>
          <div class="tng-status-info">
            <span class="tng-status-name">Trust+</span>
            <span class="tng-status-val <?php echo $statusBadge($truststatus); ?>"><?php echo $statusText($truststatus); ?></span>
          </div>
        </div>
        <div class="tng-status-card">
          <div class="tng-status-icon">IP</div>
          <div class="tng-status-info">
            <span class="tng-status-name">External IP</span>
            <span class="tng-status-val"><?php echo $statusText($extip); ?></span>
          </div>
        </div>
        <div class="tng-status-card">
          <div class="tng-status-icon tng-status-icon-sm">UP</div>
          <div class="tng-status-info">
            <span class="tng-status-name">Uptime</span>
            <span class="tng-status-val tng-status-val-sm"><?php echo $statusText($uptime); ?></span>
          </div>
        </div>
      </div>
    </div>

    <!-- ======================================================
         RESOURCE SISTEM
         ====================================================== -->
    <?php if (!empty($resourceItems)): ?>
    <div class="tng-resource-strip">
      <?php foreach ($resourceItems as $resource):
          $label        = htmlspecialchars($resource[0], ENT_QUOTES, 'UTF-8');
          $title        = htmlspecialchars($resourceTitle($resource[0]), ENT_QUOTES, 'UTF-8');
          $value        = $resource[1];
          $state        = $resourceState($value);
          $unit         = htmlspecialchars($resourceUnit($resource[0]), ENT_QUOTES, 'UTF-8');
          $icon         = $resourceIcon($resource[0]);
          $displayValue = rtrim(rtrim(number_format($value, 1), '0'), '.');
      ?>
      <div class="tng-res-tile <?php echo $state; ?>"
           data-resource="<?php echo $label; ?>"
           data-value="<?php echo $value; ?>"
           data-unit="<?php echo $unit; ?>">
        <div class="tng-res-tile-head">
          <i class="fa-solid <?php echo $icon; ?>" aria-hidden="true"></i>
          <span class="tng-res-tile-name"><?php echo $title; ?></span>
        </div>
        <div class="tng-res-tile-value">
          <?php echo $displayValue; ?><span><?php echo $unit; ?></span>
        </div>
        <div class="tng-res-tile-bar">
          <div class="tng-res-tile-bar-fill" style="width:<?php echo $value; ?>%"></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- ======================================================
         CHARTS ROW — Queries + Blocklist side by side
         ====================================================== -->
    <div class="tng-chart-row">
      <div class="tng-query-card">
        <div class="tng-query-head">
          <div class="tng-query-meta">
            <span class="tng-query-label">Total Queries</span>
            <span class="tng-query-count" id="queryValue">0</span>
            <span class="tng-query-mode" id="queryMode">menunggu data…</span>
          </div>
          <span id="queryPulse" class="query-pulse"></span>
        </div>
        <canvas id="queryChart" width="720" height="200"></canvas>
        <div id="statsRaw" class="stats-raw">
          <?php include 's.php'; ?>
        </div>
      </div>

      <div class="tng-query-card">
        <div class="tng-query-head">
          <div class="tng-query-meta">
            <span class="tng-query-label">Blocked Queries</span>
            <span class="tng-query-count" id="blockedValue">0</span>
            <span class="tng-query-mode" id="blockedMode">menunggu data…</span>
          </div>
          <span id="blockedPulse" class="query-pulse query-pulse-rose"></span>
        </div>
        <canvas id="blockedChart" width="720" height="200"></canvas>
      </div>
    </div>

    <!-- ======================================================
         ANALYTICS ROW — Cache donut + Forward bars
         ====================================================== -->
    <div class="tng-chart-row">
      <div class="tng-query-card">
        <div class="tng-query-head">
          <div class="tng-query-meta">
            <span class="tng-query-label">Cache Performance</span>
          </div>
        </div>
        <div class="tng-donut-wrap">
          <canvas id="donutChart" width="200" height="200"></canvas>
          <div id="donutLegend" class="tng-donut-legend"></div>
        </div>
      </div>

      <div class="tng-query-card">
        <div class="tng-query-head">
          <div class="tng-query-meta">
            <span class="tng-query-label">Forward Destinations</span>
          </div>
        </div>
        <div id="forwardBars" class="tng-forward-bars"></div>
      </div>
    </div>

  </div><!-- end .tng-content -->

  <!-- Time bar -->
  <div id="ifstat" class="status-time" title="Server Date and Time Now">
    <?php include 'ifstat.php'; ?>
  </div>

  <p class="manage-footer"><small><b>&copy; 2024 Kominfo</b></small></p>

</section><!-- end .dashboard-main -->
</main>

<script src="ifstat.js"></script>
<script src="dashboard.js"></script>
<!-- Toggling handled globally by menu.js -->
</body>
</html>
