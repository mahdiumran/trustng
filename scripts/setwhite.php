<?php
error_reporting(0);
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
<body class="with-sidebar">';
include_once 'menu.php';
trustng_render_sidebar('setwhite.php');
echo '
<div align=center>
<script>
    $("document").ready(function(){
    $("#line_numbers").linenumbers({col_width:"50px"});
    })
</script>
<script src="linear.js"></script>
<form name="wlist" action="setwhite.php" method="post">
<style>
.wl-section{margin-top:4px;}
.wl-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;}
.wl-title{font-size:16px;font-weight:700;}
.wl-badge{font-family:"JetBrains Mono",monospace;font-size:11px;padding:4px 10px;border-radius:99px;
 background:rgba(59,130,246,.12);color:#93c5fd;border:1px solid rgba(59,130,246,.3);}
.wl-desc{font-size:12.5px;color:var(--muted,#8a93a8);margin-bottom:14px;line-height:1.55;}
.wl-card .areatxt{width:100%;margin:0 0 10px;}
.wl-actions{display:flex;gap:10px;align-items:center;}
.wl-hint{font-size:11.5px;color:var(--muted,#8a93a8);margin-left:auto;}
</style>
<h3>Whitelist</h3>
<form name="wlist" action="setwhite.php" method="post">
<div class="wl-section">
  <div class="wl-head">
    <span class="wl-title">Domain Pengecualian</span>
    <span class="wl-badge">' . intval(count($file ?: array())) . ' domain</span>
  </div>
  <div class="wl-desc">Domain di daftar ini <b>selalu lolos</b> walau masuk blocklist &mdash; diproses sebelum filter Trust+.</div>
  <div class="areatxt"><textarea rows="10" name="data" id="line_numbers" placeholder="satu domain per baris">';
foreach($file as $text) { echo $text; }
echo '</textarea></div>
  <div class="wl-actions">
    <input type="submit" id="submit" value="Simpan" class="submit-button"/>
    <input type="button" onclick="location.href = \'manage.php\';" class="submit-button" value="Kembali"/>
    <span class="wl-hint">*perubahan efektif setelah Reload</span>
  </div>
</div>
</form>
<p><small><b>&#169; 2024 Kominfo</b></small>
</div>';
?>
