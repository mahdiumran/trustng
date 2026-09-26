<?php
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');
error_reporting(0);

$myip = $_SERVER['SERVER_ADDR'] ?? '127.0.0.1';
$proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$httpHost = $_SERVER['HTTP_HOST'] ?? "$myip:40443";
$referer = $_SERVER['HTTP_REFERER'] ?? '';
$allowedPrefix = "$proto://$httpHost/";
if (strpos($referer, $allowedPrefix) !== 0 && strpos($referer, "https://$myip:40443/") !== 0) {
    http_response_code(403);
    echo json_encode(array('ok' => false, 'requests' => array(), 'error' => 'Forbidden'));
    exit(0);
}

require_once __DIR__ . '/includes/unbound.php';
$raw = trim(tng_unbound_collect_raw('dump_requestlist'));
$requests = array();
$thread = '-';
if ($raw !== '' && stripos($raw, 'error:') === false) {
    foreach (preg_split('/\r\n|\r|\n/', $raw) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (preg_match('/^thread\s+#?(\d+)/i', $line, $match)) {
            $thread = $match[1];
            continue;
        }
        $parts = preg_split('/\s+/', $line, 6);
        if (count($parts) < 4) continue;
        $requests[] = array(
            'thread' => $thread,
            'type' => $parts[0],
            'class' => $parts[1],
            'name' => rtrim($parts[2], '.'),
            'seconds' => is_numeric($parts[3]) ? round((float) $parts[3], 3) : $parts[3],
            'detail' => isset($parts[4]) ? implode(' ', array_slice($parts, 4)) : ''
        );
        if (count($requests) >= 50) break;
    }
}

echo json_encode(array(
    'ok' => stripos($raw, 'error:') === false,
    'count' => count($requests),
    'requests' => $requests,
    'empty' => count($requests) === 0,
    'error' => stripos($raw, 'error:') !== false ? $raw : ''
));
