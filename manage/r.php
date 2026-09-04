<?php
error_reporting(0);
require_once __DIR__ . '/includes/unbound.php';
$data = tng_unbound_collect_raw('dump_requestlist');
if (trim($data) === '' || stripos($data, 'error:') !== false) {
    if (trim($data) === '') $data = "Tidak ada permintaan DNS yang sedang diproses (request list kosong).";
}
echo "<pre>\n" . htmlspecialchars($data) . "\n</pre>";

?>
