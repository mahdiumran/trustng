# TRUST-NG

DNS filtering & management appliance berbasis Unbound, dengan web management panel berbahasa Indonesia.

## Ringkasan

TRUST-NG adalah solusi DNS sinkhole yang menjalankan Unbound (patched) sebagai resolver utama, dilengkapi:
- **Web Panel** — manajemen DNS via HTTPS (port `40443`)
- **Blocklist Engine** — blacklist/whitelist database dengan CDB lookup
- **Monitoring** — statistik real-time, activity log, system gauge (CPU/RAM/Disk)
- **Munin** — grafik performa jangka panjang
- **nftables** — firewall DNS untuk non-client

Target platform: **Debian 12 x86_64**

## Struktur Repository

```
manage/                      ← project root (git repo)
├── manage/                  ← web panel source (deploy ke /var/www/manage/)
│   ├── *.php                ← backend pages & AJAX endpoints
│   ├── *.js                 ← frontend (jQuery + Canvas charts)
│   ├── *.sh                 ← shell scripts untuk apply config Unbound
│   ├── style.css            ← design system "NEXUS COMMAND"
│   ├── img/                 ← logo & image assets
│   ├── includes/            ← auth.php, auth_guard.php, port_config.php, state_store.php
│   └── *.data, *.dig, ...  ← runtime state (tidak di-commit)
│
├── bin/                     ← patched Unbound binaries
│   ├── unbound
│   ├── unbound-checkconf
│   └── unbound-control
│
├── conf/                    ← configuration templates
│   ├── unbound.conf
│   ├── sources.txt          ← blocklist source URLs
│   └── nftables.conf        ← DNS firewall rules
│
├── scripts/                 ← utility scripts
│   ├── create_domain_cdb.py ← generate CDB blacklist database
│   ├── update-blocklist     ← blocklist update script
│   └── validate-repository.sh
│
├── systemd/                 ← systemd unit files
│   ├── unbound-override.conf
│   ├── update-blocklist.service
│   └── update-blocklist.timer
│
├── installer/               ← installer helper scripts
├── templates/               ← default template files
├── docs/                    ← documentation
│   ├── deployment.md
│   └── release-checklist.md
│
├── install.sh               ← full installer (Debian 12)
├── update.sh                ← incremental deploy ke production
└── unbound-filterdb.patch   ← source code patch untuk Unbound
```

## Requirements

### System Requirements
- **OS**: Debian 12 (Bookworm) x86_64
- **RAM**: minimal 512MB
- **Disk**: minimal 2GB
- **Network**: 1 NIC (akan di-rename ke `eth0` oleh installer)

### Package Dependencies

#### Core (otomatis terinstall oleh `install.sh`)
```
nginx
php8.2-fpm
php-sqlite3
php8.2-cli
dos2unix
curl
dnsutils
python3
systemd
openssl
adduser
passwd
sqlite3
libevent-2.1-7
libevent-core
libevent-dev
nftables
```

#### Monitoring (opsional, install manual)
```
munin
munin-node
lm-sensors
```

#### PHP Extensions yang dibutuhkan
- `pdo_sqlite` — autentikasi (SQLite database)
- `fileinfo` — upload handling

### Runtime Dependencies
- `/usr/bin/s` — system status utility (site-specific)
- `/usr/bin/r` — request list utility (site-specific)

## Instalasi

### Fresh Install
```bash
git clone <repo-url> /opt/trustng
cd /opt/trustng
bash install.sh
```

Installer akan:
1. Install semua package dependencies
2. Setup Unbound user & directories
3. Install patched Unbound binaries ke `/usr/local/sbin/`
4. Deploy web panel ke `/var/www/manage/`
5. Setup nginx HTTPS vhost (port `40443`)
6. Configure systemd services
7. Setup Munin monitoring

### Update Production
```bash
# Local update
./update.sh all

# Remote update via SSH
REMOTE=server-ip ./update.sh all

# Mode: all | binary | config | web | web-changed | blocklist
./update.sh web
```

## Web Panel

### Akses
```
https://<server-ip>:40443/login.php
```

### Fitur
| Halaman | Fungsi |
|---------|--------|
| Dashboard | Statistik real-time (queries, blocked, cache) |
| IP Address | Konfigurasi IP server |
| Lamanlabuh | IP sinkhole landing page |
| Clients | IP range client yang dilayani |
| Options | Toggle SafeSearch, TProxy, DNSSEC, SNMP |
| DB Trust+ | Pencarian database trust/blocklist |
| Blacklist | Editor blacklist manual |
| Whitelist | Editor whitelist |
| Forwarder | DNS forwarder per-domain |
| Hosts File | Custom DNS entries (A/AAAA) |
| DNS Inspector | Live dig query test |
| Graph | Gauge CPU/RAM/Disk real-time |
| Statistics | Metric Unbound (queries, cache, forward) |
| Activity | Log update blocklist |
| Maintenance | Reboot, reload, repair, reset |
| Reset Stats | Flush statistik Unbound |

### Default Ports
| Service | Port |
|---------|------|
| Web Panel (HTTPS) | 40443 |
| DNS (Unbound) | 53 |
| SSH | 22 (default) |

## Konfigurasi Runtime

File-file berikut **tidak di-commit** ke git (mutable state):

| File | Fungsi |
|------|--------|
| `*.data` | Konfigurasi runtime (forwarder, resolver, hosts, dll) |
| `*.dig` | Domain untuk DNS Inspector |
| `*.ip` | IP addresses (clients, aliases) |
| `*.db` | Database (blacklist, whitelist, auth) |
| `whitelist.db` | Daftar domain whitelist |
| `blacklist.local.db` | Blacklist manual |

## Troubleshooting

### Cek status service
```bash
systemctl status nginx php8.2-fpm unbound
```

### Validasi config Unbound
```bash
/usr/local/sbin/unbound-checkconf /etc/unbound/unbound.conf
```

### Cek error PHP
```bash
php -m | grep -E "pdo_sqlite|fileinfo"
journalctl -u php8.2-fpm -n 50
```

### Reset password admin
```bash
/usr/local/sbin/resetpass.sh
```

### Repair Munin
```bash
/usr/local/sbin/repairmunin.sh
```

## Lisensi

Hak Cipta © 2024 Kominfo
