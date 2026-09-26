<?php
error_reporting(0);
$myip = $_SERVER['SERVER_ADDR'];
$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
$http_host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : "$myip:40443";
$proto = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$allowed_prefix = "$proto://$http_host/";
$allowed_prefix_ip = "https://$myip:40443/";

if (strpos($referer, $allowed_prefix) !== 0 && strpos($referer, $allowed_prefix_ip) !== 0) {
        exit(0);
}

$ipaddr = shell_exec("ifconfig eth0 2>/dev/null | grep netmask | sed 's/ .*inet //;s/ .*//'");
$ipaddr = $ipaddr !== null ? trim($ipaddr) : '';

require_once __DIR__ . '/includes/ui.php';
require_once __DIR__ . '/includes/unbound.php';
$raw = trim(tng_unbound_stats_raw());
$pairs = tng_unbound_stats_pairs($raw);
$pairs['_raw_error'] = ($raw === '' || preg_match('/^error:|^could not/i', trim($raw))) ? ($raw ?: 'unbound-control tidak merespon') : '';
$st = function($k, $d = '0') { global $pairs; return isset($pairs[$k]) ? $pairs[$k] : $d; };

$highlights = array(
    array('Total Queries',     'total.num.queries',         'fa-solid fa-arrow-right-long', ''),
    array('Blocked (Trust+)',  'total.num.blacklist',       'fa-solid fa-ban',             'blocklist'),
    array('Cache Hits',        'total.num.cachehits',       'fa-solid fa-bolt',            ''),
    array('Cache Misses',      'total.num.cachemiss',       'fa-solid fa-magnifying-glass',''),
    array('Recursive Replies', 'total.num.recursivereplies','fa-solid fa-rotate',          ''),
    array('Prefetch',          'total.num.prefetch',        'fa-solid fa-forward',         ''),
    array('Uptime (s)',        'time.up',                   'fa-solid fa-clock',           ''),
);

tng_ui_page_start('stats.php', 'Live DNS Stats', 'Statistik runtime resolver dari unbound-control.');
?>
<div data-page-actions>
  <span class="badge"><span class="status-dot success"></span>LIVE</span>
</div>

<div class="stack">
  <div class="card">
    <div class="card-header">
      <div>
        <h2>Ringkasan</h2>
        <p>Resolver @<span class="di-server"><?php echo tng_e($ipaddr ?: '127.0.0.1'); ?></span></p>
      </div>
    </div>
    <div class="card-body">
      <div class="tng-status-strip" id="st-cards">
<?php foreach ($highlights as $h) {
    $val = tng_e($st($h[1]));
    echo '<div class="tng-status-card' . ($h[3] !== '' ? ' ' . tng_e($h[3]) : '') . '">';
    echo '<div class="tng-status-icon"><i class="' . $h[2] . '" aria-hidden="true"></i></div>';
    echo '<div class="tng-status-info"><span class="tng-status-name">' . tng_e($h[0]) . '</span><span class="tng-status-val">' . $val . '</span></div>';
    echo '</div>';
} ?>
      </div>
      <div id="st-error">
<?php if ($pairs['_raw_error'] !== '') {
    tng_ui_notice('critical', 'Unbound stats error', $pairs['_raw_error'] . ' — cek sock /etc/unbound/run/unbound.sock, groups www-data, symlink /usr/local/etc/unbound/unbound.conf');
} ?>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <div>
        <h2>Raw Statistics</h2>
        <p>Keluaran mentah unbound-control stats_noreset.</p>
      </div>
      <div class="page-heading-actions">
        <button type="button" class="button button-secondary button-small" id="st-toggle-btn" onclick="stToggleRaw()">Tampilkan</button>
        <button type="button" class="button button-small" onclick="stRefresh()"><i class="fa-solid fa-rotate" aria-hidden="true"></i> Refresh</button>
      </div>
    </div>
    <div class="card-body">
      <pre class="st-raw stats-raw" id="stats-raw"><?php echo tng_e($raw); ?></pre>
    </div>
  </div>

  <div class="di-actions">
    <a class="button button-secondary" href="/">Kembali</a>
  </div>
</div>
<?php
echo '<script src="/jquery.min.js"></script>';
echo '<script src="stats.js"></script>';
tng_ui_page_end('stats.php');
