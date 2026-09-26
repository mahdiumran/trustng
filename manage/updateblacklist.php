<?php
error_reporting(0);
$myip = $_SERVER['SERVER_ADDR'];
$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
$http_host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : "$myip:40443";
$proto = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$allowed_prefix = "$proto://$http_host/";
$allowed_prefix_ip = "https://$myip:40443/";

if (strpos($referer, $allowed_prefix . "maintenance.php") !== 0 && strpos($referer, $allowed_prefix_ip . "maintenance.php") !== 0) exit;

require_once __DIR__ . '/includes/ui.php';

$running = trim(shell_exec("systemctl is-active update-blocklist 2>/dev/null") ?? '');
if ($running === 'active') {
    tng_ui_page_start('maintenance.php', 'Update Blacklist', 'Proses update blocklist sedang berjalan.', 'SISTEM');
    tng_ui_notice('warning', 'Proses berjalan', 'Proses update blocklist sedang berjalan. Pantau progres dan hasilnya di Activity Log.');
    tng_ui_card_start('Proses Aktif', 'Service update-blocklist sedang aktif.');
    echo '<div class="form-actions"><a class="button" href="activity.php">Buka Activity Log</a><a class="button button-secondary" href="maintenance.php">Kembali</a></div>';
    tng_ui_card_end();
    tng_ui_page_end('maintenance.php');
    exit;
}

shell_exec("sudo -n /usr/bin/systemctl start update-blocklist 2>&1");

tng_ui_system_state('Update Blacklist', 'Memulai proses update blocklist. Anda akan diarahkan ke Activity Log dalam beberapa detik.', 'info', 5, 'activity.php');
?>
