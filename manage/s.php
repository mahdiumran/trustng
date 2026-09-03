<?php
// TRUST-NG stats helper — direct unbound-control call
// Outputs raw key=value from unbound-control stats_noreset
// dashboard.js parses this and computes queries/s and blocked/s from cumulative deltas

$out = @shell_exec("/usr/local/sbin/unbound-control stats_noreset 2>/dev/null");
if (!$out) $out = @shell_exec("unbound-control stats_noreset 2>/dev/null");
echo "<pre>\n" . htmlspecialchars($out ?: '', ENT_QUOTES, 'UTF-8') . "\n</pre>";
?>
