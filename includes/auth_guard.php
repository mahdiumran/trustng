<?php
/**
 * TRUST-NG auth guard — auto_prepend_file di nginx.
 * Melindungi SEMUA halaman .php tanpa mengedit file per halaman.
 * Halaman login/logout dikecualikan via TNG_AUTH_EXempt.
 */
if (!defined('TNG_AUTH_DB')) {
    require_once __DIR__ . '/auth.php';
}

// halaman yang boleh diakses tanpa sesi
$exempt = array('login.php', 'logout.php');
$script = basename(isset($_SERVER['SCRIPT_FILENAME']) ? $_SERVER['SCRIPT_FILENAME'] : '');
if (in_array($script, $exempt, true)) {
    return; // biarkan halaman itu jalan
}

tng_session_start();

// first-boot: wajib set password
if (file_exists(TNG_SETUP_FLAG)) {
    header('Location: /login.php?setup=1');
    exit(0);
}

if (empty($_SESSION['tng_user']) || !tng_current_pw_version()) {
    // sesi invalid/expired/credential berubah
    $_SESSION = array();
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
    header('Location: /login.php');
    exit(0);
}
