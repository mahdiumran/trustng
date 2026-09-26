<?php
error_reporting(0);
$file = file("hasilcari.txt");
$ipaddr = shell_exec("ifconfig eth0 | grep netmask | sed 's/ .*inet //;s/ .*//'");

require_once __DIR__ . '/includes/ui.php';

tng_ui_page_start('dbtrust.php', 'Hasil Pencarian', 'Database Trust+ untuk keyword "' . (isset($keyword) ? $keyword : '') . '".');
?>
<div data-page-actions>
  <a class="button button-secondary button-small" href="dbtrust.php"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Kembali ke Pencarian</a>
</div>

<div class="stack">
  <div class="card">
    <div class="card-header">
      <div>
        <h2>Hasil Pencarian</h2>
        <p>Host @<span class="di-server"><?php echo tng_e(trim((string) $ipaddr)); ?></span></p>
      </div>
    </div>
    <div class="card-body">
      <div class="areatxt"><textarea rows="10" cols="60" name="data" id="line_numbers" autofocus="autofocus"><?php
foreach ($file as $text) { echo tng_e($text); }
?></textarea></div>
    </div>
  </div>

  <div class="di-actions">
    <button type="button" class="button button-secondary" onclick="history.back()">Kembali</button>
  </div>
</div>
<?php
echo '<script src="/jquery.min.js"></script>';
echo '<script src="linear.js"></script>';
echo '<script>(function(){if(window.jQuery&&jQuery.fn.linenumbers){jQuery("#line_numbers").linenumbers({col_width:"50px"});}})();</script>';
tng_ui_page_end('dbtrust.php');
