<?php
// TRUST-NG DB Trust+ — AJAX endpoint untuk dbtrust.php
// Return JSON: mode keyword (grep trust.txt) atau domain (lookup CDB + status resolver).
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');
error_reporting(0);

// referer guard (XHR same-origin)
$myip = $_SERVER['SERVER_ADDR'] ?? '127.0.0.1';
$proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$http_host = $_SERVER['HTTP_HOST'] ?? "$myip:40443";
$allowed_prefix = "$proto://$http_host/";
$referer = $_SERVER['HTTP_REFERER'] ?? '';
if (strpos($referer, $allowed_prefix) !== 0 && strpos($referer, "https://$myip:40443/") !== 0) {
    echo json_encode(array('ok' => false, 'error' => 'Forbidden'));
    exit;
}

require_once __DIR__ . '/cdb_reader.php';

// --- resolver helpers (mirip digtest_data.php) ---
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

$mode = $_REQUEST['mode'] ?? 'keyword';
$q = trim($_REQUEST['q'] ?? '');

// ---------- mode domain ----------
if ($mode === 'domain') {
    $domain = strtolower($q);
    if (!preg_match('/^[a-z0-9._-]{1,253}$/', $domain)) {
        echo json_encode(array('ok' => false, 'error' => 'Format domain tidak valid'));
        exit;
    }
    $matched = trust_cdb_lookup('/etc/unbound/db/blacklist.db', $domain);
    $a = di_dig_one($domain, 'A');
    $aaaa = di_dig_one($domain, 'AAAA');
    $is_blocked = false;
    foreach ($a as $ip) { if (in_array($ip, di_lp_ips(), true)) { $is_blocked = true; break; } }
    if (di_is_whitelisted($domain)) $status = 'whitelisted';
    elseif ($is_blocked) $status = 'blocked';
    elseif (!empty($a) || !empty($aaaa)) $status = 'resolved';
    else $status = 'noanswer';
    echo json_encode(array(
        'ok' => true, 'mode' => 'domain', 'query' => $domain,
        'found' => $matched !== null, 'matched' => $matched,
        'status' => $status, 'a' => $a, 'aaaa' => $aaaa
    ));
    exit;
}

// ---------- mode keyword ----------
if ($q === '' || !preg_match('/^[\x20-\x7E]{1,120}$/', $q)) {
    echo json_encode(array('ok' => false, 'error' => 'Kata kunci tidak valid'));
    exit;
}
$safe = escapeshellarg($q);
$out = shell_exec("grep -F -m 500 $safe /etc/unbound/db/trust.txt 2>/dev/null");
$lines = array();
if ($out !== null && $out !== '') {
    foreach (explode("\n", $out) as $l) {
        $l = rtrim($l, "\r");
        if ($l !== '') $lines[] = $l;
    }
}
echo json_encode(array('ok' => true, 'mode' => 'keyword', 'query' => $q, 'total' => count($lines), 'results' => $lines));
