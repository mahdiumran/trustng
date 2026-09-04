<?php
// TRUST-NG stats helper — via resilient helper (socket perms / symlink / PATH)
require_once __DIR__ . '/includes/unbound.php';
$out = tng_unbound_stats_raw();
// Jika gagal, sertakan hint singkat agar dashboard.js tidak jatuh ke mode "sample" diam-diam
if ($out === '' || stripos($out, 'error:') !== false || stripos($out, 'could not') !== false) {
    $hint = $out !== '' ? $out : 'unbound-control tidak merespon (cek socket/permission/symlink)';
    $out = $hint . "\n[diagnose] sock=" . (file_exists('/etc/unbound/run/unbound.sock') ? 'exists' : 'missing')
         . " groups=" . trim(@shell_exec('groups www-data 2>&1') ?: '-');
}
echo "<pre>\n" . htmlspecialchars($out ?: '', ENT_QUOTES, 'UTF-8') . "\n</pre>";
?>
