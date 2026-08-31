truncate -c -s 0 /etc/unbound/whitelist.conf
while read line; do
    echo "local-zone: \"$line.\" whitelist" >> /etc/unbound/whitelist.conf
done < whitelist.db
