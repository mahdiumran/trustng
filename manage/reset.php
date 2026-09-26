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

require_once __DIR__ . '/includes/ui.php';
require_once __DIR__ . '/includes/auth.php';

function trustng_reset_system_file($path, $content)
{
    $tmp = tempnam('/tmp', 'trustng-reset-');
    if ($tmp === false || file_put_contents($tmp, $content) === false) return false;
    $output = array();
    $status = 1;
    exec('/usr/bin/sudo -n /usr/bin/cp ' . escapeshellarg($tmp) . ' ' . escapeshellarg($path) . ' 2>&1', $output, $status);
    @unlink($tmp);
    return $status === 0;
}

tng_ui_system_state('Reset System', 'Mohon ditunggu, sistem sedang me-reset ke default. Anda akan diarahkan ke halaman login dalam beberapa detik.', 'critical', 15, '/login.php');
@ob_flush(); @flush();

trustng_reset_system_file('/etc/network/interfaces', "auto lo\niface lo inet loopback\n\nallow-hotplug eth0\niface eth0 inet dhcp\n\nauto eth0:0\niface eth0:0 inet static\naddress 192.168.168.168/24\n");
trustng_reset_system_file('/etc/unbound/lamanlabuh.conf', "local-data: \"blacklist. 60 IN A 10.150.1.18\"\nlocal-data: \"blacklist. 60 IN AAAA 2a0f:85c1:8b9:600::18\"\n");
exec('/usr/bin/sudo -n /usr/local/sbin/resetmunin.sh 2>&1');

$file = fopen('clients.ip', 'w');
fwrite($file, "127.0.0.0/8\n192.168.0.0/16\n172.16.0.0/12\n10.0.0.0/8");
fclose($file);
trustng_reset_system_file('/etc/client_set', "elements = { 127.0.0.0/8, 192.168.0.0/16, 172.16.0.0/12, 10.0.0.0/8 }\n");
trustng_reset_system_file('/etc/client6_set', "elements = { ::1/128 }\n");

$file = fopen('lp1.ip', 'w');
fwrite($file, '10.150.1.18');
fclose($file);
$file = fopen('lp2.ip', 'w');
fwrite($file, '');
fclose($file);
$file = fopen('lp3.ip', 'w');
fwrite($file, '');
fclose($file);
$file = fopen('lp4.ip', 'w');
fwrite($file, '2a0f:85c1:8b9:600::18');
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

trustng_reset_system_file('/etc/unbound/module-config.conf', 'module-config: "validator iterator"');

include 'htpasswd.php';

$username = 'admin';
$password = 'trust-ng';

$encrypted_password = htpasswd($password);

$file = fopen('.htpasswd', 'w');
fwrite($file, "$username:$encrypted_password");
fclose($file);

$file = fopen(TNG_SETUP_FLAG, 'w');
fwrite($file, "ini file utk mulai");
fclose($file);
shell_exec("echo admin:$password | sudo -n /usr/sbin/chpasswd");
sleep (0.3);
shell_exec('sudo -n /usr/sbin/service sshd restart');
sleep (0.3);
shell_exec('sudo -n /usr/sbin/service snmpd stop');
sleep (0.3);
shell_exec('sudo -n /usr/sbin/systemctl disable snmpd');
sleep (0.3);
shell_exec("./setipalias.sh");
sleep (0.3);
shell_exec('sudo -n /usr/sbin/service nftables restart');
sleep (0.3);
shell_exec('sudo -n  /usr/sbin/sysctl -p');
sleep (0.3);
shell_exec('sudo -n /usr/sbin/service unbound restart');

exit(0);
