<?php
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


if($_POST['lp1'] ?? null) {
    if (strpos($referer, $allowed_prefix . "setlp.php") !== 0 && strpos($referer, $allowed_prefix_ip . "setlp.php") !== 0) exit(0);
    $lp1 = $_POST['lp1'] ?? '';
    $lp2 = $_POST['lp2'] ?? '';
    $lp3 = $_POST['lp3'] ?? '';
    $lp4 = $_POST['lp4'] ?? '';
    $lp5 = $_POST['lp5'] ?? '';
    $lp6 = $_POST['lp6'] ?? '';

    if($lp1 == '') { $lp1 = '10.150.1.18'; }

    if (!filter_var($lp1, FILTER_VALIDATE_IP)) { $lp1 = '10.150.1.18'; }
    if (!filter_var($lp2, FILTER_VALIDATE_IP)) { $lp2 = ''; }
    if (!filter_var($lp3, FILTER_VALIDATE_IP)) { $lp3 = ''; }
    if (!filter_var($lp4, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) { $lp4 = ''; }
    if (!filter_var($lp5, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) { $lp5 = ''; }
    if (!filter_var($lp6, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) { $lp6 = ''; }

    // Save to panel state files (www-data writable)
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

    // Create flag for reload.php to generate lamanlabuh.conf (runs as root via sudo)
    $file = fopen('setdns.new', 'w');
    if ($file) { fwrite($file, ''); fclose($file); }
    $notice = 'IP lamanlabuh telah disimpan. Jalankan Maintenance → Reload untuk mengaktifkan perubahan.';
    $index = 'yes'; $back = 'history.go(-2)';
}

if (strpos($referer, $allowed_prefix) !== 0 && strpos($referer, $allowed_prefix_ip) !== 0) {
        if (!isset($index) || $index !== 'yes') {
            $dashboard_ref = "https://$myip:40443/";
            if (strpos($referer, $dashboard_ref) !== 0 && strpos($referer, $allowed_prefix . "index.php") !== 0) exit(0);
        }
}

if (isset($_GET['default']) && $_GET['default'] == 'yes') {
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

tng_ui_page_start('setlp.php', 'Lamanlabuh', 'Landing page untuk situs yang diblokir oleh Trust+. Format: ip_address, bukan cname.');
if (!empty($notice)) tng_ui_notice('success', 'Perubahan tersimpan', $notice);
tng_ui_card_start('Lamanlabuh', 'Alamat landing page per keluarga IP. IP referensi: ' . trim($ipaddr));

echo '<form name="setlp" action="setlp.php" method="post">
<div class="set-section">
  <div class="set-section-head"><span class="set-section-title">IPv4</span></div>
  <div class="field-grid three">
    <div class="tng-field field"><label for="landing-ipv4-1">Lamanlabuh 1</label><input id="landing-ipv4-1" type="text" name="lp1" class="form__w" value="'.tng_e($lp1).'" placeholder="(ip4-1)" required /></div>
    <div class="tng-field field"><label for="landing-ipv4-2">Lamanlabuh 2</label><input id="landing-ipv4-2" type="text" name="lp2" class="form__w" value="'.tng_e($lp2).'" placeholder="(ip4-2)" /></div>
    <div class="tng-field field"><label for="landing-ipv4-3">Lamanlabuh 3</label><input id="landing-ipv4-3" type="text" name="lp3" class="form__w" value="'.tng_e($lp3).'" placeholder="(ip4-3)" /></div>
  </div>
</div>';
if ($useip6 == 'yes') { echo '
<div class="set-section">
  <div class="set-section-head"><span class="set-section-title">IPv6</span></div>
  <div class="field-grid three">
    <div class="tng-field field"><label for="landing-ipv6-1">Lamanlabuh 4</label><input id="landing-ipv6-1" type="text" name="lp4" class="form__w" value="'.tng_e($lp4).'" placeholder="ipv6-1" /></div>
    <div class="tng-field field"><label for="landing-ipv6-2">Lamanlabuh 5</label><input id="landing-ipv6-2" type="text" name="lp5" class="form__w" value="'.tng_e($lp5).'" placeholder="ipv6-2" /></div>
    <div class="tng-field field"><label for="landing-ipv6-3">Lamanlabuh 6</label><input id="landing-ipv6-3" type="text" name="lp6" class="form__w" value="'.tng_e($lp6).'" placeholder="ipv6-3" /></div>
  </div>
</div>';
}
echo '
<div class="form-actions di-actions">
  <input type="submit" id="submit" value="Simpan" class="submit-button"/>
  <a class="submit-button button-secondary" href="setlp.php?default=yes">Default</a>
  <input type="button" onclick="'.$back.'" class="submit-button button-secondary" value="Kembali">
</div>
</form>';
tng_ui_card_end();

tng_ui_page_end('setlp.php');
?>
