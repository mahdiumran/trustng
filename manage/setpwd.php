<?php
error_reporting(0);
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
require_once __DIR__ . '/includes/auth.php';
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
        } else {
            tng_set_password($user, $pass1);
            $proc = proc_open('sudo chpasswd', array(
                0 => array('pipe','r'), 1 => array('pipe','w'), 2 => array('pipe','w')), $pipes);
            if (is_resource($proc)) {
                fwrite($pipes[0], 'admin:' . str_replace(array("\n","\r"), '', $pass1) . "\n");
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

echo '<html>
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1"/>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" type="text/css" href="style.css" />
<title>DNS TRUST-NG - Administration</title>
<style>
.acct-main{margin-top:4px;text-align:left;color:var(--ink,#e8eaf1);}
.acct-alert-ok,.acct-alert-err{max-width:520px;margin:10px auto;padding:12px 16px;border-radius:10px;font-size:13.5px;text-align:left;}
.acct-alert-ok{background:rgba(39,173,96,.12);border:1px solid rgba(39,173,96,.35);color:#6ee7a0;}
.acct-alert-err{background:rgba(220,38,38,.12);border:1px solid rgba(220,38,38,.35);color:#fca5a5;}
.acct-row{display:flex;justify-content:space-between;align-items:center;padding:10px 0;
 border-bottom:1px solid var(--line,rgba(255,255,255,.07));font-size:13px;}
.acct-row:last-child{border-bottom:none;}
.acct-label{color:var(--muted,#8a93a8);}
.acct-val{font-family:"JetBrains Mono",monospace;font-weight:600;}
.acct-divider{margin:20px 0;border:none;border-top:1px dashed var(--line,rgba(255,255,255,.1));}
.acct-field{margin-top:14px;}
.acct-field label{display:block;font-size:12px;color:var(--muted,#8a93a8);margin-bottom:6px;}
.acct-field input{width:100%;box-sizing:border-box;padding:10px 12px;border-radius:9px;
 border:1px solid var(--line,rgba(255,255,255,.14));background:var(--surface-2,rgba(15,22,35,.5));
 color:inherit;font-size:14px;font-family:inherit;}
.acct-field input:focus{outline:none;border-color:var(--brand,#3b82f6);}
.acct-alert-ok{background:rgba(39,173,96,.12);border:1px solid rgba(39,173,96,.35);color:#6ee7a0;
 padding:10px 14px;border-radius:9px;font-size:13px;margin-bottom:16px;}
.acct-alert-err{background:rgba(220,38,38,.12);border:1px solid rgba(220,38,38,.35);color:#fca5a5;
 padding:10px 14px;border-radius:9px;font-size:13px;margin-bottom:16px;}
</style>
</head>
<body class="with-sidebar sidebar-collapsed">
<div id="sidebar-overlay"></div>
<div class="page-shell">';
include_once 'menu.php';
trustng_render_sidebar('setpwd.php');
echo '
<div class="page-content">
<div class="tng-topbar"><button class="tng-topbar-toggle" title="Toggle menu" aria-label="Toggle menu"><svg width="20" height="20" viewBox="0 0 20 20" fill="none"><rect x="2" y="4.5" width="16" height="2" rx="1" fill="currentColor"/><rect x="2" y="9" width="16" height="2" rx="1" fill="currentColor"/><rect x="2" y="13.5" width="16" height="2" rx="1" fill="currentColor"/></svg></button><span class="tng-topbar-title">Administration</span><div class="tng-topbar-spacer"></div><a class="tng-topbar-back" href="/">&#8592; Dashboard</a></div>
<div align=center>
<h3>Administration</h3>';
if ($saved) echo '<div class="acct-alert-ok">Password berhasil diganti. Sesi lain telah dikeluarkan.</div>';
if ($logo_saved) echo '<div class="acct-alert-ok">Logo berhasil diperbarui.</div>';
if ($error) echo '<div class="acct-alert-err">' . htmlspecialchars($error) . '</div>';
$csrf = htmlspecialchars(tng_csrf_token(), ENT_QUOTES, 'UTF-8');

echo '
<div class="acct-main admin-settings">
  <section class="set-section" id="logo"><div class="set-section-head"><span class="set-section-title">Logo Dashboard</span></div>
    <p class="set-section-desc">Ganti logo sidebar dan dashboard. Format JPG, PNG, GIF, atau WEBP; maksimal 1 MB.</p>
    <div class="admin-logo-grid"><div class="logo-preview-box"><img src="img/logo-img/trust-ng.jpg?' . time() . '" class="logo-preview-img" alt="Logo saat ini"></div>
    <form method="post" action="setpwd.php#logo" enctype="multipart/form-data"><input type="hidden" name="action" value="upload_logo"><input type="hidden" name="csrf" value="' . $csrf . '"><div class="acct-field"><label for="admin-logo">Logo Baru</label><input id="admin-logo" type="file" name="logo" accept="image/jpeg,image/png,image/gif,image/webp" required></div><p><input type="submit" value="Upload Logo" class="submit-button"></p></form></div>
  </section>
  <section class="set-section" id="appearance"><div class="set-section-head"><span class="set-section-title">Theme</span></div>
    <p class="set-section-desc">Darker menggelapkan seluruh web hanya saat mode gelap aktif. Pilihan ini tidak mengubah tampilan Light mode.</p>
    <div class="admin-theme-options" role="radiogroup" aria-label="Dark theme style"><label class="admin-theme-option"><input type="radio" name="chrome-theme" value="default"><span><strong>Default</strong><small>Glass navy saat ini</small></span></label><label class="admin-theme-option"><input type="radio" name="chrome-theme" value="darker"><span><strong>Darker</strong><small>Latar dan seluruh surface lebih gelap</small></span></label></div>
  </section>
  <section class="set-section" id="password"><div class="set-section-head"><span class="set-section-title">Administrator Account</span></div>
    <div class="acct-row"><span class="acct-label">Username</span><span class="acct-val">' . htmlspecialchars($user) . '</span></div><div class="acct-row"><span class="acct-label">Role</span><span class="acct-val">Administrator</span></div><div class="acct-row"><span class="acct-label">Password terakhir diubah</span><span class="acct-val">' . htmlspecialchars($last_change) . '</span></div>
    <form method="post" action="setpwd.php#password"><input type="hidden" name="action" value="change_password"><input type="hidden" name="csrf" value="' . $csrf . '"><div class="acct-field"><label>Password Baru (minimal 6 karakter)</label><input type="password" name="pass1" required minlength="6" autocomplete="new-password"></div><div class="acct-field"><label>Ulangi Password Baru</label><input type="password" name="pass2" required minlength="6" autocomplete="new-password"></div><p style="margin-top:18px;"><input type="submit" value="Simpan Password" class="submit-button"> <input type="button" onclick="location.href=\'manage.php\';" class="submit-button" value="Kembali"></p></form>
  </section>
</div>
<p><small><b>&#169; 2024 Kominfo</b></small>
</div>
</div>
</div>';
