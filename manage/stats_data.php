<?php
header('Content-Type: application/json');
error_reporting(0);
require_once __DIR__ . '/includes/unbound.php';

$raw = trim(tng_unbound_stats_raw());
$pairs = tng_unbound_stats_pairs($raw);
$err = '';
if ($raw === '' || preg_match('/^error:|^could not/i', trim($raw))) $err = $raw ?: 'unbound-control tidak merespon';
echo json_encode(array('ok' => $err === '', 'raw' => $raw, 'stats' => $pairs, 'error' => $err));
