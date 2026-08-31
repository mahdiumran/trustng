<?php
error_reporting(0);
$myip = $_SERVER['SERVER_ADDR'];
$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
$http_host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : "$myip:40443";
$proto = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$allowed_prefix = "$proto://$http_host/";
$allowed_prefix_ip = "https://$myip:40443/";

if($_POST['do'] ?? null) {
    if ($_POST['do'] === 'flush') {
        shell_exec('unbound-control flush_stats 2>&1');
        $metrics_db = '/var/lib/trustng-metrics/metrics.db';
        if (is_file($metrics_db)) {
            if (is_writable($metrics_db)) {
                @unlink($metrics_db);
            } else {
                @shell_exec('sudo /bin/rm -f ' . escapeshellarg($metrics_db) . ' 2>/dev/null');
            }
        }
        sleep(1);
        header('location: resetstats.php?done=1');
        exit(0);
    }
}

if ($referer != "https://$myip:40443/" && $referer != "https://$myip:40443/index.php") {
        if (!isset($index) || $index !== 'yes') {
            if (strpos($referer, $allowed_prefix) !== 0 && strpos($referer, $allowed_prefix_ip) !== 0) exit(0);
        }
}

$done = isset($_GET['done']);
function statval($stats, $key) {
    if (preg_match('/^' . preg_quote($key, '/') . '=(\d+)/m', $stats, $m)) return $m[1];
    return '0';
}
$stats = @shell_exec("dn stats_noreset 2>/dev/null");
if (empty($stats)) { $stats = @shell_exec("unbound-control stats_noreset 2>/dev/null"); }
$queries   = statval($stats, 'total.num.queries');
$blocked   = statval($stats, 'total.num.blacklist');
$cachehits = statval($stats, 'total.num.cachehits');
$uptime    = intval(statval($stats, 'time.up'));
$up_str = sprintf('%dd %dh %dm', intdiv($uptime, 86400), intdiv($uptime % 86400, 3600), intdiv($uptime % 3600, 60));

echo '<html>
<head>
<meta http-equiv="X-UA-Compatible" content="IE=edge"/>
<meta name="viewport" content="width=device-width, initial-scale=1"/>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" type="text/css" href="style.css" />
<title>DNS TRUST-NG - RESET STATISTIK</title>
<script src="/jquery.min.js"></script>
</head>
<body class="with-sidebar sidebar-collapsed">
<div id="sidebar-overlay"></div>
<div class="page-shell">';
include_once 'menu.php';
trustng_render_sidebar('resetstats.php');
echo '
<div class="page-content">
<div class="tng-topbar"><button class="tng-topbar-toggle" title="Toggle menu" aria-label="Toggle menu"><svg width="20" height="20" viewBox="0 0 20 20" fill="none"><rect x="2" y="4.5" width="16" height="2" rx="1" fill="currentColor"/><rect x="2" y="9" width="16" height="2" rx="1" fill="currentColor"/><rect x="2" y="13.5" width="16" height="2" rx="1" fill="currentColor"/></svg></button><span class="tng-topbar-title">Reset Stats</span><div class="tng-topbar-spacer"></div><a class="tng-topbar-back" href="/">&#8592; Dashboard</a></div>
<div align=center>
<h3>Reset Statistik Resolver</h3>';
if ($done) {
    echo '<p><span class="badge-ok">Statistik berhasil direset</span></p>';
    echo '<p>Anda akan diarahkan kembali dalam <span id="countdowntimer">5</span> detik</p>';
    echo '<div class="system-progress" aria-hidden="true"><span id="countdownprogress"></span></div>';
    echo '<script>var timeleft=5,timer=setInterval(function(){timeleft--;document.getElementById("countdowntimer").textContent=timeleft;document.getElementById("countdownprogress").style.width=(timeleft/5*100)+"%";if(timeleft<=0){clearInterval(timer);window.location.href="maintenance.php";}},1000);</script>';
}
echo '
<table>';
$rows = array(
    'Total Query'      => number_format((float)$queries, 0, ",", "."),
    'Domain Diblokir'  => number_format((float)$blocked, 0, ",", "."),
    'Cache Hits'       => number_format((float)$cachehits, 0, ",", "."),
    'Uptime Resolver'  => $up_str,
);
foreach ($rows as $k => $v) {
    echo '<tr><td align=left>' . htmlspecialchars($k) . '</td><td align=right><code>' . htmlspecialchars($v) . '</code></td></tr>';
}
echo '</table>
<p><i>*Reset menghapus counter runtime resolver (query, blokir, cache).<br/>
Riwayat arsip metrics tidak terpengaruh.</i></p>
<form method="post" action="resetstats.php">
<input type="hidden" name="do" value="flush"/>
<input type="submit" value="Reset Statistik" class="submit-button"
 onclick="return confirm(\'Konfirmasi reset statistik?\ncounter query/blokir akan kembali ke nol\')"/>
<input type="button" onclick="location.href=\'maintenance.php\';" class="submit-button" value="Kembali"/>
</form>
<p><small><b>&#169; 2024 Kominfo</b></small>
</div>
</div>
</div>';
?>
