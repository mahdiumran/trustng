<?php
error_reporting(0);

$myip = $_SERVER['SERVER_ADDR'];
$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
$refererParts = parse_url($referer);
$requestHost = strtolower(preg_replace('/:[0-9]{1,5}$/', '', trim($_SERVER['HTTP_HOST'] ?? '')));
$refererHost = strtolower(trim($refererParts['host'] ?? '', '[]'));
$requestPort = isset($_SERVER['SERVER_PORT']) ? (int) $_SERVER['SERVER_PORT'] : 40443;
$refererPort = isset($refererParts['port']) ? (int) $refererParts['port'] : 443;
if (!is_array($refererParts) || ($refererParts['scheme'] ?? '') !== 'https'
    || $refererHost !== trim($requestHost, '[]') || $refererPort !== $requestPort
    || ($refererParts['path'] ?? '') !== '/maintenance.php') {
    http_response_code(403);
    exit('Permintaan reset tidak valid');
}

echo '<!DOCTYPE html>
<html lang="id"><head><meta charset="UTF-8"/><meta name="viewport" content="width=device-width, initial-scale=1"/>
<meta http-equiv="refresh" content="15; url=/login.php"/><title>Reset System</title><link rel="stylesheet" href="style.css"/></head>
<body><main class="system-state"><img class="state-logo" src="img/logo-img/trust-ng.jpg" alt="TRUST-NG">
<h3>Reset System<br><small>' . htmlspecialchars($myip, ENT_QUOTES, 'UTF-8') . '</small></h3>
<h4>Mohon ditunggu, sistem sedang me-reset ke default...</h4>
<div class="system-progress" aria-hidden="true"><span id="countdownprogress"></span></div>
<p>Anda akan diarahkan ke halaman login dalam <span id="countdowntimer">15</span> detik</p>
<p><a href="/login.php">Lanjutkan sekarang</a></p><p><small><b>&copy; 2024 Kominfo</b></small></p>
<script>var timeleft=15,timer=setInterval(function(){timeleft--;document.getElementById("countdowntimer").textContent=timeleft;document.getElementById("countdownprogress").style.width=(timeleft/15*100)+"%";if(timeleft<=0){clearInterval(timer);window.location.href="/login.php";}},1000);</script>
</main></body></html>';
@ob_flush(); @flush();

shell_exec("echo \"auto lo\niface lo inet loopback\n\nallow-hotplug eth0\niface eth0 inet dhcp\n\nauto eth0:0\niface eth0:0 inet static\naddress 192.168.168.168/24\n\" > /etc/network/interfaces");
shell_exec("echo local-data: \'blacklist. 60 IN A 103.181.142.196\' > /etc/unbound/lamanlabuh.conf");
shell_exec("echo local-data: \'blacklist. 60 IN A 103.173.75.28\' >> /etc/unbound/lamanlabuh.conf");
shell_exec("echo local-data: \'blacklist. 60 IN A 103.155.197.107\' >> /etc/unbound/lamanlabuh.conf");
shell_exec("echo local-data: \'blacklist. 60 IN AAAA 2406:20c0::103:151:222:227\' >> /etc/unbound/lamanlabuh.conf");
shell_exec("echo local-data: \'blacklist. 60 IN AAAA 2001:df4:b100:3:1:1:ee9b:2694\' >> /etc/unbound/lamanlabuh.conf");
shell_exec("echo local-data: \'blacklist. 60 IN AAAA 2001:df7:c180:1470:87:173:230:24\' >> /etc/unbound/lamanlabuh.conf");
shell_exec("./resetmunin.sh");

$file = fopen('clients.ip', 'w');
fwrite($file, "127.0.0.0/8\n192.168.0.0/16\n172.16.0.0/12\n10.0.0.0/8");
fclose($file);
shell_exec("echo \"elements = { 127.0.0.0/8, 192.168.0.0/16, 172.16.0.0/12, 10.0.0.0/8 }\" > /etc/client_set");
shell_exec("echo \"elements = { ::1/128 }\" > /etc/client6_set");

$file = fopen('lp1.ip', 'w');
fwrite($file, '139.255.196.196');
fclose($file);
$file = fopen('lp2.ip', 'w');
fwrite($file, '103.154.123.132');
fclose($file);
$file = fopen('lp3.ip', 'w');
fwrite($file, '182.23.79.195');
fclose($file);
$file = fopen('lp4.ip', 'w');
fwrite($file, '');
fclose($file);
$file = fopen('lp5.ip', 'w');
fwrite($file, '');
fclose($file);
$file = fopen('lp6.ip', 'w');
fwrite($file, '');
fclose($file);
$file = fopen('setip6', 'w');
fwrite($file, 'no');
fclose($file);
$file = fopen('ip6auto', 'w');
fwrite($file, 'no');
fclose($file);

$file = fopen('/etc/unbound/module-config.conf', 'w');
fwrite($file, 'module-config: "validator iterator"');
fclose($file);

include 'htpasswd.php';

$username = 'admin';
$password = 'trust-ng';

$encrypted_password = htpasswd($password);

$file = fopen('.htpasswd', 'w');
fwrite($file, "$username:$encrypted_password");
fclose($file);

$file = fopen('setup.mulai', 'w');
fwrite($file, "ini file utk mulai");
fclose($file);
shell_exec("echo admin:$password | sudo /usr/sbin/chpasswd");
sleep (0.3);
shell_exec('sudo /usr/sbin/service sshd restart');
sleep (0.3);
shell_exec('sudo /usr/sbin/service snmpd stop');
sleep (0.3);
shell_exec('sudo /usr/sbin/systemctl disable snmpd');
sleep (0.3);
shell_exec("./setipalias.sh");
sleep (0.3);
shell_exec('sudo /usr/sbin/service nftables restart');
sleep (0.3);
shell_exec('sudo  /usr/sbin/sysctl -p');
sleep (0.3);
shell_exec('sudo /usr/sbin/service unbound restart');

exit(0);
