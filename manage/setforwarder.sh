#!/usr/bin/bash

truncate -c -s 0 /etc/unbound/forwarder.conf
while read line; do
    if [ "$line" != '' ]; then
	qname=`echo "$line" | cut -d, -f1`
        dns=`echo "$line" | cut -d, -f2`
        dns2=`echo "$line" | cut -d, -f3`
        dns3=`echo "$line" | cut -d, -f4`
	printf "forward-zone:\n\tname: \"$qname\"\n\tforward-addr: \"$dns\"\n" >> /etc/unbound/forwarder.conf
	if [ "$dns2" != '' ]; then printf "\tforward-addr: \"$dns2\"\n" >> /etc/unbound/forwarder.conf; fi
        if [ "$dns3" != '' ]; then printf "\tforward-addr: \"$dns3\"\n" >> /etc/unbound/forwarder.conf; fi
    fi
done < /var/www/manage/forwarder.data
chown unbound:unbound /etc/unbound/forwarder.conf 2>/dev/null || true
