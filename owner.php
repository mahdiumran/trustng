<?php
error_reporting(0);
$myip = $_SERVER['SERVER_ADDR'];
$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
$http_host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : "$myip:40443";
$proto = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$allowed_prefix = "$proto://$http_host/";
$allowed_prefix_ip = "https://$myip:40443/";

$back = 'history.back()';
$ipaddr = shell_exec("ifconfig eth0 | grep netmask | sed 's/ .*inet //;s/ .*//'");

if($_POST['owner'] ?? null) {
    if (strpos($referer, $allowed_prefix . "owner.php") !== 0 && strpos($referer, $allowed_prefix_ip . "owner.php") !== 0) exit(0);
    $rplc = array("<",">",":",",","'","?","`",";",'"');
    $pt = $_POST['pt'] ?? '';
    $pt = str_replace($rplc,'',$pt);
    $asn = $_POST['asn'] ?? '';
    $asn = str_replace($rplc,'',$asn);
    $kategori = $_POST['kategori'] ?? '';
    $nama = $_POST['nama'] ?? '';
    $nama = str_replace($rplc,'',$nama);
    $jabatan = $_POST['jabatan'] ?? '';
    $jabatan = str_replace($rplc,'',$jabatan);
    $tlp = $_POST['tlp'] ?? '';
    $tlp = str_replace($rplc,'',$tlp);
    $email = $_POST['email'] ?? '';
    $email = str_replace($rplc,'',$email);
    $file = fopen('owner.data', 'w');
    if ($file) { fwrite($file, "$pt,$asn,$kategori,$nama,$jabatan,$tlp,$email"); fclose($file); }
    $index = 'yes'; $back = 'history.go(-2)';
}

if ($referer != "https://$myip:40443/" && $referer != "https://$myip:40443/index.php") {
        if (!isset($index) || $index !== 'yes') exit(0);
}

$owner = file_get_contents("owner.data");
$rdata = explode(",", $owner);
$pt = trim($rdata[0]);
$asn = trim($rdata[1]);
$kategori = trim($rdata[2]);
$nama = trim($rdata[3]);
$jabatan = trim($rdata[4]);
$tlp = trim($rdata[5]);
$email = trim($rdata[6]);
if ($kategori == '') $select0 = 'selected';
if ($kategori == 'ISP') $select1 = 'selected';
if ($kategori == 'NAP') $select2 = 'selected';
if ($kategori == 'Dinas') $select3 = 'selected';
if ($kategori == 'Universitas') $select4 = 'selected';
if ($kategori == 'Sekolah') $select5 = 'selected';
if ($kategori == 'Perusahaan') $select6 = 'selected';
if ($kategori == 'Lainnya') $select7 = 'selected';

echo '<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1"/>
<link rel="stylesheet" type="text/css" href="style.css" />
<title>DNS TRUST-NG - OPTIONS</title>
<script src="kunci.js"></script>
</head>
<body class="with-sidebar sidebar-collapsed">
<div id="sidebar-overlay"></div>
<div class="page-shell">';
include_once 'menu.php';
trustng_render_sidebar('owner.php');

echo '<div class="page-content">';
echo '<div class="tng-topbar"><button class="tng-topbar-toggle" title="Toggle menu" aria-label="Toggle menu"><svg width="20" height="20" viewBox="0 0 20 20" fill="none"><rect x="2" y="4.5" width="16" height="2" rx="1" fill="currentColor"/><rect x="2" y="9" width="16" height="2" rx="1" fill="currentColor"/><rect x="2" y="13.5" width="16" height="2" rx="1" fill="currentColor"/></svg></button><span class="tng-topbar-title">Ownership</span><div class="tng-topbar-spacer"></div><a class="tng-topbar-back" href="/">&#8592; Dashboard</a></div>';
include 'submit.js';
echo'
<div align=center>
<a href="/"><img src="img/trustng-small.jpg" width="200px"></a>
<form name="owner" action="owner.php" method="post">
<p>
<h3>Form Ownership<br><small>'.$ipaddr.'</small></h3>
<input type="hidden" name="owner" value="submit">
<table>
<tr><td width=50>PT:</td><td><input style="display: inline;" type="text" size="30" name="pt" value="'.$pt.'" placeholder="nama PT. Perusahaan Terdaftar" required /></td></tr>
<tr><td>ASN:</td><td><input style="display: inline;" type="text" size="15" name="asn" value="'.$asn.'" placeholder="AS53241" required /></td></tr>
<tr><td>Kategori:</td><td>
<select name="kategori" id="kategori" required>
  <option value="" disabled  '.$select0.'>pilih kategori yang sesuai</option>
  <option value="ISP" '.$select1.'>ISP</option>
  <option value="NAP" '.$select2.'>NAP</option>
  <option value="Dinas" '.$select3.'>Dinas</option>
  <option value="Universitas" '.$select4.'>Universitas</option>
  <option value="Sekolah" '.$select5.'>Sekolah</option>
  <option value="Perusahaan" '.$select6.'>Perusahaan</option>
  <option value="Lainnya" '.$select7.'>Lainnya</option>
</select>
</td></tr>
<tr><td>PIC:</td><td><input style="display: inline;" type="text" size="30" name="nama" value="'.$nama.'" placeholder="nama PIC yg handle mesin ini" required /></td></tr>
<tr><td>Jabatan:</td><td><input style="display: inline;" type="text" size="30" name="jabatan" value="'.$jabatan.'" placeholder="jabatan PIC pada PT di atas" required /></td></tr>
<tr><td>Tlp (HP):</td><td><input style="display: inline;" type="text" size="15" name="tlp" value="'.$tlp.'" placeholder="08123456789" required /></td></tr>
<tr><td>Email:</td><td><input style="display: inline;" type="text" size="30" name="email" value="'.$email.'" placeholder="email PIC atau email bagian terkait" required /></td></tr>
<tr><td colspan=2><small><i>
<b>Perhatian!:</b><ol>
<li>Data PT dan ASN harus diisi sama untuk setiap mesin Trust-NG yang dipasang dibawah ASN untuk kemudahan pendataan dan validasi.</li>
<li>Data yang tidak valid akan diabaikan.</li>
<li>Yang tidak memiliki ASN tidak wajib mengisi form ini dan akan diabaikan.</li>
<li>Form ini harus diupdate jika ada perubahan data, khususnya bagian PIC, Tlp, dan Email.</li>
</ol></i></small></td></tr>
</table>
<input type="submit" id="submit" value="Simpan" class="submit-button"/> <a href="/"><input type="button" class="submit-button" value="Kembali"></a>
</form>
<p><small><b>&#169; 2024 Kominfo</b></small>
</div>';


echo '</div></div>';
?>