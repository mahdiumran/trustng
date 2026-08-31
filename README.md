# TRUST-NG

TRUST-NG is a self-hosted DNS control panel for a Debian-based Linux resolver. It combines a PHP web dashboard with Unbound (patched), Trust+ blocklist management, system monitoring (Munin), and maintenance controls.

> **Status:** Production-ready. Works on Debian 12 (Bookworm) and compatible Debian-based systems.

---

## Repository Layout

```text
.
├── manage/                 # Web panel source (nginx serves this directly on dev)
│   ├── includes/           # auth.php, auth_guard.php, port_config.php, state_store.php
│   ├── img/                # logos
│   ├── *.php               # index.php, login.php, manage.php, maintenance.php, etc.
│   ├── *.js                # dashboard.js, menu.js, stats.js, etc.
│   ├── *.sh                # gauge.sh, digtest.sh, setforwarder.sh, etc.
│   └── *.css               # style.css
├── bin/                    # Patched Unbound binaries
│   ├── unbound
│   ├── unbound-checkconf
│   └── unbound-control
├── conf/                   # Unbound configuration
│   ├── unbound.conf
│   ├── sources.txt
│   └── nftables.conf
├── scripts/                # Deployment utilities
│   ├── create_domain_cdb.py
│   ├── update-blocklist
│   └── resetpass.sh
├── systemd/                # Systemd units
│   ├── unbound-override.conf
│   ├── update-blocklist.service
│   └── update-blocklist.timer
├── docs/                   # Documentation
│   ├── deployment.md
│   └── release-checklist.md
├── scripts/                # Repository validation
│   └── validate-repository.sh
├── install.sh              # Full installer (Unbound + Munin + Panel + Nginx)
├── update.sh               # Updater for binary/config
├── unbound-filterdb.patch  # Unbound 1.26.0 patch
├── README.md
└── .gitignore
```

---

## Requirements (install before running `install.sh`)

| Component | Package | Purpose |
|-----------|---------|---------|
| **OS** | Debian 12 (Bookworm) or compatible | Base system |
| **Web** | `nginx`, `php8.2-fpm`, `php8.2-cli`, `php-sqlite3` | Web panel |
| **DNS** | `dnsutils` (`dig`), `curl` | DNS tools / HTTP |
| **System** | `systemd`, `nftables`, `openssl`, `sudo`, `dos2unix`, `python3` | Service management, firewall, crypto |
| **Monitoring** | `munin`, `munin-node` | System graphs |
| **PHP modules** | `pdo_sqlite`, `fileinfo`, `session` | Auth & file handling |

### Auto-installed by `install.sh`
The installer will install the above packages automatically on Debian 12. On other Debian-based distros, ensure these packages exist or install manually.

---

## Quick Install (single command)

```bash
# As root on a fresh Debian 12
sudo -i
cd /path/to/trustdns
bash install.sh
```

The installer is **idempotent** (safe to re-run) and will:
1. Install all system packages (nginx, php8.2-fpm, munin, etc.)
2. Deploy patched Unbound binaries (`/usr/local/sbin/`)
3. Deploy Unbound config (`/etc/unbound/`)
4. Create panel webroot at `/var/www/manage` (copied from `manage/`)
5. Configure nginx vhost on **port 40443** (HTTPS)
6. Configure PHP-FPM with `auth_guard.php` auto-prepend
7. Setup Munin monitoring + nginx `/munin/` location
7. Install systemd units (Unbound drop-in, blocklist timer)
8. Initialize blocklist (`update-blocklist` — downloads ~60MB)
9. Setup sudoers for panel privileged actions
10. Initialize SQLite auth DB with random admin password
11. Build initial Munin graphs

### After install
```
Dashboard:  https://<SERVER-IP>:40443/
Login:      admin / <random password shown at end of install>
```

On first login, you **must** set a new admin password (min 6 chars).

---

## Development Server (this machine)

Nginx is already configured to serve directly from the repo:

```
root /root/opencode/manage/manage/;
```

**Edit files in `manage/` → changes are instantly live.** No copy step needed.

Nginx config (for reference):
```nginx
server {
    listen 40443 ssl;
    listen [::]:40443 ssl;
    server_name _;
    root /root/opencode/manage/manage/;
    ...
}
```

---

## Manual Deployment (without `install.sh`)

If you want to deploy manually on a server:

### 1. Install dependencies
```bash
apt-get update
apt-get install -y nginx php8.2-fpm php-sqlite3 php8.2-cli \
    curl dnsutils python3 systemd nftables openssl sudo dos2unix \
    munin munin-node
```

### 2. Deploy Unbound patched
```bash
# Binaries
install -m 0755 bin/unbound /usr/local/sbin/unbound
install -m 0755 bin/unbound-checkconf /usr/local/sbin/unbound-checkconf
install -m 0755 bin/unbound-control /usr/local/sbin/unbound-control
install -m 0755 scripts/create_domain_cdb.py /usr/local/libexec/create_domain_cdb.py
install -m 0755 scripts/update-blocklist /usr/local/sbin/update-blocklist

# Config (idempotent)
[ -f /etc/unbound/unbound.conf ] || install -m 0644 conf/unbound.conf /etc/unbound/unbound.conf
[ -f /etc/unbound/db/sources.txt ] || install -m 0644 conf/sources.txt /etc/unbound/db/sources.txt
```

### 3. Deploy panel
```bash
WEBROOT="/var/www/manage"
install -d -m 0755 "$WEBROOT"
cp -a manage/. "$WEBROOT/"
find "$WEBROOT" -name '*.sh' -exec chmod 0755 {} +
chown -R root:root "$WEBROOT"
```

### 4. Runtime state (writable by www-data)
```bash
for f in forwarder.data resolver.data hosts.data hosts6.data ipaddr.data ip6addr.data \
    ipalias.data ipalias6.data owner.data clients.ip clients6.ip whitelist.db \
    blacklist.local.db lp1.ip lp2.ip lp3.ip lp4.ip lp5.ip lp6.ip \
    setsafesearch settproxy setdnssec setsnmpd setip6 ip6auto ssh.port ssl.port snmpd.community; do
    [ -e "$WEBROOT/$f" ] || : > "$WEBROOT/$f"
    chown www-data:www-data "$WEBROOT/$f"
    chmod 0664 "$WEBROOT/$f"
done
```

### 5. Auth database
```bash
install -d -m 0750 /var/lib/trustng-auth
chown www-data:www-data /var/lib/trustng-auth
```

### 6. Nginx + PHP-FPM
- Point nginx `root` to `$WEBROOT`
- PHP-FPM `auto_prepend_file = /var/www/manage/includes/auth_guard.php`
- Port: **40443 (HTTPS)**

### 7. Systemd + timers
```bash
cp systemd/unbound-override.conf /etc/systemd/system/unbound.service.d/override.conf
cp systemd/update-blocklist.* /etc/systemd/system/
systemctl daemon-reload
systemctl enable --now unbound update-blocklist.timer
```

### 8. Sudoers (for panel actions)
```bash
cat > /etc/sudoers.d/trustng-panel <<'EOF'
www-data ALL=(root) NOPASSWD: /usr/sbin/sshd -t
www-data ALL=(root) NOPASSWD: /usr/sbin/nginx -t
www-data ALL=(root) NOPASSWD: /usr/bin/systemctl reload ssh
www-data ALL=(root) NOPASSWD: /usr/bin/systemctl reload nginx
www-data ALL=(root) NOPASSWD: /usr/sbin/service unbound restart
www-data ALL=(root) NOPASSWD: /usr/bin/systemctl restart unbound
www-data ALL=(root) NOPASSWD: /var/www/manage/repairmunin.sh
www-data ALL=(root) NOPASSWD: /usr/local/sbin/repairmunin.sh
www-data ALL=(root) NOPASSWD: /usr/local/sbin/update-blocklist
www-data ALL=(root) NOPASSWD: /bin/rm -f /var/lib/trustng-metrics/metrics.db
EOF
chmod 440 /etc/sudoers.d/trustng-panel
visudo -cf /etc/sudoers.d/trustng-panel
```

---

## Verification & Tests

```bash
# Repo validation
sh scripts/validate-repository.sh

# PHP syntax check
php tests_port_config.php
php -l manage/index.php
php -l manage/manage.php

# Service health
systemctl is-active nginx php8.2-fpm unbound munin-node
nginx -t
dig @127.0.0.1 example.com A
```

---

## Security Notes

- **HTTPS only** — panel requires HTTPS (port 40443). Use valid certs in production.
- **Network restrict** — restrict panel to management network/VPN.
- **Strong password** — first-boot forces admin password change.
- **Sudoers are narrow** — only the exact commands the panel needs.
- **Blocklist updates are atomic** — `update-blocklist` writes to `.new` then renames.

---

## Files Not Tracked in Git (runtime state)

The following are generated at runtime and excluded by `.gitignore`:
- `*.data`, `*.ip`, `*.dig`, `*.db`, `*.new`, `*.pending`, `*.lock`
- `.htpasswd`, `recovery.key`, `setup.mulai`
- `gauge.dat`, `top1.dat`, `hasilcari.txt`, `nextjob.sh`
- `auth.db` (outside webroot at `/var/lib/trustng-auth/`)
- Backups: `*.bak`, `backup-*`

---

## License

TRUST-NG DNS Services · Kominfo