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
<style>
.di-card{max-width:720px;margin:12px auto 0;padding:22px;border-radius:14px;text-align:left;
 background:var(--glass-bg,rgba(19,29,46,.7));border:1px solid var(--glass-border,rgba(225,226,235,.12));
 backdrop-filter:blur(var(--glass-blur,16px));box-shadow:var(--shadow,0 10px 30px rgba(0,0,0,.25));
 color:var(--ink,#e8eaf1);}
.di-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;}
.di-title{font-size:16px;font-weight:700;display:flex;align-items:center;gap:9px;}
.di-title .fa-magnifying-glass-chart{color:var(--brand,#3b82f6);}
.di-server{font-family:"JetBrains Mono",monospace;font-size:11px;color:var(--muted,#8a93a8);}
.di-row{display:flex;align-items:center;gap:12px;padding:11px 12px;border-radius:10px;
 border:1px solid var(--line,rgba(255,255,255,.07));margin-bottom:8px;
 background:var(--surface-2,rgba(15,22,35,.35));transition:border-color .25s;}
.di-row:hover{border-color:var(--outline-variant,rgba(255,255,255,.15));}
.di-dot{width:10px;height:10px;border-radius:99px;flex-shrink:0;background:var(--surface-container-high,#555);}
.di-dot.resolved{background:#27ff97;box-shadow:0 0 8px rgba(39,255,151,.5);}
.di-dot.blocked{background:#ffb020;box-shadow:0 0 8px rgba(255,176,32,.5);}
.di-dot.noanswer{background:#f87171;box-shadow:0 0 8px rgba(248,113,113,.45);}
.di-domain{font-weight:600;font-size:13.5px;width:230px;flex-shrink:0;
 overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.di-status{font-size:10.5px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;
 padding:3px 9px;border-radius:99px;font-family:"JetBrains Mono",monospace;flex-shrink:0;}
.di-status.resolved{background:rgba(39,255,151,.12);color:#6ee7a0;border:1px solid rgba(39,255,151,.3);}
.di-status.blocked{background:rgba(255,176,32,.12);color:#ffd280;border:1px solid rgba(255,176,32,.3);}
.di-status.noanswer{background:rgba(248,113,113,.1);color:#fca5a5;border:1px solid rgba(248,113,113,.3);}
.di-status.pending{background:var(--surface-container-high,rgba(255,255,255,.08));color:var(--muted,#8a93a8);border:1px solid transparent;}
.di-recs{flex:1;text-align:right;font-family:"JetBrains Mono",monospace;font-size:11.5px;
 color:var(--on-surface-variant,#b9c0d0);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.di-empty{padding:26px;text-align:center;color:var(--muted,#8a93a8);font-size:13px;}
.di-actions{display:flex;gap:10px;justify-content:center;margin-top:14px;}
@keyframes di-pulse{0%,100%{opacity:.35}50%{opacity:1}}
.di-loading .di-dot{animation:di-pulse 1s infinite;}
</style>
</head>
<body class="with-sidebar sidebar-collapsed">
<div id="sidebar-overlay"></div>
<div class="page-shell">
';
include_once 'menu.php';
trustng_render_sidebar('digtest.php');

echo '<div class="page-content">';
echo '<div class="tng-topbar"><button class="tng-topbar-toggle" title="Toggle menu" aria-label="Toggle menu"><svg width="20" height="20" viewBox="0 0 20 20" fill="none"><rect x="2" y="4.5" width="16" height="2" rx="1" fill="currentColor"/><rect x="2" y="9" width="16" height="2" rx="1" fill="currentColor"/><rect x="2" y="13.5" width="16" height="2" rx="1" fill="currentColor"/></svg></button><span class="tng-topbar-title">DNS Inspector</span><div class="tng-topbar-spacer"></div><a class="tng-topbar-back" href="/">&#8592; Dashboard</a></div>';
echo '
<div>
<h3 style="margin:6px 0 10px;">DNS Inspector</h3>
<small style="color:var(--muted,#8a93a8);">Uji resolusi domain melalui resolver ini &mdash; status blokir terdeteksi otomatis dari jawaban lamanlabuh.</small>
<div class="di-card">
  <div class="di-head">
    <span class="di-title"><i class="fa-solid fa-magnifying-glass-chart"></i> Live Resolution Test</span>
    <span class="di-server">resolver @' . htmlspecialchars($ipaddr ?: '127.0.0.1') . '</span>
  </div>
  <div id="di-results"><div class="di-empty">Memuat…</div></div>
  <div class="di-actions">
    <button class="submit-button" onclick="diRun()"><i class="fa-solid fa-rotate"></i> Jalankan Test</button>
    <a href="/setdigtest.php"><input type="button" class="submit-button" value="Set Domain"/></a>
    <a href="/"><input type="button" class="submit-button" value="Kembali"/></a>
  </div>
</div>
<p><small><b>&#169; 2024 Kominfo</b></small></p>
</div>';
include 'digtest.js';
echo '</div></div>';
?>
