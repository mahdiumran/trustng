<?php
error_reporting(0);
$myip = $_SERVER['SERVER_ADDR'];
$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
$http_host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : "$myip:40443";
$proto = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$allowed_prefix = "$proto://$http_host/";
$allowed_prefix_ip = "https://$myip:40443/";

if (strpos($referer, $allowed_prefix . "maintenance.php") !== 0 && strpos($referer, $allowed_prefix_ip . "maintenance.php") !== 0) exit;
include 'reload.html';

$filename = 'setip6.new';
if(file_exists($filename)){
    shell_exec('sudo  /usr/sbin/sysctl -p');
    sleep (0.3);
    shell_exec('sudo /usr/sbin/service networking restart');
    sleep (0.3);
    $cek=`grep inet6 /etc/network/interfaces`;
    if ($cek == '') {
	shell_exec('sudo sed -i "s/do-ip6: yes/do-ip6: no/" /etc/unbound/unbound.conf');
        $file = fopen('setip6', 'w');
        fwrite($file, 'no');
        fclose($file);
    }
    shell_exec('sudo /usr/sbin/service unbound restart');
    sleep (0.3);
    unlink('setip6.new');
}

$filename = 'setip.new';
if(file_exists($filename)){
    shell_exec('sudo /usr/sbin/service networking restart');
    sleep (0.3);
    shell_exec('sudo /usr/sbin/service sshd restart');
    sleep (0.3);
    $pid = file_get_contents('/var/run/sshd2.pid');
    shell_exec("sudo /usr/bin/kill -HUP $pid");
    sleep (0.3);
    unlink('setip.new');
}

$filename = 'setdns.new';
if(file_exists($filename)){
    shell_exec('sudo /usr/sbin/service unbound restart');
    sleep (0.3);
    unlink('setdns.new');
}

$filename = 'setclient.new';
if(file_exists($filename)){
    shell_exec('sudo /usr/sbin/service nftables restart');
    sleep (0.3);
    unlink('setclient.new');
}

$filename = 'setssh.new';
if(file_exists($filename)){
    $pid = file_get_contents('/var/run/sshd2.pid');
    shell_exec("sudo /usr/bin/kill -HUP $pid");
    sleep (0.3);
    unlink('setssh.new');
}
$filename = 'setalias.new';
if(file_exists($filename)){
    shell_exec("./setipalias.sh");
    sleep (0.3);
    shell_exec('sudo /usr/sbin/service unbound restart');
    sleep (0.3);
    unlink('setalias.new');
}

$filename = 'setsnmpd.new';
if(file_exists($filename)){
    $snmpd = file_get_contents('setsnmpd');
    if ($snmpd == 'no') {
        shell_exec('sudo /usr/sbin/service snmpd stop');
        sleep (0.3);
        shell_exec('sudo /usr/sbin/systemctl disable snmpd');
   } else {
        shell_exec('sudo /usr/sbin/systemctl enable snmpd');
        sleep (0.3);
        shell_exec('sudo /usr/sbin/service snmpd start');
        sleep (0.3);
    }
    unlink('setsnmpd.new');
}

?>
