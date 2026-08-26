<?php
error_reporting(0);
$back = 'history.back()';
$myip = $_SERVER['SERVER_ADDR'];
$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
$http_host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : "$myip:40443";
$proto = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$allowed_prefix = "$proto://$http_host/";
$allowed_prefix_ip = "https://$myip:40443/";

$ipaddr = shell_exec("ifconfig eth0 | grep netmask | sed 's/ .*inet //;s/ .*//'");

if($_POST['hosts'] ?? null) {
    if (strpos($referer, $allowed_prefix . "hosts.php") !== 0 && strpos($referer, $allowed_prefix_ip . "hosts.php") !== 0) exit(0);
    $data4 = $_POST['data'] ?? '';
    $data6 = $_POST['data6'] ?? '';
    $file = fopen('hosts.data', 'w');
    if ($file) { fwrite($file, "$data4\n"); fclose($file); }
    $file = fopen('hosts6.data', 'w');
    if ($file) { fwrite($file, "$data6\n"); fclose($file); }
    shell_exec("dos2unix hosts.data");
    shell_exec("dos2unix hosts6.data");
    shell_exec("sh sethosts.sh");
    $file = fopen('setdns.new', 'w');
    if ($file) { fwrite($file, ''); fclose($file); }
    echo "<script>alert('Hosts File telah disimpan, silahkan reload atau reboot untuk mengaktifkan');</script>";
    $index = 'yes'; $back = 'history.go(-2)';
}

if ($referer != "https://$myip:40443/" && $referer != "https://$myip:40443/index.php") {
        if (!isset($index) || $index !== 'yes') exit(0);
}

$file4 = file("hosts.data");
$file6 = file("hosts6.data");
$useip6 = file_get_contents('setip6');

echo '<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1"/>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" type="text/css" href="style.css" />
<title>DNS TRUST-NG - HOSTS FILE</title>
<script src="kunci.js"></script>
</head>
<body class="with-sidebar sidebar-collapsed">
<div id="sidebar-overlay"></div>
<div class="page-shell">';
include_once 'menu.php';
trustng_render_sidebar('hosts.php');

echo '<div class="page-content">';
echo '<div class="tng-topbar"><button class="tng-topbar-toggle" title="Toggle menu" aria-label="Toggle menu"><svg width="20" height="20" viewBox="0 0 20 20" fill="none"><rect x="2" y="4.5" width="16" height="2" rx="1" fill="currentColor"/><rect x="2" y="9" width="16" height="2" rx="1" fill="currentColor"/><rect x="2" y="13.5" width="16" height="2" rx="1" fill="currentColor"/></svg></button><span class="tng-topbar-title">Hosts File</span><div class="tng-topbar-spacer"></div><a class="tng-topbar-back" href="/">&#8592; Dashboard</a></div>';
echo '
<div align=center>
<a href="/"><img src="img/trustng-small.jpg" width="200px"></a>
<form name="domforward" action="hosts.php" method="post">
<p>
<h3>Hosts File<br><small>'.$ipaddr.'</small></h3>
<small>format hosts file: ip_address domain_name<br>warning, salah isi dns bisa tidak berfungsi!</small><br>
IPv4
<div class="areatxt"><textarea rows="10" cols="20" name="data" autofocus="autofocus"'; echo "placeholder='contoh:\n192.168.2.1 gateway.hotspot.local\n0.0.0.0 dns.google\n10.0.1.10 localserver'>";
foreach($file4 as $text) { echo $text; }
echo '</textarea></div>';
if ($useip6 == 'yes') { echo '
IPv6
<div class="areatxt"><textarea rows="10" cols="20" name="data6"'; echo "placeholder='contoh:\n::1 gateway.hotspot.local\n::2 dns.google\n::3 localservice'>";
foreach($file6 as $text) { echo $text; }
echo '</textarea></div>'; }
echo '<input type="hidden" name="hosts" value="submit">
<input type="submit" id="submit" value="Simpan" class="submit-button"/> <a href="/"> <input type="button" class="submit-button" value="Kembali"></a>
</form>
<p><small><b>&#169; 2024 Kominfo</b></small>
</div>';

echo '</div></div>';
?>