<?php
$data = @shell_exec("bash " . __DIR__ . "/digtest.sh 2>/dev/null");
echo "<pre>" . htmlspecialchars((string) $data, ENT_QUOTES, 'UTF-8') . "</pre>";
?>
