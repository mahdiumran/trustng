#!/bin/bash
# TRUST-NG Full Installer — Unbound patched + blocklist + Munin + Panel + Nginx
# Idempotent, run as root on Debian 12 x86_64
set -eu

DEPLOY_DIR=$(CDPATH='' cd -- "$(dirname -- "$0")" && pwd)
test "$(id -u)" -eq 0 || { echo "install.sh harus dijalankan sebagai root" >&2; exit 1; }

echo "== TRUST-NG Full Installer =="
echo "Deploy dir: $DEPLOY_DIR"

# ---- 1. Install required packages first (before dependency check)
echo "[INFO] Installing dependencies..."
apt-get update -qq
DEBIAN_FRONTEND=noninteractive apt-get install -y -qq \
    nginx php8.2-fpm php-sqlite3 php8.2-cli dos2unix \
    curl dnsutils python3 systemd openssl adduser passwd sqlite3 \
    libevent-2.1-7 libevent-core libevent-dev \
    munin munin-node \
    || echo "[WARN] Some packages failed — check output above"

# nftables — needed for DNS firewall (silent-drop port 53 for non-clients)
apt-get install -y -qq nftables || echo "[WARN] nftables install failed — firewall rules will not be applied"

# Ensure /usr/sbin is in PATH (useradd, adduser, nft live here)
export PATH="/usr/sbin:/sbin:$PATH"

# ---- 2. Dependency check (after packages installed)
for cmd in curl dig python3 systemctl openssl php useradd; do
    command -v $cmd >/dev/null 2>&1 || { echo "ERROR: '$cmd' tidak ada setelah install. Coba install manual: apt install <package>" >&2; exit 1; }
done
echo "[OK] Semua dependencies terinstall"

# ---- 3. Bundel artifacts check
required="bin/unbound bin/unbound-checkconf bin/unbound-control scripts/create_domain_cdb.py scripts/update-blocklist conf/unbound.conf conf/sources.txt conf/nftables.conf"
for f in $required; do
    test -s "$DEPLOY_DIR/$f" || { echo "ERROR: artifact hilang: $f" >&2; exit 1; }
done

# ---- 3. User & dirs
id unbound >/dev/null 2>&1 || {
    command -v useradd >/dev/null 2>&1 || apt-get install -y -qq passwd >/dev/null 2>&1 || true
    /usr/sbin/useradd --system --home-dir /var/lib/unbound --shell /usr/sbin/nologin unbound 2>/dev/null \
    || adduser --system --home /var/lib/unbound --shell /usr/sbin/nologin --group unbound 2>/dev/null \
    || { echo "ERROR: Tidak bisa membuat user unbound. Install manual: apt install passwd adduser" >&2; exit 1; }
}
install -d -m 0755 /etc/unbound /etc/unbound/db /etc/unbound/run /etc/unbound/key /usr/local/sbin /usr/local/bin /usr/local/libexec
install -d -o unbound -g unbound -m 0755 /etc/unbound/run /etc/unbound/db

# ---- 4. Install Unbound patched binaries and utility scripts
install -m 0755 "$DEPLOY_DIR/bin/unbound"          /usr/local/sbin/unbound
install -m 0755 "$DEPLOY_DIR/bin/unbound-checkconf" /usr/local/sbin/unbound-checkconf
install -m 0755 "$DEPLOY_DIR/bin/unbound-control"   /usr/local/sbin/unbound-control
install -m 0755 "$DEPLOY_DIR/scripts/create_domain_cdb.py" /usr/local/libexec/create_domain_cdb.py
install -m 0755 "$DEPLOY_DIR/scripts/update-blocklist"     /usr/local/sbin/update-blocklist
[ -f "$DEPLOY_DIR/scripts/resetpass.sh" ] && install -m 0755 "$DEPLOY_DIR/scripts/resetpass.sh" /usr/local/sbin/resetpass.sh
[ -f "$DEPLOY_DIR/manage/repairmunin.sh" ] && install -m 0755 "$DEPLOY_DIR/manage/repairmunin.sh" /usr/local/sbin/repairmunin.sh

# ---- 5. Unbound config (idempotent)
[ -f /etc/unbound/unbound.conf ] || install -m 0644 "$DEPLOY_DIR/conf/unbound.conf" /etc/unbound/unbound.conf
[ -f /etc/unbound/db/sources.txt ] || install -m 0644 "$DEPLOY_DIR/conf/sources.txt" /etc/unbound/db/sources.txt

# Hints + trust anchor
[ -f /etc/unbound/run/hints ] || install -o unbound -g unbound -m 0644 /usr/share/dns/root.hints /etc/unbound/run/hints 2>/dev/null || true

# Generate root.key using unbound-anchor (required by unbound-checkconf)
if [ ! -s /etc/unbound/key/root.key ]; then
    # Install unbound-anchor if missing
    command -v unbound-anchor >/dev/null 2>&1 || apt-get install -y -qq unbound-anchor 2>/dev/null || true
    mkdir -p /etc/unbound/key
    # Try generating trust anchor
    if command -v unbound-anchor >/dev/null 2>&1; then
        unbound-anchor -a /etc/unbound/key/root.key 2>/dev/null || true
    fi
    # Fallback: copy from system locations
    [ -s /etc/unbound/key/root.key ] || cp -f /var/lib/unbound/root.key /etc/unbound/key/root.key 2>/dev/null || true
    [ -s /etc/unbound/key/root.key ] || cp -f /usr/share/dns/root.key /etc/unbound/key/root.key 2>/dev/null || true
    # Final fallback: minimal valid trust anchor
    [ -s /etc/unbound/key/root.key ] || cat > /etc/unbound/key/root.key <<'KEY'
. IN DS 20326 8 2 683D2D0ACB5C2EED8C6783AFA516D0BE8A937AC3504823D56FA7010615E84B1C
KEY
    chown unbound:unbound /etc/unbound/key/root.key
    chmod 0644 /etc/unbound/key/root.key
fi

# Fragment defaults
touch_empty() { [ -f "$1" ] || { : > "$1"; chown unbound:unbound "$1"; }; }
for f in whitelist.conf refuse-any.conf rpz.conf hosts.conf forwarder.conf parent.conf; do
    touch_empty /etc/unbound/$f
done
[ -f /etc/unbound/module-config.conf ] || printf 'module-config: "iterator"' > /etc/unbound/module-config.conf
chown unbound:unbound /etc/unbound/module-config.conf 2>/dev/null || true
[ -f /etc/unbound/lamanlabuh.conf ] || cat > /etc/unbound/lamanlabuh.conf <<'LLAB'
local-data: "blacklist. 60 IN A 10.150.1.18"
local-data: "blacklist. 60 IN AAAA 2a0f:85c1:8b9:600::18"
LLAB

# ---- 6. Panel installation (manage/ -> /var/www/manage)
WEBROOT="/var/www/manage"
PANEL_SRC="$DEPLOY_DIR/manage"
install -d -m 0755 "$WEBROOT"

# Copy panel source
while IFS= read -r -d '' source; do
    relative=${source#"$DEPLOY_DIR/manage/"}
    [ -z "$relative" ] && continue
    case "$relative" in
        *.data|*.data.set|*.db|*.dig|*.ip|*.log|*.new|*.pending|*.bak|*.lock|*.key) continue ;;
        .htpasswd|setup.mulai|recovery.key|gauge.dat|top1.dat|hasilcari.txt|nextjob.sh) continue ;;
        ip6.loopback|reload.lock) continue ;;
        AGENTS.md|DESIGN.md|tests_port_config.php|backup-*/*) continue ;;
    esac
    destination="$WEBROOT/$relative"
    install -d -m 0755 "$(dirname "$destination")"
    install -m 0644 "$source" "$destination"
done < <(find "$DEPLOY_DIR/manage" -type f -print0)

find "$WEBROOT" -type f -name '*.sh' -exec chmod 0755 {} +
chown -R root:root "$WEBROOT"

# Panel runtime state
for name in forwarder.data resolver.data hosts.data hosts6.data ipaddr.data ip6addr.data ipalias.data ipalias6.data owner.data clients.ip clients6.ip whitelist.db blacklist.local.db lp1.ip lp2.ip lp3.ip lp4.ip lp5.ip lp6.ip setsafesearch settproxy setdnssec setsnmpd setip6 ip6auto ssh.port ssl.port snmpd.community; do
    [ -e "$WEBROOT/$name" ] || : > "$WEBROOT/$name"
    chown www-data:www-data "$WEBROOT/$name"
    chmod 0664 "$WEBROOT/$name"
done

# Auth database
install -d -m 0750 /var/lib/trustng-auth
chown www-data:www-data /var/lib/trustng-auth

# ---- 7. Generate whitelist from panel (must run from WEBROOT so whitelist.db is found)
if [ -x "$WEBROOT/setwhitelist.sh" ] && [ -s "$WEBROOT/whitelist.db" ]; then
    (cd "$WEBROOT" && sh setwhitelist.sh) && chown unbound:unbound /etc/unbound/whitelist.conf 2>/dev/null || true
fi

# ---- 8. Validate Unbound config
/usr/local/sbin/unbound-checkconf /etc/unbound/unbound.conf

# ---- 9. Systemd unit for patched Unbound
mkdir -p /etc/systemd/system/unbound.service.d

# Create base unit if it doesn't exist (patched unbound has no apt package)
if [ ! -f /etc/systemd/system/unbound.service ] && [ ! -f /lib/systemd/system/unbound.service ]; then
    cat > /etc/systemd/system/unbound.service <<'UNIT'
[Unit]
Description=Unbound DNS Validator
After=network-online.target
Wants=network-online.target

[Service]
Type=simple
ExecStartPre=/usr/local/sbin/unbound-anchor -a /var/lib/unbound/root.key
ExecStart=/usr/local/sbin/unbound -d -c /etc/unbound/unbound.conf
ExecReload=/bin/kill -HUP $MAINPID
Restart=on-failure
RestartSec=5
SupplementaryGroups=unbound

[Install]
WantedBy=multi-user.target
UNIT
    echo "[OK] unbound.service unit created"
fi

# Drop-in override for patched binary paths
cp "$DEPLOY_DIR/systemd/unbound-override.conf" /etc/systemd/system/unbound.service.d/override.conf
systemctl daemon-reload

# ---- 10. Initial blocklist
if [ ! -s /etc/unbound/db/blacklist.db ]; then
    echo "[INFO] blacklist.db belum ada — jalankan updater awal (bisa lama, download ~60MB)"
    /usr/local/sbin/update-blocklist || echo "[WARN] updater awal gagal; timer akan mencoba lagi"
else
    chown unbound:unbound /etc/unbound/db/blacklist.db 2>/dev/null || true
fi

# ---- 11. Auto-update timer
install -m 0644 "$DEPLOY_DIR/systemd/update-blocklist.service" /etc/systemd/system/
install -m 0644 "$DEPLOY_DIR/systemd/update-blocklist.timer"   /etc/systemd/system/
systemctl daemon-reload
systemctl enable --now update-blocklist.timer

# ---- 12. Start Unbound
systemctl enable unbound
systemctl restart unbound
sleep 3
systemctl is-active unbound >/dev/null || { echo "ERROR: unbound gagal start — cek journalctl -u unbound" >&2; exit 1; }

# ---- 13. Munin + Nginx integration
MUNIN_DOMAIN=$(hostname -d 2>/dev/null)
[ -z "$MUNIN_DOMAIN" ] && MUNIN_DOMAIN="localdomain"
MUNIN_HOST=$(hostname -s 2>/dev/null || hostname)

# Munin config
if [ ! -f /etc/munin/munin.conf ] || ! grep -q '^\[' /etc/munin/munin.conf 2>/dev/null; then
    cat > /etc/munin/munin.conf <<EOF
# TRUST-NG generated munin.conf (domain=$MUNIN_DOMAIN host=$MUNIN_HOST)
dbdir    /var/lib/munin
htmldir  /var/cache/munin/www
logdir   /var/log/munin
rundir   /var/run/munin
tmpldir  /etc/munin/templates
graph_strategy cron
html_strategy cron
timeout 60

[$MUNIN_DOMAIN;$MUNIN_HOST]
    address 127.0.0.1
    use_node_name yes
EOF
    echo "[OK] munin.conf (domain=$MUNIN_DOMAIN host=$MUNIN_HOST)"
fi

# Munin cron variants for panel repair/reset
cat > /bin/munin-cron.full <<'EOF'
#!/bin/bash
/usr/share/munin/munin-update  "$@" || exit 1
/usr/share/munin/munin-limits  "$@"
/usr/share/munin/munin-html    "$@" || exit 1
/usr/share/munin/munin-graph --cron "$@" || exit 1
EOF
chmod +x /bin/munin-cron.full
[ -f /bin/munin-cron ] || cp -f /bin/munin-cron.full /bin/munin-cron
cp -f /bin/munin-cron.full /bin/munin-cron.old
cp -f /bin/munin-cron.full /bin/munin-cron.new
chmod +x /bin/munin-cron /bin/munin-cron.old /bin/munin-cron.new

install -d -o munin -g munin -m 0755 /var/cache/munin/www /var/lib/munin /var/log/munin /var/run/munin
systemctl enable --now munin-node

# ---- 14. Nginx vhost (port 40443)
NGINX_CONF="/etc/nginx/sites-available/trustng"
WEBROOT="/var/www/manage"
cat > "$NGINX_CONF" <<NGINX
server {
    listen 40443 ssl;
    listen [::]:40443 ssl;
    server_name _;

    ssl_certificate     /etc/nginx/ssl/trustng.crt;
    ssl_certificate_key /etc/nginx/ssl/trustng.key;
    ssl_protocols       TLSv1.2 TLSv1.3;

    root /var/www/manage;
    index index.php index.html;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php\$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        fastcgi_param HTTPS on;
        fastcgi_param PHP_VALUE "auto_prepend_file=/var/www/manage/includes/auth_guard.php";
    }

    location ^~ /includes/ {
        deny all;
    }

    location /munin/ {
        auth_request /munin-auth;
        error_page 401 @munin_login;
        alias /var/cache/munin/www/;
        index index.html;
        autoindex off;
    }

    location = /munin-auth {
        internal;
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME /var/www/manage/munin_auth.php;
        fastcgi_param HTTPS on;
        fastcgi_param PHP_VALUE "auto_prepend_file=/var/www/manage/includes/auth_guard.php";
    }

    location @munin_login {
        return 302 /login.php;
    }

    location ~ /\.(htpasswd|htaccess|data|ip|dig|dat|db|key|count|new|sh)\$ {
        deny all;
    }
}
NGINX

ln -sf "$NGINX_CONF" /etc/nginx/sites-enabled/trustng 2>/dev/null || true

# SSL cert (self-signed for initial install)
[ -f /etc/nginx/ssl/trustng.crt ] || {
    mkdir -p /etc/nginx/ssl
    openssl req -x509 -nodes -newkey rsa:2048 -days 3650 \
        -subj "/CN=trustng.local" \
        -keyout /etc/nginx/ssl/trustng.key \
        -out /etc/nginx/ssl/trustng.crt 2>/dev/null
}

# Validate & reload nginx
nginx -t && systemctl reload nginx

# ---- 15. Munin auth endpoint (skip if already copied from repo)
if [ ! -f "$WEBROOT/munin_auth.php" ]; then
    cat > "$WEBROOT/munin_auth.php" <<'EOF'
<?php
require_once __DIR__ . '/includes/auth.php';
error_reporting(0);
tng_session_start();
if (!empty($_SESSION['tng_user']) && tng_current_pw_version()) {
    http_response_code(200);
} else {
    http_response_code(401);
}
EOF
    chown www-data:www-data "$WEBROOT/munin_auth.php" 2>/dev/null || true
fi

# ---- 17. Sudoers for panel actions (group-based, proven from production)
cat > /etc/sudoers.d/trustng-panel <<'EOF'
# TRUST-NG panel — NOPASSWD sudo rules for www-data group
# Service management (broad)
%www-data ALL=(root) NOPASSWD: /usr/sbin/service
%www-data ALL=(root) NOPASSWD: /usr/bin/systemctl

# System utilities
%www-data ALL=(root) NOPASSWD: /sbin/reboot
%www-data ALL=(root) NOPASSWD: /usr/sbin/sysctl
%www-data ALL=(root) NOPASSWD: /usr/sbin/ifconfig
%www-data ALL=(root) NOPASSWD: /usr/sbin/chpasswd
%www-data ALL=(root) NOPASSWD: /usr/bin/kill
%www-data ALL=(root) NOPASSWD: /usr/sbin/sshd -t
%www-data ALL=(root) NOPASSWD: /usr/sbin/nginx -t

# File operations
%www-data ALL=(root) NOPASSWD: /usr/bin/cp
%www-data ALL=(root) NOPASSWD: /usr/bin/rm
%www-data ALL=(root) NOPASSWD: /usr/bin/sed
%www-data ALL=(root) NOPASSWD: /usr/bin/grep
%www-data ALL=(root) NOPASSWD: /usr/bin/wc
%www-data ALL=(root) NOPASSWD: /usr/bin/top
%www-data ALL=(root) NOPASSWD: /usr/bin/nproc
%www-data ALL=(root) NOPASSWD: /usr/bin/paste
%www-data ALL=(root) NOPASSWD: /usr/bin/printf
%www-data ALL=(root) NOPASSWD: /usr/bin/sh
%www-data ALL=(root) NOPASSWD: /usr/bin/tee
%www-data ALL=(root) NOPASSWD: /usr/local/sbin/repairmunin.sh
%www-data ALL=(root) NOPASSWD: /usr/local/sbin/update-blocklist
%www-data ALL=(root) NOPASSWD: /usr/bin/systemctl start update-blocklist
EOF
chmod 440 /etc/sudoers.d/trustng-panel
visudo -cf /etc/sudoers.d/trustng-panel && echo "[OK] sudoers panel installed"

# ---- 17. nftables firewall (DNS port 53 silent-drop)
if command -v nft >/dev/null 2>&1; then
    # Create default client sets if missing (nftables needs them to load)
    [ -f /etc/client_set ] || printf 'elements = { 127.0.0.0/8, 192.168.0.0/16, 172.16.0.0/12, 10.0.0.0/8 }\n' > /etc/client_set
    [ -f /etc/client6_set ] || printf 'elements = { ::1/128 }\n' > /etc/client6_set

    [ -f "$DEPLOY_DIR/conf/nftables.conf" ] && [ ! -f /etc/nftables.conf ] && {
        install -m 0644 "$DEPLOY_DIR/conf/nftables.conf" /etc/nftables.conf
        systemctl enable --now nftables
        echo "[OK] nftables firewall aktif (DNS 53 filtered by client set)"
    }
    [ -f /etc/nftables.conf ] && echo "[OK] nftables already configured"
else
    echo "[WARN] nft not found — DNS firewall (nftables) skipped"
    echo "[INFO] Install manually: apt install nftables"
fi

# ---- 19. Auth SQLite (initial password)
if command -v php >/dev/null && php -m | grep -q pdo_sqlite; then
    install -d -m 0750 /var/lib/trustng-auth
    [ -f /var/lib/trustng-auth/auth.db ] || {
        sqlite3 /var/lib/trustng-auth/auth.db "
CREATE TABLE IF NOT EXISTS users(username TEXT PRIMARY KEY, password_hash TEXT NOT NULL, pw_version INTEGER NOT NULL DEFAULT 1, updated_at INTEGER NOT NULL);
CREATE TABLE IF NOT EXISTS login_attempts(id INTEGER PRIMARY KEY AUTOINCREMENT, ip TEXT NOT NULL, ts INTEGER NOT NULL, ok INTEGER NOT NULL);
CREATE INDEX IF NOT EXISTS idx_attempts_ip_ts ON login_attempts(ip, ts);
CREATE TABLE IF NOT EXISTS settings(k TEXT PRIMARY KEY, v TEXT);"
        P=$(head -c 64 /dev/urandom | base64 | tr -dc 'A-Za-z0-9' | head -c 16)
        HASH=$(php -r 'echo password_hash($argv[1], PASSWORD_DEFAULT);' "$P")
        sqlite3 /var/lib/trustng-auth/auth.db "INSERT INTO users(username,password_hash,pw_version,updated_at) VALUES('admin','$HASH',1,strftime('%s','now'));"
        chown -R www-data:www-data /var/lib/trustng-auth
        echo ""
        echo "========================================"
        echo "PASSWORD PANEL (simpan sekarang): $P"
        echo "========================================"
        echo ""
    }
fi

# ---- 20. Health checks
T=$(head -n1 /etc/unbound/db/trust.txt 2>/dev/null || true)
[ -n "$T" ] && ANS=$(dig +short +time=3 "@127.0.0.1" "$T" A | head -n1) && [ -n "$ANS" ] && echo "[HEALTH] blokir $T -> $ANS"
dig +short +time=3 "@127.0.0.1" example.com A | grep -q . && echo "[HEALTH] resolusi normal OK"

echo ""
echo "========================================"
echo "INSTALASI LENGKAP SELESAI"
echo "========================================"
echo "Dashboard: https://<IP-server>:40443/"
echo "Login: admin / [password di atas]"
echo "Unbound: systemctl status unbound"
echo "Munin:   systemctl status munin-node"
echo "Nginx:   systemctl status nginx"
echo "Panel:   https://<IP>:40443/"