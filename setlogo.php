<?php
require_once __DIR__ . '/includes/auth.php';
tng_session_start();
if (empty($_SESSION['tng_user'])) {
    header('Location: /login.php');
    exit(0);
}
header('Location: setpwd.php#logo');
exit(0);
