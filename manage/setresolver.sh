#!/usr/bin/bash

truncate -c -s 0 /etc/unbound/parent.conf
res1=`cat /var/www/manage/resolver.data | cut -d, -f1`
res2=`cat /var/www/manage/resolver.data | cut -d, -f2`
res3=`cat /var/www/manage/resolver.data | cut -d, -f3`
res4=`cat /var/www/manage/resolver.data | cut -d, -f4`
res5=`cat /var/www/manage/resolver.data | cut -d, -f5`
res6=`cat /var/www/manage/resolver.data | cut -d, -f6`
printf "forward-zone:\n\tname: \".\"\n" > /etc/unbound/parent.conf
if [ "$res1" != '' ]; then printf "\tforward-addr: \"$res1\"\n" >> /etc/unbound/parent.conf; fi
if [ "$res2" != '' ]; then printf "\tforward-addr: \"$res2\"\n" >> /etc/unbound/parent.conf; fi
if [ "$res3" != '' ]; then printf "\tforward-addr: \"$res3\"\n" >> /etc/unbound/parent.conf; fi
if [ "$res4" != '' ]; then printf "\tforward-addr: \"$res4\"\n" >> /etc/unbound/parent.conf; fi
if [ "$res5" != '' ]; then printf "\tforward-addr: \"$res5\"\n" >> /etc/unbound/parent.conf; fi
if [ "$res6" != '' ]; then printf "\tforward-addr: \"$res6\"\n" >> /etc/unbound/parent.conf; fi
