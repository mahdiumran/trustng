<?php
// Cache performance breakdown — via resilient helper
header('Content-Type: application/json');
error_reporting(0);
require_once __DIR__ . '/includes/unbound.php';
$stats = tng_unbound_stats_raw();
$pairs = tng_unbound_stats_pairs($stats);
$cachehits = intval($pairs['total.num.cachehits'] ?? 0);
$cachemiss = intval($pairs['total.num.cachemiss'] ?? 0);
$result = array();
if ($cachehits > 0) $result['Cache Hits'] = $cachehits;
if ($cachemiss > 0) $result['Cache Miss'] = $cachemiss;
if (empty($result) && ($stats === '' || preg_match('/^error:|^could not/i', trim($stats)))) $result['_error'] = $stats ?: 'unbound-control tidak merespon';
echo json_encode($result);
?>
