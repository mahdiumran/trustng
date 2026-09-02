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

$jumlah = @file_get_contents('/etc/unbound/db/trust.count');
$jumlah = $jumlah !== false ? trim($jumlah) : '0';
$last = @shell_exec("stat -c %z /etc/unbound/db/trust.txt 2>/dev/null | cut -d. -f1");

echo '<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1"/>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" type="text/css" href="style.css" />
<title>DNS TRUST-NG - DB Trust+</title>
<script src="/jquery.min.js"></script>
</head>
<body class="with-sidebar sidebar-collapsed">
<div id="sidebar-overlay"></div>
<div class="page-shell">
';
include_once 'menu.php';
trustng_render_sidebar('dbtrust.php');

echo '<div class="page-content">
<div class="tng-topbar"><button class="tng-topbar-toggle" title="Toggle menu" aria-label="Toggle menu"><svg width="20" height="20" viewBox="0 0 20 20" fill="none"><rect x="2" y="4.5" width="16" height="2" rx="1" fill="currentColor"/><rect x="2" y="9" width="16" height="2" rx="1" fill="currentColor"/><rect x="2" y="13.5" width="16" height="2" rx="1" fill="currentColor"/></svg></button><span class="tng-topbar-title">Database Trust+</span><div class="tng-topbar-spacer"></div><a class="tng-topbar-back" href="/">&#8592; Dashboard</a></div>
<div align=center>
<canvas class="logo-canvas" width="600" height="60"></canvas>
<h3>Database <span class="grad">Trust+</span></h3>
<small>Jumlah domain: <b>'.htmlspecialchars($jumlah).'</b> &middot; Perubahan terakhir: '.htmlspecialchars($last ?: '-').'</small>

<div class="di-card">
  <div class="di-head">
    <span class="di-title"><i class="fa-solid fa-database"></i> Pencarian</span>
    <span class="di-server" id="db-mode-label">Mode: Keyword</span>
  </div>
  <div class="db-modes">
    <button type="button" class="db-mode active" data-mode="keyword" onclick="dbSetMode(\'keyword\')">Keyword</button>
    <button type="button" class="db-mode" data-mode="domain" onclick="dbSetMode(\'domain\')">Domain</button>
  </div>
  <small class="di-manual-hint" id="db-hint">Cari kata kunci di dalam daftar Trust+ (maks 500 hasil).</small>
  <div class="di-query">
    <input type="text" id="dbQ" class="di-input" placeholder="mis. xnxx" onkeydown="if(event.key===\'Enter\'){event.preventDefault();dbRun();}" />
    <button class="submit-button" onclick="dbRun()"><i class="fa-solid fa-magnifying-glass"></i> Cari</button>
  </div>
  <div id="db-results"><div class="di-empty">Masukkan kata kunci atau domain lalu tekan Cari.</div></div>
  <div class="di-actions">
    <a class="submit-button" href="/">Kembali</a>
  </div>
</div>

<p><small><b>&#169; 2024 Kominfo</b></small></p>
</div>';
echo '<script src="dbtrust.js"></script>';
echo '</div></div>';
?>
