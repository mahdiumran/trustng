<?php
// DNS totals from Unbound stats
// Returns JSON: {"total_queries":1705383,"blocked_queries":3637,"cache_hits":1635027}
header('Content-Type: application/json');

$stats = @shell_exec("/usr/local/sbin/unbound-control stats_noreset 2>/dev/null");
if (!$stats) {
    $stats = @shell_exec("unbound-control stats_noreset 2>/dev/null");
}

$total_queries = 0;
$blocked_queries = 0;
$cache_hits = 0;

if ($stats) {
    // Parse all key=value pairs
    foreach (explode("\n", $stats) as $ln) {
        if (preg_match('/^([^=]+)=(\d+)$/', trim($ln), $m)) {
            $key = $m[1];
            $val = intval($m[2]);
            // Match num.queries, num.blacklist, num.cachehits (top-level, not per-thread)
            if ($key === 'num.queries') $total_queries = $val;
            elseif ($key === 'num.blacklist') $blocked_queries = $val;
            elseif ($key === 'num.cachehits') $cache_hits = $val;
            // Also try total.* prefix
            elseif ($key === 'total.num.queries') $total_queries = $val;
            elseif ($key === 'total.num.blacklist') $blocked_queries = $val;
            elseif ($key === 'total.num.cachehits') $cache_hits = $val;
        }
    }
    // Fallback: sum thread values if still zero
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
