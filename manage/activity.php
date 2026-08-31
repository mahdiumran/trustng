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

echo '<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1"/>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" type="text/css" href="style.css" />
<title>DNS TRUST-NG - Activity Log</title>
<script src="/jquery.min.js"></script>
</head>
<body class="with-sidebar sidebar-collapsed">
<div id="sidebar-overlay"></div>
<div class="page-shell">
';
include_once 'menu.php';
trustng_render_sidebar('activity.php');

echo '<div class="page-content">
<div class="tng-topbar"><button class="tng-topbar-toggle" title="Toggle menu" aria-label="Toggle menu"><svg width="20" height="20" viewBox="0 0 20 20" fill="none"><rect x="2" y="4.5" width="16" height="2" rx="1" fill="currentColor"/><rect x="2" y="9" width="16" height="2" rx="1" fill="currentColor"/><rect x="2" y="13.5" width="16" height="2" rx="1" fill="currentColor"/></svg></button><span class="tng-topbar-title">Activity Log</span><div class="tng-topbar-spacer"></div><a class="tng-topbar-back" href="/">&#8592; Dashboard</a></div>
<div align=center>
<canvas class="logo-canvas" width="600" height="60"></canvas>
<h3>Activity <span class="grad">Log</span></h3>
<small>Status &amp; riwayat pembaruan blocklist Trust+ (auto-refresh 10s).</small>

<div class="tng-status-strip" id="act-status">
  <div class="tng-status-card"><div class="tng-status-icon"><i class="fa-solid fa-list"></i></div><div class="tng-status-info"><span class="tng-status-name">Domain Count</span><span class="tng-status-val" id="st-count">&mdash;</span></div></div>
  <div class="tng-status-card"><div class="tng-status-icon"><i class="fa-solid fa-clock"></i></div><div class="tng-status-info"><span class="tng-status-name">Update Terakhir</span><span class="tng-status-val tng-status-val-sm" id="st-update">&mdash;</span></div></div>
  <div class="tng-status-card"><div class="tng-status-icon"><i class="fa-solid fa-heart-pulse"></i></div><div class="tng-status-info"><span class="tng-status-name">Health Terakhir</span><span class="tng-status-val" id="st-health">&mdash;</span></div></div>
  <div class="tng-status-card"><div class="tng-status-icon"><i class="fa-solid fa-calendar-clock"></i></div><div class="tng-status-info"><span class="tng-status-name">Update Berikutnya</span><span class="tng-status-val tng-status-val-sm" id="st-next">&mdash;</span></div></div>
</div>

<div class="di-card">
  <div class="di-head">
    <span class="di-title"><i class="fa-solid fa-clock-rotate"></i> Riwayat Update Blocklist</span>
    <span class="di-server" id="act-last"></span>
  </div>
  <div id="act-rows"><div class="di-empty">Memuat…</div></div>
  <div class="di-actions">
    <button class="submit-button" onclick="actRun()"><i class="fa-solid fa-rotate"></i> Refresh</button>
    <a class="submit-button" href="/">Kembali</a>
  </div>
</div>

<p><small><b>&#169; 2024 Kominfo</b></small></p>
</div>';
echo '<script src="activity.js"></script>';
echo '</div></div>';
?>
