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
    'setip6.new' => array('sudo /usr/sbin/sysctl -p', 'sudo /usr/sbin/service networking restart', 'sudo /usr/sbin/service unbound restart'),
    'setip.new' => array('sudo /usr/sbin/service networking restart', 'sudo /usr/sbin/service sshd restart'),
    'setalias.new' => array('./setipalias.sh', 'sudo /usr/sbin/service unbound restart'),
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
            if (!$commandOk && $output !== '') $messages[] = htmlspecialchars($output, ENT_QUOTES, 'UTF-8');
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
            trustng_command('sudo /usr/bin/cp ' . escapeshellarg($tmpDns) . ' /etc/unbound/lamanlabuh.conf');
            @unlink($tmpDns);
        }
        trustng_command('sudo /usr/sbin/service unbound restart');
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
            $ok4 = trustng_command('sudo /usr/bin/cp ' . escapeshellarg($tmp) . ' /etc/client_set');
            @unlink($tmp);
            if (!$ok4) $messages[] = 'Gagal update /etc/client_set';
        }
        // IPv6
        $lines6 = @file("$webroot/clients6.ip", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines6) {
            $data6 = implode(', ', array_map('trim', $lines6));
            $tmp6 = tempnam('/tmp', 'trustng6-');
            file_put_contents($tmp6, "elements = { $data6 }\n");
            $ok6 = trustng_command('sudo /usr/bin/cp ' . escapeshellarg($tmp6) . ' /etc/client6_set');
            @unlink($tmp6);
            if (!$ok6) $messages[] = 'Gagal update /etc/client6_set';
        }
        $outNft = '';
        $okNft = trustng_command('sudo /usr/sbin/service nftables restart', $outNft);
        if (!$okNft && $outNft !== '') {
            $messages[] = 'Gagal restart nftables: ' . htmlspecialchars($outNft, ENT_QUOTES, 'UTF-8');
        }
        @unlink(__DIR__ . '/setclient.new');
    }

    if (file_exists(__DIR__ . '/setsnmpd.new')) {
        $enabled = trim(@file_get_contents(__DIR__ . '/setsnmpd')) === 'yes';
        $commands = $enabled
            ? array('sudo /usr/sbin/systemctl enable snmpd', 'sudo /usr/sbin/service snmpd start')
            : array('sudo /usr/sbin/service snmpd stop', 'sudo /usr/sbin/systemctl disable snmpd');
        $ok = true;
        foreach ($commands as $command) {
            $output = '';
            $commandOk = trustng_command($command, $output);
            if (!$commandOk && $output !== '') $messages[] = htmlspecialchars($output, ENT_QUOTES, 'UTF-8');
            $ok = $commandOk && $ok;
        }
        if ($ok) @unlink(__DIR__ . '/setsnmpd.new');
    }
} catch (Exception $e) {
    $messages[] = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
}

flock($lock, LOCK_UN);
fclose($lock);
?><!DOCTYPE html>
<html lang="id"><head><meta name="viewport" content="width=device-width, initial-scale=1">
<meta http-equiv="refresh" content="15; url=/">
<link rel="stylesheet" href="style.css"><title>Reload System</title></head>
<body><main class="system-state"><img class="state-logo" src="img/logo-img/trust-ng.jpg" alt="TRUST-NG">
<h3>Reload System<br><small><?php echo htmlspecialchars($myip, ENT_QUOTES, 'UTF-8'); ?></small></h3>
<?php foreach ($messages as $message): ?><p><?php echo $message; ?></p><?php endforeach; ?>
<p>Konfigurasi service telah diproses. Anda akan diarahkan dalam <span id="countdowntimer">15</span> detik.</p><div class="system-progress" aria-hidden="true"><span id="countdownprogress"></span></div><p><a href="/">Lanjutkan sekarang</a></p>
<p><small><b>&copy; 2024 Kominfo</b></small></p><script>var timeleft=15,timer=setInterval(function(){timeleft--;document.getElementById('countdowntimer').textContent=timeleft;document.getElementById('countdownprogress').style.width=(timeleft/15*100)+'%';if(timeleft<=0){clearInterval(timer);window.location.href='/';}},1000);</script></main></body></html>
