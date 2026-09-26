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

if($_POST['forward'] ?? null) {
    if (strpos($referer, $allowed_prefix . "forwarder.php") !== 0 && strpos($referer, $allowed_prefix_ip . "forwarder.php") !== 0) exit(0);
    $data = $_POST['data'] ?? '';
    trustng_state_write('forwarder.data', str_replace("\r\n", "\n", $data) . "\n");
    trustng_run_panel_script('setforwarder.sh');
    trustng_state_touch('setdns.new');
    $notice = 'Domain Forwarder telah disimpan. Jalankan Maintenance → Reload untuk mengaktifkan perubahan.';
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
    trustng_state_write('resolver.data', "$res1,$res2,$res3,$res4,$res5,$res6");
    if ( $res1 == '' && $res2 == '' && $res3 == '' && $res4 == '' && $res5 == '' && $res6 == '') {
        // clear parent.conf via sudo (www-data cannot write /etc/unbound directly)
        shell_exec('sudo -n sh -c ' . escapeshellarg('truncate -c -s 0 /etc/unbound/parent.conf; chown unbound:unbound /etc/unbound/parent.conf 2>/dev/null || true'));
    } else {
        trustng_run_panel_script('setresolver.sh');
    }

    trustng_state_touch('setdns.new');
    $notice = 'Parent Resolver telah disimpan. Jalankan Maintenance → Reload untuk mengaktifkan perubahan.';
    $index = 'yes'; $back = 'history.go(-2)';
}

if (strpos($referer, $allowed_prefix) !== 0 && strpos($referer, $allowed_prefix_ip) !== 0) {
    if (!isset($index) || $index !== 'yes') exit(0);
}

$file = trustng_state_lines('forwarder.data');
$resolver = trustng_state_read('resolver.data', ',,,,,');
$rdata = explode(",", $resolver);
$res1 = trim($rdata[0]);
$res2 = trim($rdata[1]);
$res3 = trim($rdata[2]);
$res4 = trim($rdata[3]);
$res5 = trim($rdata[4]);
$res6 = trim($rdata[5]);
$useip6 = file_get_contents('setip6');

tng_ui_page_start('forwarder.php', 'Forwarder', 'Domain forwarder dan parent resolver. IP referensi: ' . trim($ipaddr));
if (!empty($notice)) tng_ui_notice('success', 'Perubahan tersimpan', $notice);

tng_ui_card_start('Domain Forwarder', 'Format: domain_name,ip_resolver1,ip_resolver2,ip_resolver3. Warning, salah isi DNS bisa tidak berfungsi.');
echo '<form name="domforward" action="forwarder.php" method="post">
<input type="hidden" name="forward" value="submit">
<div class="tng-field field"><div class="areatxt"><textarea rows="8" cols="16" name="data" autofocus="autofocus" placeholder="contoh:
facebook.com,8.8.8.8,8.8.4.4,1.1.1.1
akamai.com,1.1.1.1@153,9.9.9.9@253
google.com,::1,::2@253,::3
yahoo.com,::1,::2,::3">';
foreach($file as $text) { echo htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); }
echo '</textarea></div></div>
<div class="form-actions di-actions">
  <input type="submit" id="submit-domain-forwarder" value="Simpan" class="submit-button"/>
  <a class="submit-button button-secondary" href="/">Kembali</a>
</div>
</form>';
tng_ui_card_end();

tng_ui_card_start('Parent Resolver', 'Format: ip_resolver atau ip_resolver@port. Warning, salah isi DNS bisa tidak berfungsi.');
echo '<form name="forward" action="forwarder.php" method="post">
<input type="hidden" name="parentfwd" value="submit">
  <div class="field-grid">
  <div class="tng-field field"><label for="parent-resolver-1">Resolver 1</label><input id="parent-resolver-1" type="text" name="res1" class="form__w" value="'.tng_e($res1).'" placeholder="1.2.3.4" /></div>
  <div class="tng-field field"><label for="parent-resolver-2">Resolver 2</label><input id="parent-resolver-2" type="text" name="res2" class="form__w" value="'.tng_e($res2).'" placeholder="2.3.4.5@5353" /></div>
  <div class="tng-field field"><label for="parent-resolver-3">Resolver 3</label><input id="parent-resolver-3" type="text" name="res3" class="form__w" value="'.tng_e($res3).'" placeholder="3.4.5.6@253" /></div>
  <div class="tng-field field"><label for="parent-resolver-4">Resolver 4</label><input id="parent-resolver-4" type="text" name="res4" class="form__w" value="'.tng_e($res4).'" placeholder="::1" /></div>
  <div class="tng-field field"><label for="parent-resolver-5">Resolver 5</label><input id="parent-resolver-5" type="text" name="res5" class="form__w" value="'.tng_e($res5).'" placeholder="::2@153" /></div>
  <div class="tng-field field"><label for="parent-resolver-6">Resolver 6</label><input id="parent-resolver-6" type="text" name="res6" class="form__w" value="'.tng_e($res6).'" placeholder="::2" /></div>
</div>
<div class="form-actions di-actions">
  <input type="submit" id="submit-parent-resolver" value="Simpan" class="submit-button"/>
  <a class="submit-button button-secondary" href="/">Kembali</a>
</div>
</form>';
tng_ui_card_end();

echo '<script src="kunci.js"></script>';
tng_ui_page_end('forwarder.php');
?>