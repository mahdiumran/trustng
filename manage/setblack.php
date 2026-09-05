<?php
require_once __DIR__ . '/includes/state_store.php';
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

// CSRF: guard referer HARUS berjalan SEBELUM memproses POST
if (strpos($referer, $allowed_prefix) !== 0 && strpos($referer, $allowed_prefix_ip) !== 0) {
    exit(0);
}

$BL_FILE = '/var/www/manage/blacklist.local.db';

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
    trustng_state_write(basename($BL_FILE), implode("\n", $clean) . "\n");
    shell_exec('dos2unix ' . escapeshellarg(trustng_state_path(basename($BL_FILE))) . ' 2>/dev/null');
    shell_exec('sudo -n /usr/local/sbin/update-blocklist > /dev/null 2>&1 &');
    sleep (0.3);
    header('location: setblack.php');
    exit;
}

$file = file($BL_FILE);
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
<body class="with-sidebar sidebar-collapsed">
<div id="sidebar-overlay"></div>
<div class="page-shell">';
include_once 'menu.php';
trustng_render_sidebar('setblack.php');
echo '
<div class="page-content">
<div class="tng-topbar"><button class="tng-topbar-toggle" title="Toggle menu" aria-label="Toggle menu"><svg width="20" height="20" viewBox="0 0 20 20" fill="none"><rect x="2" y="4.5" width="16" height="2" rx="1" fill="currentColor"/><rect x="2" y="9" width="16" height="2" rx="1" fill="currentColor"/><rect x="2" y="13.5" width="16" height="2" rx="1" fill="currentColor"/></svg></button><span class="tng-topbar-title">Blacklist</span><div class="tng-topbar-spacer"></div><a class="tng-topbar-back" href="/">&#8592; Dashboard</a></div>
<div align=center>
<script>
    $("document").ready(function(){
    $("#line_numbers").linenumbers({col_width:"50px"});
    })
</script>
<script src="linear.js"></script>
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
</div>
</div>
</div>';
?>
