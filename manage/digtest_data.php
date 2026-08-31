<?php
// TRUST-NG DNS Inspector — AJAX endpoint untuk digtest.php
// Return JSON: per-domain status resolve/blocked/nxdomain + jawaban A/AAAA.
// Mode manual: ?manual=1&domains=... (satu per baris/koma) untuk test di luar live test.
header('Content-Type: application/json');
error_reporting(0);

function di_dig_one($domain, $type) {
    $cmd = 'dig +short +time=2 +tries=1 @127.0.0.1 ' . escapeshellarg($domain) . ' ' . $type . ' 2>/dev/null';
    $out = trim(shell_exec($cmd) ?? '');
    return $out === '' ? array() : preg_split('/\s+/', $out);
}

function di_lp_ips() {
    $lp_ips = array();
    $lpconf = @file_get_contents('/etc/unbound/lamanlabuh.conf');
    if ($lpconf !== false) {
        preg_match_all('/IN A\s+([0-9.]+)/', $lpconf, $m);
        $lp_ips = $m[1] ?? array();
    }
    return $lp_ips;
}

function di_whitelist_list() {
    static $list = null;
    if ($list !== null) return $list;
    $list = array();
    $c = @file_get_contents(__DIR__ . '/whitelist.db');
    if ($c !== false) {
        foreach (preg_split('/\r\n|\r|\n/', $c) as $line) {
            $line = strtolower(trim($line));
            if ($line === '' || $line[0] === '#') continue;
            $list[] = $line;
        }
    }
    return $list;
}

function di_is_whitelisted($domain) {
    $list = di_whitelist_list();
    if (empty($list)) return false;
    $parts = explode('.', $domain);
    for ($i = 0; $i < count($parts); $i++) {
        $cand = implode('.', array_slice($parts, $i));
        if (in_array($cand, $list, true)) return true;
    }
    return false;
}

function di_build($domains) {
    $lp_ips = di_lp_ips();
    $results = array();
    foreach ($domains as $d) {
        $a = di_dig_one($d, 'A');
        $aaaa = di_dig_one($d, 'AAAA');
        $is_blocked = false;
        foreach ($a as $ip) {
            if (in_array($ip, $lp_ips, true)) { $is_blocked = true; break; }
        }
        if (di_is_whitelisted($d)) {
            $status = 'whitelisted';
        } elseif ($is_blocked) {
            $status = 'blocked';
        } elseif (!empty($a) || !empty($aaaa)) {
            $status = 'resolved';
        } else {
            $status = 'noanswer';
        }
        $results[] = array(
            'domain'  => $d,
            'status'  => $status,
            'a'       => $a,
            'aaaa'    => $aaaa,
        );
    }
    return $results;
}

// --- Mode manual: domain dari user ---
if (isset($_REQUEST['manual']) && $_REQUEST['manual'] === '1') {
    $raw = isset($_REQUEST['domains']) ? (string) $_REQUEST['domains'] : '';
    $lines = preg_split('/\r\n|\r|\n|,/', $raw);
    $domains = array();
    foreach ($lines as $l) {
        $l = strtolower(trim($l));
        if ($l === '' || $l[0] === '#') continue;
        if (!preg_match('/^[a-z0-9._-]+$/', $l)) continue;
        if (strlen($l) > 253) continue;
        $domains[] = $l;
    }
    $domains = array_slice(array_unique($domains), 0, 50);
    if (empty($domains)) {
        echo json_encode(array('ok' => false, 'error' => 'Tidak ada domain valid'));
        exit;
    }
    echo json_encode(array('ok' => true, 'results' => di_build($domains)));
    exit;
}

// --- Mode default: 10 domain terkonfigurasi ---
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

echo json_encode(array('ok' => true, 'results' => di_build($domains)));
