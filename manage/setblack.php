<?php
require_once __DIR__ . '/includes/state_store.php';
require_once __DIR__ . '/includes/ui.php';
require_once __DIR__ . '/includes/auth.php';
error_reporting(0);
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
$myip = $_SERVER['SERVER_ADDR'];
$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
$http_host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : "$myip:40443";
$proto = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$allowed_prefix = "$proto://$http_host/";
$allowed_prefix_ip = "https://$myip:40443/";

// CSRF: guard referer HARUS berjalan SEBELUM memproses POST
if (strpos($referer, $allowed_prefix) !== 0 && strpos($referer, $allowed_prefix_ip) !== 0) {
    exit(0);
}

$BL_FILE = '/var/www/manage/blacklist.local.db';

if($_POST['data'] ?? null) {
    if (!tng_csrf_check($_POST['csrf'] ?? '')) {
        http_response_code(403);
        exit(0);
    }
    $data = $_POST['data'] ?? '';
    // sanitasi: hanya domain chars, satu per baris
    $lines = preg_split('/\r\n|\r|\n/', $data);
    $clean = array();
    foreach ($lines as $line) {
        $line = strtolower(trim($line));
        if ($line === '' || $line[0] === '#') continue;
        if (!preg_match('/^[a-z0-9._-]+$/', $line)) continue;
        if (strlen($line) > 253) continue;
        $clean[] = $line;
    }
    trustng_state_write(basename($BL_FILE), implode("\n", $clean) . "\n");
    shell_exec('dos2unix ' . escapeshellarg(trustng_state_path(basename($BL_FILE))) . ' 2>/dev/null');
    shell_exec('sudo -n /usr/local/sbin/update-blocklist > /dev/null 2>&1 &');
    sleep (0.3);
    header('location: setblack.php');
    exit;
}

$file = is_file($BL_FILE) ? file($BL_FILE) : array();
$count = is_file('/etc/unbound/db/trust.count') ? intval(file_get_contents('/etc/unbound/db/trust.count')) : 0;
tng_ui_page_start('setblack.php', 'Blacklist', 'Domain blokir tambahan untuk digabung ke blocklist Komdigi Trust+.');
tng_ui_card_start('Blacklist Manual', 'Domain di daftar Komdigi diperbarui otomatis 2× sehari. Tambahkan domain sendiri di bawah — akan digabung ke blocklist saat updater berikutnya berjalan.');
echo '<div class="bl-section">
<form name="blist" action="setblack.php" method="post">
  <input type="hidden" name="csrf" value="' . tng_e(tng_csrf_token()) . '">
  <div class="bl-head editor-head">
    <span class="bl-title">Domain Blokir Tambahan</span>
    <span class="bl-badge">Komdigi Trust+: ' . number_format($count, 0, ",", ".") . ' domain</span>
  </div>
  <div class="areatxt"><textarea rows="12" name="data" placeholder="satu domain per baris" spellcheck="false" autocomplete="off" aria-label="Daftar domain blokir manual">';
if (is_array($file)) { foreach($file as $text) { echo htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); } }
echo '</textarea></div>
  <div class="bl-actions form-actions">
    <input type="submit" id="submit" value="Simpan" class="submit-button"/>
    <a href="/" class="submit-button button-secondary">Kembali</a>
    <span class="bl-hint">*domain divalidasi otomatis</span>
  </div>
</form>
</div>';
tng_ui_card_end();

tng_ui_page_end('setblack.php');
?>
