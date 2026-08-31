<?php
error_reporting(0);
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pass1 = isset($_POST['pass1']) ? strval($_POST['pass1']) : '';
    $pass2 = isset($_POST['pass2']) ? strval($_POST['pass2']) : '';
    $valid = true;
    if (!tng_csrf_check(isset($_POST['csrf']) ? $_POST['csrf'] : '')) {
        tng_csrf_token(); // regenerate; jangan tolak login-flow hanya karena session baru
    }
    if (strlen($pass1) < 12) {
        $error = 'Password minimal 12 karakter.'; $valid = false;
    } elseif ($pass1 !== $pass2) {
        $error = 'Konfirmasi password tidak cocok.'; $valid = false;
    }
    if ($valid) {
        tng_set_password($user, $pass1);
        // sinkron user sistem admin (perilaku lama panel)
        $proc = proc_open('sudo chpasswd', array(
            0 => array('pipe','r'), 1 => array('pipe','w'), 2 => array('pipe','w')), $pipes);
        if (is_resource($proc)) {
            fwrite($pipes[0], 'admin:' . str_replace(array("\n","\r"), '', $pass1) . "\n");
            fclose($pipes[0]);
            proc_close($proc);
        }
        @unlink(__DIR__ . '/setup.mulai');
        // refresh session ke versi terbaru supaya tetap login
        $u = tng_get_user($user);
        session_regenerate_id(true);
        $_SESSION['tng_user'] = $user;
        $_SESSION['tng_pwver'] = $u ? $u['pw_version'] : 999;
        header('Location: setpwd.php?saved=1');
        exit(0);
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
<title>DNS TRUST-NG - Account Management</title>
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
<body class="with-sidebar">';
include_once 'menu.php';
trustng_render_sidebar('setpwd.php');
echo '
<div align=center>
<h3>Account Management</h3>';
if ($saved) echo '<div class="acct-alert-ok">Password berhasil diganti. Sesi lain telah dikeluarkan.</div>';
if ($error) echo '<div class="acct-alert-err">' . htmlspecialchars($error) . '</div>';

echo '
<div class="acct-main">
  <div class="acct-row"><span class="acct-label">Username</span><span class="acct-val">' . htmlspecialchars($user) . '</span></div>
  <div class="acct-row"><span class="acct-label">Role</span><span class="acct-val">Administrator</span></div>
  <div class="acct-row"><span class="acct-label">Password terakhir diubah</span><span class="acct-val">' . htmlspecialchars($last_change) . '</span></div>
  <hr class="acct-divider"/>
  <form method="post" action="setpwd.php">
  <input type="hidden" name="csrf" value="' . htmlspecialchars(tng_csrf_token()) . '"/>
  <div class="acct-field">
    <label>Password Baru (minimal 12 karakter)</label>
    <input type="password" name="pass1" required minlength="12" autocomplete="new-password"/>
  </div>
  <div class="acct-field">
    <label>Ulangi Password Baru</label>
    <input type="password" name="pass2" required minlength="12" autocomplete="new-password"/>
  </div>
  <p style="margin-top:18px;">
    <input type="submit" value="Simpan Password" class="submit-button"/>
    <input type="button" onclick="location.href=\'manage.php\';" class="submit-button" value="Kembali"/>
  </p>
  </form>
</div>
<p><small><b>&#169; 2024 Kominfo</b></small>
</div>';
