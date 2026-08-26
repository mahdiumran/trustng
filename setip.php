<?php
error_reporting(0);
$myip = $_SERVER['SERVER_ADDR'];
$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
$http_host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : "$myip:40443";
$proto = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$allowed_prefix = "$proto://$http_host/";
$allowed_prefix_ip = "https://$myip:40443/";

$back = 'history.back()';

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

    if ($dhcp != 'yes') {
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
	    if (filter_var($mask, FILTER_VALIDATE_IP)) {
	        if (filter_var($gw, FILTER_VALIDATE_IP)) {
		    shell_exec("echo \"auto lo\niface lo inet loopback\n\nallow-hotplug eth0\niface eth0 inet static\n    address $ip\n    netmask $mask\n    gateway $gw\n\nauto eth0:0\niface eth0:0 inet static\n    address 192.168.168.168/24\n\" > /etc/network/interfaces");
 		    $file = fopen('ipaddr.data', 'w');
 		    if ($file) { fwrite($file, "$ip,$mask,$gw"); fclose($file); }
                    $file = fopen('setip.new', 'w');
                    if ($file) { fwrite($file, ''); fclose($file); }
                    echo "<script>alert('ip address telah diubah, silahkan reload atau reboot untuk mengaktifkan');</script>";
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
	shell_exec("echo \"auto lo\niface lo inet loopback\n\nallow-hotplug eth0\niface eth0 inet dhcp\n\nauto eth0:0\niface eth0:0 inet static\n    address 192.168.168.168/24\n\" > /etc/network/interfaces");
        $file = fopen('setip.new', 'w');
        if ($file) { fwrite($file, ''); fclose($file); }
        echo "<script>alert('ip address telah diubah, silahkan reload atau reboot untuk mengaktifkan');</script>";
    }

    if ($ip6auto != 'yes') {
        $file = fopen('ip6auto', 'w');
        if ($file) { fwrite($file, 'no'); fclose($file); }

	if ($ip6 != '') {
            if (filter_var($ip6, FILTER_VALIDATE_IP)) {
                if (filter_var($ip6gw, FILTER_VALIDATE_IP)) {
	            shell_exec("echo \"iface eth0 inet6 static\n    address $ip6\n    netmask $ip6prefix\n    gateway $ip6gw\" >> /etc/network/interfaces");
                    $file = fopen('ip6addr.data', 'w');
                    if ($file) { fwrite($file, "$ip6,$ip6prefix,$ip6gw"); fclose($file); }
                    $file = fopen('setip6.new', 'w');
                    if ($file) { fwrite($file, ''); fclose($file); }
                    echo "<script>alert('ip6 address telah diubah, silahkan reload atau reboot untuk mengaktifkan');</script>";
                } else {
                    echo 'ip6 gateway gagal, mohon cek ulang isian gateway'; exit;
                }
            } else {
                echo 'ip6 gagal, mohon cek ulang isian ip'; exit;
            }
	} else {
            $file = fopen('ip6addr.data', 'w');
            if ($file) { fwrite($file, "$ip6,$ip6prefix,$ip6gw"); fclose($file); }
	}
    } else {
        shell_exec("echo \"iface eth0 inet6 dhcp\n\" >> /etc/network/interfaces");
        $file = fopen('setip.new', 'w');
        if ($file) { fwrite($file, ''); fclose($file); }
        echo "<script>alert('ip6 address telah diubah, silahkan reload atau reboot untuk mengaktifkan');</script>";
        $file = fopen('ip6auto', 'w');
        if ($file) { fwrite($file, 'yes'); fclose($file); }
    }
    $index = 'yes'; $back = 'history.go(-2)';
}

if($_POST['ipalias'] ?? null) {
    $data4 = $_POST['data'] ?? '';
    foreach(preg_split("/((\r?\n)|(\r\n?))/", $data4) as $line){
        if (isValidCIDR($line)) {
        } else if ($line !='') {
            echo "<script>alert('$line tidak valid');history.back();</script>";
            $problem4 = 'yes';
        }
    }
    $data6 = $_POST['data6'] ?? '';
    foreach(preg_split("/((\r?\n)|(\r\n?))/", $data6) as $line){
        if (isValidCIDR($line)) {
        } else if ($line !='') {
            echo "<script>alert('$line tidak valid');history.back();</script>";
            $problem6 = 'yes';
        }
    }

    if (($problem4 ?? '') != 'yes') {
        $file = fopen('ipalias.data', 'w');
        if ($file) { fwrite($file, "$data4"); fclose($file); }
        shell_exec("dos2unix ipalias.data 2>/dev/null");
        $index = 'yes'; $back = 'history.go(-2)';
        $file = fopen('setalias.new', 'w');
        if ($file) { fwrite($file, ''); fclose($file); }
        echo "<script>alert('ip alias telah diubah, silahkan reload atau reboot untuk mengaktifkan');</script>";
    }
    if (($problem6 ?? '') != 'yes') {
        $file = fopen('ip6alias.data', 'w');
        if ($file) { fwrite($file, "$data6"); fclose($file); }
        shell_exec("dos2unix ip6alias.data 2>/dev/null");
        $index = 'yes'; $back = 'history.go(-2)';
        $file = fopen('setalias.new', 'w');
        if ($file) { fwrite($file, ''); fclose($file); }
        echo "<script>alert('ip6 alias telah diubah, silahkan reload atau reboot untuk mengaktifkan');</script>";
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
$file6 = file("ip6alias.data");
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
<a href="/"><img src="img/trustng-small.jpg" width="200px"></a>
<form name="isian" action="setip.php" method="post">
<p>
<h3>Konfigurasi IP Address<br><small>'.$ipaddr.'</small></h3>
IPv4 <input type="checkbox" name="dhcp" value="yes" class="submit-button" '.$dhcp.'/> DHCP
<input type="text" name="ipaddr" class="form__w" onKeyup="checkform()" value="'.$ipaddr.'" placeholder="192.168.168.168 (ip)" required />
<input type="text" name="netmask" class="form__w" onKeyup="checkform()"  value="'.$netmask.'" placeholder="255.255.255.0 (netmask)" required />
<input type="text" name="gateway" class="form__w" onKeyup="checkform()" value="'.$gateway.'"  placeholder="192.168.168.1 (gateway)" required />';

if ($useip6 == 'yes') { echo '
IPv6 <input type="checkbox" name="ip6auto" value="yes" class="submit-button" '.$ip6auto.'/> DHCP
<input type="text" name="ip6addr" class="form__w" onKeyup="checkform()" value="'.$ip6addr.'" placeholder="::1 (ip6)" />
<input type="text" name="ip6prefix" class="form__w" onKeyup="checkform()"  value="'.$ip6prefix.'" placeholder="64 (prefix length)" />
<input type="text" name="ip6gateway" class="form__w" onKeyup="checkform()" value="'.$ip6gateway.'"  placeholder="::2 (gateway)" />';
}
echo'
<input type="submit" id="submit" value="Simpan" class="submit-button"/> <input type="button"  onclick="'.$back.'" class="submit-button" value="Kembali">
</form>
<form name="ipalias" action="setip.php" method="post">
<b>Loopback IP Alias (Optional)</b><br>
<small><i>Tambahkan beberapa ip alias di bawah ini, sehingga<br>TrustNG bisa serving di ip ini tanpa perlu Tproxy<br>format: ip/cidr</i></small>
<br>IPv4
<div class="areatxt2"><textarea rows="10" cols="20" name="data"'; echo "placeholder='contoh:\n8.8.8.8/32\n1.1.1.1/32\n9.9.9.9/32\n192.168.1.11/32'"; echo 'onkeyup="checkIPList(this);">';
foreach($file as $text) { echo $text; }
echo '</textarea></div>';
if ($useip6 == 'yes') { echo '
IPv6
<div class="areatxt2"><textarea rows="10" cols="20" name="data6"'; echo "placeholder='contoh:\n::1/128\n::2/128\n::3/128\n::4/128'"; echo '>';
foreach($file6 as $text) { echo $text; }
echo '</textarea></div>';
}
echo'
<input type="hidden" name="ipalias" value="submit">
<input type="submit" id="submit" value="Simpan" class="submit-button"/> <a href="/"> <input type="button" class="submit-button" value="Kembali"></a>
</form>
<p><small><b>&#169; 2024 Kominfo</b></small>
</div>';


echo '</div></div>';
?>