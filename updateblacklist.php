<?php
error_reporting(0);
$myip = $_SERVER['SERVER_ADDR'];
$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
$http_host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : "$myip:40443";
$proto = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$allowed_prefix = "$proto://$http_host/";
$allowed_prefix_ip = "https://$myip:40443/";

if (strpos($referer, $allowed_prefix . "maintenance.php") !== 0 && strpos($referer, $allowed_prefix_ip . "maintenance.php") !== 0) exit;

$running = trim(shell_exec("systemctl is-active update-blocklist 2>/dev/null") ?? '');
if ($running === 'active') {
    include_once 'menu.php';
    trustng_render_sidebar('maintenance.php');
    echo '<div class="page-content">
    <div class="tng-topbar"><button class="tng-topbar-toggle" title="Toggle menu" aria-label="Toggle menu"><svg width="20" height="20" viewBox="0 0 20 20" fill="none"><rect x="2" y="4.5" width="16" height="2" rx="1" fill="currentColor"/><rect x="2" y="9" width="16" height="2" rx="1" fill="currentColor"/><rect x="2" y="13.5" width="16" height="2" rx="1" fill="currentColor"/></svg></button><span class="tng-topbar-title">Maintenance</span><div class="tng-topbar-spacer"></div><a class="tng-topbar-back" href="/">&#8592; Dashboard</a></div>
    <div align=center>
    <div class="di-card" style="max-width:520px;margin:24px auto;">
      <div class="di-head"><span class="di-title"><i class="fa-solid fa-cloud-arrow-down"></i> Update Blacklist</span></div>
      <p>Proses update blocklist <b>sedang berjalan</b>. Pantau progres & hasil di <a href="activity.php">Activity Log</a>.</p>
      <div class="di-actions">
        <a class="submit-button" href="activity.php">Buka Activity Log</a>
        <a class="submit-button" href="maintenance.php">Kembali</a>
      </div>
    </div>
    <p><small><b>&#169; 2024 Kominfo</b></small></p>
    </div></div>';
    exit;
}

shell_exec("sudo /usr/bin/systemctl start update-blocklist 2>&1");
?>
<!DOCTYPE html>
<html lang="id"><head><meta charset="UTF-8"/><meta name="viewport" content="width=device-width, initial-scale=1"/>
<meta http-equiv="refresh" content="5; url=activity.php"/>
<title>Update Blacklist</title><link rel="stylesheet" href="style.css"/></head>
<body><main class="system-state"><img class="state-logo" src="img/logo-img/trust-ng.jpg" alt="TRUST-NG">
<h3>Update Blacklist<br><small><?php echo htmlspecialchars($myip, ENT_QUOTES, 'UTF-8'); ?></small></h3>
<h4>Memulai proses update blocklist...</h4>
<div class="system-progress" aria-hidden="true"><span id="countdownprogress"></span></div>
<p>Anda akan diarahkan ke Activity Log dalam <span id="countdowntimer">5</span> detik</p>
<p><a href="activity.php">Lanjutkan sekarang</a></p>
<p><small><b>&copy; 2024 Kominfo</b></small></p>
<script>var timeleft=5,timer=setInterval(function(){timeleft--;document.getElementById("countdowntimer").textContent=timeleft;document.getElementById("countdownprogress").style.width=(timeleft/5*100)+"%";if(timeleft<=0){clearInterval(timer);window.location.href="activity.php";}},1000);</script>
</main></body></html>
