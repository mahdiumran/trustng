<?php
error_reporting(0);
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
$myip = $_SERVER['SERVER_ADDR'];
$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
$http_host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : "$myip:40443";
$proto = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$allowed_prefix = "$proto://$http_host/";
$allowed_prefix_ip = "https://$myip:40443/";

if($_POST['data'] ?? null) {
    $data = $_POST['data'] ?? '';
    $file = fopen('whitelist.db', 'w');
    if ($file) { fwrite($file, "$data"); fclose($file); }
    $data = preg_replace('#\s+#',', ',trim($data));
    shell_exec('dos2unix whitelist.db');
    $file = fopen('setdns.new', 'w');
    if ($file) { fwrite($file, ''); fclose($file); }
    shell_exec('sh setwhitelist.sh');
    sleep (0.3);
    header('location: setwhite.php');
}

if ($referer != "https://$myip:40443/" && $referer != "https://$myip:40443/index.php") {
        if (!isset($index) || $index !== 'yes') {
            if (strpos($referer, $allowed_prefix) !== 0 && strpos($referer, $allowed_prefix_ip) !== 0) exit(0);
        }
}

$file = file("whitelist.db");
echo '<html>
<head>
<meta http-equiv="X-UA-Compatible" content="IE=edge"/>
<meta name="viewport" content="width=device-width, initial-scale=1"/>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" type="text/css" href="style.css" />
<title>DNS TRUST-NG - WHITELIST</title>
<script src="/jquery.min.js"></script>
</head>
<body class="with-sidebar sidebar-collapsed">
<div id="sidebar-overlay"></div>
<div class="page-shell">';
include_once 'menu.php';
trustng_render_sidebar('setwhite.php');
echo '
<div class="page-content">
<div class="tng-topbar"><button class="tng-topbar-toggle" title="Toggle menu" aria-label="Toggle menu"><svg width="20" height="20" viewBox="0 0 20 20" fill="none"><rect x="2" y="4.5" width="16" height="2" rx="1" fill="currentColor"/><rect x="2" y="9" width="16" height="2" rx="1" fill="currentColor"/><rect x="2" y="13.5" width="16" height="2" rx="1" fill="currentColor"/></svg></button><span class="tng-topbar-title">Whitelist</span><div class="tng-topbar-spacer"></div><a class="tng-topbar-back" href="/">&#8592; Dashboard</a></div>
<div align=center>
<script>
    $("document").ready(function(){
    $("#line_numbers").linenumbers({col_width:"50px"});
    })
</script>
<script src="linear.js"></script>
<h3>Whitelist</h3>
<form name="wlist" action="setwhite.php" method="post">
<div class="wl-section">
  <div class="wl-head">
    <span class="wl-title">Domain Pengecualian</span>
    <span class="wl-badge">' . intval(count($file ?: array())) . ' domain</span>
  </div>
  <div class="wl-desc">Domain di daftar ini <b>selalu lolos</b> walau masuk blocklist &mdash; diproses sebelum filter Trust+.</div>
  <div class="areatxt"><textarea rows="10" name="data" id="line_numbers" placeholder="satu domain per baris">';
foreach($file as $text) { echo htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); }
echo '</textarea></div>
  <div class="wl-actions">
    <input type="submit" id="submit" value="Simpan" class="submit-button"/>
    <input type="button" onclick="location.href = \'manage.php\';" class="submit-button" value="Kembali"/>
    <span class="wl-hint">*perubahan efektif setelah Reload</span>
  </div>
</div>
</form>
<p><small><b>&#169; 2024 Kominfo</b></small>
</div>
</div>
</div>';
?>
