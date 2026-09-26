<?php
error_reporting(0);
$myip = $_SERVER['SERVER_ADDR'];
$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
$http_host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : "$myip:40443";
$proto = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$allowed_prefix = "$proto://$http_host/";
$allowed_prefix_ip = "https://$myip:40443/";

require_once __DIR__ . '/includes/unbound.php';
require_once __DIR__ . '/includes/auth.php';
if (isset($_POST['do']) && !tng_csrf_check($_POST['csrf'] ?? '')) {
    http_response_code(403);
    exit(0);
}
if (isset($_POST['do']) && strpos($referer, $allowed_prefix) !== 0 && strpos($referer, $allowed_prefix_ip) !== 0) {
    http_response_code(403);
    exit(0);
}
if($_POST['do'] ?? null) {
    if ($_POST['do'] === 'flush') {
        tng_unbound_collect_raw('flush_stats');
        $metrics_db = '/var/lib/trustng-metrics/metrics.db';
        if (is_file($metrics_db)) {
            if (is_writable($metrics_db)) {
                @unlink($metrics_db);
            } else {
                @shell_exec('sudo -n /usr/bin/rm -f ' . escapeshellarg($metrics_db) . ' 2>/dev/null');
            }
        }
        sleep(1);
        header('location: resetstats.php?done=1');
        exit(0);
    }
}

if ($referer != "https://$myip:40443/" && $referer != "https://$myip:40443/index.php") {
        if (!isset($index) || $index !== 'yes') {
            if (strpos($referer, $allowed_prefix) !== 0 && strpos($referer, $allowed_prefix_ip) !== 0) exit(0);
        }
}

require_once __DIR__ . '/includes/ui.php';

$done = isset($_GET['done']);

if ($done) {
    tng_ui_system_state('Statistik Direset', 'Counter runtime resolver berhasil direset. Riwayat arsip metrics tidak terpengaruh.', 'success', 5, 'maintenance.php');
    exit(0);
}

function statval($stats, $key) {
    if (preg_match('/^' . preg_quote($key, '/') . '=(\d+)/m', $stats, $m)) return $m[1];
    return '0';
}
$stats = tng_unbound_stats_raw();
$queries   = statval($stats, 'total.num.queries');
$blocked   = statval($stats, 'total.num.blacklist');
$cachehits = statval($stats, 'total.num.cachehits');
$uptime    = intval(statval($stats, 'time.up'));
$up_str = sprintf('%dd %dh %dm', intdiv($uptime, 86400), intdiv($uptime % 86400, 3600), intdiv($uptime % 3600, 60));

$rows = array(
    'Total Query'      => number_format((float)$queries, 0, ",", "."),
    'Domain Diblokir'  => number_format((float)$blocked, 0, ",", "."),
    'Cache Hits'       => number_format((float)$cachehits, 0, ",", "."),
    'Uptime Resolver'  => $up_str,
);

tng_ui_page_start('resetstats.php', 'Reset Statistik', 'Nol-kan counter runtime resolver. Riwayat arsip metrics tidak terpengaruh.', 'SISTEM');
tng_ui_card_start('Counter Runtime', 'Nilai saat ini sebelum reset dijalankan.');
foreach ($rows as $k => $v) {
    echo '<div class="set-row"><div class="set-row-info"><span class="set-row-name">' . tng_e($k) . '</span></div><span class="label-mono">' . tng_e($v) . '</span></div>';
}
tng_ui_card_end();

$confirm = "Konfirmasi reset statistik?\ncounter query/blokir akan kembali ke nol";

tng_ui_card_start('Konfirmasi', 'Reset menghapus counter runtime resolver (query, blokir, cache). Riwayat arsip metrics tidak terpengaruh.');
echo '<form method="post" action="resetstats.php"><input type="hidden" name="do" value="flush"/><input type="hidden" name="csrf" value="' . tng_e(tng_csrf_token()) . '"/>
<div class="form-actions"><button type="submit" class="button button-danger" data-confirm="' . tng_e($confirm) . '">Reset Statistik</button><a class="button button-secondary" href="maintenance.php">Kembali</a></div>
</form>';
tng_ui_card_end();
tng_ui_page_end('resetstats.php');
?>
