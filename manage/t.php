<?php
$data = `/usr/bin/sensors`;
echo "<pre><small>" . htmlspecialchars((string) $data, ENT_QUOTES, 'UTF-8') . "</small></pre>";

?>
