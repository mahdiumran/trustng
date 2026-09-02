<?php
// Cache performance breakdown from Unbound stats
// Returns JSON: {"Cache Hits":1635027,"Cache Miss":73993}
header('Content-Type: application/json');

$stats = @shell_exec("/usr/local/sbin/unbound-control stats_noreset 2>/dev/null");
if (!$stats) {
    $stats = @shell_exec("unbound-control stats_noreset 2>/dev/null");
}

$cachehits = 0;
$cachemiss = 0;

if ($stats) {
    // Use total.* aggregate lines
    if (preg_match('/^total\.num\.cachehits\s*=\s*(\d+)/m', $stats, $m)) {
        $cachehits = intval($m[1]);
    }
    if (preg_match('/^total\.num\.cachemiss\s*=\s*(\d+)/m', $stats, $m)) {
        $cachemiss = intval($m[1]);
    }
    // Fallback: sum thread values
    if ($cachehits === 0 && $cachemiss === 0) {
        if (preg_match_all('/thread\d+\.num\.cachehits\s*=\s*(\d+)/i', $stats, $matches)) {
            foreach ($matches[1] as $v) $cachehits += intval($v);
        }
        if (preg_match_all('/thread\d+\.num\.cachemiss\s*=\s*(\d+)/i', $stats, $matches)) {
            foreach ($matches[1] as $v) $cachemiss += intval($v);
        }
    }
}

$result = array();
if ($cachehits > 0) $result['Cache Hits'] = $cachehits;
if ($cachemiss > 0) $result['Cache Miss'] = $cachemiss;

echo json_encode($result);
?>
