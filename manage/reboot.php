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

require_once __DIR__ . '/includes/ui.php';

tng_ui_system_state('Reboot System', 'Mohon ditunggu, sistem sedang reboot. Anda akan diarahkan kembali dalam beberapa detik.', 'critical', 60, '/');
@ob_flush(); @flush();

shell_exec('sudo -n /sbin/reboot');
