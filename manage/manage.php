<?php
error_reporting(0);

if (!isset($index) || $index !== 'yes') exit(0);

require_once __DIR__ . '/includes/ui.php';

// --- Data gathering dengan safe fallback ---
$myip        = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '';
$myv         = trim((string) @shell_exec('/usr/local/sbin/unbound -V 2>/dev/null | head -1'));
$myv         = preg_match('/Version\\s+([^\\s]+)/i', $myv, $version) ? $version[1] : 'tidak tersedia';
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
        'RAM'    => 'Penggunaan RAM',
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

// --- DNS Statistics via resilient helper ---
require_once __DIR__ . '/includes/unbound.php';
$unbound_stats = tng_unbound_stats_raw();
$pairs = tng_unbound_stats_pairs($unbound_stats);
$totalQueries = intval($pairs['total.num.queries'] ?? 0);
$blockedQueries = intval($pairs['total.num.blacklist'] ?? 0);
$cacheHits = intval($pairs['total.num.cachehits'] ?? 0);
$cacheMisses = intval($pairs['total.num.cachemiss'] ?? 0);
$prefetchCount = intval($pairs['total.num.prefetch'] ?? 0);
$requestCurrent = intval($pairs['total.requestlist.current.all'] ?? 0);
$resolverUptime = intval(floatval($pairs['time.up'] ?? 0));
$recursionAverage = floatval($pairs['total.recursion.time.avg'] ?? 0) * 1000;
$cacheMemory = intval($pairs['mem.cache.rrset'] ?? 0) + intval($pairs['mem.cache.message'] ?? 0);
$statsErr = ($unbound_stats === '' || preg_match('/^error:|^could not/i', trim($unbound_stats))) ? ($unbound_stats ?: 'unbound-control tidak merespon') : '';

// Trust+ blocklist entries count
$trustCount = @file_get_contents('/etc/unbound/db/trust.count');
$trustCount = ($trustCount !== false && trim($trustCount) !== '') ? trim($trustCount) : '0';

// Format numbers for display
$totalQueriesFmt = number_format($totalQueries);
$blockedQueriesFmt = number_format($blockedQueries);
$trustCountFmt = number_format(intval($trustCount));
$blockRate = ($totalQueries > 0) ? round(($blockedQueries / $totalQueries) * 100, 1) : 0;
$cacheTotal = $cacheHits + $cacheMisses;
$cacheRatio = $cacheTotal > 0 ? round(($cacheHits / $cacheTotal) * 100, 1) : 0;
$formatDuration = function($seconds) {
    $seconds = max(0, intval($seconds));
    $days = intdiv($seconds, 86400);
    $hours = intdiv($seconds % 86400, 3600);
    $minutes = intdiv($seconds % 3600, 60);
    return ($days > 0 ? $days . 'h ' : '') . $hours . 'j ' . $minutes . 'm';
};
$resolverUptimeText = $formatDuration($resolverUptime);
$hostName = gethostname() ?: 'trust-ng';
$kernelVersion = php_uname('r');
$cpuCores = intval(@shell_exec('nproc 2>/dev/null')) ?: 1;
$blocklistMtime = @filemtime('/etc/unbound/db/trust.txt');
$blocklistUpdated = $blocklistMtime ? date('d-m-Y H:i', $blocklistMtime) : 'Tidak tersedia';
$blocklistTimer = trim((string) @shell_exec('systemctl is-enabled update-blocklist.timer 2>/dev/null'));
$blocklistTimerText = $blocklistTimer === 'enabled' ? 'Timer aktif' : 'Timer tidak aktif';
$dnssecEnabled = trim((string) @file_get_contents('setdnssec')) !== 'no';
$safeSearchEnabled = trim((string) @file_get_contents('setsafesearch')) === 'yes';
$resolverData = trim((string) @file_get_contents('resolver.data'));
$resolverCount = 0;
if ($resolverData !== '') {
    foreach (explode(',', $resolverData) as $resolverValue) {
        if (trim($resolverValue) !== '') $resolverCount++;
    }
}
$cacheMemoryText = number_format($cacheMemory / 1048576, 1) . ' MB';
$recursionAverageText = $recursionAverage > 0 ? number_format($recursionAverage, 3) . ' ms' : '—';

tng_ui_page_start('manage.php', 'Dashboard', 'Ringkasan operasional resolver DNS, status layanan, dan trafik blokir secara langsung.');
?>
<div data-page-actions>
  <span class="badge live-pill"><span class="status-dot success"></span>LIVE</span>
</div>

<div class="tng-content dashboard-console">

  <section class="dashboard-commandbar" aria-label="Status node DNS">
    <div class="dashboard-command-copy">
      <span class="dashboard-path">TRUST-NG / DNS CONSOLE / <strong>RESOLVER METRICS</strong></span>
      <div class="dashboard-node-meta">
        <span><i class="fa-solid fa-server" aria-hidden="true"></i><?php echo tng_e($hostName); ?></span>
        <?php if ($ipaddr !== ''): ?><span class="label-mono">IPv4 <?php echo tng_e($ipaddr); ?></span><?php endif; ?>
        <?php if ($ip6 !== ''): ?><span class="label-mono">IPv6 <?php echo tng_e($ip6); ?></span><?php endif; ?>
      </div>
    </div>
    <div class="dashboard-command-actions">
      <a class="button button-secondary button-small" href="stats.php"><i class="fa-solid fa-chart-line" aria-hidden="true"></i>Statistik</a>
      <a class="button button-small" href="digtest.php"><i class="fa-solid fa-terminal" aria-hidden="true"></i>DNS Inspector</a>
    </div>
  </section>

  <section class="dashboard-ticker <?php echo $statsErr === '' ? 'is-healthy' : 'is-critical'; ?>" aria-label="Ringkasan status operasional">
    <span class="dashboard-ticker-label"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i>STATUS FEED</span>
    <p><?php if ($statsErr === ''): ?>Unbound aktif · Trust+ <?php echo tng_e($trustCountFmt); ?> signature · database diperbarui <?php echo tng_e($blocklistUpdated); ?><?php else: ?>Statistik Unbound bermasalah: <?php echo tng_e($statsErr); ?><?php endif; ?></p>
    <span class="dashboard-ticker-meta"><?php echo tng_e($blocklistTimerText); ?></span>
  </section>

  <div class="tng-hero-row dashboard-kpi-grid">
    <div class="tng-hero-card dashboard-kpi-card">
      <div class="tng-hero-icon"><i class="fa-solid fa-globe" aria-hidden="true"></i></div>
      <div class="tng-hero-info">
        <span class="kicker">Total Queries</span>
        <span class="tng-hero-value" id="heroTotal"><?php echo htmlspecialchars($totalQueriesFmt, ENT_QUOTES, 'UTF-8'); ?></span>
        <span class="tng-hero-unit">sejak service start</span>
      </div>
    </div>
    <div class="tng-hero-card dashboard-kpi-card hero-blocked">
      <div class="tng-hero-icon"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i></div>
      <div class="tng-hero-info">
        <span class="kicker">Blocked Queries</span>
        <span class="tng-hero-value" id="heroBlocked"><?php echo htmlspecialchars($blockedQueriesFmt, ENT_QUOTES, 'UTF-8'); ?></span>
        <span class="tng-hero-unit">diblokir Trust+</span>
      </div>
    </div>
    <div class="tng-hero-card dashboard-kpi-card hero-rate">
      <div class="tng-hero-icon"><i class="fa-solid fa-percent" aria-hidden="true"></i></div>
      <div class="tng-hero-info">
        <span class="kicker">Block Rate</span>
        <span class="tng-hero-value" id="heroRate"><?php echo tng_e(number_format($blockRate, 1)); ?></span>
        <span class="tng-hero-unit">% dari total</span>
      </div>
    </div>
    <div class="tng-hero-card dashboard-kpi-card">
      <div class="tng-hero-icon"><i class="fa-solid fa-database" aria-hidden="true"></i></div>
      <div class="tng-hero-info">
        <span class="kicker">Blocklist Entries</span>
        <span class="tng-hero-value"><?php echo htmlspecialchars($trustCountFmt, ENT_QUOTES, 'UTF-8'); ?></span>
        <span class="tng-hero-unit">domain</span>
      </div>
    </div>
  </div>

  <section class="dashboard-operations-grid">
    <div class="dashboard-panel">
      <header class="dashboard-panel-head">
        <div><span class="kicker">CORE SERVICES</span><h2>Status Layanan</h2></div>
        <span class="label-mono">Unbound Control</span>
      </header>
      <div class="dashboard-service-grid">
        <article class="dashboard-service-card">
          <div class="dashboard-service-title"><i class="fa-solid fa-server" aria-hidden="true"></i><strong>Unbound Core</strong></div>
          <span class="badge <?php echo $statusBadge($mystatus); ?>"><?php echo $statusText($mystatus); ?></span>
          <small>Control socket lokal</small>
        </article>
        <article class="dashboard-service-card">
          <div class="dashboard-service-title"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i><strong>Trust+ Filter</strong></div>
          <span class="badge <?php echo $statusBadge($truststatus); ?>"><?php echo $statusText($truststatus); ?></span>
          <small><?php echo tng_e($trustCountFmt); ?> signature</small>
        </article>
        <article class="dashboard-service-card">
          <div class="dashboard-service-title"><i class="fa-solid fa-lock" aria-hidden="true"></i><strong>DNSSEC</strong></div>
          <span class="badge <?php echo $dnssecEnabled ? 'badge-ok' : 'badge-warn'; ?>"><?php echo $dnssecEnabled ? 'Aktif' : 'Nonaktif'; ?></span>
          <small>Validator module</small>
        </article>
        <article class="dashboard-service-card">
          <div class="dashboard-service-title"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><strong>SafeSearch</strong></div>
          <span class="badge <?php echo $safeSearchEnabled ? 'badge-ok' : 'badge-unknown'; ?>"><?php echo $safeSearchEnabled ? 'Aktif' : 'Nonaktif'; ?></span>
          <small>RPZ enforcement</small>
        </article>
      </div>
      <div class="dashboard-service-footer">
        <span><strong>External IP</strong><?php echo $statusText($extip); ?></span>
        <span><strong>Resolver Uptime</strong><?php echo tng_e($resolverUptimeText); ?></span>
        <span><strong>Request Aktif</strong><span id="activeRequestCount"><?php echo tng_e($requestCurrent); ?></span></span>
      </div>
    </div>

    <?php if (!empty($resourceItems)): ?>
    <div class="dashboard-panel">
      <header class="dashboard-panel-head">
        <div><span class="kicker">HOST TELEMETRY</span><h2>Resource Sistem</h2></div>
        <span class="label-mono"><?php echo tng_e($cpuCores); ?> CPU core</span>
      </header>
      <div class="tng-resource-strip dashboard-resource-grid">
        <?php foreach ($resourceItems as $resource):
            $label        = htmlspecialchars($resource[0], ENT_QUOTES, 'UTF-8');
            $title        = htmlspecialchars($resourceTitle($resource[0]), ENT_QUOTES, 'UTF-8');
            $value        = $resource[1];
            $state        = $resourceState($value);
            $unit         = htmlspecialchars($resourceUnit($resource[0]), ENT_QUOTES, 'UTF-8');
            $icon         = $resourceIcon($resource[0]);
            $displayValue = rtrim(rtrim(number_format($value, 1), '0'), '.');
        ?>
        <div class="tng-res-tile <?php echo $state; ?>" data-resource="<?php echo $label; ?>" data-value="<?php echo $value; ?>" data-unit="<?php echo $unit; ?>">
          <div class="tng-res-tile-head"><i class="fa-solid <?php echo $icon; ?>" aria-hidden="true"></i><span><?php echo $title; ?></span></div>
          <div class="tng-res-tile-value"><?php echo $displayValue; ?><span><?php echo $unit; ?></span></div>
          <div class="tng-res-tile-bar"><div class="tng-res-tile-bar-fill" style="width:<?php echo $value; ?>%"></div></div>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="dashboard-panel-foot"><span>Kernel <?php echo tng_e($kernelVersion); ?></span><span>Model <?php echo tng_e($model !== '' ? $model : 'Tidak tersedia'); ?></span></div>
    </div>
    <?php endif; ?>
  </section>

  <div class="tng-chart-row">
    <div class="tng-query-card dashboard-data-card">
      <div class="tng-query-head">
        <div class="tng-query-meta">
          <span class="tng-query-label">Total Queries Real-Time</span>
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

    <div class="tng-query-card dashboard-data-card">
      <div class="tng-query-head">
        <div class="tng-query-meta">
          <span class="tng-query-label">Blocked Queries Real-Time</span>
          <span class="tng-query-count" id="blockedValue">0</span>
          <span class="tng-query-mode" id="blockedMode">menunggu data…</span>
        </div>
        <span id="blockedPulse" class="query-pulse query-pulse-rose"></span>
      </div>
      <canvas id="blockedChart" width="720" height="200"></canvas>
    </div>
  </div>

  <div class="tng-chart-row">
    <div class="tng-query-card dashboard-data-card">
      <div class="tng-query-head">
        <div class="tng-query-meta">
          <span class="tng-query-label">Cache Performance</span>
          <span class="tng-query-count" id="cacheRatioValue"><?php echo tng_e(number_format($cacheRatio, 1)); ?>%</span>
          <span class="tng-query-mode"><?php echo tng_e(number_format($cacheHits)); ?> hit · <?php echo tng_e(number_format($cacheMisses)); ?> miss</span>
        </div>
      </div>
      <div class="tng-donut-wrap">
        <canvas id="donutChart" width="200" height="200"></canvas>
        <div id="donutLegend" class="tng-donut-legend"></div>
      </div>
    </div>

    <div class="tng-query-card dashboard-data-card">
      <div class="tng-query-head">
        <div class="tng-query-meta">
          <span class="tng-query-label">Forward Destinations</span>
          <span class="tng-query-count"><?php echo tng_e($resolverCount); ?></span>
          <span class="tng-query-mode">parent resolver terkonfigurasi</span>
        </div>
      </div>
      <div id="forwardBars" class="tng-forward-bars"></div>
    </div>
  </div>

  <section class="dashboard-runtime-strip" aria-label="Detail runtime Unbound">
    <span><i class="fa-solid fa-memory" aria-hidden="true"></i><strong>Cache Memory</strong><?php echo tng_e($cacheMemoryText); ?></span>
    <span><i class="fa-solid fa-forward-fast" aria-hidden="true"></i><strong>Prefetch</strong><?php echo tng_e(number_format($prefetchCount)); ?></span>
    <span><i class="fa-solid fa-stopwatch" aria-hidden="true"></i><strong>Rata-rata Rekursi</strong><span id="recursionAverage"><?php echo tng_e($recursionAverageText); ?></span></span>
    <span><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i><strong>Uptime Resolver</strong><span id="resolverUptime"><?php echo tng_e($resolverUptimeText); ?></span></span>
  </section>

  <section class="dashboard-panel dashboard-ledger">
    <header class="dashboard-panel-head dashboard-ledger-head">
      <div>
        <span class="kicker">UNBOUND REQUESTLIST</span>
        <h2>Active DNS Request Ledger</h2>
        <p>Permintaan yang sedang berada di pipeline resolver, bukan histori query.</p>
      </div>
      <div class="dashboard-ledger-actions">
        <span class="badge badge-unknown" id="requestLedgerStatus">Memuat</span>
        <button class="button button-secondary button-small" id="requestRefresh" type="button"><i class="fa-solid fa-rotate" aria-hidden="true"></i>Refresh</button>
        <a class="button button-secondary button-small" href="reqlist.php">Buka Request List</a>
      </div>
    </header>
    <div class="table-wrap dashboard-ledger-table">
      <table>
        <thead><tr><th>Thread</th><th>Domain</th><th>Type</th><th>Class</th><th>Umur</th><th>Module / Status</th></tr></thead>
        <tbody id="requestLedgerRows"><tr><td colspan="6"><div class="loading-state"><i class="fa-solid fa-circle-notch fa-spin" aria-hidden="true"></i><span>Mengambil request aktif dari Unbound.</span></div></td></tr></tbody>
      </table>
    </div>
  </section>

  <footer class="dashboard-signature">
    <span>TRUST-NG DNS Control · Unbound <?php echo tng_e($myv); ?></span>
    <span><?php echo tng_e($hostName); ?> · <?php echo tng_e($kernelVersion); ?> · status <?php echo $statsErr === '' ? 'tersinkronisasi' : 'perlu diperiksa'; ?></span>
  </footer>

</div>

<div id="ifstat" class="status-time" title="Waktu server saat ini">
  <?php include 'ifstat.php'; ?>
</div>

<script src="/jquery.min.js"></script>
<script src="loader.js"></script>
<script src="ifstat.js"></script>
<script src="dashboard.js"></script>
<?php
tng_ui_page_end('manage.php');
