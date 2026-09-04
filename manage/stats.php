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

echo '<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1"/>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" type="text/css" href="style.css" />
<title>DNS TRUST-NG - STATS</title>
<script src="/jquery.min.js"></script>
</head>
<body class="with-sidebar sidebar-collapsed">
<div id="sidebar-overlay"></div>
<div class="page-shell">
';
include_once 'menu.php';
trustng_render_sidebar('stats.php');

echo '<div class="page-content">
<div class="tng-topbar"><button class="tng-topbar-toggle" title="Toggle menu" aria-label="Toggle menu"><svg width="20" height="20" viewBox="0 0 20 20" fill="none"><rect x="2" y="4.5" width="16" height="2" rx="1" fill="currentColor"/><rect x="2" y="9" width="16" height="2" rx="1" fill="currentColor"/><rect x="2" y="13.5" width="16" height="2" rx="1" fill="currentColor"/></svg></button><span class="tng-topbar-title">Live DNS Stats</span><div class="tng-topbar-spacer"></div><a class="tng-topbar-back" href="/">&#8592; Dashboard</a></div>
<div align=center>
<canvas class="logo-canvas" width="600" height="60"></canvas>
<h3>Live DNS Stats<br><small>Resolver @' . htmlspecialchars($ipaddr ?: '127.0.0.1', ENT_QUOTES, 'UTF-8') . '</small></h3>
<div class="tng-section-label">Ringkasan</div>
<div class="tng-stats-grid" id="st-cards">';
foreach ($highlights as $h) {
    $val = htmlspecialchars($st($h[1]), ENT_QUOTES, 'UTF-8');
    echo '<div class="tng-stat-card ' . $h[3] . '">
    <div class="tng-stat-head"><span class="tng-stat-icon"><i class="' . $h[2] . '"></i></span><span class="tng-stat-name">' . $h[0] . '</span></div>
    <div class="tng-stat-value">' . $val . '</div>
  </div>';
}
if ($pairs['_raw_error'] !== '') {
  echo '<div class="di-card" style="border-left:3px solid #ff4d6d"><div class="di-head"><span class="di-title" style="color:#ff8fa0"><i class="fa-solid fa-triangle-exclamation"></i> Unbound stats error</span></div><pre class="st-raw" style="color:#ffb3c0;white-space:pre-wrap">'.htmlspecialchars($pairs['_raw_error'], ENT_QUOTES, 'UTF-8')."\nHints: cek sock /etc/unbound/run/unbound.sock, groups www-data, symlink /usr/local/etc/unbound/unbound.conf</pre></div>";
}
echo '</div>

<div class="di-card">
  <div class="di-head">
    <span class="di-title"><i class="fa-solid fa-list"></i> Raw Statistics</span>
    <span class="di-card-actions">
      <button type="button" class="submit-button" id="st-toggle-btn" onclick="stToggleRaw()">Tampilkan</button>
      <button type="button" class="submit-button di-refresh" onclick="stRefresh()"><i class="fa-solid fa-rotate"></i> Refresh</button>
    </span>
  </div>
  <pre class="st-raw is-hidden" id="stats-raw">' . htmlspecialchars($raw, ENT_QUOTES, 'UTF-8') . '</pre>
</div>

<div class="di-actions" style="margin-top:16px;">
  <a class="submit-button" href="/">Kembali</a>
</div>

<p><small><b>&#169; 2024 Kominfo</b></small></p>
</div>';
echo '<script src="stats.js"></script>';
echo '</div></div>';
?>
