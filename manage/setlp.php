<?php
error_reporting(0);
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
$back = 'history.back()';
$myip = $_SERVER['SERVER_ADDR'];
$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
$http_host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : "$myip:40443";
$proto = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$allowed_prefix = "$proto://$http_host/";
$allowed_prefix_ip = "https://$myip:40443/";


if($_POST['lp1'] ?? null) {
    if (strpos($referer, $allowed_prefix . "setlp.php") !== 0 && strpos($referer, $allowed_prefix_ip . "setlp.php") !== 0) exit(0);
    $lp1 = $_POST['lp1'] ?? '';
    $lp2 = $_POST['lp2'] ?? '';
    $lp3 = $_POST['lp3'] ?? '';
    $lp4 = $_POST['lp4'] ?? '';
    $lp5 = $_POST['lp5'] ?? '';
    $lp6 = $_POST['lp6'] ?? '';

    if($lp1 == '') { $lp1 = '139.255.196.196'; }

    if (filter_var($lp1, FILTER_VALIDATE_IP)) {
	shell_exec("echo local-data: \'blacklist. 60 IN A $lp1\' > /etc/unbound/lamanlabuh.conf");
    }
    if (filter_var($lp2, FILTER_VALIDATE_IP)) {
        shell_exec("echo local-data: \'blacklist. 60 IN A $lp2\' >> /etc/unbound/lamanlabuh.conf");
    } else {
	$lp2 = '';
    }
    if (filter_var($lp3, FILTER_VALIDATE_IP)) {
        shell_exec("echo local-data: \'blacklist. 60 IN A $lp3\' >> /etc/unbound/lamanlabuh.conf");
    } else {
        $lp3 = '';
    }
    if (filter_var($lp4, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        shell_exec("echo local-data: \'blacklist. 60 IN AAAA $lp4\' >> /etc/unbound/lamanlabuh.conf");
    } else {
        $lp4 = '';
    }

    if (filter_var($lp5, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        shell_exec("echo local-data: \'blacklist. 60 IN AAAA $lp5\' >> /etc/unbound/lamanlabuh.conf");
    } else {
        $lp5 = '';
    }
    if (filter_var($lp6, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        shell_exec("echo local-data: \'blacklist. 60 IN AAAA $lp6\' >> /etc/unbound/lamanlabuh.conf");
    } else {
        $lp6 = '';
    }

    $file = fopen('lp1.ip', 'w');
    if ($file) { fwrite($file, "$lp1"); fclose($file); }
    $file = fopen('lp2.ip', 'w');
    if ($file) { fwrite($file, "$lp2"); fclose($file); }
    $file = fopen('lp3.ip', 'w');
    if ($file) { fwrite($file, "$lp3"); fclose($file); }
    $file = fopen('lp4.ip', 'w');
    if ($file) { fwrite($file, "$lp4"); fclose($file); }
    $file = fopen('lp5.ip', 'w');
    if ($file) { fwrite($file, "$lp5"); fclose($file); }
    $file = fopen('lp6.ip', 'w');
    if ($file) { fwrite($file, "$lp6"); fclose($file); }

    $file = fopen('setdns.new', 'w');
    if ($file) { fwrite($file, ''); fclose($file); }
    echo "<script>alert('ip lamanlabuh telah diubah, silahkan reload atau reboot untuk mengaktifkan');</script>";
    $index = 'yes'; $back = 'history.go(-2)';
}

if (strpos($referer, $allowed_prefix) !== 0 && strpos($referer, $allowed_prefix_ip) !== 0) {
        if (!isset($index) || $index !== 'yes') {
            $dashboard_ref = "https://$myip:40443/";
            if (strpos($referer, $dashboard_ref) !== 0 && strpos($referer, $allowed_prefix . "index.php") !== 0) exit(0);
        }
}

if ($_GET['default'] == 'yes') {
    $lp1 = '';
    $lp2 = '';
    $lp3 = '';
    $lp4 = '';
    $lp5 = '';
    $lp6 = '';
} else {
    $lp1 = file_get_contents('lp1.ip');
    $lp2 = file_get_contents('lp2.ip');
    $lp3 = file_get_contents('lp3.ip');
    $lp4 = file_get_contents('lp4.ip');
    $lp5 = file_get_contents('lp5.ip');
    $lp6 = file_get_contents('lp6.ip');
}
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
<title>DNS TRUST-NG - LAMANLABUH</title>
</head>
<body class="with-sidebar sidebar-collapsed">
<div id="sidebar-overlay"></div>
<div class="page-shell">
';
include_once 'menu.php';
trustng_render_sidebar('setlp.php');

echo '<div class="page-content">';
echo '<div class="tng-topbar"><button class="tng-topbar-toggle" title="Toggle menu" aria-label="Toggle menu"><svg width="20" height="20" viewBox="0 0 20 20" fill="none"><rect x="2" y="4.5" width="16" height="2" rx="1" fill="currentColor"/><rect x="2" y="9" width="16" height="2" rx="1" fill="currentColor"/><rect x="2" y="13.5" width="16" height="2" rx="1" fill="currentColor"/></svg></button><span class="tng-topbar-title">Lamanlabuh</span><div class="tng-topbar-spacer"></div><a class="tng-topbar-back" href="/">&#8592; Dashboard</a></div>';
echo '
<div align=center>
<a href="/"><img src="img/logo-img/trust-ng.jpg" width="200px"></a>
<form name="setlp" action="setlp.php" method="post">
<p/>
<h3>Lamanlabuh<br><small>'.$ipaddr.'</small></h3>
<small>Landing page untuk situs yang diblokir oleh Trust+<br>format: ip_address bukan cname</small><br>
IPv4
<input type="text" name="lp1" class="form__w" value="'.$lp1.'"placeholder="(ip4-1)" required />
<input type="text" name="lp2" class="form__w" value="'.$lp2.'" placeholder="(ip4-2)" />
<input type="text" name="lp3" class="form__w" value="'.$lp3.'" placeholder="(ip4-3)" />';
if ($useip6 == 'yes') { echo '
IPv6
<input type="text" name="lp4" class="form__w" value="'.$lp4.'"placeholder="ipv6-1" />
<input type="text" name="lp5" class="form__w" value="'.$lp5.'" placeholder="ipv6-2" />
<input type="text" name="lp6" class="form__w" value="'.$lp6.'" placeholder="ipv6-3" />';
}
echo '
<input type="submit" id="submit" value="Simpan" class="submit-button"/> <a href="setlp.php?default=yes"><input type="button" class="submit-button" value="Default"></a> <input type="button"  onclick="'.$back.'" class="submit-button" value="Kembali">
</form>
<p><small><b>&#169; 2024 Kominfo</b></small>
</div>';

echo '</div></div>';
?>
