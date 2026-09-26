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
$cache_miss = intval($pairs['total.num.cachemiss'] ?? 0);
$cache_total = $cache_hits + $cache_miss;
$cache_ratio = $cache_total > 0 ? round(($cache_hits / $cache_total) * 100, 1) : 0;
$uptime = intval(floatval($pairs['time.up'] ?? 0));
$request_current = intval($pairs['total.requestlist.current.all'] ?? 0);
$recursion_avg_ms = round(floatval($pairs['total.recursion.time.avg'] ?? 0) * 1000, 3);
$err = ($stats === '' || preg_match('/^error:|^could not/i', trim($stats))) ? ($stats ?: 'unbound-control tidak merespon') : '';
echo json_encode(array(
    'total_queries' => $total_queries,
    'blocked_queries' => $blocked_queries,
    'cache_hits' => $cache_hits,
    'cache_miss' => $cache_miss,
    'cache_ratio' => $cache_ratio,
    'uptime' => $uptime,
    'request_current' => $request_current,
    'recursion_avg_ms' => $recursion_avg_ms,
    'error' => $err,
));
?>
