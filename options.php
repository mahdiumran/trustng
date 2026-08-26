
<?php
error_reporting(0);
$myip = $_SERVER['SERVER_ADDR'];
$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
$http_host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : "$myip:40443";
$proto = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$allowed_prefix = "$proto://$http_host/";
$allowed_prefix_ip = "https://$myip:40443/";
$back = 'history.back()';
$ipaddr = shell_exec("ifconfig eth0 | grep netmask | sed 's/ .*inet //;s/ .*//'");
if (!file_exists('setsafesearch')) {
    $file = fopen('setsafesearch', 'w');
    if ($file) { fwrite($file, ''); fclose($file); }
}
if (!file_exists('ssh.port')) {
    $file = fopen('ssh.port', 'w');
    if ($file) { fwrite($file, '7857'); fclose($file); }
}
if (!file_exists('settproxy')) {
    $file = fopen('settproxy', 'w');
    if ($file) { fwrite($file, ''); fclose($file); }
}
if (!file_exists('setdnssec')) {
    $file = fopen('setdnssec', 'w');
    if ($file) { fwrite($file, ''); fclose($file); }
}

if (!file_exists('setsnmpd')) {
    $file = fopen('setsnmpd', 'w');
    if ($file) { fwrite($file, ''); fclose($file); }
}

if (!file_exists('setip6')) {
    $file = fopen('setip6', 'w');
    if ($file) { fwrite($file, ''); fclose($file); }
}

$csafe = file_get_contents('setsafesearch');
$cssh = file_get_contents('ssh.port');
$ctproxy = file_get_contents('settproxy');
$cdnssec = file_get_contents('setdnssec');
$csnmpd = file_get_contents('setsnmpd');
$cip6 = file_get_contents('setip6');

$info = '';

if($_POST['options'] ?? null) {
    if (strpos($referer, $allowed_prefix . "options.php") !== 0 && strpos($referer, $allowed_prefix_ip . "options.php") !== 0) exit(0);

    $ssh = $_POST['ssh'] ?? '';
    $safe = $_POST['safe'] ?? '';
    $tproxy = $_POST['tproxy'] ?? '';
    $dnssec = $_POST['dnssec'] ?? '';
    $snmpd = $_POST['snmpd'] ?? '';
    $community = $_POST['community'] ?? '';
    $ip6 = $_POST['ip6'] ?? '';

    if ($community == '') $community = 'public';
    $file = fopen('snmpd.community', 'w');
    if ($file) { fwrite($file, "$community"); fclose($file); }

    if ($cssh != $ssh) {
        $info1 = 'Port SSH,';
        $file = fopen('/etc/ssh/port.conf', 'w');
        if ($file) { fwrite($file, "Port $ssh"); fclose($file); }
        $file = fopen('ssh.port', 'w');
        if ($file) { fwrite($file, "$ssh"); fclose($file); }
        $file = fopen('setssh.new', 'w');
        if ($file) { fwrite($file, ''); fclose($file); }
    }
    if ($safe == 'yes' && $csafe == '') {
        $file = fopen('setsafesearch', 'w');
        if ($file) { fwrite($file, 'yes'); fclose($file); }
        $file = fopen('setdns.new', 'w');
        if ($file) { fwrite($file, ''); fclose($file); }
        $file = fopen('/etc/unbound/module-config.conf', 'w');
        if ($file) {
            if ($dnssec != 'no') {
                fwrite($file, 'module-config: "respip validator iterator"');
            } else {
                fwrite($file, 'module-config: "respip iterator"');
            }
            fclose($file);
        }
        $file = fopen('/etc/unbound/rpz.conf', 'w');
        if ($file) { fwrite($file, "rpz:\n\tname: rpz.safesearch\n\tzonefile: \"/etc/unbound/rpz.safesearch\""); fclose($file); }
	$info2 = 'Safesearch,';
    } elseif ($safe == '' && $csafe == 'yes') {
        $file = fopen('setsafesearch', 'w');
        if ($file) { fwrite($file, ''); fclose($file); }
        $file = fopen('setdns.new', 'w');
        if ($file) { fwrite($file, ''); fclose($file); }
        $file = fopen('/etc/unbound/module-config.conf', 'w');
        if ($file) {
            if ($dnssec != 'no') {
                fwrite($file, 'module-config: "validator iterator"');
            } else {
                fwrite($file, 'module-config: "iterator"');
            }
            fclose($file);
        }
        $file = fopen('/etc/unbound/rpz.conf', 'w');
        if ($file) { fwrite($file, ''); fclose($file); }
        $info2 = 'Safesearch,';
    }

    if ($tproxy == 'yes' && $ctproxy == '') {
        $file = fopen('settproxy', 'w');
        if ($file) { fwrite($file, 'yes'); fclose($file); }
        $file = fopen('setclient.new', 'w');
        if ($file) { fwrite($file, ''); fclose($file); }
        $info3 = 'Tproxy';
	shell_exec("/usr/bin/cp /etc/tproxy.conf.new /etc/tproxy.conf");
    } elseif ($tproxy == '' && $ctproxy == 'yes') {
        $file = fopen('settproxy', 'w');
        if ($file) { fwrite($file, ''); fclose($file); }
        $file = fopen('setclient.new', 'w');
        if ($file) { fwrite($file, ''); fclose($file); }
        $info3 = 'Tproxy';
        $file = fopen('/etc/tproxy.conf', 'w');
        if ($file) { fwrite($file, ''); fclose($file); }
    }
    if ($dnssec != 'no' && $cdnssec != 'yes') {
        $file = fopen('setdnssec', 'w');
        if ($file) { fwrite($file, 'yes'); fclose($file); }
        $file = fopen('setdns.new', 'w');
        if ($file) { fwrite($file, ''); fclose($file); }
        $info4 = 'Dnssec';
        $file = fopen('/etc/unbound/module-config.conf', 'w');
	if ($file) {
	    if ($safe == 'yes') {
                fwrite($file, 'module-config: "respip validator iterator"');
	    } else {
                fwrite($file, 'module-config: "validator iterator"');
	    }
            fclose($file);
	}

    } elseif ($dnssec == 'no' && $cdnssec != 'no') {
        $file = fopen('setdnssec', 'w');
        if ($file) { fwrite($file, 'no'); fclose($file); }
        $file = fopen('setdns.new', 'w');
        if ($file) { fwrite($file, ''); fclose($file); }
        $info4 = 'Dnssec';
        $file = fopen('/etc/unbound/module-config.conf', 'w');
        if ($file) {
            if ($safe == 'yes') {
                fwrite($file, 'module-config: "respip iterator"');
            } else {
                fwrite($file, 'module-config: "iterator"');
            }
            fclose($file);
        }
    }

    `printf "agentaddress udp:161\nrocommunity $community 0.0.0.0/0\n\nagentaddress udp6:161\nrocommunity6 $community ::/0\n" > /etc/snmp/snmpd.conf`;
    if ($snmpd == 'yes'&& $csnmpd != 'yes') {
        $file = fopen('setsnmpd', 'w');
        if ($file) { fwrite($file, 'yes'); fclose($file); }
        $file = fopen('setsnmpd.new', 'w');
        if ($file) { fwrite($file, ''); fclose($file); }
    } elseif ($snmpd != 'yes' && $csnmpd == 'yes') {
        $file = fopen('setsnmpd', 'w');
        if ($file) { fwrite($file, 'no'); fclose($file); }
        $file = fopen('setsnmpd.new', 'w');
        if ($file) { fwrite($file, ''); fclose($file); }
    }

    if ($ip6 == 'yes' && $cip6 != 'yes') {
        $file = fopen('setip6', 'w');
        if ($file) { fwrite($file, 'yes'); fclose($file); }
        $file = fopen('setip6.new', 'w');
        if ($file) { fwrite($file, ''); fclose($file); }
        $info6 = 'Ip6';
	shell_exec('sudo sed -i "s/do-ip6: no/do-ip6: yes/" /etc/unbound/unbound.conf');
	`sudo sed -i 's/ = 1/ = 0/' /etc/sysctl.conf`;
	`sudo sed -i 's/lo.disable_ipv6 = 1/lo.disable_ipv6 = 0/' /etc/sysctl.conf`;
    } elseif ($ip6 != 'yes' && $cip6 == 'yes') {
        $file = fopen('setip6', 'w');
        if ($file) { fwrite($file, 'no'); fclose($file); }
        $file = fopen('setip6.new', 'w');
        if ($file) { fwrite($file, ''); fclose($file); }
        $info6 = 'Ip6';
        shell_exec('sudo sed -i "s/do-ip6: yes/do-ip6: no/" /etc/unbound/unbound.conf');
        `sudo sed -i 's/ = 0/ = 1/' /etc/sysctl.conf`;
        `sudo sed -i 's/lo.disable_ipv6 = 1/lo.disable_ipv6 = 0/' /etc/sysctl.conf`;
    }

    if ( $info1 != '' || $info2 != '' || $info3 != '' || $info4 != '' || $info5 != '' || $info6 != '') {
        echo "<script>alert('$info1 $info2 $info3 $info4 $info6 telah diubah, silahkan reload atau reboot untuk mengaktifkan');</script>";
    }
    $index = 'yes'; $back = 'history.go(-2)';
}

$cssh = file_get_contents('ssh.port');
$csafe = file_get_contents('setsafesearch');
$ctproxy = file_get_contents('settproxy');
$cdnssec = file_get_contents('setdnssec');
$csnmpd = file_get_contents('setsnmpd');
$community = file_get_contents('snmpd.community');
$cip6 = file_get_contents('setip6');

if ($csafe == "yes") {
    $safe = 'checked';
} else {
    $safe = '';
}
if ($ctproxy == "yes") {
    $tproxy = 'checked';
} else {
    $tproxy = '';
}
if ($cdnssec == "no") {
    $dnssec = 'checked';
} else {
    $dnssec = '';
}
if ($csnmpd == "yes") {
    $snmpd = 'checked';
} else {
    $snmpd = '';
}

if ($cip6 == "yes") {
    $ip6 = 'checked';
} else {
    $ip6 = '';
}

if (strpos($referer, $allowed_prefix) !== 0 && strpos($referer, $allowed_prefix_ip) !== 0) {
        if (!isset($index) || $index !== 'yes') {
            $dashboard_ref = "https://$myip:40443/";
            if (strpos($referer, $dashboard_ref) !== 0 && strpos($referer, $allowed_prefix . "index.php") !== 0) exit(0);
        }
}

echo '<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1"/>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" type="text/css" href="style.css" />
<title>DNS TRUST-NG - OPTIONS</title>
<script src="kunci.js"></script>
</head>
<body class="with-sidebar sidebar-collapsed">
<div id="sidebar-overlay"></div>
<div class="page-shell">';
include_once 'menu.php';
trustng_render_sidebar('options.php');

echo '<div class="page-content">';
echo '<div class="tng-topbar"><button class="tng-topbar-toggle" title="Toggle menu" aria-label="Toggle menu"><svg width="20" height="20" viewBox="0 0 20 20" fill="none"><rect x="2" y="4.5" width="16" height="2" rx="1" fill="currentColor"/><rect x="2" y="9" width="16" height="2" rx="1" fill="currentColor"/><rect x="2" y="13.5" width="16" height="2" rx="1" fill="currentColor"/></svg></button><span class="tng-topbar-title">Options</span><div class="tng-topbar-spacer"></div><a class="tng-topbar-back" href="/">&#8592; Dashboard</a></div>';
//include 'submit.js';
echo'
<div align=center>
<a href="/"><img src="img/trustng-small.jpg" width="200px"></a>
<p>
<h3>Options<br><small>'.$ipaddr.'</small></h3>
<form name="ports" action="options.php" method="post">
<input type="hidden" name="options" value="submit">
<table>
<tr><td width=100>SSH Port</td><td width=80><input type="text" size="5" name="ssh" value="'.$cssh.'" placeholder="7857" onkeyup="checkNum(this);" required /></td><td><small>Jailed ssh untuk keperluan pengecekan</small></td></tr>
<tr><td>Cpanel Port</td><td><input type="text" size="5" name="dash" value="40443" placeholder="40443" disabled /></td><td><small>Akses web control panel dashboard</td></tr>
<noscript>
<tr><td>Î£ Threads</td><td>
<select name="thread" id="thr" required>
  <option value="4" disabled selected>default</option>
  <option value="1" '.$select1.'>1</option>
  <option value="2" '.$select2.'>2</option>
  <option value="4" '.$select3.'>4</option>
  <option value="5" '.$select4.'>8</option>
</select>
</td><td><small>Jumlah Unbound threads, default = 4</small></td></tr>
</noscript>
<tr><td>Safesearch</td><td><input style="display:inline;" type="checkbox" name="safe" value="yes" '.$safe.'/> Enable</td><td><small>Forced safesearch pada Google, Bing, Yandex, dan Duckduckgo</small></td></tr>
<tr><td>Tproxy</td><td><input style="display:inline;" type="checkbox" name="tproxy" value="yes" '.$tproxy.'/> Enable</td><td><small>Transparent DNS Server pada tcp/udp port 53</small></td></tr>
<tr><td>DNSSEC</td><td><input style="display:inline;" type="checkbox" name="dnssec" value="no" '.$dnssec.'/> Disable</td><td><small>Dnssec mungkin bermasalah dengan Safesearch, Forwarder, Hosts</small></td></tr>
<tr><td>SNMPD</td><td><input style="display:inline;" type="checkbox" name="snmpd" value="yes" '.$snmpd.'/> Enable</td><td rowspan=2><small>Fitur untuk keperluan software monitoring system via snmp. misal Cacti, PRTG, MRTG, dll</small></td></tr>
<tr><td>Community</td><td><input style="display:inline;" type="text" size="8" name="community" value="'.$community.'" placeholder="public"/></td></tr>
<tr><td>IPv6</td><td><input style="display:inline;" type="checkbox" name="ip6" value="yes" '.$ip6.'/> Enable</td><td><small>Support IPv6 dual stack, jika enable anda wajib isi IPv6, jika tidak system akan error</small></td></tr>
</table>
<input type="submit" id="submit" value="Simpan" class="submit-button"/> <a href="/"><input type="button" class="submit-button" value="Kembali"></a>
</form>
<p><small><b>&#169; 2024 Kominfo</b></small>
</div>';


echo '</div></div>';
?>