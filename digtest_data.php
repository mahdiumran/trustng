<?php
// TRUST-NG DNS Inspector — AJAX endpoint untuk digtest.php
// Return JSON: per-domain status resolve/blocked/nxdomain + jawaban A/AAAA.
header('Content-Type: application/json');
error_reporting(0);

$defaults = array('www.google.com','www.facebook.com','www.bca.co.id','www.detik.com',
  'www.youtube.com','pornhub.com','kominfo.go.id','reddit.com','lamanlabuh.resolver.id','www.tiktok.com');
$domains = array();
for ($i = 0; $i < 10; $i++) {
    $f = __DIR__ . '/d' . $i . '.dig';
    $d = @file_get_contents($f);
    $d = strtolower(trim($d));
    if ($d === '' || !preg_match('/^[a-z0-9._-]+$/', $d)) {
        if (isset($defaults[$i])) { $domains[] = $defaults[$i]; }
        continue;
    }
    $domains[] = $d;
}
if (empty($domains)) $domains = array('example.com');

function dig_one($domain, $type) {
    $cmd = 'dig +short +time=2 +tries=1 @127.0.0.1 ' . escapeshellarg($domain) . ' ' . $type . ' 2>/dev/null';
    $out = trim(shell_exec($cmd) ?? '');
    return $out === '' ? array() : preg_split('/\s+/', $out);
}

$results = array();
foreach ($domains as $d) {
    $a = dig_one($d, 'A');
    $aaaa = dig_one($d, 'AAAA');
    // blocked = dijawab dari apex blacklist (A = IP lamanlabuh)
    $lp_ips = array();
    $lpconf = @file_get_contents('/etc/unbound/lamanlabuh.conf');
    if ($lpconf !== false) {
        preg_match_all('/IN A\s+([0-9.]+)/', $lpconf, $m);
        $lp_ips = $m[1] ?? array();
    }
    $is_blocked = false;
    foreach ($a as $ip) {
        if (in_array($ip, $lp_ips, true)) { $is_blocked = true; break; }
    }
    if ($is_blocked) {
        $status = 'blocked';
    } elseif (!empty($a) || !empty($aaaa)) {
        $status = 'resolved';
    } else {
        $status = 'noanswer'; // NXDOMAIN / no record
    }
    $results[] = array(
        'domain'  => $d,
        'status'  => $status,
        'a'       => $a,
        'aaaa'    => $aaaa,
    );
}

echo json_encode(array('ok' => true, 'results' => $results));
