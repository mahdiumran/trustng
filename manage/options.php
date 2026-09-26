<?php
error_reporting(0);
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/ui.php';
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
$ipaddr = shell_exec("ifconfig eth0 | grep netmask | sed 's/ .*inet //;s/ .*//'");
if (!file_exists('setsafesearch')) {
    $file = fopen('setsafesearch', 'w');
    if ($file) { fwrite($file, ''); fclose($file); }
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
$ctproxy = file_get_contents('settproxy');
$cdnssec = file_get_contents('setdnssec');
$csnmpd = file_get_contents('setsnmpd');
$cip6 = file_get_contents('setip6');

$info = '';

function trustng_write_system_file($path, $content)
{
    $tmp = tempnam('/tmp', 'trustng-config-');
    if ($tmp === false || file_put_contents($tmp, $content) === false) return false;
    $output = array();
    $status = 1;
    exec('/usr/bin/sudo -n /usr/bin/cp ' . escapeshellarg($tmp) . ' ' . escapeshellarg($path) . ' 2>&1', $output, $status);
    @unlink($tmp);
    return $status === 0;
}

if($_POST['options'] ?? null) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !tng_csrf_check($_POST['csrf'] ?? null)) {
        http_response_code(403);
        exit(0);
    }

    $safe = $_POST['safe'] ?? '';
    $tproxy = $_POST['tproxy'] ?? '';
    $dnssec = $_POST['dnssec'] ?? '';
    $snmpd = $_POST['snmpd'] ?? '';
    $community = $_POST['community'] ?? '';
    $ip6 = $_POST['ip6'] ?? '';

    if ($info === '') {
    $community = preg_replace('/[^A-Za-z0-9_.-]/', '', (string) $community);
    if ($community === '') $community = 'public';
    $snmp_conf = 'agentaddress udp:161' . "\n"
        . 'rocommunity ' . $community . " 0.0.0.0/0\n\n"
        . 'agentaddress udp6:161' . "\n"
        . 'rocommunity6 ' . $community . " ::/0\n";
    $tmp_snmp = tempnam('/tmp', 'trustng-snmp-');
    if ($tmp_snmp !== false) {
        file_put_contents($tmp_snmp, $snmp_conf);
        shell_exec('sudo -n /usr/bin/cp ' . escapeshellarg($tmp_snmp) . ' /etc/snmp/snmpd.conf 2>/dev/null');
        @unlink($tmp_snmp);
    }
    $file = fopen('snmpd.community', 'w');
    if ($file) { fwrite($file, $community); fclose($file); }
    if ($safe == 'yes' && $csafe == '') {
        $file = fopen('setsafesearch', 'w');
        if ($file) { fwrite($file, 'yes'); fclose($file); }
        $file = fopen('setdns.new', 'w');
        if ($file) { fwrite($file, ''); fclose($file); }
        $module_config = $dnssec != 'no' ? 'module-config: "respip validator iterator"' : 'module-config: "respip iterator"';
        trustng_write_system_file('/etc/unbound/module-config.conf', $module_config);
        trustng_write_system_file('/etc/unbound/rpz.conf', "rpz:\n\tname: rpz.safesearch\n\tzonefile: \"/etc/unbound/rpz.safesearch\"");
	$info2 = 'Safesearch,';
    } elseif ($safe == '' && $csafe == 'yes') {
        $file = fopen('setsafesearch', 'w');
        if ($file) { fwrite($file, ''); fclose($file); }
        $file = fopen('setdns.new', 'w');
        if ($file) { fwrite($file, ''); fclose($file); }
        $module_config = $dnssec != 'no' ? 'module-config: "validator iterator"' : 'module-config: "iterator"';
        trustng_write_system_file('/etc/unbound/module-config.conf', $module_config);
        trustng_write_system_file('/etc/unbound/rpz.conf', '');
        $info2 = 'Safesearch,';
    }

    if ($tproxy == 'yes' && $ctproxy == '') {
        $file = fopen('settproxy', 'w');
        if ($file) { fwrite($file, 'yes'); fclose($file); }
        $file = fopen('setclient.new', 'w');
        if ($file) { fwrite($file, ''); fclose($file); }
        $info3 = 'Tproxy';
	trustng_write_system_file('/etc/tproxy.conf', (string) @file_get_contents('/etc/tproxy.conf.new'));
    } elseif ($tproxy == '' && $ctproxy == 'yes') {
        $file = fopen('settproxy', 'w');
        if ($file) { fwrite($file, ''); fclose($file); }
        $file = fopen('setclient.new', 'w');
        if ($file) { fwrite($file, ''); fclose($file); }
        $info3 = 'Tproxy';
        trustng_write_system_file('/etc/tproxy.conf', '');
    }
    if ($dnssec != 'no' && $cdnssec != 'yes') {
        $file = fopen('setdnssec', 'w');
        if ($file) { fwrite($file, 'yes'); fclose($file); }
        $file = fopen('setdns.new', 'w');
        if ($file) { fwrite($file, ''); fclose($file); }
        $info4 = 'Dnssec';
        $module_config = $safe == 'yes' ? 'module-config: "respip validator iterator"' : 'module-config: "validator iterator"';
        trustng_write_system_file('/etc/unbound/module-config.conf', $module_config);

    } elseif ($dnssec == 'no' && $cdnssec != 'no') {
        $file = fopen('setdnssec', 'w');
        if ($file) { fwrite($file, 'no'); fclose($file); }
        $file = fopen('setdns.new', 'w');
        if ($file) { fwrite($file, ''); fclose($file); }
        $info4 = 'Dnssec';
        $module_config = $safe == 'yes' ? 'module-config: "respip iterator"' : 'module-config: "iterator"';
        trustng_write_system_file('/etc/unbound/module-config.conf', $module_config);
    }

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
	shell_exec('sudo -n sed -i "s/do-ip6: no/do-ip6: yes/" /etc/unbound/unbound.conf');
	`sudo -n sed -i 's/ = 1/ = 0/' /etc/sysctl.conf`;
	`sudo -n sed -i 's/lo.disable_ipv6 = 1/lo.disable_ipv6 = 0/' /etc/sysctl.conf`;
    } elseif ($ip6 != 'yes' && $cip6 == 'yes') {
        $file = fopen('setip6', 'w');
        if ($file) { fwrite($file, 'no'); fclose($file); }
        $file = fopen('setip6.new', 'w');
        if ($file) { fwrite($file, ''); fclose($file); }
        $info6 = 'Ip6';
        shell_exec('sudo -n sed -i "s/do-ip6: yes/do-ip6: no/" /etc/unbound/unbound.conf');
        `sudo -n sed -i 's/ = 0/ = 1/' /etc/sysctl.conf`;
        `sudo -n sed -i 's/lo.disable_ipv6 = 1/lo.disable_ipv6 = 0/' /etc/sysctl.conf`;
    }

    if ( ($info2 ?? '') != '' || ($info3 ?? '') != '' || ($info4 ?? '') != '' || ($info6 ?? '') != '') {
        $info = ($info2 ?? '') . ' ' . ($info3 ?? '') . ' ' . ($info4 ?? '') . ' ' . ($info6 ?? '') . ' telah disimpan, buka Maintenance lalu Reload untuk mengaktifkan';
    }
    $index = 'yes'; $back = 'history.go(-2)';
    }
}

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

tng_ui_page_start('options.php', 'Options', 'Validasi, IPv6, dan monitoring resolver. IP referensi: ' . trim($ipaddr));

if ($info !== '') {
    tng_ui_notice('success', 'Perubahan tersimpan', $info);
}

tng_ui_card_start('Akses & Keamanan', 'Validasi dan keamanan resolver DNS.');
echo '<form name="ports" action="options.php" method="post">
<input type="hidden" name="options" value="submit">
<input type="hidden" name="csrf" value="'.htmlspecialchars(tng_csrf_token(), ENT_QUOTES, 'UTF-8').'">
<div class="set-section">
  <div class="set-row">
    <div class="set-row-info">
      <span class="set-row-name" id="safe-label">Safesearch</span>
      <span class="set-row-desc">Paksa Safesearch pada Google, Bing, Yandex, dan DuckDuckGo.</span>
    </div>
    <div class="set-row-control">
      <label class="tng-switch"><input type="checkbox" name="safe" value="yes" aria-labelledby="safe-label" '.$safe.'><span class="tng-switch-track"></span></label>
    </div>
  </div>

  <div class="set-row">
    <div class="set-row-info">
      <span class="set-row-name" id="dnssec-label">DNSSEC</span>
      <span class="set-row-desc">Geser untuk menonaktifkan validasi DNSSEC (dapat bermasalah dengan Safesearch / Forwarder / Hosts).</span>
    </div>
    <div class="set-row-control">
      <label class="tng-switch"><input type="checkbox" name="dnssec" value="no" aria-labelledby="dnssec-label" '.$dnssec.'><span class="tng-switch-track"></span></label>
    </div>
  </div>

  <div class="set-row">
    <div class="set-row-info">
      <span class="set-row-name" id="tproxy-label">Tproxy</span>
      <span class="set-row-desc">Transparent DNS Server pada tcp/udp port 53.</span>
    </div>
    <div class="set-row-control">
      <label class="tng-switch"><input type="checkbox" name="tproxy" value="yes" aria-labelledby="tproxy-label" '.$tproxy.'><span class="tng-switch-track"></span></label>
    </div>
  </div>
</div>

<div class="set-section">
  <div class="set-section-head"><span class="set-section-title">Jaringan</span></div>
  <p class="set-section-desc">Dukungan IPv6 dual stack untuk resolver DNS.</p>

  <div class="set-row">
    <div class="set-row-info">
      <span class="set-row-name" id="ipv6-label">IPv6</span>
      <span class="set-row-desc">Dukungan IPv6 dual stack. Jika enable, wajib diisi di halaman IP Address.</span>
    </div>
    <div class="set-row-control">
      <label class="tng-switch"><input type="checkbox" name="ip6" value="yes" aria-labelledby="ipv6-label" '.$ip6.'><span class="tng-switch-track"></span></label>
    </div>
  </div>
</div>

<div class="set-section">
  <div class="set-section-head"><span class="set-section-title">Monitoring</span></div>
  <p class="set-section-desc">Integrasi dengan sistem monitoring via SNMP.</p>

  <div class="set-row">
    <div class="set-row-info">
      <span class="set-row-name" id="snmp-label">SNMPD</span>
      <span class="set-row-desc">Aktifkan layanan SNMP untuk Cacti, PRTG, MRTG, dll.</span>
    </div>
    <div class="set-row-control">
      <label class="tng-switch"><input type="checkbox" name="snmpd" value="yes" aria-labelledby="snmp-label" '.$snmpd.'><span class="tng-switch-track"></span></label>
    </div>
  </div>

  <div class="set-row">
    <div class="set-row-info">
      <span class="set-row-name"><label for="snmp-community">Community</label></span>
      <span class="set-row-desc">String community SNMP (default: public).</span>
    </div>
    <div class="set-row-control">
      <input id="snmp-community" class="compact-input" type="text" name="community" value="'.tng_e($community).'" placeholder="public" />
    </div>
  </div>
</div>

<div class="form-actions di-actions">
  <input type="submit" id="submit" value="Simpan" class="submit-button"/>
  <a class="submit-button button-secondary" href="/">Kembali</a>
</div>
</form>';
tng_ui_card_end();

echo '<script src="kunci.js"></script>';
tng_ui_page_end('options.php');
?>