<?php
require_once __DIR__ . '/includes/port_config.php';

$valid = array('1', '22', '40443', '65535');
foreach ($valid as $port) {
    if (trustng_validate_port($port) !== (string) (int) $port) exit("invalid valid port $port\n");
}
$invalid = array('', '0', '65536', '-1', '22x', "22\nPort 23");
foreach ($invalid as $port) {
    try { trustng_validate_port($port); exit("accepted invalid port $port\n"); }
    catch (InvalidArgumentException $e) {}
}
try { trustng_validate_port_pair('22', '22'); exit("accepted duplicate ports\n"); }
catch (InvalidArgumentException $e) {}
if (trustng_nginx_with_port("listen 40443 ssl;\nlisten [::]:40443 ssl;\n", '40443', '4443') !== "listen 4443 ssl;\nlisten [::]:4443 ssl;\n") exit("nginx replacement failed\n");
echo "port helper tests passed\n";
?>
