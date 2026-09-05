truncate -c -s 0 /etc/unbound/whitelist.conf
while read line; do
    [ -z "$line" ] && continue
    echo "local-zone: \"$line.\" whitelist" >> /etc/unbound/whitelist.conf
done < /var/www/manage/whitelist.db
chown unbound:unbound /etc/unbound/whitelist.conf 2>/dev/null || true
