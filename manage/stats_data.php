<?php
header('Content-Type: application/json');
error_reporting(0);

function st_collect() {
    $out = @shell_exec("dn stats_noreset 2>/dev/null");
    if (!$out) $out = @shell_exec("unbound-control stats_noreset 2>/dev/null");
    if (!$out) $out = @shell_exec("/usr/bin/s 2>/dev/null");
    return $out ?: '';
}

$raw = trim(st_collect());
$pairs = array();
foreach (explode("\n", $raw) as $ln) {
    if (preg_match('/^([^=]+)=(.*)$/', $ln, $m)) $pairs[trim($m[1])] = trim($m[2]);
}

// `s` only emits per-thread keys; derive totals by summing threads
$derive = array('num.queries', 'num.blacklist', 'num.cachehits', 'num.cachemiss', 'num.recursivereplies', 'num.prefetch');
foreach ($derive as $suf) {
    if (!isset($pairs['total.' . $suf])) {
        $sum = 0; $found = false;
        foreach ($pairs as $k => $v) {
            if (preg_match('/^thread\d+\.' . preg_quote($suf, '/') . '$/', $k)) { $sum += (float)$v; $found = true; }
        }
        if ($found) $pairs['total.' . $suf] = (string)$sum;
    }
}

echo json_encode(array('ok' => true, 'raw' => $raw, 'stats' => $pairs));
