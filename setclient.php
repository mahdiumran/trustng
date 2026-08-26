<?php
error_reporting(0);
$back = 'history.back()';
$myip = $_SERVER['SERVER_ADDR'];
$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
$http_host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : "$myip:40443";
$proto = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$allowed_prefix = "$proto://$http_host/";
$allowed_prefix_ip = "https://$myip:40443/";


function isValidCIDR4($cidr)
{
    $parts = explode('/', $cidr);
    // it should have only two parts
    if(count($parts) != 2) {
        return false;
    }

    $ip = $parts[0];
    $cuk = $parts[1];
    $netmask = intval($parts[1]);

    if($cuk == '') {
        return false;
    }

    if($netmask < 0) {
        return false;
    }

    // check if it is a valid IPv4
    if(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        // netmask for IPv4 should be less than 32
        return $netmask <= 32;
    }

    // well, if no match, then it is an invalid CIDR string
    return false;
}

function isValidCIDR6($cidr)
{
    $parts = explode('/', $cidr);
    // it should have only two parts
    if(count($parts) != 2) {
        return false;
    }

    $ip = $parts[0];
    $cuk = $parts[1];
    $netmask = intval($parts[1]);

    if($cuk == '') {
        return false;
    }

    if($netmask < 0) {
        return false;
    }

    // check if it is a valid IPv6
    if(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        // netmask should be less than 128
        return $netmask <= 128;
    }

    // well, if no match, then it is an invalid CIDR string
    return false;
}
if($_POST['data'] ?? null) {
    if (strpos($referer, $allowed_prefix . "setclient.php") !== 0 && strpos($referer, $allowed_prefix_ip . "setclient.php") !== 0) exit(0);
    $data4 = $_POST['data'] ?? '';
    $data4 = str_replace(';', '', $data4);
    $data6 = $_POST['data6'] ?? '';
    $data6 = str_replace(';', '', $data6);

    $file = fopen('clients.ip', 'w');
    if ($file) { fwrite($file, "127.0.0.0/8\n$data4"); fclose($file); }
    shell_exec("dos2unix clients.ip");
    $file = fopen('clients6.ip', 'w');
    if ($file) { fwrite($file, "::1/128\n$data6"); fclose($file); }
    shell_exec("dos2unix clients6.ip");

    $lines = file('clients.ip');
    $lines = array_unique($lines);
    file_put_contents('clients.ip', implode($lines));
    $subject = file_get_contents('clients.ip');
    foreach(preg_split("/((\r?\n)|(\r\n?))/", $subject) as $line){
	if (isValidCIDR4($line)) {
            $problem4 = 'no';
	} else {
	    echo "<script>alert('$line (ipv4) tidak valid');history.back();</script>";
	    $problem4 = 'yes';
	}
    }
    $lines = file('clients6.ip');
    $lines = array_unique($lines);
    file_put_contents('clients6.ip', implode($lines));
    $subject = file_get_contents('clients6.ip');
    foreach(preg_split("/((\r?\n)|(\r\n?))/", $subject) as $line){
      if($line != '') {
        if (isValidCIDR6($line)) {
            $problem6 = 'no';
        } else {
            echo "<script>alert('$line (ipv6) tidak valid');history.back();</script>";
            $problem6 = 'yes';
        }
      }
    }

    if ($problem4 != 'yes') {
	$data4 = file_get_contents('clients.ip');
	$data4 = preg_replace('#\s+#',', ',trim($data4));
        shell_exec("echo \"elements = { $data4 }\" > /etc/client_set");
        $file = fopen('setclient.new', 'w');
        if ($file) { fwrite($file, ''); fclose($file); }
        echo "<script>alert('recursive clients ipv4 telah diubah, silahkan reload atau reboot untuk mengaktifkan');</script>";
    }
    if ($problem6 != 'yes') {
        $data6 = file_get_contents('clients6.ip');
	$data6 = preg_replace('#\s+#',', ',trim($data6));
        shell_exec("echo \"elements = { $data6 }\" > /etc/client6_set");
        $file = fopen('setclient.new', 'w');
        if ($file) { fwrite($file, ''); fclose($file); }
        echo "<script>alert('recursive clients ipv4 telah diubah, silahkan reload atau reboot untuk mengaktifkan');</script>";
    }

    $index = 'yes'; $back = 'history.go(-2)';
}

if (strpos($referer, $allowed_prefix) !== 0 && strpos($referer, $allowed_prefix_ip) !== 0) {
        if (!isset($index) || $index !== 'yes') {
            $dashboard_ref = "https://$myip:40443/";
            if (strpos($referer, $dashboard_ref) !== 0 && strpos($referer, $allowed_prefix . "index.php") !== 0) exit(0);
        }
}

$file4 = file("clients.ip");
$file6 = file("clients6.ip");
$ipaddr = shell_exec("ifconfig eth0 | grep netmask | sed 's/ .*inet //;s/ .*//'");
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
<title>DNS TRUST-NG - ACL CLIENTS</title>
<script src="kunci.js"></script>
</head>
<body class="with-sidebar sidebar-collapsed">
<div id="sidebar-overlay"></div>
<div class="page-shell">';
include_once 'menu.php';
trustng_render_sidebar('setclient.php');

echo '<div class="page-content">';
echo '<div class="tng-topbar"><button class="tng-topbar-toggle" title="Toggle menu" aria-label="Toggle menu"><svg width="20" height="20" viewBox="0 0 20 20" fill="none"><rect x="2" y="4.5" width="16" height="2" rx="1" fill="currentColor"/><rect x="2" y="9" width="16" height="2" rx="1" fill="currentColor"/><rect x="2" y="13.5" width="16" height="2" rx="1" fill="currentColor"/></svg></button><span class="tng-topbar-title">ACL Clients</span><div class="tng-topbar-spacer"></div><a class="tng-topbar-back" href="/">&#8592; Dashboard</a></div>';
echo '
<div align=center>
<a href="/"><img src="img/logo-img/trust-ng.jpg" width="200px"></a>
<form name="client" action="setclient.php" method="post">
<p>
<h3>ACL Recursive Clients<br><small>'.$ipaddr.'</small></h3>
<small>Format: ip_address/cidr per baris, tanpa titik koma (;)<br>warning, jangan asal copas! syntax harus benar</small><br>
IPv4
<div class="areatxt2"><textarea rows="10" cols="20" name="data" onkeyup="checkIPList(this);" autofocus="autofocus"'; echo "placeholder='contoh:\n127.0.0.0/8\n192.168.0.0/16\n172.16.0.0/12\n10.0.0.0/8'>";
foreach($file4 as $text) { echo $text; }
echo '</textarea></div>';
if ($useip6 == 'yes') { echo '
IPv6
<div class="areatxt2"><textarea rows="10" cols="20" name="data6" autofocus="autofocus"'; echo "placeholder='contoh:\n::1/64\n::2/64\n::3/64'>";
foreach($file6 as $text) { echo $text; }
echo '</textarea></div>';
}
echo '
<input type="submit" id="submit" value="Simpan" class="submit-button"/>  <input type="button" onclick="'.$back.'" class="submit-button" value="Kembali">
</form>
<p><small><b>&#169; 2024 Kominfo</small></b>
</div>';

echo '</div></div>';
?>