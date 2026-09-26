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
if (strpos($referer, $allowed_prefix) !== 0 && strpos($referer, $allowed_prefix_ip) !== 0) {
    http_response_code(403);
    exit(0);
}

if (array_key_exists('data', $_POST)) {
    if (!tng_csrf_check($_POST['csrf'] ?? '')) {
        http_response_code(403);
        exit(0);
    }
    $data = $_POST['data'] ?? '';
    $valid = true;
    foreach (preg_split('/\r\n|\r|\n/', $data) as $line) {
        $line = strtolower(trim($line));
        if ($line === '' || $line[0] === '#') continue;
        if (strlen($line) > 253 || !preg_match('/^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?)(?:\.(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?))*$/', $line)) {
            $error = 'Domain tidak valid: ' . $line;
            $valid = false;
            break;
        }
    }
    if ($valid) {
        trustng_state_write('whitelist.db', $data);
        shell_exec('dos2unix ' . escapeshellarg(trustng_state_path('whitelist.db')));
        trustng_state_write('setdns.new', '');
        trustng_run_panel_script('setwhitelist.sh');
        header('Location: setwhite.php?saved=1');
        exit(0);
    }
}

if ($referer !== "https://$myip:40443/" && $referer !== "https://$myip:40443/index.php") {
    if (!isset($index) || $index !== 'yes') {
        if (strpos($referer, $allowed_prefix) !== 0 && strpos($referer, $allowed_prefix_ip) !== 0) exit(0);
    }
}

$file = is_file('whitelist.db') ? file('whitelist.db') : array();
$saved = isset($_GET['saved']) && $_GET['saved'] === '1';
tng_ui_page_start('setwhite.php', 'Whitelist', 'Domain pengecualian yang selalu lolos walau masuk blocklist.');
if (!empty($saved)) tng_ui_notice('success', 'Perubahan tersimpan', 'Whitelist berhasil disimpan. Jalankan Maintenance → Reload untuk mengaktifkan.');
if (!empty($error)) tng_ui_notice('critical', 'Domain tidak valid', $error);
tng_ui_card_start('Whitelist', 'Domain di daftar ini selalu lolos — diproses sebelum filter Trust+. Efektif setelah Reload.');
echo '<div class="wl-section">
<form name="wlist" action="setwhite.php" method="post">
  <input type="hidden" name="csrf" value="' . tng_e(tng_csrf_token()) . '">
  <div class="wl-head editor-head">
    <span class="wl-title">Domain Pengecualian</span>
    <span class="wl-badge">' . intval(count($file ?: array())) . ' domain</span>
  </div>
  <div class="areatxt"><textarea rows="12" name="data" placeholder="satu domain per baris" spellcheck="false" autocomplete="off" aria-label="Daftar domain whitelist manual">';
foreach($file as $text) { echo htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); }
echo '</textarea></div>
  <div class="wl-actions form-actions">
    <input type="submit" id="submit" value="Simpan" class="submit-button"/>
    <a href="/" class="submit-button button-secondary">Kembali</a>
    <span class="wl-hint">*perubahan efektif setelah Reload</span>
  </div>
</form>
</div>';
tng_ui_card_end();

tng_ui_page_end('setwhite.php');
?>
