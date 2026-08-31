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
    exit('Permintaan reboot tidak valid');
}

echo '<!DOCTYPE html>
<html lang="id"><head><meta charset="UTF-8"/><meta name="viewport" content="width=device-width, initial-scale=1"/>
<title>Reboot System</title><link rel="stylesheet" href="style.css"/></head>
<body><main class="system-state"><img class="state-logo" src="img/logo-img/trust-ng.jpg" alt="TRUST-NG">
<h3>Reboot System<br><small>' . htmlspecialchars($myip, ENT_QUOTES, 'UTF-8') . '</small></h3>
<h4>Mohon ditunggu, sistem sedang reboot...</h4>
<div class="system-progress" aria-hidden="true"><span id="countdownprogress"></span></div>
<p>Anda akan diarahkan kembali dalam <span id="countdowntimer">60</span> detik</p>
<p><a href="/">Lanjutkan sekarang</a></p><p><small><b>&copy; 2024 Kominfo</b></small></p>
<script>var timeleft=60,timer=setInterval(function(){timeleft--;document.getElementById("countdowntimer").textContent=timeleft;document.getElementById("countdownprogress").style.width=(timeleft/60*100)+"%";if(timeleft<=0){clearInterval(timer);window.location.href="/";}},1000);</script>
</main></body></html>';
@ob_flush(); @flush();

shell_exec('sudo /sbin/reboot');
