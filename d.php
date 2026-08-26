<?php
$data = @shell_exec("bash " . __DIR__ . "/digtest.sh 2>/dev/null");
echo "<div align=center>$data</div>";
?>
