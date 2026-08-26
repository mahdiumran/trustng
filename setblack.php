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
    // sanitasi: hanya domain chars, satu per baris
    $lines = preg_split('/\r\n|\r|\n/', $data);
    $clean = array();
    foreach ($lines as $line) {
        $line = strtolower(trim($line));
        if ($line === '' || $line[0] === '#') continue;
        if (!preg_match('/^[a-z0-9._-]+$/', $line)) continue;
        if (strlen($line) > 253) continue;
        $clean[] = $line;
    }
    $file = fopen('blacklist.local.db', 'w');
    if ($file) { fwrite($file, implode("\n", $clean) . "\n"); fclose($file); }
    shell_exec('dos2unix blacklist.local.db 2>/dev/null');
    shell_exec('sudo /usr/local/sbin/update-blocklist > /dev/null 2>&1 &');
    sleep (0.3);
    header('location: setblack.php');
}

if ($referer != "https://$myip:40443/" && $referer != "https://$myip:40443/index.php") {
        if (!isset($index) || $index !== 'yes') {
            if (strpos($referer, $allowed_prefix) !== 0 && strpos($referer, $allowed_prefix_ip) !== 0) exit(0);
        }
}

$file = file('blacklist.local.db');
$count = is_file('/etc/unbound/db/trust.count') ? intval(file_get_contents('/etc/unbound/db/trust.count')) : 0;
echo '<html>
<head>
<meta http-equiv="X-UA-Compatible" content="IE=edge"/>
<meta name="viewport" content="width=device-width, initial-scale=1"/>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" type="text/css" href="style.css" />
<title>DNS TRUST-NG - BLACKLIST</title>
<script src="/jquery.min.js"></script>
</head>
<body class="with-sidebar">';
include_once 'menu.php';
trustng_render_sidebar('setblack.php');
echo '
<div align=center>
<script>
    $("document").ready(function(){
    $("#line_numbers").linenumbers({col_width:"50px"});
    })
</script>
<script src="linear.js"></script>
<style>
.bl-section{margin-top:4px;}
.bl-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;}
.bl-title{font-size:16px;font-weight:700;}
.bl-badge{font-family:"JetBrains Mono",monospace;font-size:11px;padding:4px 10px;border-radius:99px;
 background:var(--secondary-container,rgba(39,173,96,.15));color:#6ee7a0;border:1px solid rgba(39,173,96,.3);}
.bl-desc{font-size:12.5px;color:var(--muted,#8a93a8);margin-bottom:14px;line-height:1.55;}
.bl-card .areatxt{width:100%;margin:0 0 10px;}
.bl-actions{display:flex;gap:10px;align-items:center;}
.bl-hint{font-size:11.5px;color:var(--muted,#8a93a8);margin-left:auto;}
</style>
<h3>Blacklist Manual</h3>
<form name="blist" action="setblack.php" method="post">
<div class="bl-section">
  <div class="bl-head">
    <span class="bl-title">Domain Blokir Tambahan</span>
    <span class="bl-badge">Komdigi Trust+: ' . number_format($count, 0, ",", ".") . ' domain</span>
  </div>
  <div class="bl-desc">Domain di daftar Komdigi diperbarui otomatis 2&times; sehari. Tambahkan domain sendiri di bawah &mdash; akan digabung ke blocklist saat updater berikutnya berjalan.</div>
  <div class="areatxt"><textarea rows="10" name="data" id="line_numbers" placeholder="satu domain per baris">';
if (is_array($file)) { foreach($file as $text) { echo htmlspecialchars($text); } }
echo '</textarea></div>
  <div class="bl-actions">
    <input type="submit" id="submit" value="Simpan" class="submit-button"/>
    <input type="button" onclick="location.href=\'manage.php\';" class="submit-button" value="Kembali"/>
    <span class="bl-hint">*domain divalidasi otomatis</span>
  </div>
</div>
</form>
<p><small><b>&#169; 2024 Kominfo</b></small>
</div>';
?>
