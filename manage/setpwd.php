<?php
error_reporting(0);
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
require_once __DIR__ . '/includes/auth.php';
if (file_exists(TNG_SETUP_FLAG)) { header('Location: /login.php?setup=1'); exit(0); }
$myip = $_SERVER['SERVER_ADDR'];
$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
$http_host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : "$myip:40443";
$proto = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$allowed_prefix = "$proto://$http_host/";
$allowed_prefix_ip = "https://$myip:40443/";

if (strpos($referer, $allowed_prefix) !== 0 && strpos($referer, $allowed_prefix_ip) !== 0) exit(0);

tng_session_start();
if (empty($_SESSION['tng_user'])) { header('Location: /login.php'); exit(0); }

$user = $_SESSION['tng_user'];
$info = tng_get_user($user);
$pw_updated = $info ? intval($info['updated_at']) : 0;
$last_change = $pw_updated ? date('d M Y H:i', $pw_updated) : '-';

$error = '';
$saved = isset($_GET['saved']) && $_GET['saved'] == '1';

$logo_saved = isset($_GET['logo_saved']) && $_GET['logo_saved'] === '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? strval($_POST['action']) : 'change_password';
    if (!tng_csrf_check(isset($_POST['csrf']) ? $_POST['csrf'] : '')) {
        http_response_code(403);
        $error = 'Permintaan tidak valid. Muat ulang halaman dan coba kembali.';
    } elseif ($action === 'upload_logo') {
        $upload = isset($_FILES['logo']) ? $_FILES['logo'] : null;
        $allowed_types = array('image/jpeg', 'image/png', 'image/gif', 'image/webp');
        if (!$upload || $upload['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($upload['tmp_name'])) {
            $error = 'Pilih file logo yang valid.';
        } elseif (intval($upload['size']) > 1048576) {
            $error = 'Ukuran logo maksimal 1 MB.';
        } else {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = $finfo ? finfo_file($finfo, $upload['tmp_name']) : false;
            if ($finfo) finfo_close($finfo);
            $image_info = @getimagesize($upload['tmp_name']);
            if (!in_array($mime, $allowed_types, true) || $image_info === false) {
                $error = 'Format logo harus JPG, PNG, GIF, atau WEBP.';
            } else {
                $logo_dir = __DIR__ . '/img/logo-img';
                $primary = $logo_dir . '/trust-ng.jpg';
                $small = __DIR__ . '/img/trustng-small.jpg';
                if (!is_dir($logo_dir) && !@mkdir($logo_dir, 0755, true)) {
                    $error = 'Folder logo tidak dapat dibuat.';
                } else {
                    if (is_file($primary)) @copy($primary, $primary . '.bak');
                    if (!move_uploaded_file($upload['tmp_name'], $primary) || !@copy($primary, $small)) {
                        $error = 'Logo gagal disimpan. Periksa permission folder logo.';
                    } else {
                        header('Location: setpwd.php?logo_saved=1#logo');
                        exit(0);
                    }
                }
            }
        }
    } elseif ($action === 'change_password') {
        $pass1 = isset($_POST['pass1']) ? strval($_POST['pass1']) : '';
        $pass2 = isset($_POST['pass2']) ? strval($_POST['pass2']) : '';
        if (strlen($pass1) < 6) {
            $error = 'Password minimal 6 karakter.';
        } elseif ($pass1 !== $pass2) {
            $error = 'Konfirmasi password tidak cocok.';
        } elseif (!tng_set_password($user, $pass1)) {
            $error = 'Password gagal disimpan. Periksa penyimpanan autentikasi.';
        } else {
            $proc = proc_open('/usr/bin/sudo -n /usr/sbin/chpasswd', array(
                0 => array('pipe','r'), 1 => array('pipe','w'), 2 => array('pipe','w')), $pipes);
            if (is_resource($proc)) {
                fwrite($pipes[0], $user . ':' . str_replace(array("\n","\r"), '', $pass1) . "\n");
                fclose($pipes[0]);
                proc_close($proc);
            }
            @unlink(__DIR__ . '/setup.mulai');
            $u = tng_get_user($user);
            session_regenerate_id(true);
            $_SESSION['tng_user'] = $user;
            $_SESSION['tng_pwver'] = $u ? $u['pw_version'] : 999;
            header('Location: setpwd.php?saved=1#password');
            exit(0);
        }
    }
}

require_once __DIR__ . '/includes/ui.php';

$csrf = htmlspecialchars(tng_csrf_token(), ENT_QUOTES, 'UTF-8');

tng_ui_page_start('setpwd.php', 'Administration', 'Kelola logo, tema, dan kredensial administrator.', 'SISTEM');

if ($saved) tng_ui_notice('success', 'Password diperbarui', 'Password berhasil diganti. Sesi lain telah dikeluarkan.');
if ($logo_saved) tng_ui_notice('success', 'Logo diperbarui', 'Logo berhasil diperbarui.');
if ($error !== '') tng_ui_notice('critical', 'Gagal memproses', $error);

echo '<div class="admin-settings stack">';

tng_ui_card_start('Logo Dashboard', 'Ganti logo sidebar dan dashboard. Format JPG, PNG, GIF, atau WEBP; maksimal 1 MB.');
echo '<section class="set-section" id="logo">';
echo '<div class="admin-logo-grid"><div class="logo-preview-box"><img src="img/logo-img/trust-ng.jpg?' . time() . '" class="logo-preview-img" alt="Logo saat ini"></div>';
echo '<form method="post" action="setpwd.php#logo" enctype="multipart/form-data"><input type="hidden" name="action" value="upload_logo"><input type="hidden" name="csrf" value="' . $csrf . '"><div class="field"><label class="field-label" for="admin-logo">Logo Baru</label><input id="admin-logo" type="file" name="logo" accept="image/jpeg,image/png,image/gif,image/webp" required></div><div class="form-actions"><button type="submit" class="button">Upload Logo</button></div></form></div>';
echo '</section>';
tng_ui_card_end();

tng_ui_card_start('Theme', 'Mode Terang, Gelap, atau mengikuti preferensi sistem. Pilihan tersimpan di browser ini.');
echo '<section class="set-section" id="appearance">';
echo '<div class="admin-theme-options" role="radiogroup" aria-label="Tema tampilan"><button type="button" class="admin-theme-option" role="radio" aria-checked="false" data-theme-value="light"><span><strong>Terang</strong><small>Permukaan terang untuk penggunaan harian</small></span></button><button type="button" class="admin-theme-option" role="radio" aria-checked="false" data-theme-value="dark"><span><strong>Gelap</strong><small>Kontras rendah untuk ruang gelap</small></span></button><button type="button" class="admin-theme-option" role="radio" aria-checked="false" data-theme-value="system"><span><strong>Sistem</strong><small>Ikuti preferensi perangkat</small></span></button></div>';
echo '</section>';
tng_ui_card_end();

tng_ui_card_start('Administrator Account', 'Ganti password akun administrator panel.');
echo '<section class="set-section" id="password">';
echo '<div class="set-row"><div class="set-row-info"><span class="set-row-name">Username</span><span class="set-row-desc">Akun autentikasi panel</span></div><span class="label-mono">' . tng_e($user) . '</span></div>';
echo '<div class="set-row"><div class="set-row-info"><span class="set-row-name">Role</span><span class="set-row-desc">Hak akses</span></div><span class="label-mono">Administrator</span></div>';
echo '<div class="set-row"><div class="set-row-info"><span class="set-row-name">Password terakhir diubah</span><span class="set-row-desc">Waktu perubahan terakhir</span></div><span class="label-mono">' . tng_e($last_change) . '</span></div>';
echo '<form method="post" action="setpwd.php#password"><input type="hidden" name="action" value="change_password"><input type="hidden" name="csrf" value="' . $csrf . '"><div class="field"><label class="field-label" for="pass1">Password Baru (minimal 6 karakter)</label><input id="pass1" type="password" name="pass1" required minlength="6" autocomplete="new-password"></div><div class="field"><label class="field-label" for="pass2">Ulangi Password Baru</label><input id="pass2" type="password" name="pass2" required minlength="6" autocomplete="new-password"></div><div class="form-actions"><button type="submit" class="button">Simpan Password</button><a class="button button-secondary" href="/">Kembali</a></div></form>';
echo '</section>';
tng_ui_card_end();

echo '</div>';
tng_ui_page_end('setpwd.php');
?>
