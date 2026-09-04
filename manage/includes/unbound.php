<?php
/**
 * TRUST-NG Unbound helper — centralized, resilient stats collection
 * Mengatasi: permission socket, missing symlink, PATH, dan silent 2>/dev/null
 * yang bikin deploy baru tampil 0 padahal file PHP sama.
 */
error_reporting(0);

if (!defined('TNG_UNBOUND_CONF')) define('TNG_UNBOUND_CONF', '/etc/unbound/unbound.conf');
if (!defined('TNG_UNBOUND_SOCK')) define('TNG_UNBOUND_SOCK', '/etc/unbound/run/unbound.sock');

function tng_unbound_collect_raw($cmd_suffix) {
    // $cmd_suffix: "stats_noreset" | "dump_requestlist" | "flush_stats" | "status"
    $candidates = array(
        "/usr/local/sbin/unbound-control -c " . escapeshellarg(TNG_UNBOUND_CONF) . " $cmd_suffix 2>&1",
        "/usr/local/sbin/unbound-control $cmd_suffix 2>&1",
        "unbound-control -c " . escapeshellarg(TNG_UNBOUND_CONF) . " $cmd_suffix 2>&1",
        "unbound-control $cmd_suffix 2>&1",
    );
    $last = '';
    foreach ($candidates as $cmd) {
        $out = @shell_exec($cmd);
        if ($out === null) $out = '';
        $out = trim($out);
        // Valid output contains key=value (stats) atau non-error text
        if ($out !== '' && stripos($out, 'error:') === false && stripos($out, 'could not') === false && stripos($out, 'failed') === false) {
            return $out;
        }
        // Simpan error untuk fallback sudo / diagnostic
        if ($out !== '') $last = $out;
        // Jika output mengandung thread/total/time key, anggap valid walau ada warning
        if (preg_match('/^(thread\d+\.|total\.|time\.)/m', $out)) return $out;
    }
    // Fallback sudo — hanya jika error permission / config
    if ($last !== '' && (stripos($last, 'Permission denied') !== false || stripos($last, 'connect:') !== false || stripos($last, 'could not read') !== false)) {
        foreach (array(
            "sudo /usr/local/sbin/unbound-control -c " . escapeshellarg(TNG_UNBOUND_CONF) . " $cmd_suffix 2>&1",
            "sudo unbound-control -c " . escapeshellarg(TNG_UNBOUND_CONF) . " $cmd_suffix 2>&1",
            "sudo /usr/local/sbin/unbound-control $cmd_suffix 2>&1",
        ) as $cmd) {
            $out = @shell_exec($cmd);
            if ($out === null) $out = '';
            $out = trim($out);
            if ($out !== '' && stripos($out, 'error:') === false) return $out;
            if (preg_match('/^(thread\d+\.|total\.|time\.)/m', $out)) return $out;
            if ($out !== '') $last = $out;
        }
    }
    // Return raw error for diagnostic (caller decides)
    return $last;
}

function tng_unbound_stats_raw() {
    $raw = tng_unbound_collect_raw('stats_noreset');
    // Light syslog on failure to aid remote diagnose without exposing to browser
    if ($raw === '' || stripos($raw, 'error:') !== false) {
        @error_log("[trustng] unbound-control stats_noreset failed: " . substr($raw, 0, 500));
    }
    return $raw;
}

function tng_unbound_stats_pairs($raw) {
    $pairs = array();
    if ($raw === '' || $raw === null) return $pairs;
    foreach (explode("\n", $raw) as $ln) {
        $ln = trim($ln);
        if ($ln === '' || strpos($ln, '=') === false) continue;
        if (preg_match('/^([^=]+)=(.*)$/', $ln, $m)) $pairs[trim($m[1])] = trim($m[2]);
    }
    // Derive total.* sama seperti stats.php:28 — fallback thread sum jika hanya threadX
    $derive = array('num.queries','num.blacklist','num.cachehits','num.cachemiss','num.recursivereplies','num.prefetch');
    foreach ($derive as $suf) {
        if (!isset($pairs['total.' . $suf])) {
            if (isset($pairs[$suf])) {
                $pairs['total.' . $suf] = $pairs[$suf];
            } else {
                $sum = 0; $found = false;
                foreach ($pairs as $k => $v) {
                    if (preg_match('/^thread\d+\.' . preg_quote($suf, '/') . '$/', $k)) { $sum += (float)$v; $found = true; }
                }
                if ($found) $pairs['total.' . $suf] = (string)$sum;
            }
        }
    }
    return $pairs;
}

function tng_unbound_diagnose() {
    $checks = array();
    $checks['sock_exists'] = file_exists(TNG_UNBOUND_SOCK) ? 'yes' : 'no';
    $checks['sock_perms'] = @fileperms(TNG_UNBOUND_SOCK) ? sprintf('%o', @fileperms(TNG_UNBOUND_SOCK) & 0777) : '-';
    $checks['sock_group'] = @filegroup(TNG_UNBOUND_SOCK) !== false ? @posix_getgrgid(@filegroup(TNG_UNBOUND_SOCK))['name'] ?? (string)@filegroup(TNG_UNBOUND_SOCK) : '-';
    $checks['link_unbound_conf'] = is_link('/usr/local/etc/unbound/unbound.conf') ? readlink('/usr/local/etc/unbound/unbound.conf') : (file_exists('/usr/local/etc/unbound/unbound.conf') ? 'file' : 'missing');
    $checks['www_groups'] = trim(@shell_exec('groups www-data 2>&1') ?: '');
    $checks['php_path'] = getenv('PATH') ?: trim(@shell_exec('echo $PATH 2>&1') ?: '');
    $checks['unbound_version'] = trim(@shell_exec('/usr/local/sbin/unbound -V 2>&1 | head -1') ?: '');
    $checks['control_test'] = substr(tng_unbound_collect_raw('stats_noreset'), 0, 200);
    return $checks;
}
