<?php
error_reporting(0);
require_once __DIR__ . '/includes/auth.php';
$filename = TNG_SETUP_FLAG;
$index='yes';

if(file_exists($filename)){
    header('Location: /login.php?setup=1');
    exit(0);
} else {
    include 'manage.php';
}
?>
