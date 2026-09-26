<?php
// Forward destination breakdown from Unbound stats
// Returns JSON: {"Local Cache":1635027,"Resolver1 (1.1.1.1)":73993,...}
header('Content-Type: application/json');
error_reporting(0);
require_once __DIR__ . '/includes/unbound.php';
$stats = tng_unbound_stats_raw();
$pairs = tng_unbound_stats_pairs($stats);
$cachehits = intval($pairs['total.num.cachehits'] ?? 0);
$cachemiss = intval($pairs['total.num.cachemiss'] ?? 0);

// Read parent resolvers from resolver.data
$resolver_data = @file_get_contents('resolver.data');
$resolvers = array();
if ($resolver_data !== false && trim($resolver_data) !== '') {
    $parts = explode(',', $resolver_data);
    foreach ($parts as $i => $r) {
        $r = trim($r);
        if ($r !== '') {
            $resolvers['Resolver' . ($i + 1) . ' (' . $r . ')'] = 0;
        }
    }
}

$result = array();
if ($cachehits > 0) $result['Local Cache'] = $cachehits;
if ($cachemiss > 0) $result['Recursive / Forwarded'] = $cachemiss;
if (empty($result) && ($stats === '' || preg_match('/^error:|^could not/i', trim($stats)))) $result['_error'] = $stats ?: 'unbound-control tidak merespon';

echo json_encode($result);
?>
