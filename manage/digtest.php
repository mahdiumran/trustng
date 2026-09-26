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

$ipaddr = shell_exec("ifconfig eth0 2>/dev/null | grep netmask | sed 's/ .*inet //;s/ .*//'");
$ipaddr = $ipaddr !== null ? trim($ipaddr) : '';

require_once __DIR__ . '/includes/ui.php';

tng_ui_page_start('digtest.php', 'DNS Inspector', 'Uji status domain melalui resolver ini — terdeteksi otomatis: Resolved, Blocked (Trust+), atau Whitelisted.');
?>
<div data-page-actions>
  <a class="button button-small" href="/setdigtest.php"><i class="fa-solid fa-gear" aria-hidden="true"></i> Set Domain</a>
</div>

<div class="stack">
  <div class="card">
    <div class="card-header">
      <div>
        <h2>Uji Domain</h2>
        <p>Masukkan satu domain, lalu tekan Uji (atau Enter).</p>
      </div>
    </div>
    <div class="card-body">
      <div class="di-query">
        <input type="text" id="diDomain" class="di-input" placeholder="mis. google.com" value="google.com" onkeydown="if(event.key==='Enter'){event.preventDefault();diRunManual();}" />
        <button type="button" class="button" onclick="diRunManual()"><i class="fa-solid fa-play" aria-hidden="true"></i> Uji</button>
      </div>
      <div id="di-manual-results"><div class="empty-state"><i class="fa-solid fa-circle-notch fa-spin" aria-hidden="true"></i><span>Mengetes&hellip;</span></div></div>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <div>
        <h2>Live Resolution Test</h2>
        <p>Resolver @<span class="di-server"><?php echo tng_e($ipaddr ?: '127.0.0.1'); ?></span></p>
      </div>
      <div class="page-heading-actions">
        <button type="button" class="button button-small" onclick="diRun()"><i class="fa-solid fa-rotate" aria-hidden="true"></i> Jalankan Test</button>
      </div>
    </div>
    <div class="card-body">
      <div id="di-results"><div class="empty-state"><i class="fa-solid fa-circle-notch fa-spin" aria-hidden="true"></i><span>Memuat&hellip;</span></div></div>
    </div>
  </div>

  <div class="di-actions">
    <a class="button button-secondary" href="/">Kembali</a>
  </div>
</div>
<?php
echo '<script src="/jquery.min.js"></script>';
echo '<script src="digtest.js"></script>';
tng_ui_page_end('digtest.php');
