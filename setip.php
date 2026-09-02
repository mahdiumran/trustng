<?php
require_once __DIR__ . '/includes/state_store.php';
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
    exec('sudo /usr/bin/cp ' . escapeshellarg($tmp) . ' /etc/network/interfaces 2>&1', $output, $status);
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
        echo "<script>alert('Konfigurasi IP address berhasil disimpan.\nSilahkan buka menu Maintenance -> Reload System untuk mengaktifkan perubahan.');</script>";
    }
    $index = 'yes'; $back = 'history.go(-2)';
}

if($_POST['ipalias'] ?? null) {
    $data4 = $_POST['data'] ?? '';
    foreach(preg_split("/((\r?\n)|(\r\n?))/", $data4) as $line){
        $line = trim($line);
        if (isValidCIDR($line)) {
        } else if ($line !='') {
            echo "<script>alert('$line tidak valid');history.back();</script>";
            $problem4 = 'yes';
            break;
        }
    }
    $data6 = $_POST['data6'] ?? '';
    foreach(preg_split("/((\r?\n)|(\r\n?))/", $data6) as $line){
        $line = trim($line);
        if (isValidCIDR($line)) {
        } else if ($line !='') {
            echo "<script>alert('$line tidak valid');history.back();</script>";
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
        echo "<script>alert('IP alias berhasil disimpan.\nSilahkan buka menu Maintenance -> Reload System untuk mengaktifkan perubahan.');</script>";
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

$file = file("ipalias.data");
$file6 = file("ipalias6.data");
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
<title>DNS TRUST-NG - IP CONFIG</title>
<script src="kunci.js"></script>
</head>
<body class="with-sidebar sidebar-collapsed">
<div id="sidebar-overlay"></div>
<div class="page-shell">';
include_once 'menu.php';
trustng_render_sidebar('setip.php');

echo '<div class="page-content">';
echo '<div class="tng-topbar"><button class="tng-topbar-toggle" title="Toggle menu" aria-label="Toggle menu"><svg width="20" height="20" viewBox="0 0 20 20" fill="none"><rect x="2" y="4.5" width="16" height="2" rx="1" fill="currentColor"/><rect x="2" y="9" width="16" height="2" rx="1" fill="currentColor"/><rect x="2" y="13.5" width="16" height="2" rx="1" fill="currentColor"/></svg></button><span class="tng-topbar-title">IP Address</span><div class="tng-topbar-spacer"></div><a class="tng-topbar-back" href="/">&#8592; Dashboard</a></div>';
//include 'submit.js';
echo'
<div align=center>
<canvas class="logo-canvas" width="600" height="60"></canvas>
<h3>Konfigurasi <span class="grad">IP Address</span></h3>

<form name="isian" action="setip.php" method="post">
<div class="set-section">
  <div class="set-section-head"><span class="set-section-title">IPv4</span></div>
  <div class="set-row">
    <div class="set-row-info">
      <span class="set-row-name">Mode DHCP</span>
      <span class="set-row-desc">Alamat IP otomatis dari server DHCP.</span>
    </div>
    <div class="set-row-control">
      <label class="tng-switch"><input type="checkbox" name="dhcp" value="yes" '.$dhcp.'><span class="tng-switch-track"></span></label>
    </div>
  </div>
  <div class="set-grid">
    <div class="tng-field"><label>IP Address</label><input type="text" name="ipaddr" class="form__w" onKeyup="checkform()" value="'.htmlspecialchars($ipaddr).'" placeholder="192.168.168.168" required /></div>
    <div class="tng-field"><label>Netmask</label><input type="text" name="netmask" class="form__w" onKeyup="checkform()" value="'.htmlspecialchars($netmask).'" placeholder="255.255.255.0" required /></div>
    <div class="tng-field"><label>Gateway</label><input type="text" name="gateway" class="form__w" onKeyup="checkform()" value="'.htmlspecialchars($gateway).'" placeholder="192.168.168.1" required /></div>
  </div>
</div>';

if ($useip6 == 'yes') { echo '
<div class="set-section">
  <div class="set-section-head"><span class="set-section-title">IPv6</span></div>
  <div class="set-row">
    <div class="set-row-info">
      <span class="set-row-name">Auto (SLAAC / DHCP)</span>
      <span class="set-row-desc">Alamat IPv6 otomatis.</span>
    </div>
    <div class="set-row-control">
      <label class="tng-switch"><input type="checkbox" name="ip6auto" value="yes" '.$ip6auto.'><span class="tng-switch-track"></span></label>
    </div>
  </div>
  <div class="set-grid">
    <div class="tng-field"><label>IPv6 Address</label><input type="text" name="ip6addr" class="form__w" onKeyup="checkform()" value="'.htmlspecialchars($ip6addr).'" placeholder="::1" /></div>
    <div class="tng-field"><label>Prefix Length</label><input type="text" name="ip6prefix" class="form__w" onKeyup="checkform()" value="'.htmlspecialchars($ip6prefix).'" placeholder="64" /></div>
    <div class="tng-field"><label>Gateway</label><input type="text" name="ip6gateway" class="form__w" onKeyup="checkform()" value="'.htmlspecialchars($ip6gateway).'" placeholder="::2" /></div>
  </div>
</div>';
}

echo'
<div class="di-actions">
  <input type="submit" id="submit" value="Simpan" class="submit-button"/>
  <input type="button" onclick="'.$back.'" class="submit-button" value="Kembali">
</div>
</form>

<form name="ipalias" action="setip.php" method="post">
<div class="set-section">
  <div class="set-section-head"><span class="set-section-title">Loopback IP Alias</span></div>
  <p class="set-section-desc">Tambahkan IP alias (format ip/cidr) agar TrustNG dapat melayani di IP tersebut tanpa perlu Tproxy.</p>
  <div class="tng-field"><label>IPv4</label><div class="areatxt2"><textarea rows="8" cols="20" name="data"'; echo "placeholder='contoh:\n8.8.8.8/32\n1.1.1.1/32\n9.9.9.9/32\n192.168.1.11/32'"; echo 'onkeyup="checkIPList(this);">';
foreach($file as $text) { echo $text; }
echo '</textarea></div></div>';
if ($useip6 == 'yes') { echo '
<div class="tng-field"><label>IPv6</label><div class="areatxt2"><textarea rows="8" cols="20" name="data6"'; echo "placeholder='contoh:\n::1/128\n::2/128\n::3/128\n::4/128'"; echo '>';
foreach($file6 as $text) { echo $text; }
echo '</textarea></div></div>';
}
echo'
<input type="hidden" name="ipalias" value="submit">
<div class="di-actions">
  <input type="submit" id="submit" value="Simpan" class="submit-button"/>
  <a class="submit-button" href="/">Kembali</a>
</div>
</form>

<p><small><b>&#169; 2024 Kominfo</b></small>
</div>';


echo '</div></div>';
?>