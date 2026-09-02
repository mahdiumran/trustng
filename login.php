<?php
error_reporting(0);
require_once __DIR__ . '/includes/auth.php';
tng_session_start();

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
    $csrf_sent = isset($_POST['csrf']) ? strval($_POST['csrf']) : '';
    if ($csrf_sent === '' || empty($_SESSION['tng_csrf'])) {
        tng_csrf_token();
    }
    if ($setup_mode) {
        if (strlen($password) < 6) {
            $error = 'Password minimal 6 karakter.';
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
$panel_port = isset($_SERVER['SERVER_PORT']) ? (int) $_SERVER['SERVER_PORT'] : 40443;
if ($panel_port < 1 || $panel_port > 65535) $panel_port = 40443;
$form_action = 'login.php' . ($setup_mode ? '?setup=1' : '');
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1"/>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" type="text/css" href="/style.css"/>
<title>DNS TRUST-NG — Login</title>
<style>
.login-screen{min-height:100vh;display:grid;place-items:center;padding:28px;background:var(--background,#060b16);color:var(--on-surface,#eaf2ff)}
.login-console{display:grid;grid-template-columns:minmax(0,1.35fr) minmax(340px,.65fr);width:min(1120px,100%);min-height:650px;overflow:hidden;border:1px solid var(--glass-border);border-radius:24px;background:var(--glass-bg-strong);box-shadow:var(--shadow);}
.login-brand{display:flex;flex-direction:column;padding:56px 64px;background:linear-gradient(135deg,rgba(94,231,245,.08),transparent 45%),var(--surface-0);border-right:1px solid var(--glass-border)}
.login-mark{display:flex;align-items:center;gap:14px;font-family:var(--font-mono);font-weight:700;letter-spacing:.08em}.login-mark img{width:42px;height:42px;object-fit:cover;border-radius:10px}.login-kicker,.login-meta{font:600 11px var(--font-mono);letter-spacing:.12em;color:var(--muted);text-transform:uppercase}.login-status{margin-top:86px;color:var(--secondary);font:600 11px var(--font-mono);letter-spacing:.1em}.login-status:before{content:'';display:inline-block;width:7px;height:7px;margin-right:9px;border-radius:50%;background:currentColor;box-shadow:0 0 14px currentColor}.login-brand h1{max-width:480px;margin:20px 0 18px;font-size:clamp(42px,5vw,70px);letter-spacing:-.06em;line-height:.98}.login-brand p{max-width:470px;color:var(--on-surface-variant);font-size:15px}.login-network{display:flex;align-items:center;gap:0;margin:64px 0 18px;max-width:470px}.login-node{width:11px;height:11px;border:2px solid var(--primary);border-radius:50%;background:var(--surface-0);box-shadow:0 0 14px var(--primary-glow)}.login-link{height:1px;flex:1;background:linear-gradient(90deg,var(--primary),var(--secondary))}.login-network-label{font:11px var(--font-mono);color:var(--muted)}.login-meta{margin-top:auto;display:flex;gap:24px}.login-auth{display:flex;flex-direction:column;justify-content:center;padding:56px 48px;background:rgba(6,11,22,.42)}.login-auth-head{margin-bottom:32px}.login-auth-head .login-kicker{display:block;text-align:right;margin-bottom:72px}.login-auth h2{margin:0 0 8px;font-size:30px;letter-spacing:-.03em}.login-auth-intro{margin:0;color:var(--muted);font-size:14px}.login-field{margin:22px 0}.login-field label{display:block;margin-bottom:8px;color:var(--muted);font:600 11px var(--font-mono);letter-spacing:.1em;text-transform:uppercase}.login-field input{width:100%;padding:14px 15px;border:1px solid var(--outline);border-radius:9px;background:var(--surface-1);color:var(--on-surface);font:15px var(--font-ui)}.login-field input:focus{outline:none;border-color:var(--primary);box-shadow:var(--glow-focus)}.login-submit{width:100%;padding:14px;border:0;border-radius:9px;background:var(--primary);color:#06111a;font-weight:800;cursor:pointer}.login-submit:hover{filter:brightness(1.08)}.login-submit:focus-visible{outline:3px solid var(--secondary);outline-offset:3px}.login-error,.login-setup{padding:12px 14px;border-radius:9px;font-size:13px}.login-error{border:1px solid rgba(255,157,173,.35);background:var(--tertiary-soft);color:var(--tertiary)}.login-setup{border:1px solid var(--primary-glow);background:var(--primary-soft);color:var(--primary)}
@media(max-width:760px){.login-screen{padding:12px}.login-console{display:block;min-height:0}.login-brand{padding:30px 26px 26px;border-right:0;border-bottom:1px solid var(--glass-border)}.login-status{margin-top:38px}.login-brand h1{font-size:44px}.login-brand p{font-size:13px}.login-network{margin:34px 0 10px}.login-meta{margin-top:28px}.login-auth{padding:32px 26px}.login-auth-head .login-kicker{text-align:left;margin-bottom:42px}}
@media(prefers-reduced-motion:reduce){*{scroll-behavior:auto!important;animation:none!important;transition:none!important}}
</style>
</head>
<body>
<main class="login-screen">
  <section class="login-console" aria-label="TRUST-NG sign in">
    <div class="login-brand">
      <div class="login-mark"><img src="/img/logo-img/trust-ng.jpg" alt="" aria-hidden="true"><span>TRUST-NG</span></div>
      <div class="login-kicker">DNS MANAGEMENT PLATFORM</div>
      <div class="login-status">NETWORK CONTROL PLANE · ONLINE</div>
      <h1>Your DNS.<br><span style="color:var(--primary)">Under control.</span></h1>
      <p>Centralized DNS management, filtering, and monitoring for infrastructure that stays reliable under pressure.</p>
      <div class="login-network" aria-hidden="true"><span class="login-node"></span><span class="login-link"></span><span class="login-node"></span><span class="login-link"></span><span class="login-node"></span><span class="login-link"></span><span class="login-node"></span></div>
      <div class="login-network-label">RESOLVER NETWORK · SECURE ROUTE</div>
      <div class="login-meta"><span>SECURE SESSION</span><span>DNS CONTROL</span></div>
    </div>
    <div class="login-auth">
      <div class="login-auth-head"><span class="login-kicker">CONTROL PANEL · :<?php echo $panel_port; ?></span><h2>Welcome back</h2><p class="login-auth-intro">Sign in to continue to your console.</p></div>
      <?php if ($setup_mode): ?><div class="login-setup" role="status">First boot — atur password administrator (minimal 6 karakter).</div><?php endif; ?>
      <?php if ($error): ?><div class="login-error" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
      <form method="post" action="<?php echo htmlspecialchars($form_action, ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="csrf" value="<?php echo htmlspecialchars(tng_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>"/>
        <?php if (!$setup_mode): ?><div class="login-field"><label for="username">Username</label><input id="username" type="text" name="username" value="<?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?>" autocomplete="username" required/></div><?php endif; ?>
        <div class="login-field"><label for="password">Password<?php echo $setup_mode ? ' Baru' : ''; ?></label><input id="password" type="password" name="password" autocomplete="current-password new-password" minlength="6" required autofocus/></div>
        <?php if ($setup_mode): ?><div class="login-field"><label for="password2">Ulangi Password Baru</label><input id="password2" type="password" name="password2" autocomplete="new-password" minlength="6" required/></div><?php endif; ?>
        <button class="login-submit" type="submit"><?php echo $setup_mode ? 'Atur Password & Masuk' : 'Sign in Console'; ?></button>
      </form>
    </div>
  </section>
</main>
</body>
</html>
