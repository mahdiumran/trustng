<?php
require_once __DIR__ . '/includes/ui.php';
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

tng_ui_page_start('digtest.php', 'DNS Inspector', 'Konfigurasi 10 domain untuk pengujian resolusi. IP referensi: ' . trim($ipaddr));
tng_ui_card_start('Set Dig Test', 'Isi 10 domain yang akan diuji pada halaman DNS Inspector.');
echo '<form name="setdig" action="setdigtest.php" method="post">
<input type="hidden" name="setdig" value="submit">
<div class="field-grid">
  <div class="tng-field field"><label for="dig-domain-0">Domain 1</label><input id="dig-domain-0" type="text" size="20" name="d0" class="form__w" value="'.htmlspecialchars($d0, ENT_QUOTES, 'UTF-8').'" placeholder="www.google.com"/></div>
  <div class="tng-field field"><label for="dig-domain-1">Domain 2</label><input id="dig-domain-1" type="text" name="d1" class="form__w" value="'.htmlspecialchars($d1, ENT_QUOTES, 'UTF-8').'" placeholder="www.facebook.com"/></div>
  <div class="tng-field field"><label for="dig-domain-2">Domain 3</label><input id="dig-domain-2" type="text" name="d2" class="form__w" value="'.htmlspecialchars($d2, ENT_QUOTES, 'UTF-8').'" placeholder="www.bca.co.id"/></div>
  <div class="tng-field field"><label for="dig-domain-3">Domain 4</label><input id="dig-domain-3" type="text" name="d3" class="form__w" value="'.htmlspecialchars($d3, ENT_QUOTES, 'UTF-8').'" placeholder="www.detik.com"/></div>
  <div class="tng-field field"><label for="dig-domain-4">Domain 5</label><input id="dig-domain-4" type="text" name="d4" class="form__w" value="'.htmlspecialchars($d4, ENT_QUOTES, 'UTF-8').'" placeholder="www.youtube.com"/></div>
  <div class="tng-field field"><label for="dig-domain-5">Domain 6</label><input id="dig-domain-5" type="text" name="d5" class="form__w" value="'.htmlspecialchars($d5, ENT_QUOTES, 'UTF-8').'" placeholder="pornhub.com"/></div>
  <div class="tng-field field"><label for="dig-domain-6">Domain 7</label><input id="dig-domain-6" type="text" name="d6" class="form__w" value="'.htmlspecialchars($d6, ENT_QUOTES, 'UTF-8').'" placeholder="kominfo.go.id"/></div>
  <div class="tng-field field"><label for="dig-domain-7">Domain 8</label><input id="dig-domain-7" type="text" name="d7" class="form__w" value="'.htmlspecialchars($d7, ENT_QUOTES, 'UTF-8').'" placeholder="reddit.com"/></div>
  <div class="tng-field field"><label for="dig-domain-8">Domain 9</label><input id="dig-domain-8" type="text" name="d8" class="form__w" value="'.htmlspecialchars($d8, ENT_QUOTES, 'UTF-8').'" placeholder="lamanlabuh.resolver.id"/></div>
  <div class="tng-field field"><label for="dig-domain-9">Domain 10</label><input id="dig-domain-9" type="text" name="d9" class="form__w" value="'.htmlspecialchars($d9, ENT_QUOTES, 'UTF-8').'" placeholder="www.tiktok.com"/></div>
</div>
<div class="form-actions di-actions">
  <input type="submit" id="submit" value="Simpan" class="submit-button"/>
  <a class="submit-button button-secondary" href="/">Kembali</a>
</div>
</form>';
tng_ui_card_end();

tng_ui_page_end('digtest.php');
?>
