<?php
error_reporting(0);
$myip = $_SERVER['SERVER_ADDR'];
$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
$http_host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : "$myip:40443";
$proto = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$allowed_prefix = "$proto://$http_host/";
$allowed_prefix_ip = "https://$myip:40443/";


if ($_POST['carikey'] ?? null) {
    if (strpos($referer, $allowed_prefix . "dbtrust.php") !== 0 && strpos($referer, $allowed_prefix_ip . "dbtrust.php") !== 0) exit;
    $keyword = $_POST['caridb'] ?? '';
    shell_exec("sudo grep '$keyword' /etc/unbound/db/trust.txt > hasilcari.txt");
    include 'hasilcari.php';
    exit(0);
}

if ($_POST['caridom'] ?? null) {
    if (strpos($referer, $allowed_prefix . "dbtrust.php") !== 0 && strpos($referer, $allowed_prefix_ip . "dbtrust.php") !== 0) exit;
    $search =  $_POST['caridb'] ?? '';

    function wireformat($domain) {
      $w = "";
      foreach($domain as $bit)
        $w = $w.chr(strlen($bit)).$bit;
      $w = $w.chr(0);
      return($w);
    }
    include 'hasilcari2.php';
    exit(0);
}

if ($referer != "https://$myip:40443/" && $referer != "https://$myip:40443/index.php") {
        if (!isset($index) || $index !== 'yes') exit(0);
}

$last = shell_exec("stat -c %z /etc/unbound/db/trust.txt | cut -d. -f1");
sleep (0.3);
$jumlah = file_get_contents('/etc/unbound/db/trust.count');
$ipaddr = shell_exec("ifconfig eth0 | grep netmask | sed 's/ .*inet //;s/ .*//'");

echo '<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1"/>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" type="text/css" href="style.css" />
<title>DNS TRUST-NG - DB Trust+</title>
</head>
<body class="with-sidebar sidebar-collapsed">
<div id="sidebar-overlay"></div>
<div class="page-shell">';
include_once 'menu.php';
trustng_render_sidebar('dbtrust.php');

echo '<div class="page-content">';
echo '<div class="tng-topbar"><button class="tng-topbar-toggle" title="Toggle menu" aria-label="Toggle menu"><svg width="20" height="20" viewBox="0 0 20 20" fill="none"><rect x="2" y="4.5" width="16" height="2" rx="1" fill="currentColor"/><rect x="2" y="9" width="16" height="2" rx="1" fill="currentColor"/><rect x="2" y="13.5" width="16" height="2" rx="1" fill="currentColor"/></svg></button><span class="tng-topbar-title">Database Trust+</span><div class="tng-topbar-spacer"></div><a class="tng-topbar-back" href="/">&#8592; Dashboard</a></div>';
echo '
<div align=center>
<a href="/"><img src="img/logo-img/trust-ng.jpg" width="200px"></a>
<form name="lookup" action="dbtrust.php" method="post">
<p>
<h3>DATABASE Trust+<br><small>'.$ipaddr.'</small></h3>
Perubahan terakhir: '.$last.' WIB<br>
Jumlah baris Trust+: '.$jumlah.'<br><br>
<input type="text" name="caridb" class="form__w" placeholder="masukkan kata pencarian" required />
<input type="submit" name="carikey" id="submit" value="Cari Keyword" class="submit-button"/> <input type="submit" name="caridom" id="submit" value="Cari Domain" class="submit-button"/> <input type="button" onclick="history.back()" class="submit-button" value="Kembali">
</form>
<small><b>&#169; 2024 Kominfo</b></small>
</div>';

echo '</div></div>';
?>