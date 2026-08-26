<?php
$myip = $_SERVER['SERVER_ADDR'];
$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
$http_host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : "$myip:40443";
$proto = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$allowed_prefix = "$proto://$http_host/";
$allowed_prefix_ip = "https://$myip:40443/";

if (strpos($referer, $allowed_prefix . "maintenance.php") !== 0 && strpos($referer, $allowed_prefix_ip . "maintenance.php") !== 0) exit;

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
$pid = file_get_contents('/var/run/sshd2.pid');
shell_exec("sudo /usr/bin/kill -HUP $pid");
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

header('location: index.php');

?>
