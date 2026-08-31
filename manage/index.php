<?php
error_reporting(0);
$filename = 'setup.mulai';
$index='yes';

if(file_exists($filename)){
    echo "<script>alert('Pertama kali login harus ganti password');</script>";
    include 'setpwd.php';
    exit;
} else {
    include 'manage.php';
}
?>
