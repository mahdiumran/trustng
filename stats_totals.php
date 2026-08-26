<?php
// DNS totals from Unbound stats
// Returns JSON: {"total_queries":1705383,"blocked_queries":3637,"cache_hits":1635027}
header('Content-Type: application/json');

$stats = @shell_exec("dn stats_noreset 2>/dev/null");
if (!$stats) {
    $stats = @shell_exec("unbound-control stats_noreset 2>/dev/null");
}

$total_queries = 0;
$blocked_queries = 0;
$cache_hits = 0;

if ($stats) {
    // Use total.* aggregate lines
    if (preg_match('/^total\.num\.queries\s*=\s*(\d+)/m', $stats, $m)) {
        $total_queries = intval($m[1]);
    }
    if (preg_match('/^total\.num\.blacklist\s*=\s*(\d+)/m', $stats, $m)) {
        $blocked_queries = intval($m[1]);
    }
    if (preg_match('/^total\.num\.cachehits\s*=\s*(\d+)/m', $stats, $m)) {
        $cache_hits = intval($m[1]);
    }
    // Fallback: sum thread values
    if ($total_queries === 0) {
        if (preg_match_all('/thread\d+\.num\.queries\s*=\s*(\d+)/i', $stats, $matches)) {
            foreach ($matches[1] as $v) $total_queries += intval($v);
        }
    }
    if ($blocked_queries === 0) {
        if (preg_match_all('/thread\d+\.num\.blacklist\s*=\s*(\d+)/i', $stats, $matches)) {
            foreach ($matches[1] as $v) $blocked_queries += intval($v);
        }
    }
    if ($cache_hits === 0) {
        if (preg_match_all('/thread\d+\.num\.cachehits\s*=\s*(\d+)/i', $stats, $matches)) {
            foreach ($matches[1] as $v) $cache_hits += intval($v);
        }
    }
}

echo json_encode(array(
    'total_queries' => $total_queries,
    'blocked_queries' => $blocked_queries,
    'cache_hits' => $cache_hits
));
?>
