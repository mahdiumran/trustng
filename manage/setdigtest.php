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


if($_POST['setdig'] ?? null) {
    if (strpos($referer, $allowed_prefix . "setdigtest.php") !== 0 && strpos($referer, $allowed_prefix_ip . "setdigtest.php") !== 0) exit(0);

    $d0 = $_POST['d0'] ?? '';
    $d1 = $_POST['d1'] ?? '';
    $d2 = $_POST['d2'] ?? '';
    $d3 = $_POST['d3'] ?? '';
    $d4 = $_POST['d4'] ?? '';
    $d5 = $_POST['d5'] ?? '';
    $d6 = $_POST['d6'] ?? '';
    $d7 = $_POST['d7'] ?? '';
    $d8 = $_POST['d8'] ?? '';
    $d9 = $_POST['d9'] ?? '';

    $file = fopen('d0.dig', 'w');
    if ($file) { fwrite($file, "$d0"); fclose($file); }
    $file = fopen('d1.dig', 'w');
    if ($file) { fwrite($file, "$d1"); fclose($file); }
    $file = fopen('d2.dig', 'w');
    if ($file) { fwrite($file, "$d2"); fclose($file); }
    $file = fopen('d3.dig', 'w');
    if ($file) { fwrite($file, "$d3"); fclose($file); }
    $file = fopen('d4.dig', 'w');
    if ($file) { fwrite($file, "$d4"); fclose($file); }
    $file = fopen('d5.dig', 'w');
    if ($file) { fwrite($file, "$d5"); fclose($file); }
    $file = fopen('d6.dig', 'w');
    if ($file) { fwrite($file, "$d6"); fclose($file); }
    $file = fopen('d7.dig', 'w');
    if ($file) { fwrite($file, "$d7"); fclose($file); }
    $file = fopen('d8.dig', 'w');
    if ($file) { fwrite($file, "$d8"); fclose($file); }
    $file = fopen('d9.dig', 'w');
    if ($file) { fwrite($file, "$d9"); fclose($file); }
    $index = 'yes';
}

if (strpos($referer, $allowed_prefix . "digtest.php") !== 0 && strpos($referer, $allowed_prefix_ip . "digtest.php") !== 0) {
    if ($referer != "https://$myip:40443/" && $referer != "https://$myip:40443/index.php") {
        if (!isset($index) || $index !== 'yes') exit(0);
    }
}

$ipaddr = shell_exec("ifconfig eth0 | grep netmask | sed 's/ .*inet //;s/ .*//'");
$d0 = file_get_contents('d0.dig');
$d1 = file_get_contents('d1.dig');
$d2 = file_get_contents('d2.dig');
$d3 = file_get_contents('d3.dig');
$d4 = file_get_contents('d4.dig');
$d5 = file_get_contents('d5.dig');
$d6 = file_get_contents('d6.dig');
$d7 = file_get_contents('d7.dig');
$d8 = file_get_contents('d8.dig');
$d9 = file_get_contents('d9.dig');

echo '<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1"/>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" type="text/css" href="style.css" />
<title>DNS TRUST-NG - SET DIG TEST</title>
</head>
<body class="with-sidebar"><div class="page-shell">
';
include_once 'menu.php';
trustng_render_sidebar('digtest.php');
echo '
<div class="page-content"><div class="tng-topbar"><button class="tng-topbar-toggle" title="Toggle menu" aria-label="Toggle menu"><i class="fa-solid fa-bars"></i></button><span class="tng-topbar-title">DNS Inspector</span><div class="tng-topbar-spacer"></div><a class="tng-topbar-back" href="/">&#8592; Dashboard</a></div>
<div align=center>
<h3>Set Dig Test<br><small>'.$ipaddr.'</small></h3>
<form name="setdig" action="setdigtest.php" method="post">
<input type="hidden" name="setdig" value="submit">
<table>
<tr><td><input type="text" size="20" name="d0" value="'.htmlspecialchars($d0, ENT_QUOTES, 'UTF-8').'" placeholder="www.google.com"/></td></tr>
<tr><td><input type="text" name="d1" value="'.htmlspecialchars($d1, ENT_QUOTES, 'UTF-8').'" placeholder="www.facebook.com"/></td></tr>
<tr><td><input type="text" name="d2" value="'.htmlspecialchars($d2, ENT_QUOTES, 'UTF-8').'" placeholder="www.bca.co.id"/></td></tr>
<tr><td><input type="text" name="d3" value="'.htmlspecialchars($d3, ENT_QUOTES, 'UTF-8').'" placeholder="www.detik.com"/></td></tr>
<tr><td><input type="text" name="d4" value="'.htmlspecialchars($d4, ENT_QUOTES, 'UTF-8').'" placeholder="www.youtube.com"/></td></tr>
<tr><td><input type="text" name="d5" value="'.htmlspecialchars($d5, ENT_QUOTES, 'UTF-8').'" placeholder="pornhub.com"/></td></tr>
<tr><td><input type="text" name="d6" value="'.htmlspecialchars($d6, ENT_QUOTES, 'UTF-8').'" placeholder="kominfo.go.id"/></td></tr>
<tr><td><input type="text" name="d7" value="'.htmlspecialchars($d7, ENT_QUOTES, 'UTF-8').'" placeholder="reddit.com"/></td></tr>
<tr><td><input type="text" name="d8" value="'.htmlspecialchars($d8, ENT_QUOTES, 'UTF-8').'" placeholder="lamanlabuh.resolver.id"/></td></tr>
<tr><td><input type="text" name="d9" value="'.htmlspecialchars($d9, ENT_QUOTES, 'UTF-8').'" placeholder="www.tiktok.com"/></td></tr>
</table>
<input type="submit" id="submit" value="Simpan" class="submit-button"/> <a href="/"><input type="button" class="submit-button" value="Kembali"></a>
</form>
<p><small><b>&#169; 2024 Kominfo</b></small>
</div></div>
';
?>
