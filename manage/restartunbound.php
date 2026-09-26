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
    exit('Permintaan restart tidak valid');
}

$output = array();
$status = 1;
exec('sudo -n /usr/sbin/service unbound restart 2>&1', $output, $status);
$ok = ($status === 0);
$message = $ok
    ? 'Unbound berhasil direstart.'
    : 'Gagal me-restart Unbound: ' . implode("\n", $output);
if (!$ok && $status === 1 && strpos(implode("\n", $output), 'password') !== false) {
    $message .= "\n\nPastikan aturan sudo NOPASSWD untuk www-data terpasang (install.sh / update.sh) dan PHP-FPM sudah direstart.";
}

require_once __DIR__ . '/includes/ui.php';

tng_ui_system_state('Restart Unbound', $message, $ok ? 'success' : 'critical', 15, '/');
?>
