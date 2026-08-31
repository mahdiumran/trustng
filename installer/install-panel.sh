#!/usr/bin/env bash
# Install the panel source from this repository into /var/www/manage (production webroot).
set -eu

SCRIPT_DIR=$(CDPATH='' cd -- "$(dirname -- "$0")" && pwd)
REPO_ROOT=$(CDPATH='' cd -- "$SCRIPT_DIR/.." && pwd)
WEBROOT=${WEBROOT:-/var/www/manage}
OWNER=${PANEL_OWNER:-root:root}

[ "$(id -u)" -eq 0 ] || { echo 'Run install-panel.sh as root.' >&2; exit 1; }

echo "== TRUST-NG Panel Installer =="
echo "Source: $REPO_ROOT/manage/"
echo "Target: $WEBROOT"

# ---- 1. Create target directory
install -d -m 0755 "$WEBROOT"

# ---- 2. Copy web source files (manage/ folder) to production webroot
while IFS= read -r -d '' source; do
    relative=${source#"$REPO_ROOT/manage/"}
    [ -z "$relative" ] && continue
    # Skip runtime state files and sensitive files (will be initialized later)
    case "$relative" in
        *.data|*.data.set|*.db|*.dig|*.ip|*.log|*.new|*.pending|*.bak|*.lock|*.key) continue ;;
        .htpasswd|setup.mulai|recovery.key|gauge.dat|top1.dat|hasilcari.txt|nextjob.sh) continue ;;
        ip6.loopback|reload.lock) continue ;;
    esac
    destination="$WEBROOT/$relative"
    install -d -m 0755 "$(dirname "$destination")"
    install -m 0644 "$source" "$destination"
done < <(find "$REPO_ROOT/manage" -type f -print0)

# Make shell scripts executable
find "$WEBROOT" -type f -name '*.sh' -exec chmod 0755 {} +
chown -R "$OWNER" "$WEBROOT"

# ---- 3. Initialize runtime state files (only if missing)
for name in forwarder.data resolver.data hosts.data hosts6.data ipaddr.data ip6addr.data ipalias.data ipalias6.data owner.data clients.ip clients6.ip whitelist.db blacklist.local.db lp1.ip lp2.ip lp3.ip lp4.ip lp5.ip lp6.ip setsafesearch settproxy setdnssec setsnmpd setip6 ip6auto ssh.port ssl.port snmpd.community; do
    [ -e "$WEBROOT/$name" ] || : > "$WEBROOT/$name"
    chown www-data:www-data "$WEBROOT/$name"
    chmod 0664 "$WEBROOT/$name"
done

# ---- 4. Auth database directory
install -d -m 0750 /var/lib/trustng-auth
chown www-data:www-data /var/lib/trustng-auth

# ---- 5. Sudoers for www-data (panel actions)
cat > /etc/sudoers.d/trustng-panel <<'EOF'
# TRUST-NG panel: service restart/reload commands for www-data
www-data ALL=(root) NOPASSWD: /usr/sbin/sshd -t
www-data ALL=(root) NOPASSWD: /usr/sbin/nginx -t
www-data ALL=(root) NOPASSWD: /usr/bin/systemctl reload ssh
www-data ALL=(root) NOPASSWD: /usr/bin/systemctl reload nginx
www-data ALL=(root) NOPASSWD: /usr/bin/systemctl is-active --quiet nginx
www-data ALL=(root) NOPASSWD: /bin/cp
www-data ALL=(root) NOPASSWD: /bin/rm -f /etc/ssh/sshd_config.d/99-trustng-port.conf
www-data ALL=(root) NOPASSWD: /usr/sbin/service unbound restart
www-data ALL=(root) NOPASSWD: /usr/bin/systemctl restart unbound
www-data ALL=(root) NOPASSWD: /var/www/manage/repairmunin.sh
www-data ALL=(root) NOPASSWD: /usr/local/sbin/repairmunin.sh
www-data ALL=(root) NOPASSWD: /usr/local/sbin/update-blocklist
www-data ALL=(root) NOPASSWD: /bin/rm -f /var/lib/trustng-metrics/metrics.db
EOF
chmod 440 /etc/sudoers.d/trustng-panel
if command -v visudo >/dev/null 2>&1; then
    visudo -cf /etc/sudoers.d/trustng-panel || { echo "ERROR: invalid sudoers syntax" >&2; exit 1; }
fi
echo "[OK] sudoers panel installed"

# ---- 6. Nginx vhost configuration
NGINX_CONF=/etc/nginx/sites-available/trustng
if [ ! -f "$NGINX_CONF" ]; then
    cat > "$NGINX_CONF" <<NGINX
server {
    listen 40443 ssl;
    listen [::]:40443 ssl;
    server_name _;

    ssl_certificate     /etc/nginx/ssl/trustng.crt;
    ssl_certificate_key /etc/nginx/ssl/trustng.key;
    ssl_protocols       TLSv1.2 TLSv1.3;

    root $WEBROOT;
    index index.php index.html;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php\$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        fastcgi_param HTTPS on;
        fastcgi_param PHP_VALUE "auto_prepend_file=$WEBROOT/includes/auth_guard.php";
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
        fastcgi_param SCRIPT_FILENAME $WEBROOT/munin_auth.php;
        fastcgi_param HTTPS on;
        fastcgi_param PHP_VALUE "auto_prepend_file=$WEBROOT/includes/auth_guard.php";
    }

    location @munin_login {
        return 302 /login.php;
    }

    location ~ /\.(htpasswd|htaccess|data|ip|dig|dat|db|key|count|new|sh)$ {
        deny all;
    }
}
NGINX
    echo "[OK] nginx config created at $NGINX_CONF"
else
    echo "[SKIP] nginx config already exists at $NGINX_CONF"
fi

# Ensure symlink exists
ln -sf "$NGINX_CONF" /etc/nginx/sites-enabled/trustng 2>/dev/null || true

# Validate nginx config
if command -v nginx >/dev/null 2>&1; then
    if nginx -t 2>&1; then
        systemctl reload nginx 2>/dev/null || true
        echo "[OK] nginx reloaded"
    else
        echo "[WARN] nginx config test failed — check $NGINX_CONF"
    fi
fi

echo ""
echo "Panel installed at: $WEBROOT"
echo "Nginx listens on: https://<server-ip>:40443/"
echo "PHP-FPM auto_prepend: $WEBROOT/includes/auth_guard.php"
