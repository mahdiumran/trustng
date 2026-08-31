#!/usr/bin/bash

for i in {1..255}; do
    sudo /usr/sbin/ifconfig lo:$i 0.0.0.0
done

ifconfig | grep inet6 | grep global | sed 's/  / /g;s/  //g' | cut -d' ' -f2,4 | sed 's/ /\//' > ip6.loopback

n=0
sed '$ s/$/\n/' /var/www/manage/ipalias.data > /var/www/manage/ipalias.data.set
sed '$ s/$/\n/' /var/www/manage/ipalias6.data > /var/www/manage/ipalias6.data.set
while read line; do
    n=$(($n+1))
    if [ "$line" != '' ]; then
	sudo /usr/sbin/ifconfig lo:$n $line
    fi
done < /var/www/manage/ipalias.data.set

while read line; do
    ifconfig lo del $line
done < ip6.loopback

while read line; do
    ifconfig lo add $line
done < /var/www/manage/ipalias6.data.set
