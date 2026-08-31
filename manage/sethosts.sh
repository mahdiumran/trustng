#!/usr/bin/bash

truncate -c -s 0 /etc/unbound/hosts.conf
file=`cat /var/www/manage/hosts.data`
if [ "$file" != '' ]; then
    printf "server:\n" > /etc/unbound/hosts.conf
else
    exit
fi
while read line; do
    if [ "$line" != '' ]; then
	ip=`echo "$line" | cut -d' ' -f1`
        name=`echo "$line" | cut -d' ' -f2`
	printf "local-data: \"$name IN A $ip\"\n" >> /etc/unbound/hosts.conf
    fi
done < /var/www/manage/hosts.data

while read line; do
    if [ "$line" != '' ]; then
        ip=`echo "$line" | cut -d' ' -f1`
        name=`echo "$line" | cut -d' ' -f2`
        printf "local-data: \"$name IN AAAA $ip\"\n" >> /etc/unbound/hosts.conf
    fi
done < /var/www/manage/hosts6.data
