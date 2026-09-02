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
    exit('Permintaan repair tidak valid');
}

$output = array();
$status = 1;
$script = file_exists('/usr/local/sbin/repairmunin.sh')
    ? '/usr/local/sbin/repairmunin.sh'
    : __DIR__ . '/repairmunin.sh';
exec('sudo ' . escapeshellarg($script) . ' 2>&1', $output, $status);
$message = $status === 0
    ? 'Repair Munin selesai. Grafik akan dibangun kembali dalam beberapa menit.'
    : 'Repair Munin gagal: ' . implode("\n", $output);
?>
<!DOCTYPE html>
<html lang="id"><head><meta charset="UTF-8"/><meta name="viewport" content="width=device-width, initial-scale=1"/>
<meta http-equiv="refresh" content="15; url=/"/><link rel="stylesheet" href="style.css"/><title>Repair Munin</title></head>
<body><main class="system-state"><img class="state-logo" src="img/logo-img/trust-ng.jpg" alt="TRUST-NG">
<h3>Repair Munin<br><small><?php echo htmlspecialchars($myip, ENT_QUOTES, 'UTF-8'); ?></small></h3>
<p><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
<p>Anda akan diarahkan kembali dalam <span id="countdowntimer">15</span> detik.</p>
<div class="system-progress" aria-hidden="true"><span id="countdownprogress"></span></div>
<p><a href="/">Lanjutkan sekarang</a></p><p><small><b>&copy; 2024 Kominfo</b></small></p>
<script>var timeleft=15,timer=setInterval(function(){timeleft--;document.getElementById('countdowntimer').textContent=timeleft;document.getElementById('countdownprogress').style.width=(timeleft/15*100)+'%';if(timeleft<=0){clearInterval(timer);window.location.href='/';}},1000);</script>
</main></body></html>
