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
    // Parse all key=value pairs
    foreach (explode("\n", $stats) as $ln) {
        if (preg_match('/^([^=]+)=(\d+)$/', trim($ln), $m)) {
            $key = $m[1];
            $val = intval($m[2]);
            if ($key === 'num.cachehits') $cachehits = $val;
            elseif ($key === 'num.cachemiss') $cachemiss = $val;
            elseif ($key === 'total.num.cachehits') $cachehits = $val;
            elseif ($key === 'total.num.cachemiss') $cachemiss = $val;
        }
    }
    // Fallback: sum thread values if still zero
    if ($cachehits === 0) {
        if (preg_match_all('/thread\d+\.num\.cachehits\s*=\s*(\d+)/i', $stats, $matches)) {
            foreach ($matches[1] as $v) $cachehits += intval($v);
        }
    }
    if ($cachemiss === 0) {
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
