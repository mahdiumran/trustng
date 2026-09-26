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
if (array_key_exists('data', $_POST)) {
    if (strpos($referer, $allowed_prefix . "setclient.php") !== 0 && strpos($referer, $allowed_prefix_ip . "setclient.php") !== 0) exit(0);
    $data4 = $_POST['data'] ?? '';
    $data4 = str_replace(';', '', $data4);
    $data6 = $_POST['data6'] ?? '';
    $data6 = str_replace(';', '', $data6);

    trustng_state_write('clients.ip', "127.0.0.0/8\n" . str_replace("\r\n", "\n", $data4));
    trustng_state_write('clients6.ip', "::1/128\n" . str_replace("\r\n", "\n", $data6));

    $lines = trustng_state_lines('clients.ip');
    $lines = array_unique(array_filter(array_map('trim', $lines)));
    trustng_state_write('clients.ip', implode("\n", $lines) . "\n");
    $subject = trustng_state_read('clients.ip');
    $problem4 = 'no';
    foreach(preg_split("/((\r?\n)|(\r\n?))/", $subject) as $line){
        $line = trim($line);
        if ($line === '') continue;
        if (isValidCIDR4($line)) {
            // valid
        } else {
            $error = $line . ' (IPv4) tidak valid';
            $problem4 = 'yes';
            break;
        }
    }

    $lines6 = trustng_state_lines('clients6.ip');
    $lines6 = array_unique(array_filter(array_map('trim', $lines6)));
    trustng_state_write('clients6.ip', implode("\n", $lines6) . "\n");
    $subject6 = trustng_state_read('clients6.ip');
    $problem6 = 'no';
    foreach(preg_split("/((\r?\n)|(\r\n?))/", $subject6) as $line){
        $line = trim($line);
        if ($line !== '') {
            if (isValidCIDR6($line)) {
                // valid
            } else {
                $error = $line . ' (IPv6) tidak valid';
                $problem6 = 'yes';
                break;
            }
        }
    }

    if ($problem4 !== 'yes' && $problem6 !== 'yes') {
        trustng_state_touch('setclient.new');
        $notice = 'ACL clients berhasil disimpan. Jalankan Maintenance → Reload untuk mengaktifkan perubahan.';
    }

    $index = 'yes'; $back = 'history.go(-2)';
}

if (strpos($referer, $allowed_prefix) !== 0 && strpos($referer, $allowed_prefix_ip) !== 0) {
        if (!isset($index) || $index !== 'yes') {
            $dashboard_ref = "https://$myip:40443/";
            if (strpos($referer, $dashboard_ref) !== 0 && strpos($referer, $allowed_prefix . "index.php") !== 0) exit(0);
        }
}

$file4 = trustng_state_lines('clients.ip');
$file6 = trustng_state_lines('clients6.ip');
$ipaddr = shell_exec("ifconfig eth0 | grep netmask | sed 's/ .*inet //;s/ .*//'");
$useip6 = file_get_contents('setip6');

tng_ui_page_start('setclient.php', 'ACL Clients', 'Daftar CIDR klien yang diizinkan melakukan rekursi. IP referensi: ' . trim($ipaddr));
if (!empty($notice)) tng_ui_notice('success', 'Perubahan tersimpan', $notice);
if (!empty($error)) tng_ui_notice('critical', 'Data tidak valid', $error);
tng_ui_card_start('ACL Recursive Clients', 'Format: ip_address/cidr per baris, tanpa titik koma (;). Warning, jangan asal copas — syntax harus benar.');

echo '<form name="client" action="setclient.php" method="post">';
echo '<div class="tng-field field"><label>IPv4</label><div class="areatxt2"><textarea rows="10" cols="20" name="data" onkeyup="checkIPList(this);" autofocus="autofocus" placeholder="contoh:
127.0.0.0/8
192.168.0.0/16
172.16.0.0/12
10.0.0.0/8">';
foreach($file4 as $text) { echo htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); }
echo '</textarea></div></div>';
if ($useip6 == 'yes') { echo '
<div class="tng-field field"><label>IPv6</label><div class="areatxt2"><textarea rows="10" cols="20" name="data6" autofocus="autofocus" placeholder="contoh:
::1/64
::2/64
::3/64">';
foreach($file6 as $text) { echo htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); }
echo '</textarea></div></div>';
}
echo '
<div class="form-actions di-actions">
  <input type="submit" id="submit" value="Simpan" class="submit-button"/>
  <input type="button" onclick="'.$back.'" class="submit-button button-secondary" value="Kembali">
</div>
</form>';
tng_ui_card_end();

echo '<script src="kunci.js"></script>';
tng_ui_page_end('setclient.php');
?>