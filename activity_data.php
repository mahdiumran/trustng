<?php
// TRUST-NG Activity Log — AJAX endpoint untuk activity.php
// Return JSON: status saat ini + riwayat update blocklist (dari blocklist_history.log).
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
    echo json_encode(array('status' => null, 'history' => array()));
    exit;
}

$DB_DIR = '/etc/unbound/db';
$count = trim(@file_get_contents("$DB_DIR/trust.count") ?: '0');

$history = array();
$histfile = "$DB_DIR/blocklist_history.log";
if (is_file($histfile)) {
    foreach (file($histfile) as $line) {
        $line = trim($line);
        if ($line === '') continue;
        $p = explode(' | ', $line);
        if (count($p) < 5) continue;
        $domains = 0; $build = '';
        if (preg_match('/domains=(\d+)/', $p[3], $m)) $domains = (int)$m[1];
        if (preg_match('/build=(\d+)s/', $p[4], $m)) $build = $m[1] . 's';
        $history[] = array('ts' => $p[0], 'status' => $p[1], 'health' => $p[2], 'domains' => $domains, 'build' => $build);
    }
    $history = array_reverse($history);
    $history = array_slice($history, 0, 50);
}

$last_update = $history ? $history[0]['ts'] : '';
$last_health = $history ? $history[0]['health'] : '';
if ($last_update === '' || $last_health === '') {
    $st = @file_get_contents("$DB_DIR/blocklist_status.log");
    if ($st && preg_match('/\[SUCCESS\]\s*([\d\-: ]+)/', $st, $m)) $last_update = trim($m[1]);
}
if ($last_update === '') {
    $mt = @filemtime("$DB_DIR/trust.txt");
    if ($mt) $last_update = date('Y-m-d H:i:s', $mt);
}

$next_run = '—';
$enabled = false;
$out = @shell_exec("systemctl list-timers update-blocklist.timer --no-pager 2>/dev/null");
if ($out && preg_match('/update-blocklist\.timer/', $out)) {
    $next_run = trim(@shell_exec("systemctl list-timers update-blocklist.timer --no-pager 2>/dev/null | awk '/update-blocklist.timer/{print $1, $2, $3, $4; exit}'")) ?: '—';
    $enabled = true;
} else {
    $en = @shell_exec("systemctl is-enabled update-blocklist.timer 2>/dev/null");
    $enabled = trim($en) === 'enabled';
}

echo json_encode(array(
    'status' => array(
        'count' => (int)$count,
        'last_update' => $last_update ?: '—',
        'last_health' => $last_health ?: '—',
        'next_run' => $next_run,
        'timer_enabled' => $enabled
    ),
    'history' => $history
));
