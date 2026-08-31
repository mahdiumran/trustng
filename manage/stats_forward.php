<?php
// Forward destination breakdown from Unbound stats
// Returns JSON: {"Local Cache":1635027,"Resolver1 (1.1.1.1)":73993,...}
header('Content-Type: application/json');

$stats = @shell_exec("dn stats_noreset 2>/dev/null");
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

if ($cachemiss > 0) {
    if (count($resolvers) > 0) {
        // Split cachemiss across resolvers
        $per_resolver = intval($cachemiss / count($resolvers));
        foreach ($resolvers as $name => $_) {
            $result[$name] = $per_resolver;
        }
    } else {
        $result['Upstream'] = $cachemiss;
    }
}

echo json_encode($result);
?>
