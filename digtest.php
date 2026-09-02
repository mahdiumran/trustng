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
echo '<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1"/>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" type="text/css" href="style.css" />
<title>DNS TRUST-NG - DNS Inspector</title>
<script src="/jquery.min.js"></script>
</head>
<body class="with-sidebar sidebar-collapsed">
<div id="sidebar-overlay"></div>
<div class="page-shell">
';
include_once 'menu.php';
trustng_render_sidebar('digtest.php');

echo '<div class="page-content">
<div class="tng-topbar"><button class="tng-topbar-toggle" title="Toggle menu" aria-label="Toggle menu"><svg width="20" height="20" viewBox="0 0 20 20" fill="none"><rect x="2" y="4.5" width="16" height="2" rx="1" fill="currentColor"/><rect x="2" y="9" width="16" height="2" rx="1" fill="currentColor"/><rect x="2" y="13.5" width="16" height="2" rx="1" fill="currentColor"/></svg></button><span class="tng-topbar-title">DNS Inspector</span><div class="tng-topbar-spacer"></div><a class="tng-topbar-back" href="/">&#8592; Dashboard</a></div>
<div align=center>
<canvas class="logo-canvas" width="600" height="60"></canvas>
<h3>DNS <span class="grad">Inspector</span></h3>
<small>Uji status domain melalui resolver ini &mdash; terdeteksi otomatis: <b>Resolved</b>, <b>Blocked</b> (Trust+), atau <b>Whitelisted</b>.</small>

<div class="di-card">
  <div class="di-head">
    <span class="di-title"><i class="fa-solid fa-magnifying-glass"></i> Uji Domain</span>
  </div>
  <small class="di-manual-hint">Masukkan satu domain, lalu tekan <b>Uji</b> (atau Enter).</small>
  <div class="di-query">
    <input type="text" id="diDomain" class="di-input" placeholder="mis. google.com" value="google.com" onkeydown="if(event.key===\'Enter\'){event.preventDefault();diRunManual();}" />
    <button class="submit-button" onclick="diRunManual()"><i class="fa-solid fa-play"></i> Uji</button>
  </div>
  <div id="di-manual-results"><div class="di-empty">Mengetes…</div></div>
  <div class="di-actions">
    <a class="submit-button" href="/">Kembali</a>
  </div>
</div>

<div class="di-card">
  <div class="di-head">
    <span class="di-title"><i class="fa-solid fa-magnifying-glass-chart"></i> Live Resolution Test</span>
    <span class="di-server">resolver @' . htmlspecialchars($ipaddr ?: '127.0.0.1') . '</span>
  </div>
  <div id="di-results"><div class="di-empty">Memuat…</div></div>
  <div class="di-actions">
    <button class="submit-button" onclick="diRun()"><i class="fa-solid fa-rotate"></i> Jalankan Test</button>
    <a class="submit-button" href="/setdigtest.php">Set Domain</a>
    <a class="submit-button" href="/">Kembali</a>
  </div>
</div>

<p><small><b>&#169; 2024 Kominfo</b></small></p>
</div>';
echo '<script src="digtest.js"></script>';
echo '</div></div>';
?>
