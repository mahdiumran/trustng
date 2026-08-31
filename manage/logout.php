<?php
error_reporting(0);
require_once __DIR__ . '/includes/auth.php';
tng_session_start();
$_SESSION = array();
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();
header('Location: /login.php');
exit(0);
