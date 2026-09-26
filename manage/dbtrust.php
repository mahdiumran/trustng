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

$jumlah = @file_get_contents('/etc/unbound/db/trust.count');
$jumlah = $jumlah !== false ? trim($jumlah) : '0';
$last = @shell_exec("stat -c %z /etc/unbound/db/trust.txt 2>/dev/null | cut -d. -f1");

require_once __DIR__ . '/includes/ui.php';

tng_ui_page_start('dbtrust.php', 'Database Trust+', 'Pencarian kata kunci dan lookup domain pada blocklist Trust+.');
?>
<div data-page-actions>
  <span class="badge badge-ok"><?php echo tng_e(number_format((int) $jumlah)); ?> domain</span>
</div>

<div class="stack">
  <div class="tng-status-strip">
    <div class="tng-status-card">
      <div class="tng-status-icon"><i class="fa-solid fa-database" aria-hidden="true"></i></div>
      <div class="tng-status-info"><span class="tng-status-name">Jumlah Domain</span><span class="tng-status-val"><?php echo tng_e($jumlah); ?></span></div>
    </div>
    <div class="tng-status-card">
      <div class="tng-status-icon"><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i></div>
      <div class="tng-status-info"><span class="tng-status-name">Perubahan Terakhir</span><span class="tng-status-val tng-status-val-sm"><?php echo tng_e($last ?: '-'); ?></span></div>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <div>
        <h2>Pencarian</h2>
        <p id="db-hint">Cari kata kunci di dalam daftar Trust+ (maks 500 hasil).</p>
      </div>
      <div class="page-heading-actions">
        <span class="di-server" id="db-mode-label">Mode: Keyword</span>
      </div>
    </div>
    <div class="card-body">
      <div class="di-actions db-mode-actions" id="db-modes">
        <button type="button" class="db-mode button button-secondary active" data-mode="keyword" onclick="dbSetMode('keyword')">Keyword</button>
        <button type="button" class="db-mode button button-secondary" data-mode="domain" onclick="dbSetMode('domain')">Domain</button>
      </div>
      <div class="di-query">
        <input type="text" id="dbQ" class="di-input" placeholder="mis. xnxx" onkeydown="if(event.key==='Enter'){event.preventDefault();dbRun();}" />
        <button type="button" class="button" onclick="dbRun()"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> Cari</button>
      </div>
      <div id="db-results"><div class="empty-state"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><span>Masukkan kata kunci atau domain lalu tekan Cari.</span></div></div>
    </div>
  </div>

  <div class="di-actions">
    <a class="button button-secondary" href="/">Kembali</a>
  </div>
</div>
<?php
echo '<script src="/jquery.min.js"></script>';
echo '<script src="dbtrust.js"></script>';
tng_ui_page_end('dbtrust.php');
