<?php
error_reporting(0);
require_once __DIR__ . '/includes/auth.php';
tng_session_start();

// sudah login → langsung dashboard
if (!empty($_SESSION['tng_user']) && tng_current_pw_version() && !file_exists(TNG_SETUP_FLAG)) {
    header('Location: /');
    exit(0);
}

$setup_mode = file_exists(TNG_SETUP_FLAG) || (isset($_GET['setup']) && $_GET['setup'] == '1');
$error = '';
$ip = tng_client_ip();
$username = isset($_POST['username']) ? trim($_POST['username']) : 'admin';
$password = isset($_POST['password']) ? strval($_POST['password']) : '';
$password2 = isset($_POST['password2']) ? strval($_POST['password2']) : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF: token divalidasi hanya bila session punya token yang cocok.
    // Session stale/hilang (cookie lama, cache, dsb) TIDAK menolak login —
    // user tetap bisa masuk; token baru dibuat untuk form berikutnya.
    $csrf_sent = isset($_POST['csrf']) ? strval($_POST['csrf']) : '';
    if ($csrf_sent === '' || empty($_SESSION['tng_csrf'])) {
        tng_csrf_token(); // generate fresh
    }
    if ($setup_mode) {
        // mode setup: buat password pertama
        if (strlen($password) < 12) {
            $error = 'Password minimal 12 karakter.';
        } elseif ($password !== $password2) {
            $error = 'Konfirmasi password tidak cocok.';
        } else {
            tng_set_password('admin', $password);
            @unlink(TNG_SETUP_FLAG);
            session_regenerate_id(true);
            $_SESSION['tng_user'] = 'admin';
            $u = tng_get_user('admin');
            $_SESSION['tng_pwver'] = $u ? $u['pw_version'] : 1;
            header('Location: /');
            exit(0);
        }
    } else {
        if (tng_is_locked_out($ip)) {
            $error = 'Terlalu banyak percobaan gagal. Coba lagi dalam 15 menit.';
        } else {
            $u = tng_get_user($username);
            if ($u && password_verify($password, $u['password_hash'])) {
                tng_record_attempt($ip, true);
                session_regenerate_id(true);
                $_SESSION['tng_user'] = $u['username'];
                $_SESSION['tng_pwver'] = $u['pw_version'];
                header('Location: /');
                exit(0);
            }
            tng_record_attempt($ip, false);
            $error = 'Username atau password salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1"/>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" type="text/css" href="/style.css"/>
<title>DNS TRUST-NG — Login</title>
<style>
.login-wrap{min-height:100vh;display:flex;align-items:center;justify-content:center;
  background:radial-gradient(1200px 800px at 20% 0%,rgba(59,130,246,.12),transparent),
             radial-gradient(1000px 700px at 90% 100%,rgba(39,255,151,.08),transparent),var(--bg,#0f1623);}
.login-card{width:380px;padding:36px;border-radius:16px;background:var(--glass-bg,rgba(19,29,46,.7));
  border:1px solid var(--glass-border,rgba(225,226,235,.1));backdrop-filter:blur(18px);
  box-shadow:0 24px 60px rgba(0,0,0,.4);color:var(--ink,#e8eaf1);}
.login-logo{display:block;margin:0 auto 14px;width:150px;border-radius:8px;}
.login-title{text-align:center;font-size:22px;font-weight:700;margin:0 0 4px;}
.login-sub{text-align:center;font-size:12px;color:var(--muted,#8a93a8);font-family:'JetBrains Mono',monospace;
  letter-spacing:.08em;margin-bottom:24px;}
.login-card label{display:block;font-size:12px;color:var(--muted,#8a93a8);margin:12px 0 5px;}
.login-card input[type=text],.login-card input[type=password]{width:100%;box-sizing:border-box;
  padding:11px 13px;border-radius:9px;border:1px solid var(--line,rgba(225,226,235,.14));
  background:var(--surface-2,rgba(15,22,35,.55));color:var(--ink,#e8eaf1);font-size:14px;font-family:inherit;}
.login-card input:focus{outline:none;border-color:var(--brand,#3b82f6);}
.login-btn{width:100%;margin-top:20px;padding:12px;border:none;border-radius:10px;
  background:var(--brand,#3b82f6);color:#fff;font-size:14px;font-weight:600;cursor:pointer;font-family:inherit;}
.login-btn:hover{filter:brightness(1.1);}
.login-error{background:rgba(220,38,38,.12);border:1px solid rgba(220,38,38,.35);color:#fca5a5;
  padding:10px 12px;border-radius:8px;font-size:13px;margin-top:14px;}
.setup-note{background:rgba(59,130,246,.1);border:1px solid rgba(59,130,246,.3);color:#93c5fd;
  padding:10px 12px;border-radius:8px;font-size:13px;margin-bottom:8px;}
</style>
</head>
<body>
<div class="login-wrap">
  <div class="login-card">
    <img class="login-logo" src="/img/logo-img/trust-ng.jpg" alt="TRUST-NG"/>
    <h1 class="login-title">TRUST-NG DNS</h1>
    <div class="login-sub">CONTROL PANEL · :40443</div>
    <?php if ($setup_mode): ?>
      <div class="setup-note">First boot — atur password administrator (minimal 12 karakter).</div>
    <?php endif; ?>
    <form method="post" action="login.php<?php echo $setup_mode ? '?setup=1' : ''; ?>">
      <input type="hidden" name="csrf" value="<?php echo htmlspecialchars(tng_csrf_token()); ?>"/>
      <?php if (!$setup_mode): ?>
      <label>Username</label>
      <input type="text" name="username" value="admin" autocomplete="username" required/>
      <?php endif; ?>
      <label>Password<?php echo $setup_mode ? ' Baru' : ''; ?></label>
      <input type="password" name="password" autocomplete="current-password new-password" required autofocus/>
      <?php if ($setup_mode): ?>
      <label>Ulangi Password Baru</label>
      <input type="password" name="password2" autocomplete="new-password" required/>
      <?php endif; ?>
      <?php if ($error): ?><div class="login-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
      <button class="login-btn" type="submit"><?php echo $setup_mode ? 'Atur Password & Masuk' : 'Masuk'; ?></button>
    </form>
  </div>
</div>
</body>
</html>
