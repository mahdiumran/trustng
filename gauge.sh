#!/bin/bash
PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin
export DISPLAY=:0.0

#while true; do
        top -b -n 3 > /var/www/manage/top1.dat
        core=`nproc`
	cek=`grep Cpu /var/www/manage/top1.dat | tail -$core`
	if [ "$cek" == '' ]; then exit; fi

        cpuidle=`grep Cpu /var/www/manage/top1.dat | tail -$core | sed 's/\./,/g' |cut -d, -f7 | paste -s -d+ | bc`
        wait=`grep Cpu /var/www/manage/top1.dat | tail -$core | sed 's/\./,/g' | cut -d, -f9 | paste -s -d+ | bc`
	max=$(($core * 100))
        load=`uptime | sed 's/.*: //' | cut -d, -f1`
        ramtotal=`grep 'MemTotal' /proc/meminfo | cut -d: -f2 | sed 's/[^0-9]*//g'`
        ramfree=`grep 'MemFree' /proc/meminfo | cut -d: -f2 | sed 's/[^0-9]*//g'`

        gcpu=`echo "(100 - ($cpuidle / $core))" | bc`
        gload=`echo "($load * 100) / $core" | bc`
        iowait=`echo "$wait / $core" | bc`
        geram=`echo "($ramtotal - $ramfree) *100 / $ramtotal" | bc`
        gdisk=`df -h | grep /$ | sed 's/% \///;s/.* //'`;

        printf "['Label', 'Value'],\n" > /var/www/manage/gauge.dat
        printf "['RAM', $geram],\n" >> /var/www/manage/gauge.dat
        printf "['CPU', $gcpu],\n" >> /var/www/manage/gauge.dat
        printf "['Load', $gload],\n" >> /var/www/manage/gauge.dat
        printf "['Disk', $gdisk],\n" >> /var/www/manage/gauge.dat
        printf "['Iowait', $iowait]" >> /var/www/manage/gauge.dat
#done
