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

require_once __DIR__ . '/includes/ui.php';

tng_ui_page_start('activity.php', 'Activity Log', 'Status dan riwayat pembaruan blocklist Trust+ (auto-refresh 10s).');
?>
<div data-page-actions>
  <span class="badge"><span class="status-dot success"></span>LIVE</span>
</div>

<div class="stack">
  <div class="tng-status-strip" id="act-status">
    <div class="tng-status-card">
      <div class="tng-status-icon"><i class="fa-solid fa-list" aria-hidden="true"></i></div>
      <div class="tng-status-info"><span class="tng-status-name">Domain Count</span><span class="tng-status-val" id="st-count">&mdash;</span></div>
    </div>
    <div class="tng-status-card">
      <div class="tng-status-icon"><i class="fa-solid fa-clock" aria-hidden="true"></i></div>
      <div class="tng-status-info"><span class="tng-status-name">Update Terakhir</span><span class="tng-status-val tng-status-val-sm" id="st-update">&mdash;</span></div>
    </div>
    <div class="tng-status-card">
      <div class="tng-status-icon"><i class="fa-solid fa-heart-pulse" aria-hidden="true"></i></div>
      <div class="tng-status-info"><span class="tng-status-name">Health Terakhir</span><span class="tng-status-val" id="st-health">&mdash;</span></div>
    </div>
    <div class="tng-status-card">
      <div class="tng-status-icon"><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i></div>
      <div class="tng-status-info"><span class="tng-status-name">Update Berikutnya</span><span class="tng-status-val tng-status-val-sm" id="st-next">&mdash;</span></div>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <div>
        <h2>Riwayat Update Blocklist</h2>
        <p id="act-last"></p>
      </div>
      <div class="page-heading-actions">
        <button type="button" class="button button-small" onclick="actRun()"><i class="fa-solid fa-rotate" aria-hidden="true"></i> Refresh</button>
      </div>
    </div>
    <div class="card-body">
      <div id="act-rows"><div class="empty-state"><i class="fa-solid fa-circle-notch fa-spin" aria-hidden="true"></i><span>Memuat&hellip;</span></div></div>
    </div>
  </div>

  <div class="di-actions">
    <a class="button button-secondary" href="/">Kembali</a>
  </div>
</div>
<?php
echo '<script src="/jquery.min.js"></script>';
echo '<script src="activity.js"></script>';
tng_ui_page_end('activity.php');
