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
    exit('Permintaan reload tidak valid');
}

function trustng_command($command, &$output = null)
{
    $lines = array();
    $status = 1;
    exec($command . ' 2>&1', $lines, $status);
    $output = implode("\n", $lines);
    return $status === 0;
}

$actions = array(
    'setip6.new' => array('sudo -n /usr/sbin/sysctl -p', 'sudo -n /usr/sbin/service networking restart', 'sudo -n /usr/sbin/service unbound restart'),
    'setip.new' => array('sudo -n /usr/sbin/service networking restart', 'sudo -n /usr/sbin/service sshd restart', 'sudo -n /usr/sbin/service unbound restart'),
    'setalias.new' => array('./setipalias.sh', 'sudo -n /usr/sbin/service unbound restart'),
);

$messages = array();
$lock = fopen(__DIR__ . '/reload.lock', 'c');
if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
    http_response_code(409);
    exit('Proses reload lain sedang berjalan');
}

try {
    foreach ($actions as $flag => $commands) {
        if (!file_exists(__DIR__ . '/' . $flag)) continue;
        $ok = true;
        foreach ($commands as $command) {
            $output = '';
            $commandOk = trustng_command($command, $output);
            if (!$commandOk && $output !== '') $messages[] = $output;
            $ok = $commandOk && $ok;
        }
        if ($ok) @unlink(__DIR__ . '/' . $flag);
    }

    // Handle setdns.new — generate /etc/unbound/lamanlabuh.conf from lp*.ip files
    if (file_exists(__DIR__ . '/setdns.new')) {
        $webroot = __DIR__;
        $conf = '';
        for ($i = 1; $i <= 6; $i++) {
            $lip = @trim(file_get_contents("$webroot/lp$i.ip"));
            if ($lip === '' || !filter_var($lip, FILTER_VALIDATE_IP)) continue;
            $type = (strpos($lip, ':') !== false) ? 'AAAA' : 'A';
            $conf .= "local-data: \"blacklist. 60 IN $type $lip\"\n";
        }
        if ($conf !== '') {
            $tmpDns = tempnam('/tmp', 'trustng-dns-');
            file_put_contents($tmpDns, $conf);
            trustng_command('sudo -n /usr/bin/cp ' . escapeshellarg($tmpDns) . ' /etc/unbound/lamanlabuh.conf');
            @unlink($tmpDns);
        }
        trustng_command('sudo -n /usr/sbin/service unbound restart');
        @unlink(__DIR__ . '/setdns.new');
    }

    // Handle setclient.new — generate /etc/client_set from clients.ip
    if (file_exists(__DIR__ . '/setclient.new')) {
        $webroot = __DIR__;
        // IPv4
        $lines4 = @file("$webroot/clients.ip", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines4) {
            $data4 = implode(', ', array_map('trim', $lines4));
            $tmp = tempnam('/tmp', 'trustng-');
            file_put_contents($tmp, "elements = { $data4 }\n");
            $ok4 = trustng_command('sudo -n /usr/bin/cp ' . escapeshellarg($tmp) . ' /etc/client_set');
            @unlink($tmp);
            if (!$ok4) $messages[] = 'Gagal update /etc/client_set';
        }
        // IPv6
        $lines6 = @file("$webroot/clients6.ip", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines6) {
            $data6 = implode(', ', array_map('trim', $lines6));
            $tmp6 = tempnam('/tmp', 'trustng6-');
            file_put_contents($tmp6, "elements = { $data6 }\n");
            $ok6 = trustng_command('sudo -n /usr/bin/cp ' . escapeshellarg($tmp6) . ' /etc/client6_set');
            @unlink($tmp6);
            if (!$ok6) $messages[] = 'Gagal update /etc/client6_set';
        }
        $outNft = '';
        $okNft = trustng_command('sudo -n /usr/sbin/service nftables restart', $outNft);
        if (!$okNft && $outNft !== '') {
            $messages[] = 'Gagal restart nftables: ' . $outNft;
        }
        @unlink(__DIR__ . '/setclient.new');
    }

    if (file_exists(__DIR__ . '/setsnmpd.new')) {
        $enabled = trim(@file_get_contents(__DIR__ . '/setsnmpd')) === 'yes';
        $commands = $enabled
            ? array('sudo -n /usr/sbin/systemctl enable snmpd', 'sudo -n /usr/sbin/service snmpd start')
            : array('sudo -n /usr/sbin/service snmpd stop', 'sudo -n /usr/sbin/systemctl disable snmpd');
        $ok = true;
        foreach ($commands as $command) {
            $output = '';
            $commandOk = trustng_command($command, $output);
            if (!$commandOk && $output !== '') $messages[] = $output;
            $ok = $commandOk && $ok;
        }
        if ($ok) @unlink(__DIR__ . '/setsnmpd.new');
    }
} catch (Exception $e) {
    $messages[] = $e->getMessage();
}

flock($lock, LOCK_UN);
fclose($lock);

require_once __DIR__ . '/includes/ui.php';

if (count($messages) > 0) {
    $tone = 'critical';
    $title = 'Reload selesai dengan peringatan';
    $message = "Konfigurasi service telah diproses dengan catatan berikut.\n\n" . implode("\n", $messages);
} else {
    $tone = 'success';
    $title = 'Reload Selesai';
    $message = 'Konfigurasi service telah diproses. Anda akan diarahkan dalam beberapa detik.';
}

tng_ui_system_state($title, $message, $tone, 15, '/');
?>
