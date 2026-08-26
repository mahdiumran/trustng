<?php
$core=`nproc | tr -d '\n'`;
//$cpuidle=`grep Cpu /var/www/manage/top1.dat | tail -$core | cut -d, -f7 | paste -s -d+ | bc`;
$idle = `grep 'Cpu' top1.dat | tail -$core | cut -d, -f4 | sed 's/^ //' | cut -d' ' -f1 |cut -d. -f1 | paste -s -d+ | bc` ;
$wait=`grep Cpu /var/www/manage/top1.dat | tail -$core | cut -d, -f5 | sed 's/ //;s/^ //' | cut -d' ' -f1 | cut -d. -f1| paste -s -d+ | bc`;

//echo "core = $core, idle = $idle, wait = $wait";
echo "$idle";
echo "$wait";
?>
