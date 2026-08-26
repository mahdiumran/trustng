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
$back = 'history.back()';
$ipaddr = shell_exec("ifconfig eth0 | grep netmask | sed 's/ .*inet //;s/ .*//'");


echo '<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1"/>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" type="text/css" href="style.css" />
<title>DNS TRUST-NG - MANAGE</title>
</head>
<body class="with-sidebar sidebar-collapsed">
<div id="sidebar-overlay"></div>
<div class="page-shell">';
include_once 'menu.php';
trustng_render_sidebar('maintenance.php');

echo '<div class="page-content">';
echo '<div class="tng-topbar"><button class="tng-topbar-toggle" title="Toggle menu" aria-label="Toggle menu"><svg width="20" height="20" viewBox="0 0 20 20" fill="none"><rect x="2" y="4.5" width="16" height="2" rx="1" fill="currentColor"/><rect x="2" y="9" width="16" height="2" rx="1" fill="currentColor"/><rect x="2" y="13.5" width="16" height="2" rx="1" fill="currentColor"/></svg></button><span class="tng-topbar-title">Maintenance</span><div class="tng-topbar-spacer"></div><a class="tng-topbar-back" href="/">&#8592; Dashboard</a></div>';
echo'
<div align=center>
<a href="/"><img src="img/logo-img/trust-ng.jpg" width="200px"></a>
<h3>Maintenance<br><small>'.$ipaddr.'</small></h3>
<div class="action-grid">
<a class="action-card" href="repairmunin.php" onclick="return confirm(\'Konfirmasi repair Munin? graph akan direset dan bisa dilihat kembali 5 menit kemudian\')"><i class="fa-solid fa-chart-simple"></i><span>Repair Graph</span></a>
<a class="action-card" href="restartunbound.php" onclick="return confirm(\'yakin mau restart Unbound? dns cache akan terhapus\')"><i class="fa-solid fa-rotate"></i><span>Restart Unbound</span></a>
<a class="action-card danger" href="reset.php" onclick="return confirm(\'Konfirmasi reset system? konfigurasi akan dikembalikan ke default\n\nPerubahan efektif setelah dilakukan perintah Reboot\')"><i class="fa-solid fa-arrow-rotate-left"></i><span>Reset</span></a>
<a class="action-card" href="reload.php" onclick="return confirm(\'Konfirmasi reload system? hanya services terkait perubahan yang akan dijalankan ulang\')"><i class="fa-solid fa-arrows-rotate"></i><span>Reload</span></a>
<a class="action-card danger" href="reboot.php" onclick="return confirm(\'Konfirmasi reboot system? keseluruhan system akan dijalankan ulang\')"><i class="fa-solid fa-power-off"></i><span>Reboot</span></a>
</div>
<p><a href="/"> <input type="button" class="submit-button" value="Kembali"></a></p>
<br><br>
<p><small><b>&#169; 2024 Kominfo</b></small>
</div>';

echo '</div></div>';
?>