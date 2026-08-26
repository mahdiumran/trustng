#!/usr/bin/bash

# Detect Munin domain and hostname dynamically so graphs work on any server
MUNIN_DOMAIN=$(grep -m1 '^\[' /etc/munin/munin.conf 2>/dev/null | sed 's/^\[//;s/\]//;s/;.*//;s/:.*//')
MUNIN_HOST=$(grep -m1 '^\[' /etc/munin/munin.conf 2>/dev/null | sed 's/^\[//;s/\]//;s/.*;//;s/.*://')

# Fallback: derive from system hostname
if [ -z "$MUNIN_DOMAIN" ] || [ "$MUNIN_DOMAIN" = "$MUNIN_HOST" ]; then
    MUNIN_DOMAIN=$(hostname -d 2>/dev/null)
    [ -z "$MUNIN_DOMAIN" ] && MUNIN_DOMAIN="localdomain"
fi
if [ -z "$MUNIN_HOST" ]; then
    MUNIN_HOST=$(hostname -s 2>/dev/null || hostname)
fi

MUNIN_CACHE="/var/cache/munin/www/${MUNIN_DOMAIN}/${MUNIN_HOST}"
MUNIN_LIB="/var/lib/munin/${MUNIN_DOMAIN}"

sudo rm -rf "${MUNIN_CACHE}/"* /var/www/manage/ssh.port 2>/dev/null
sudo rm -rf "${MUNIN_CACHE}" 2>/dev/null
sudo rm -f "${MUNIN_LIB}/"* 2>/dev/null
sudo rm -f /var/log/munin/*.log.* /var/log/munin/*.log /var/www/manage/*.new 2>/dev/null
sudo cp /bin/munin-cron.old /bin/munin-cron 2>/dev/null

truncate -s 0 /etc/unbound/parent.conf /etc/unbound/rpz.conf /etc/unbound/hosts.conf /etc/unbound/forwarder.conf /etc/tproxy.conf /var/www/manage/hosts6.data
truncate -s 0 /var/www/manage/forwarder.data /var/www/manage/resolver.data /var/www/manage/hosts.data /var/www/manage/ipalias.data /var/www/manage/setsafesearch
truncate -s 0 /var/www/manage/settproxy /var/www/manage/setdnssec /var/www/manage/owner.data /var/www/manage/ipalias6.data /var/www/manage/setsnmpd /var/www/manage/snmpd.community

sudo sed -i 's/.*/Port 7857/' /etc/ssh/port.conf

printf "sleep 310\ncp /bin/munin-cron.new /bin/munin-cron\n" > /var/www/manage/nextjob.sh

sudo sed -i 's/ = 0/ = 1/' /etc/sysctl.conf
sudo sed -i 's/lo.disable_ipv6 = 1/lo.disable_ipv6 = 0/' /etc/sysctl.conf
sudo /usr/sbin/service snmpd stop
sudo /usr/sbin/systemctl disable snmpd
printf "agentaddress udp:161\nrocommunity public 0.0.0.0/0\n\nagentaddress udp6:161\nrocommunity6 public ::/0\n" > /etc/snmp/snmpd.conf
rm *.dig
