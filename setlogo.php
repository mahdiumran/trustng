<?php
error_reporting(0);
$myip = $_SERVER['SERVER_ADDR'];
$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
$http_host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : "$myip:40443";
$proto = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$allowed_prefix = "$proto://$http_host/";
$allowed_prefix_ip = "https://$myip:40443/";

$back = 'history.back()';

if($_POST['upload'] ?? null) {
    if (strpos($referer, $allowed_prefix . "setlogo.php") !== 0 && strpos($referer, $allowed_prefix_ip . "setlogo.php") !== 0) exit(0);

    $info = '';
    $target_dir = 'img/logo-img/';

    // Ensure directory exists
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0755, true);
    }

    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $tmp_name = $_FILES['logo']['tmp_name'];
        $filename = $_FILES['logo']['name'];
        $filesize = $_FILES['logo']['size'];
        $allowed_types = array('image/jpeg', 'image/png', 'image/gif', 'image/webp');
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $tmp_name);
        finfo_close($finfo);

        if (!in_array($mime, $allowed_types)) {
            echo "<script>alert('Format file tidak didukung. Gunakan JPG, PNG, GIF, atau WEBP.');history.back();</script>";
            exit;
        }

        if ($filesize > 1048576) {
            echo "<script>alert('Ukuran file terlalu besar. Maksimal 1MB.');history.back();</script>";
            exit;
        }

        // Backup current logo
        $current_logo = $target_dir . 'trust-ng.jpg';
        if (file_exists($current_logo)) {
            @copy($current_logo, $current_logo . '.bak');
        }

        // Save new logo (convert to jpg name for compatibility)
        $dest = $target_dir . 'trust-ng.jpg';
        if (move_uploaded_file($tmp_name, $dest)) {
            // Also copy to trustng-small.jpg for sub-pages
            @copy($dest, 'img/trustng-small.jpg');

            $index = 'yes';
            $back = 'history.go(-2)';
            echo "<script>alert('Logo berhasil diubah. Reload halaman untuk melihat perubahan.');history.go(-2);</script>";
        } else {
            echo "<script>alert('Gagal mengupload logo. Periksa permission folder.');history.back();</script>";
            exit;
        }
    } else {
        echo "<script>alert('Pilih file logo terlebih dahulu.');history.back();</script>";
        exit;
    }
}

if (strpos($referer, $allowed_prefix) !== 0 && strpos($referer, $allowed_prefix_ip) !== 0) {
        if (!isset($index) || $index !== 'yes') {
            $dashboard_ref = "https://$myip:40443/";
            if (strpos($referer, $dashboard_ref) !== 0 && strpos($referer, $allowed_prefix . "index.php") !== 0) exit(0);
        }
}

$ipaddr = shell_exec("ifconfig eth0 | grep netmask | sed 's/ .*inet //;s/ .*//'");

echo '<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1"/>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" type="text/css" href="style.css" />
<title>DNS TRUST-NG - LOGO</title>
</head>
<body class="with-sidebar sidebar-collapsed">
<div id="sidebar-overlay"></div>
<div class="page-shell">
';
include_once 'menu.php';
trustng_render_sidebar('setlogo.php');

echo '<div class="page-content">';
echo '<div class="tng-topbar"><button class="tng-topbar-toggle" title="Toggle menu" aria-label="Toggle menu"><svg width="20" height="20" viewBox="0 0 20 20" fill="none"><rect x="2" y="4.5" width="16" height="2" rx="1" fill="currentColor"/><rect x="2" y="9" width="16" height="2" rx="1" fill="currentColor"/><rect x="2" y="13.5" width="16" height="2" rx="1" fill="currentColor"/></svg></button><span class="tng-topbar-title">Logo</span><div class="tng-topbar-spacer"></div><a class="tng-topbar-back" href="/">&#8592; Dashboard</a></div>';
echo '
<div align=center>
<h3>Logo Dashboard<br><small>'.$ipaddr.'</small></h3>

<div class="logo-preview-box">
  <p class="tng-section-label">Logo Saat Ini</p>
  <img src="img/logo-img/trust-ng.jpg?' . time() . '" class="logo-preview-img" alt="Current Logo">
</div>

<form name="logoupload" action="setlogo.php" method="post" enctype="multipart/form-data">
<input type="hidden" name="upload" value="submit">
<p>
<table>
<tr><td width=100>Logo Baru</td><td><input type="file" name="logo" accept="image/jpeg,image/png,image/gif,image/webp" required /></td></tr>
<tr><td></td><td><small>Format: JPG, PNG, GIF, WEBP. Maksimal 1MB.<br>
Logo akan tampil di sidebar dan topbar dashboard.<br>
Disarankan ukuran persegi, minimal 64x64 px.</small></td></tr>
</table>
<input type="submit" id="submit" value="Upload" class="submit-button"
  onclick="return confirm(\'Konfirmasi upload logo baru? Logo lama akan dibackup otomatis.\')"/>
<a href="/"><input type="button" class="submit-button" value="Kembali"></a>
</form>
<p><small><b>&#169; 2024 Kominfo</b></small>
</div>';

echo '</div></div>';
?>
