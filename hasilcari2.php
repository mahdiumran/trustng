<?php
error_reporting(0);

$db = dba_open("/etc/unbound/db/blacklist.db", "r", "cdb");
$qname = explode('.', $search);
$ipaddr = shell_exec("ifconfig eth0 | grep netmask | sed 's/ .*inet //;s/ .*//'");

echo '<html>
<head>
<meta http-equiv="X-UA-Compatible" content="IE=edge"/>
<meta name="viewport" content="width=device-width, initial-scale=1"/>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" type="text/css" href="style.css" />
<title>DNS TRUST-NG - SEARCH RESULT</title>
<script src="/jquery.min.js"></script>
</head>
<body class="with-sidebar">';
include_once 'menu.php';
trustng_render_sidebar('dbtrust.php');
echo '
<div align=center>
<a href="/"><img src="img/logo-img/trust-ng.jpg" width="200px"></a>
<script>
    $("document").ready(function(){
    $("#line_numbers").linenumbers({col_width:"50px"});
    })
</script>
<script src="linear.js"></script>
<p>
<h3>Hasil Pencarian<br>'.$ipaddr.'</h3><h4> Database Trust+ untuk domain "'.$search.'"</h4>
<div class="areatxt"><textarea rows="10" cols="60" name="data"  id="line_numbers" autofocus="autofocus">';

    if (dba_exists(wireformat($qname), $db))
      printf("qname: %s, found: %s\n", $search, implode('.',$qname));
    while (array_shift($qname)) {
      if (dba_exists(wireformat($qname),$db))
        printf("qname: %s, found: %s\n", $search, implode('.',$qname));
    }

echo '</textarea></div>
<input type="button"  onclick="history.back()" class="submit-button" value="Kembali">
<p><small><b>&#169; 2024 Kominfo</b></small>
</div>';
?>
