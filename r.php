<?php
error_reporting(0);
$data = shell_exec("/usr/local/sbin/unbound-control dump_requestlist 2>/dev/null");
if ($data === null || trim($data) === '') {
    $data = shell_exec("unbound-control dump_requestlist 2>/dev/null");
}
if (trim($data) === '') {
    $data = "Tidak ada permintaan DNS yang sedang diproses (request list kosong).";
}
echo "<pre>\n" . htmlspecialchars($data) . "\n</pre>";

?>
