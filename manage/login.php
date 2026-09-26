<?php
error_reporting(0);
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/ui.php';
tng_session_start();

if (!empty($_SESSION['tng_user']) && tng_current_pw_version() && !file_exists(TNG_SETUP_FLAG)) {
    header('Location: /');
    exit(0);
}

$setup_mode = file_exists(TNG_SETUP_FLAG);
$error = isset($_GET['session']) && $_GET['session'] === 'expired'
    ? 'Sesi login diperbarui. Silakan masukkan password kembali.'
    : '';
$ip = tng_client_ip();
$username = isset($_POST['username']) ? trim($_POST['username']) : 'admin';
$password = isset($_POST['password']) ? strval($_POST['password']) : '';
$password2 = isset($_POST['password2']) ? strval($_POST['password2']) : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_sent = isset($_POST['csrf']) ? strval($_POST['csrf']) : '';
    if (!tng_csrf_check($csrf_sent)) {
        unset($_SESSION['tng_csrf']);
        session_regenerate_id(true);
        header('Location: /login.php?session=expired');
        exit(0);
    } elseif ($setup_mode && file_exists(TNG_SETUP_FLAG)) {
        if (strlen($password) < 6) {
            $error = 'Password minimal 6 karakter.';
        } elseif ($password !== $password2) {
            $error = 'Konfirmasi password tidak cocok.';
        } elseif (!tng_set_password('admin', $password)) {
            $error = 'Password gagal disimpan. Periksa penyimpanan autentikasi.';
        } else {
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

tng_ui_head($setup_mode ? 'Setup Administrator' : 'Login');
?>
<main class="login-page">
  <section class="login-brand-panel" aria-label="TRUST-NG">
    <div class="login-brand">
      <span class="login-brand-mark" aria-hidden="true"><img src="img/logo-img/trust-ng.jpg" alt=""></span>
      <span class="login-brand-copy"><strong>TRUST-NG</strong><small>DNS Control Plane</small></span>
    </div>
    <div class="login-brand-content">
      <span class="login-brand-kicker">KOMINFO · RESOLVER TERKELOLA</span>
      <h1>DNS nasional yang aman, cepat, dan transparan.</h1>
      <p>Kelola resolver, filter konten, dan pemantauan DNS dari satu ruang kerja terkendali untuk institusi dan penyedia layanan internet.</p>
      <div class="login-network" aria-hidden="true">
        <i class="fa-solid fa-diagram-project"></i>
        <span>Resolver network · secure route</span>
      </div>
    </div>
    <div class="login-brand-footer">
      <span>TRUST-NG DNS Services · &copy; 2024 Kominfo</span>
    </div>
  </section>
  <section class="login-form-panel">
    <div class="login-form-wrap">
      <span class="kicker">CONTROL PANEL · :<?php echo tng_e($panel_port); ?></span>
      <h2><?php echo $setup_mode ? 'Setup Administrator' : 'Selamat datang'; ?></h2>
      <p class="login-intro"><?php echo $setup_mode ? 'Atur password administrator untuk mengaktifkan panel (minimal 6 karakter).' : 'Masuk untuk melanjutkan ke console DNS.'; ?></p>
      <?php if ($setup_mode): ?>
      <div class="login-setup" role="status"><i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i> First boot — atur password administrator (minimal 6 karakter).</div>
      <?php endif; ?>
      <?php if ($error): ?>
      <div class="login-error" role="alert"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i> <?php echo tng_e($error); ?></div>
      <?php endif; ?>
      <form class="login-form" method="post" action="<?php echo tng_e($form_action); ?>" data-busy-label="<?php echo $setup_mode ? 'Menyimpan…' : 'Memverifikasi…'; ?>">
        <input type="hidden" name="csrf" value="<?php echo tng_e(tng_csrf_token()); ?>"/>
        <?php if (!$setup_mode): ?>
        <div class="login-field field">
          <label for="username">Username</label>
          <input id="username" type="text" name="username" value="<?php echo tng_e($username); ?>" autocomplete="username" required/>
        </div>
        <?php endif; ?>
        <div class="login-field field">
          <label for="password">Password<?php echo $setup_mode ? ' Baru' : ''; ?></label>
          <input id="password" type="password" name="password" autocomplete="<?php echo $setup_mode ? 'new-password' : 'current-password'; ?>" minlength="6" required autofocus/>
        </div>
        <?php if ($setup_mode): ?>
        <div class="login-field field">
          <label for="password2">Ulangi Password Baru</label>
          <input id="password2" type="password" name="password2" autocomplete="new-password" minlength="6" required/>
        </div>
        <?php endif; ?>
        <button class="login-submit submit-button" type="submit"><i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i><span class="login-submit-label"><?php echo $setup_mode ? 'Atur Password & Masuk' : 'Masuk ke Console'; ?></span></button>
      </form>
      <div class="login-trust" role="note">
        <span><i class="fa-solid fa-lock" aria-hidden="true"></i> Sesi aman</span>
        <span><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> CSRF aktif</span>
        <span><i class="fa-solid fa-clock" aria-hidden="true"></i> Lockout 15 menit</span>
      </div>
    </div>
  </section>
</main>
<script src="menu.js"></script>
<script>(function(){var f=document.querySelector('.login-form');if(!f)return;f.addEventListener('submit',function(){var b=f.querySelector('.login-submit');if(!b||b.disabled)return;var l=b.querySelector('.login-submit-label');if(l)l.textContent=f.getAttribute('data-busy-label')||'Memproses…';b.disabled=true;b.setAttribute('aria-busy','true');});})();</script>
</body>
</html>
