<?php
require_once __DIR__ . '/includes/state_store.php';
require_once __DIR__ . '/includes/ui.php';
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

$ipaddr = shell_exec("ifconfig eth0 | grep netmask | sed 's/ .*inet //;s/ .*//'");

if($_POST['hosts'] ?? null) {
    if (strpos($referer, $allowed_prefix . "hosts.php") !== 0 && strpos($referer, $allowed_prefix_ip . "hosts.php") !== 0) exit(0);
    $data4 = $_POST['data'] ?? '';
    $data6 = $_POST['data6'] ?? '';
    trustng_state_write('hosts.data', str_replace("\r\n", "\n", $data4) . "\n");
    trustng_state_write('hosts6.data', str_replace("\r\n", "\n", $data6) . "\n");
    trustng_run_panel_script('sethosts.sh');
    trustng_state_touch('setdns.new');
    $notice = 'Hosts File telah disimpan. Jalankan Maintenance → Reload untuk mengaktifkan perubahan.';
    $index = 'yes'; $back = 'history.go(-2)';
}

if (strpos($referer, $allowed_prefix) !== 0 && strpos($referer, $allowed_prefix_ip) !== 0) {
    if (!isset($index) || $index !== 'yes') exit(0);
}

$file4 = trustng_state_lines('hosts.data');
$file6 = trustng_state_lines('hosts6.data');
$useip6 = file_get_contents('setip6');

tng_ui_page_start('hosts.php', 'Hosts File', 'Override DNS lokal. Format hosts file: ip_address domain_name. IP referensi: ' . trim($ipaddr));
if (!empty($notice)) tng_ui_notice('success', 'Perubahan tersimpan', $notice);
tng_ui_card_start('Hosts File', 'Warning, salah isi DNS bisa tidak berfungsi.');

echo '<form name="domforward" action="hosts.php" method="post">';
echo '<div class="tng-field field"><label>IPv4</label><div class="areatxt"><textarea rows="10" cols="20" name="data" autofocus="autofocus" placeholder="contoh:
192.168.2.1 gateway.hotspot.local
0.0.0.0 dns.google
10.0.1.10 localserver">';
foreach($file4 as $text) { echo htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); }
echo '</textarea></div></div>';
if ($useip6 == 'yes') { echo '
<div class="tng-field field"><label>IPv6</label><div class="areatxt"><textarea rows="10" cols="20" name="data6" placeholder="contoh:
::1 gateway.hotspot.local
::2 dns.google
::3 localservice">';
foreach($file6 as $text) { echo htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); }
echo '</textarea></div></div>'; }
echo '
<input type="hidden" name="hosts" value="submit">
<div class="form-actions di-actions">
  <input type="submit" id="submit" value="Simpan" class="submit-button"/>
  <a class="submit-button button-secondary" href="/">Kembali</a>
</div>
</form>';
tng_ui_card_end();

echo '<script src="kunci.js"></script>';
tng_ui_page_end('hosts.php');
?>