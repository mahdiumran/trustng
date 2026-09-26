<?php
error_reporting(0);
$myip = $_SERVER['SERVER_ADDR'];
$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
$http_host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : "$myip:40443";
$proto = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$allowed_prefix = "$proto://$http_host/";
$allowed_prefix_ip = "https://$myip:40443/";

if (strpos($referer, $allowed_prefix) !== 0 && strpos($referer, $allowed_prefix_ip) !== 0) {
        exit(0);
}
$back = 'history.back()';
$ipaddr = shell_exec("ifconfig eth0 | grep netmask | sed 's/ .*inet //;s/ .*//'");

require_once __DIR__ . '/includes/ui.php';

$actions = array(
    array('repairmunin.php', 'fa-chart-simple', 'Repair Graph', '', 'Konfirmasi repair Munin? graph akan direset dan bisa dilihat kembali 5 menit kemudian'),
    array('restartunbound.php', 'fa-rotate', 'Restart Unbound', '', 'yakin mau restart Unbound? dns cache akan terhapus'),
    array('reset.php', 'fa-arrow-rotate-left', 'Reset', 'danger', "Konfirmasi reset system? konfigurasi akan dikembalikan ke default\n\nPerubahan efektif setelah dilakukan perintah Reboot"),
    array('reload.php', 'fa-arrows-rotate', 'Reload', '', 'Konfirmasi reload system? hanya services terkait perubahan yang akan dijalankan ulang'),
    array('updateblacklist.php', 'fa-cloud-arrow-down', 'Update Blacklist', '', 'Konfirmasi update blacklist sekarang? proses mengambil ~9.5 jt domain dan bisa memakan waktu beberapa menit'),
    array('reboot.php', 'fa-power-off', 'Reboot', 'danger', 'Konfirmasi reboot system? keseluruhan system akan dijalankan ulang'),
);

tng_ui_page_start('maintenance.php', 'Maintenance', 'Operasi pemeliharaan dan pemulihan layanan resolver.', 'SISTEM');
tng_ui_card_start('Tindakan Sistem', 'Setiap tindakan memengaruhi layanan resolver secara langsung. Tindakan berisiko meminta konfirmasi terlebih dahulu.');
echo '<div class="action-grid">';
foreach ($actions as $action) {
    $class = 'action-card' . ($action[3] !== '' ? ' ' . $action[3] : '');
    echo '<a class="' . tng_e($class) . '" href="' . tng_e($action[0]) . '" data-confirm="' . tng_e($action[4]) . '"><i class="fa-solid ' . tng_e($action[1]) . '" aria-hidden="true"></i><span>' . tng_e($action[2]) . '</span></a>';
}
echo '</div>';
tng_ui_card_end();

$ip = $ipaddr !== null ? trim($ipaddr) : '';
if ($ip !== '') {
    tng_ui_card_start('Antarmuka Manajemen', 'Alamat IP yang digunakan untuk mengakses panel ini.');
    echo '<div class="set-row"><div class="set-row-info"><span class="set-row-name">eth0</span><span class="set-row-desc">Antarmuka manajemen</span></div><span class="label-mono">' . tng_e($ip) . '</span></div>';
    tng_ui_card_end();
}

echo '<div class="form-actions"><a class="button button-secondary" href="/">Kembali ke dashboard</a></div>';
tng_ui_page_end('maintenance.php');
?>
