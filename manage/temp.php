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

$ipaddr = shell_exec("ifconfig eth0 | grep netmask | sed 's/ .*inet //;s/ .*//'");

require_once __DIR__ . '/includes/ui.php';

tng_ui_page_start('temp.php', 'Temperature Sensors', 'Pembacaan sensor suhu perangkat (lm-sensors).');
?>
<div data-page-actions>
  <button type="button" class="button button-small" onclick="temp()"><i class="fa-solid fa-rotate" aria-hidden="true"></i> Refresh</button>
</div>

<div class="stack">
  <div class="card">
    <div class="card-header">
      <div>
        <h2>Sensor Suhu</h2>
        <p>Host @<span class="di-server"><?php echo tng_e(trim((string) $ipaddr)); ?></span></p>
      </div>
    </div>
    <div class="card-body">
      <div id="temp" title="Temperature Sensors"><?php include 't.php'; ?></div>
    </div>
  </div>

  <div class="di-actions">
    <a class="button button-secondary" href="/">Kembali</a>
  </div>
</div>
<?php
echo '<script src="/jquery.min.js"></script>';
echo '<script src="temp.js"></script>';
tng_ui_page_end('temp.php');
