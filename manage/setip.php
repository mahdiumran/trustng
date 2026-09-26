<?php
require_once __DIR__ . '/includes/state_store.php';
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

$back = 'history.back()';

function trustng_write_interfaces($content)
{
    $tmp = tempnam('/tmp', 'trustng-if-');
    if ($tmp === false) return false;
    file_put_contents($tmp, $content);
    $output = [];
    $status = 1;
    exec('sudo -n /usr/bin/cp ' . escapeshellarg($tmp) . ' /etc/network/interfaces 2>&1', $output, $status);
    @unlink($tmp);
    return $status === 0;
}

function isValidCIDR($cidr)
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

    // check if it is a valid IPv6
    if(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        // netmask should be less than 128
        return $netmask <= 128;
    }

    // well, if no match, then it is an invalid CIDR string
    return false;
}

if($_POST['ipaddr'] ?? null) {
    if (strpos($referer, $allowed_prefix . "setip.php") !== 0 && strpos($referer, $allowed_prefix_ip . "setip.php") !== 0) exit(0);
    $dhcp = $_POST['dhcp'] ?? '';
    $ip = $_POST['ipaddr'] ?? '';
    $mask = $_POST['netmask'] ?? '';
    $gw = $_POST['gateway'] ?? '';

    $ip6auto = $_POST['ip6auto'] ?? '';
    $ip6 = $_POST['ip6addr'] ?? '';
    $ip6prefix = $_POST['ip6prefix'] ?? '';
    $ip6gw = $_POST['ip6gateway'] ?? '';

    $ifContent = '';
    if ($dhcp != 'yes') {
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
		    if (filter_var($mask, FILTER_VALIDATE_IP)) {
		        if (filter_var($gw, FILTER_VALIDATE_IP)) {
			        $ifContent = "auto lo
iface lo inet loopback

allow-hotplug eth0
iface eth0 inet static
    address $ip
    netmask $mask
    gateway $gw

auto eth0:0
iface eth0:0 inet static
    address 192.168.168.168/24
";
	 		        trustng_state_write('ipaddr.data', "$ip,$mask,$gw");
	                trustng_state_touch('setip.new');
			    } else {
			        echo 'gateway gagal, mohon cek ulang isian gateway'; exit;
			    }
		    } else {
			    echo 'netmask gagal, mohon cek ulang isian netmask'; exit;
		    }
		} else {
		    echo 'ip gagal, mohon cek ulang isian ip'; exit;
		}
    } else {
		$ifContent = "auto lo
iface lo inet loopback

allow-hotplug eth0
iface eth0 inet dhcp

auto eth0:0
iface eth0:0 inet static
    address 192.168.168.168/24
";
	    trustng_state_touch('setip.new');
    }

    if ($ip6auto != 'yes') {
        trustng_state_write('ip6auto', 'no');

		if ($ip6 != '') {
	            if (filter_var($ip6, FILTER_VALIDATE_IP)) {
	                if (filter_var($ip6gw, FILTER_VALIDATE_IP)) {
		                $ifContent .= "
iface eth0 inet6 static
    address $ip6
    netmask $ip6prefix
    gateway $ip6gw
";
	                    trustng_state_write('ip6addr.data', "$ip6,$ip6prefix,$ip6gw");
	                    trustng_state_touch('setip6.new');
	                } else {
	                    echo 'ip6 gateway gagal, mohon cek ulang isian gateway'; exit;
	                }
	            } else {
	                echo 'ip6 gagal, mohon cek ulang isian ip'; exit;
	            }
		} else {
	            trustng_state_write('ip6addr.data', "$ip6,$ip6prefix,$ip6gw");
		}
    } else {
        $ifContent .= "
iface eth0 inet6 dhcp
";
        trustng_state_touch('setip.new');
        trustng_state_write('ip6auto', 'yes');
    }

    if ($ifContent !== '') {
        trustng_write_interfaces($ifContent);
        $notice = 'Konfigurasi IP address berhasil disimpan. Jalankan Maintenance → Reload untuk mengaktifkan perubahan.';
    }
    $index = 'yes'; $back = 'history.go(-2)';
}

if($_POST['ipalias'] ?? null) {
    $data4 = $_POST['data'] ?? '';
    foreach(preg_split("/((\r?\n)|(\r\n?))/", $data4) as $line){
        $line = trim($line);
        if (isValidCIDR($line)) {
        } else if ($line !='') {
                    $error = $line . ' tidak valid';
                    $problem4 = 'yes';
            break;
        }
    }
    $data6 = $_POST['data6'] ?? '';
    foreach(preg_split("/((\r?\n)|(\r\n?))/", $data6) as $line){
        $line = trim($line);
        if (isValidCIDR($line)) {
        } else if ($line !='') {
                    $error = $line . ' tidak valid';
                    $problem6 = 'yes';
            break;
        }
    }

    if (($problem4 ?? '') != 'yes') {
        trustng_state_write('ipalias.data', "$data4");
        $index = 'yes'; $back = 'history.go(-2)';
        trustng_state_touch('setalias.new');
    }
    if (($problem6 ?? '') != 'yes') {
        trustng_state_write('ipalias6.data', "$data6");
        $index = 'yes'; $back = 'history.go(-2)';
        trustng_state_touch('setalias.new');
    }
    if (($problem4 ?? '') != 'yes' && ($problem6 ?? '') != 'yes') {
            $notice = 'IP alias berhasil disimpan. Jalankan Maintenance → Reload untuk mengaktifkan perubahan.';
    }
}

if (strpos($referer, $allowed_prefix) !== 0 && strpos($referer, $allowed_prefix_ip) !== 0) {
        if (!isset($index) || $index !== 'yes') {
            // Allow direct access from dashboard or if POST was just processed
            $dashboard_ref = "https://$myip:40443/";
            if (strpos($referer, $dashboard_ref) !== 0 && strpos($referer, $allowed_prefix . "index.php") !== 0) exit(0);
        }
}

$ipcfg = shell_exec("grep 'eth0 inet' /etc/network/interfaces | head -1 | sed 's/.*inet //'");
if ($ipcfg == "dhcp\n") {
    $dhcp = 'checked';
    $ipaddr = shell_exec("ifconfig eth0 | grep netmask | sed 's/ .*inet //;s/ .*//'");
    $netmask = shell_exec("ifconfig eth0 | grep netmask | sed 's/ .*netmask //;s/ .*//'");
    $gateway = shell_exec("netstat -nr | grep 0.0.0.0 | head -1 | cut -d' ' -f10");
} else {
    $dhcp = '';
    $ipdata = file_get_contents('ipaddr.data');
    $ipnet = explode(",", $ipdata);
    $ipaddr = trim($ipnet[0]);
    if ($ipaddr == '') $ipaddr = shell_exec("ifconfig eth0 | grep netmask | sed 's/ .*inet //;s/ .*//'");
    $netmask = trim($ipnet[1]);
    if ($netmask == '') $netmask = shell_exec("ifconfig eth0 | grep netmask | sed 's/ .*netmask //;s/ .*//'");
    $gateway = trim($ipnet[2]);
    if ($gateway == '')  $gateway = shell_exec("netstat -nr | grep 0.0.0.0 | head -1 | cut -d' ' -f10");
}

$ip6auto = file_get_contents('ip6auto'); if ($ip6auto == 'yes') $ip6auto = 'checked';
$ip6data = file_get_contents('ip6addr.data');
$ip6net = explode(",", $ip6data);
$ip6addr = trim($ip6net[0]);
$ip6prefix = trim($ip6net[1]);
$ip6gateway = trim($ip6net[2]);

$file = is_file('ipalias.data') ? file('ipalias.data') : array();
$file6 = is_file('ipalias6.data') ? file('ipalias6.data') : array();
$useip6 = file_get_contents('setip6');
tng_ui_page_start('setip.php', 'IP Address', 'Konfigurasi IPv4/IPv6 dan loopback IP alias resolver.');
if (!empty($notice)) tng_ui_notice('success', 'Perubahan tersimpan', $notice);
if (!empty($error)) tng_ui_notice('critical', 'Data tidak valid', $error);

tng_ui_card_start('Konfigurasi IP Address', 'Atur alamat IP statis atau DHCP untuk antarmuka eth0.');
echo '<form name="isian" action="setip.php" method="post">
<div class="set-section">
  <div class="set-section-head"><span class="set-section-title">IPv4</span></div>
  <div class="set-row">
    <div class="set-row-info">
      <span class="set-row-name" id="dhcp-label">Mode DHCP</span>
      <span class="set-row-desc">Alamat IP otomatis dari server DHCP.</span>
    </div>
    <div class="set-row-control">
      <label class="tng-switch"><input type="checkbox" name="dhcp" value="yes" aria-labelledby="dhcp-label" '.$dhcp.'><span class="tng-switch-track"></span></label>
    </div>
  </div>
  <div class="set-grid field-grid three">
    <div class="tng-field field"><label for="ipv4-address">IP Address</label><input id="ipv4-address" type="text" name="ipaddr" class="form__w" value="'.htmlspecialchars($ipaddr).'" placeholder="192.168.168.168" required /></div>
    <div class="tng-field field"><label for="ipv4-netmask">Netmask</label><input id="ipv4-netmask" type="text" name="netmask" class="form__w" value="'.htmlspecialchars($netmask).'" placeholder="255.255.255.0" required /></div>
    <div class="tng-field field"><label for="ipv4-gateway">Gateway</label><input id="ipv4-gateway" type="text" name="gateway" class="form__w" value="'.htmlspecialchars($gateway).'" placeholder="192.168.168.1" required /></div>
  </div>
</div>';

if ($useip6 == 'yes') { echo '
<div class="set-section">
  <div class="set-section-head"><span class="set-section-title">IPv6</span></div>
  <div class="set-row">
    <div class="set-row-info">
      <span class="set-row-name" id="ipv6-auto-label">Auto (SLAAC / DHCP)</span>
      <span class="set-row-desc">Alamat IPv6 otomatis.</span>
    </div>
    <div class="set-row-control">
      <label class="tng-switch"><input type="checkbox" name="ip6auto" value="yes" aria-labelledby="ipv6-auto-label" '.$ip6auto.'><span class="tng-switch-track"></span></label>
    </div>
  </div>
  <div class="set-grid field-grid three">
    <div class="tng-field field"><label for="ipv6-address">IPv6 Address</label><input id="ipv6-address" type="text" name="ip6addr" class="form__w" value="'.htmlspecialchars($ip6addr).'" placeholder="::1" /></div>
    <div class="tng-field field"><label for="ipv6-prefix">Prefix Length</label><input id="ipv6-prefix" type="text" name="ip6prefix" class="form__w" value="'.htmlspecialchars($ip6prefix).'" placeholder="64" /></div>
    <div class="tng-field field"><label for="ipv6-gateway">Gateway</label><input id="ipv6-gateway" type="text" name="ip6gateway" class="form__w" value="'.htmlspecialchars($ip6gateway).'" placeholder="::2" /></div>
  </div>
</div>';
}

echo '
<div class="form-actions di-actions">
  <input type="submit" id="submit-ip-address" value="Simpan" class="submit-button"/>
  <input type="button" onclick="'.$back.'" class="submit-button button-secondary" value="Kembali">
</div>
</form>';
tng_ui_card_end();

tng_ui_card_start('Loopback IP Alias', 'Tambahkan IP alias (format ip/cidr) agar TrustNG dapat melayani di IP tersebut tanpa perlu Tproxy.');
echo '<form name="ipalias" action="setip.php" method="post">
<div class="tng-field field"><label>IPv4</label><div class="areatxt2"><textarea rows="8" cols="20" name="data" onkeyup="checkIPList(this);" placeholder="contoh:
8.8.8.8/32
1.1.1.1/32
9.9.9.9/32
192.168.1.11/32">';
foreach($file as $text) { echo htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); }
echo '</textarea></div></div>';
if ($useip6 == 'yes') { echo '
<div class="tng-field field"><label>IPv6</label><div class="areatxt2"><textarea rows="8" cols="20" name="data6" placeholder="contoh:
::1/128
::2/128
::3/128
::4/128">';
foreach($file6 as $text) { echo htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); }
echo '</textarea></div></div>';
}
echo '
<input type="hidden" name="ipalias" value="submit">
<div class="form-actions di-actions">
  <input type="submit" id="submit-ip-alias" value="Simpan" class="submit-button"/>
  <a class="submit-button button-secondary" href="/">Kembali</a>
</div>
</form>';
tng_ui_card_end();

echo '<script src="kunci.js"></script>';
tng_ui_page_end('setip.php');
?>