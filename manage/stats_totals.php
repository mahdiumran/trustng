<?php
// DNS totals from Unbound stats — via resilient helper
header('Content-Type: application/json');
error_reporting(0);
require_once __DIR__ . '/includes/unbound.php';

$stats = tng_unbound_stats_raw();
$pairs = tng_unbound_stats_pairs($stats);
$total_queries = intval($pairs['total.num.queries'] ?? 0);
$blocked_queries = intval($pairs['total.num.blacklist'] ?? 0);
$cache_hits = intval($pairs['total.num.cachehits'] ?? 0);
$err = ($stats === '' || preg_match('/^error:|^could not/i', trim($stats))) ? ($stats ?: 'unbound-control tidak merespon') : '';
echo json_encode(array(
    'total_queries' => $total_queries,
    'blocked_queries' => $blocked_queries,
    'cache_hits' => $cache_hits,
    'error' => $err,
));
?>
