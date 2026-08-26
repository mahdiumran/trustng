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

if($_POST['forward'] ?? null) {
    if (strpos($referer, $allowed_prefix . "forwarder.php") !== 0 && strpos($referer, $allowed_prefix_ip . "forwarder.php") !== 0) exit(0);
    $data = $_POST['data'] ?? '';
    $file = fopen('forwarder.data', 'w');
    if ($file) { fwrite($file, "$data\n"); fclose($file); }
    shell_exec("dos2unix forwarder.data");
    shell_exec("sh setforwarder.sh");
    $file = fopen('setdns.new', 'w');
    if ($file) { fwrite($file, ''); fclose($file); }
    echo "<script>alert('Domain Forwarder telah disimpan, silahkan reload atau reboot untuk mengaktifkan');</script>";
    $index = 'yes'; $back = 'history.go(-2)';
}

if($_POST['parentfwd'] ?? null) {
    if (strpos($referer, $allowed_prefix . "forwarder.php") !== 0 && strpos($referer, $allowed_prefix_ip . "forwarder.php") !== 0) exit(0);
    $res1 = $_POST['res1'] ?? '';
    $res2 = $_POST['res2'] ?? '';
    $res3 = $_POST['res3'] ?? '';
    $res4 = $_POST['res4'] ?? '';
    $res5 = $_POST['res5'] ?? '';
    $res6 = $_POST['res6'] ?? '';
    $file = fopen('resolver.data', 'w');
    if ($file) { fwrite($file, "$res1,$res2,$res3,$res4,$res5,$res6"); fclose($file); }
    shell_exec("dos2unix resolver.data");
    if ( $res1 == '' && $res2 == '' && $res3 == '' && $res4 == '' && $res5 == '' && $res6 == '') {
        $file = fopen('/etc/unbound/parent.conf', 'w');
        if ($file) { fwrite($file, ''); fclose($file); }
    } else {
        shell_exec("sh setresolver.sh");
    }

    $file = fopen('setdns.new', 'w');
    if ($file) { fwrite($file, ''); fclose($file); }
    echo "<script>alert('Parent Resolver telah disimpan, silahkan reload atau reboot untuk mengaktifkan');</script>";
    $index = 'yes'; $back = 'history.go(-2)';
}

if ($referer != "https://$myip:40443/" && $referer != "https://$myip:40443/index.php") {
        if (!isset($index) || $index !== 'yes') exit(0);
}

$file = file("forwarder.data");
$resolver = file_get_contents("resolver.data");
$rdata = explode(",", $resolver);
$res1 = trim($rdata[0]);
$res2 = trim($rdata[1]);
$res3 = trim($rdata[2]);
$res4 = trim($rdata[3]);
$res5 = trim($rdata[4]);
$res6 = trim($rdata[5]);
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
<title>DNS TRUST-NG - DNS FORWARDER</title>
<script src="kunci.js"></script>
</head>
<body class="with-sidebar sidebar-collapsed">
<div id="sidebar-overlay"></div>
<div class="page-shell">';
include_once 'menu.php';
trustng_render_sidebar('forwarder.php');

echo '<div class="page-content">';
echo '<div class="tng-topbar"><button class="tng-topbar-toggle" title="Toggle menu" aria-label="Toggle menu"><svg width="20" height="20" viewBox="0 0 20 20" fill="none"><rect x="2" y="4.5" width="16" height="2" rx="1" fill="currentColor"/><rect x="2" y="9" width="16" height="2" rx="1" fill="currentColor"/><rect x="2" y="13.5" width="16" height="2" rx="1" fill="currentColor"/></svg></button><span class="tng-topbar-title">Forwarder DNS</span><div class="tng-topbar-spacer"></div><a class="tng-topbar-back" href="/">&#8592; Dashboard</a></div>';
echo '
<div align=center>
<a href="/"><img src="img/trustng-small.jpg" width="200px"></a>
<form name="domforward" action="forwarder.php" method="post">
<input type="hidden" name="forward" value="submit">
<p>
<h3>Forwarder<br><small>'.$ipaddr.'</small></h3>
<b>Domain Forwarder</b><br>
<small>Format: domain_name,ip_resolver1,ip_resolver2,ip_resolver3<br>warning, salah isi dns bisa tidak berfungsi!</small><br>
<div class="areatxt"><textarea rows="8" cols="16" name="data" autofocus="autofocus" placeholder="'; echo "contoh:\nfacebook.com,8.8.8.8,8.8.4.4,1.1.1.1\nakamai.com,1.1.1.1@153,9.9.9.9@253\ngoogle.com,::1,::2@253,::3\nyahoo.com,::1,::2,::3\">";
foreach($file as $text) { echo $text; }
echo '</textarea></div>
<input type="submit" id="submit" value="Simpan" class="submit-button"/> <a href="/"> <input type="button" class="submit-button" value="Kembali"></a>
</form>

<form name="forward" action="forwarder.php" method="post">
<p>
<b>Parent Resolver</b><br>
<small>Format: ip_resolver atau ip_resolver@port<br>warning, salah isi dns bisa tidak berfungsi!</small><br>
<input type="hidden" name="parentfwd" value="submit">
Resolver1: <input style="display: inline;" type="text" size="15" name="res1" value="'.$res1.'" placeholder="1.2.3.4" /><br>
Resolver2: <input style="display: inline;" type="text" size="15" name="res2" value="'.$res2.'" placeholder="2.3.4.5@5353" /><br>
Resolver3: <input style="display: inline;" type="text" size="15" name="res3" value="'.$res3.'" placeholder="3.4.5.6@253" /><br>
Resolver4: <input style="display: inline;" type="text" size="15" name="res4" value="'.$res4.'" placeholder="::1" /><br>
Resolver5: <input style="display: inline;" type="text" size="15" name="res5" value="'.$res5.'" placeholder="::2@153" /><br>
Resolver6: <input style="display: inline;" type="text" size="15" name="res6" value="'.$res6.'" placeholder="::2" /><br>
<input type="submit" id="submit" value="Simpan" class="submit-button"/> <a href="/"> <input type="button" class="submit-button" value="Kembali"></a>
</form>
<small><b>&#169; 2024 Kominfo</b></small>
</div>';

echo '</div></div>';
?>