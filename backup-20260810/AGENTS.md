# AGENTS.md — TRUST-NG DNS Control Panel

> Context document for AI agents working on the TRUST-NG management dashboard.
> Read this **before** modifying any file in this project.

---

## 1. Project Overview

**TRUST-NG** is a self-hosted DNS control panel for managing a Linux-based recursive DNS resolver (Unbound). It is designed for Indonesian ISPs and institutions (Kominfo / government use), providing a web-based admin interface to configure DNS settings, manage blocklists (Trust+), monitor system health, and maintain the server.

- **Stack**: PHP (no framework, procedural), vanilla JavaScript, CSS (no preprocessor), Bash shell scripts
- **Web Server**: Apache/Nginx with PHP, served over HTTPS on port **40443**
- **DNS Engine**: Unbound (configured via files in `/etc/unbound/`)
- **OS**: Linux (Debian-based), uses `systemctl`, `ifconfig`, `nproc`, `/proc` filesystem
- **Auth**: HTTP Basic Auth via `.htpasswd` (APR1-MD5 hashed), user is always `admin`
- **Language**: UI is in Indonesian (Bahasa Indonesia)

---

## 2. Directory Structure

```
manage/
├── index.php              # Entry point — first-login check or dashboard
├── manage.php             # Main dashboard (status, resources, DNS query chart)
├── menu.php               # Sidebar navigation renderer (shared by all pages)
├── style.css              # Global stylesheet (dark/light theme, ~652 lines)
├── dashboard.js           # Dashboard JS (query chart, resource sparklines, live polling)
├── menu.js                # Sidebar toggle, theme toggle, localStorage persistence
├── kunci.js               # Input validation functions (IP, CIDR, domain, cron, numeric)
├── submit.js              # Form validation helper (enable/disable submit button)
├── linear.js              # jQuery line-numbers plugin for textarea display
├── loader.js              # Google Charts loader (for legacy gauge.php)
├── jquery.min.js          # jQuery library
│
├── ├── CONFIG PAGES ──────────────────────────────────────────
├── setip.php              # IP Address config (IPv4/IPv6 static/DHCP, loopback aliases)
├── setclient.php          # ACL recursive clients (CIDR lists for Unbound)
├── options.php            # System options (SSH port, SafeSearch, Tproxy, DNSSEC, SNMP, IPv6)
├── forwarder.php          # DNS forwarder + parent resolver config
├── hosts.php              # Custom DNS hosts file (local-data overrides)
├── setlp.php              # Lamanlabuh (landing page IPs for blocked domains)
├── setdigtest.php         # Dig test domain list configuration
├── setpwd.php             # Password change (htpasswd + system user)
├── setwhite.php           # Whitelist editor (domains exempt from blocking)
│
├── ├── MONITOR PAGES ──────────────────────────────────────────
├── stats.php              # Live Unbound statistics (via /usr/bin/s)
├── reqlist.php            # Live DNS request list (via /usr/bin/r)
├── digtest.php            # DNS resolution test (dig 10 configurable domains)
├── temp.php               # Hardware temperature sensors (lm-sensors)
├── gauge.php / gauge0.php # Legacy Google Gauge charts (uses gauge.sh)
│
├── ├── DB TRUST+ ─────────────────────────────────────────────
├── dbtrust.php            # Trust+ database search (keyword + domain lookup)
├── hasilcari.php          # Search results display (keyword search in trust.txt)
├── hasilcari2.php         # Search results display (domain lookup in blacklist.db)
│
├── ├── MAINTENANCE ───────────────────────────────────────────
├── maintenance.php        # Maintenance hub (repair graph, restart, reset, reload, reboot)
├── reload.php             # Selective service reload (reads *.new flag files)
├── reset.php              # Full factory reset (restores all defaults)
├── reboot.php             # System reboot
├── repairmunin.php        # Munin graph repair (clears cache, restores munin-cron)
│
├── ├── TINY INCLUDES ─────────────────────────────────────────
├── c.php                  # (unused, was port check wrapper)
├── d.php                  # Dig test wrapper (runs digtest.sh)
├── s.php                  # Unbound stats wrapper (runs /usr/bin/s)
├── r.php                  # Request list wrapper (runs /usr/bin/r)
├── t.php                  # Temperature wrapper (runs /usr/bin/sensors)
├── ifstat.php             # Server time display
├── htpasswd.php           # APR1-MD5 password hashing function
│
├── ├── SHELL SCRIPTS ─────────────────────────────────────────
├── setforwarder.sh        # Generate /etc/unbound/forwarder.conf from forwarder.data
├── sethosts.sh            # Generate /etc/unbound/hosts.conf from hosts.data
├── setipalias.sh          # Configure loopback IP aliases (ifconfig lo:N)
├── setresolver.sh         # Generate /etc/unbound/parent.conf from resolver.data
├── setwhitelist.sh        # Generate /etc/unbound/whitelist.conf from whitelist.db
├── gauge.sh               # Collect system metrics → gauge.dat
├── digtest.sh             # Run dig queries on 10 domains → HTML table
├── resetmunin.sh          # Full munin reset + clear all config data files (dynamic hostname detection)
├── repairmunin.sh         # Munin cache clear + cron restore (dynamic hostname detection)
│
├── ├── DATA FILES (state) ────────────────────────────────────
├── ipaddr.data            # Current IPv4 config: ip,netmask,gateway
├── ip6addr.data           # Current IPv6 config: ip,prefix,gateway
├── ipalias.data           # IPv4 loopback aliases (CIDR per line)
├── ip6alias.data          # IPv6 loopback aliases
├── ipalias.data.set       # Working copy (with trailing newline)
├── ip6alias.data.set      # Working copy (with trailing newline)
├── clients.ip             # Allowed recursive client CIDRs (IPv4)
├── clients6.ip            # Allowed recursive client CIDRs (IPv6)
├── forwarder.data         # Domain forwarder entries
├── resolver.data          # Parent resolver IPs (comma-separated)
├── hosts.data             # Custom DNS hosts entries (IPv4)
├── hosts6.data            # Custom DNS hosts entries (IPv6)
├── lp1.ip .. lp6.ip       # Lamanlabuh landing page IPs (3×IPv4 + 3×IPv6)
├── owner.data             # Ownership info (CSV: PT,ASN,kategori,nama,jabatan,tlp,email)
├── whitelist.db           # Whitelisted domains (one per line)
├── whitelist.db.default   # Default whitelist backup
├── d0.dig .. d9.dig       # Domain list for digtest
├── gauge.dat              # System metrics output (JS array format)
├── top1.dat               # Raw `top` output (used by gauge.sh)
├── hasilcari.txt          # Trust+ search results temp file
│
├── ├── FLAG FILES (trigger reload) ───────────────────────────
├── setip.new              # → reload.php: restart networking + sshd + unbound
├── setip6.new             # → reload.php: sysctl + networking + unbound (IPv6)
├── setdns.new             # → reload.php: restart unbound
├── setclient.new          # → reload.php: restart nftables
├── setssh.new             # → reload.php: HUP sshd
├── setalias.new           # → reload.php: run setipalias.sh + restart unbound
├── setsnmpd.new           # → reload.php: enable/disable + start/stop snmpd
│
├── ├── OPTION FLAGS (yes/no or empty) ────────────────────────
├── setsafesearch          # 'yes' = SafeSearch enabled
├── settproxy              # 'yes' = Tproxy enabled
├── setdnssec              # 'yes' = DNSSEC enabled (inverted: 'no' = disabled)
├── setsnmpd               # 'yes' = SNMP enabled
├── setip6                 # 'yes' = IPv6 enabled
├── ip6auto                # 'yes' = IPv6 autoconf/DHCP
├── snmpd.community        # SNMP community string
├── ssh.port               # SSH port number
│
├── ├── AUTH ──────────────────────────────────────────────────
├── .htpasswd              # Apache basic auth (admin:apr1hash)
├── setup.mulai            # Exists on first boot → forces password change
│
├── ├── HTML TEMPLATES ────────────────────────────────────────
├── reload.html            # Shown during reload (loading spinner)
├── reboot.html            # Shown during reboot (loading spinner)
│
└── img/                   # Logos and images
    ├── logo-img/trust-ng.jpg
    └── trustng-small.jpg
```

---

## 3. Application Architecture

### 3.1 Entry Flow

```
Browser → index.php
              │
              ├─ if setup.mulai exists → setpwd.php (force password change)
              │       └─ on success: delete setup.mulai → redirect to index.php
              │
              └─ else → manage.php (main dashboard)
```

### 3.2 Authentication & Access Control

- **HTTP Basic Auth**: `.htpasswd` file with APR1-MD5 hashed password, username is always `admin`
- **Referer check**: Every PHP page validates `$_SERVER['HTTP_REFERER']` against the expected prefix (`https://$http_host:40443/` or `https://$myip:40443/`). If referer doesn't match, the script exits immediately with `exit(0)`.
- **First-login enforcement**: `setup.mulai` flag file forces password change on first boot
- **Password change** (`setpwd.php`): Updates both `.htpasswd` (web auth) and system user `admin` via `chpasswd`

### 3.3 Page Template Pattern

All sub-pages follow a consistent template:

```php
<?php
error_reporting(0);
$myip = $_SERVER['SERVER_ADDR'];
$referer = ...; $allowed_prefix = ...; $allowed_prefix_ip = ...;

// 1. Handle POST (if form submitted)
if($_POST['something']) {
    // validate referer
    // process input, write data files, create *.new flag files
    $index = 'yes'; $back = 'history.go(-2)';
}

// 2. Referer guard (if not POST)
if (strpos($referer, $allowed_prefix) !== 0 && ...) {
    if (!isset($index) || $index !== 'yes') exit(0);
}

// 3. Read current state from data files
$data = file_get_contents('...');

// 4. Render HTML with sidebar
echo '<!DOCTYPE html>...<body class="with-sidebar sidebar-collapsed">';
include_once 'menu.php';
trustng_render_sidebar('thisfile.php');
echo '<div class="page-content">';
echo '<div class="tng-topbar">...toggle button...title...back link...</div>';
// page content with forms
echo '</div></div>';
```

### 3.4 Sidebar Navigation (`menu.php`)

- `trustng_menu_items()` — returns array of all menu items (href, label, icon, key, optional target)
- `trustng_render_sidebar($active)` — renders `<aside>` with grouped nav, highlights active page
- Groups: **Jaringan** (Network), **Konfigurasi** (Config), **Monitor**, **Sistem**
- Theme toggle button (dark/light) embedded in sidebar header
- CSS for sidebar is **inline-embedded** in the PHP function (self-contained)
- `menu.js` is loaded at end of sidebar for toggle/theme functionality

### 3.5 Reload Mechanism (Flag File Pattern)

Config changes don't apply immediately. Instead, pages create **`.new` flag files** which `reload.php` checks and acts upon:

```
User saves form → PHP writes data file + creates *.new flag
User clicks Reload → reload.php checks each *.new file:
  - setip.new      → restart networking, sshd, unbound
  - setip6.new     → sysctl -p, restart networking, unbound (IPv6 toggle)
  - setdns.new     → restart unbound
  - setclient.new  → restart nftables
  - setssh.new     → kill -HUP sshd
  - setalias.new   → run setipalias.sh, restart unbound
  - setsnmpd.new   → enable/disable + start/stop snmpd
After processing, flag file is deleted.
```

### 3.6 Dashboard (`manage.php`)

The main dashboard displays:
- **Topbar**: Logo, version (from `/etc/myversion`), IP pills (IPv4/IPv6/model), LIVE badge
- **Status strip**: Unbound status, Trust+ status, External IP, Uptime
- **Resource gauges**: RAM, CPU, Load, Disk, Iowait (real-time from `/proc/meminfo`, `/proc/stat`, `disk_total_space`)
- **DNS Query chart**: Canvas-based live chart polling `s.php` every few seconds
- **Footer**: Time bar + copyright

**Resource gathering** uses native Linux `/proc` filesystem:
- RAM: `/proc/meminfo` (MemTotal, MemFree, Cached, Buffers)
- CPU: `/proc/stat` with 20ms delta sampling
- Load: `/proc/loadavg` + `nproc`
- Disk: `disk_total_space('/')` / `disk_free_space('/')`

Fallback: `gauge.dat` (generated by `gauge.sh`)

---

## 4. External System Dependencies

### Unbound DNS (`/etc/unbound/`)
| Config File | Generated By | Source Data |
|---|---|---|
| `forwarder.conf` | `setforwarder.sh` | `forwarder.data` |
| `hosts.conf` | `sethosts.sh` | `hosts.data` + `hosts6.data` |
| `parent.conf` | `setresolver.sh` | `resolver.data` |
| `whitelist.conf` | `setwhitelist.sh` | `whitelist.db` |
| `lamanlabuh.conf` | `setlp.php` (direct) | `lp1.ip` .. `lp6.ip` |
| `module-config.conf` | `options.php` (direct) | `setsafesearch`, `setdnssec` flags |
| `rpz.conf` | `options.php` (direct) | `setsafesearch` flag |
| `unbound.conf` | options.php (sed) | `setip6` flag (do-ip6 yes/no) |

### System Files
| Path | Purpose |
|---|---|
| `/etc/network/interfaces` | Network config (written by `setip.php`) |
| `/etc/ssh/port.conf` | SSH port config (written by `options.php`) |
| `/etc/sysctl.conf` | IPv6 enable/disable (written by `options.php`) |
| `/etc/snmp/snmpd.conf` | SNMP config (written by `options.php`) |
| `/etc/tproxy.conf` | Tproxy config (written by `options.php`) |
| `/etc/client_set` | Unbound ACL IPv4 (written by `setclient.php`) |
| `/etc/client6_set` | Unbound ACL IPv6 (written by `setclient.php`) |
| `/etc/myversion` | System version string (read-only) |
| `/etc/mymodel` | Hardware model string (read-only, gates `temp.php` menu) |
| `/run/extip` | Cached external IP (fallback to `curl icanhazip.com`) |
| `/var/run/sshd2.pid` | SSHD PID for `kill -HUP` |
| `/etc/unbound/db/trust.txt` | Trust+ blocklist database |
| `/etc/unbound/db/blacklist.db` | Trust+ CDB blocklist (for domain lookup) |
| `/etc/unbound/db/trust.count` | Trust+ entry count |

### External Binaries
| Binary | Purpose |
|---|---|
| `/usr/bin/s` | Unbound stats utility (custom, outputs `total.num.queries=...`) |
| `/usr/bin/r` | Unbound request list utility (custom) |
| `/usr/bin/sensors` | `lm-sensors` for temperature reading |
| `/var/www/manage/gauge.sh` | System metrics collector |
| `/var/www/manage/digtest.sh` | DNS resolution test runner |
| `/var/www/manage/setipalias.sh` | Loopback alias configurator |
| `dig` | DNS lookup utility (for digtest) |
| `dos2unix` | Line-ending normalizer (called after saving data files) |

### External Services
| URL | Purpose |
|---|---|
| `https://icanhazip.com` | External IP discovery (fallback) |

---

## 5. Form POST Reference

| Page | POST Trigger | Fields | Side Effects |
|---|---|---|---|
| `setip.php` | `$_POST['ipaddr']` | `dhcp`, `ipaddr`, `netmask`, `gateway`, `ip6auto`, `ip6addr`, `ip6prefix`, `ip6gateway` | Writes `/etc/network/interfaces`, `ipaddr.data`, `ip6addr.data`, `setip.new`, `setip6.new` |
| `setip.php` | `$_POST['ipalias']` | `data` (IPv4 CIDR list), `data6` (IPv6 CIDR list) | Writes `ipalias.data`, `ip6alias.data`, `setalias.new` |
| `setclient.php` | `$_POST['data']` | `data` (IPv4 CIDR), `data6` (IPv6 CIDR) | Writes `clients.ip`, `clients6.ip`, `/etc/client_set`, `/etc/client6_set`, `setclient.new` |
| `options.php` | `$_POST['options']` | `ssh`, `safe`, `tproxy`, `dnssec`, `snmpd`, `community`, `ip6` | Writes multiple flag files, `/etc/ssh/port.conf`, `/etc/unbound/module-config.conf`, `/etc/unbound/rpz.conf`, `/etc/snmp/snmpd.conf`, `/etc/tproxy.conf`, sysctl.conf sed edits |
| `forwarder.php` | `$_POST['forward']` | `data` (domain,resolver1,resolver2,resolver3 per line) | Writes `forwarder.data`, runs `setforwarder.sh`, `setdns.new` |
| `forwarder.php` | `$_POST['parentfwd']` | `res1`-`res6` | Writes `resolver.data`, runs `setresolver.sh` (or clears parent.conf), `setdns.new` |
| `hosts.php` | `$_POST['hosts']` | `data` (IPv4 hosts), `data6` (IPv6 hosts) | Writes `hosts.data`, `hosts6.data`, runs `sethosts.sh`, `setdns.new` |
| `setlp.php` | `$_POST['lp1']` | `lp1`-`lp6` | Writes `/etc/unbound/lamanlabuh.conf`, `lp1.ip`-`lp6.ip`, `setdns.new` |
| `setdigtest.php` | `$_POST['setdig']` | `d0`-`d9` (10 domain names) | Writes `d0.dig`-`d9.dig` |
| `setpwd.php` | `$_POST['uid']` | `uid` (hidden, always "admin"), `pass1`, `pass2` | Writes `.htpasswd`, runs `chpasswd`, deletes `setup.mulai` |
| `setwhite.php` | `$_POST['data']` | `data` (whitelist domains) | Writes `whitelist.db`, runs `setwhitelist.sh`, `setdns.new` |
| `dbtrust.php` | `$_POST['carikey']` | `caridb` (search keyword) | Runs `grep` on trust.txt → `hasilcari.txt` → shows `hasilcari.php` |
| `dbtrust.php` | `$_POST['caridom']` | `caridb` (domain search) | Looks up domain in CDB blacklist.db → shows `hasilcari2.php` |

---

## 6. JavaScript Reference

### `dashboard.js`
- **DNS Query Chart**: Canvas-based line chart, polls `s.php` via XHR every ~3s
  - Parses `total.num.queries=NNNN` from response, computes delta → queries/s
  - Maintains 48-point rolling window
  - Draws area+line chart with gradient fill
- **Resource Sparklines**: SVG-based mini charts on each resource card
  - Polls `gauge.dat` via AJAX (parses `['Label', value]` format)
  - Updates `.zabbix-line` and `.zabbix-area` SVG elements
  - State-based coloring (normal < 50%, warning 50-75%, critical > 75%)

### `menu.js`
- **Theme toggle**: Dark/light mode, persisted in `localStorage['trustngTheme']`
- **Sidebar toggle**: Collapsible sidebar, persisted in `localStorage['trustngSidebarCollapsed']`
- **Mobile behavior**: Sidebar becomes fixed overlay on screens ≤768px
- **Event delegation**: Clicks on `.sidebar-toggle`, `#tng-menu-toggle`, `.tng-topbar-toggle` all toggle sidebar
- **Keyboard**: Escape key collapses sidebar
- **Overlay**: Click on `#sidebar-overlay` closes sidebar on mobile

### `kunci.js`
- Input validation functions for forms: `checkNum`, `checkDec`, `checkIP`, `checkIPList`, `checkDomain`, `checkDomainList`, `checkHostList`, `checkPortList`, `checkCron1-5`, `checkSOA`, `checkFWList`, `checkURL`, `checkString`, `checkText`, `checkSlave`
- All work by regex test on `el.value`, rejecting last character if invalid

### `submit.js`
- `checkform()`: Enables/disables submit button based on whether all form fields have values

### `linear.js`
- jQuery plugin for line numbering in `<textarea>` elements (used by whitelist/search result views)

---

## 7. CSS / Styling

### `style.css` (~652 lines)
- **Theme system**: CSS custom properties (`:root` for dark, `:root.light-mode` for light)
- **Dark theme** (default): Deep navy backgrounds (`#0f1623`, `#131d2e`), bright text
- **Light theme**: Slate palette (Slate 50-900 from Tailwind)
- **Key CSS variables**: `--bg`, `--surface`, `--surface-soft`, `--surface-2`, `--ink`, `--ink-soft`, `--muted`, `--line`, `--brand`, `--brand-dark`, `--accent`, `--danger`, `--warn`, `--shadow`
- **Font**: 'Segoe UI', system-ui, -apple-system, sans-serif
- **Icons**: Font Awesome 6.5.2 (CDN, loaded via `@import` in CSS)
- **Layout**: `.page-shell` (flex sidebar + content), `.tng-topbar` (sticky top bar), `.tng-content` (scrollable body)
- **Dashboard-specific CSS**: Embedded in `<style>` block in `manage.php` (sidebar, topbar, status cards, resource gauges, query chart, footer)

### UI Components
- `.tng-status-card` — Status indicator cards (icon + label + value + badge)
- `.tng-res-card` — Resource gauge cards (icon, value, progress bar, sparkline SVG)
- `.tng-query-card` — DNS query chart container
- `.submit-button` — Standard button style
- `.areatxt` / `.areatxt2` — Textarea containers
- `.form__w` — Full-width form input
- `.action-card` / `.action-grid` — Maintenance action buttons
- `.badge-ok` / `.badge-err` / `.badge-warn` / `.badge-unknown` — Status badge colors

---

## 8. Security Patterns

### Referer Validation (all pages)
```php
$referer = $_SERVER['HTTP_REFERER'];
$allowed_prefix = "$proto://$http_host/";
$allowed_prefix_ip = "https://$myip:40443/";
if (strpos($referer, $allowed_prefix) !== 0 && strpos($referer, $allowed_prefix_ip) !== 0) exit(0);
```
**CAUTION**: This is the primary CSRF protection. Do not remove or weaken it.

### Input Sanitization
- `owner.php` (removed): formerly stripped `< > : , ' ? \` ; "` from all fields
- `setclient.php`: Strips `;` from CIDR lists, validates with `isValidCIDR4()` / `isValidCIDR6()`
- `setip.php`: Validates IPs with `filter_var(FILTER_VALIDATE_IP)`, CIDR with `isValidCIDR()`
- `kunci.js`: Client-side input validation (regex-based, blocks invalid chars on keyup)
- `error_reporting(0)`: Suppresses all PHP errors (do not change to `E_ALL` in production)

### Known Security Concerns
- `shell_exec()` / backtick operator used extensively with user input — **potential command injection** if inputs are not properly sanitized
- `dbtrust.php` line 14: `shell_exec("sudo grep '$keyword' ...")` — keyword is not escaped
- `setlp.php`: Uses `filter_var` for IP validation before shell_exec (safe)
- `options.php`: Uses backtick operator for snmpd.conf write (community string not escaped)
- Password stored in `.htpasswd` with APR1-MD5 (weak by modern standards, but required by Apache)

---

## 9. Modification Guidelines

### 9.1 Adding a New Configuration Page

1. Create `newpage.php` following the **Page Template Pattern** (section 3.3)
2. Add menu item in `menu.php` → `trustng_menu_items()` array:
   ```php
   array('newpage.php', 'Page Title', 'fa-icon-name', 'newpage.php'),
   ```
3. Add to the appropriate group in `trustng_render_sidebar()`:
   ```php
   $groups = array(
       'Jaringan' => array(..., 'newpage.php'),
       ...
   );
   ```
4. If the page needs to trigger a service reload, create a `*.new` flag file and add handling in `reload.php`
5. If writing data files, call `dos2unix` after writing to normalize line endings
6. Use `htmlspecialchars()` for all output to prevent XSS

### 9.2 Modifying the Dashboard

- **Adding a resource gauge**: Add to `$resourceItems` array in `manage.php`, add icon mapping in `$resourceIcon`, title in `$resourceTitle`, state thresholds in `$resourceState`
- **Changing the query chart**: Edit `dashboard.js` → `extractStat()` for parsing, `drawChart()` for rendering
- **Adding a status card**: Add to `.tng-status-strip` in `manage.php`

### 9.3 Modifying the Sidebar

- Menu items: `menu.php` → `trustng_menu_items()` 
- Grouping: `menu.php` → `trustng_render_sidebar()` → `$groups` array
- Styling: CSS is **inline-embedded** in the `trustng_render_sidebar()` function (echoed `<style>` block)
- Toggle/theme behavior: `menu.js`

### 9.4 Changing the Theme

- CSS variables: `style.css` → `:root` (dark) and `:root.light-mode` (light)
- Toggle logic: `menu.js` → `toggleTheme()`, `applyTheme()`
- localStorage key: `'trustngTheme'` (values: `'dark'` or `'light'`)

### 9.5 Adding a New Shell Script Integration

1. Create the `.sh` script in `/var/www/manage/`
2. Make it executable: `chmod +x script.sh`
3. Call from PHP: `shell_exec('sh script.sh')` or `shell_exec('./script.sh')`
4. If it generates config files, follow the pattern of reading from `.data` files and writing to `/etc/unbound/`
5. Create a `*.new` flag file to trigger reload if needed

### 9.6 Modifying DNS Configuration

- **Forwarder**: Edit `forwarder.data` (format: `domain,ip1,ip2,ip3` per line) → run `setforwarder.sh`
- **Parent resolver**: Edit `resolver.data` (comma-separated IPs) → run `setresolver.sh`
- **Hosts**: Edit `hosts.data`/`hosts6.data` (format: `ip domain` per line) → run `sethosts.sh`
- **Whitelist**: Edit `whitelist.db` (one domain per line) → run `setwhitelist.sh`
- **Lamanlabuh**: Edit via `setlp.php` form or directly write `lp1.ip`-`lp6.ip` → regenerate `lamanlabuh.conf`
- **After any DNS change**: Create `setdns.new` flag or restart Unbound

---

## 10. Reset / Factory Defaults

`reset.php` performs a full factory reset:
1. Network → DHCP mode, static loopback 192.168.168.168
2. Lamanlabuh → default IPs (103.181.142.196, etc.)
3. Munin → full reset (`resetmunin.sh`)
4. Clients → default ACLs (localhost + private ranges)
5. All config data files → cleared
6. All flag files → cleared
7. Module config → `validator iterator` (DNSSEC on, SafeSearch off)
8. Password → `admin:trust-ng` (default)
9. `setup.mulai` recreated → forces password change on next login
10. IPv6 → disabled
11. SNMP → disabled
12. SSH port → 7857

---

## 11. Key Constants & Defaults

| Constant | Value | Location |
|---|---|---|
| Web port | 40443 | Referer checks throughout |
| Default SSH port | 7857 | `options.php`, `resetmunin.sh` |
| Default password | `trust-ng` | `reset.php` |
| Loopback management IP | 192.168.168.168 | `setip.php` (eth0:0) |
| Default Lamanlabuh LP1 | 103.181.142.196 | `setlp.php` |
| Default Lamanlabuh LP2 | 103.173.75.28 | `setlp.php` |
| Default Lamanlabuh LP3 | 103.155.197.107 | `setlp.php` |
| Dashboard refresh interval | ~3s | `dashboard.js` (implied by polling) |
| Max chart points | 48 | `dashboard.js` |
| Resource thresholds | normal <50%, warning 50-75%, critical >75% | `manage.php` |
| Temp menu gate | model === 'BENGKEL x86 128G' | `menu.php` |

---

## 12. Common Pitfalls

1. **Never remove `error_reporting(0)`** — PHP warnings/notices break the HTML output and can leak paths
2. **Always validate referer** before processing POST data — this is the CSRF protection
3. **Always call `dos2unix`** after writing data files — Windows line endings break shell scripts
4. **Create `*.new` flag files** when config changes need service restart — don't restart services directly from config pages
5. **`setdnssec` flag is inverted** — value `'no'` means DNSSEC disabled, empty/'yes' means enabled
6. **`gauge.sh` path is hardcoded** to `/var/www/manage/` — if the web root changes, update all shell script paths
7. **`/usr/bin/s` and `/usr/bin/r` are custom binaries** — not standard system utilities, don't assume they exist on other systems
8. **`setup.mulai` must be deleted** after password change, otherwise user is stuck in password-change loop
9. **Sidebar CSS is embedded in `menu.php`** — not in `style.css`, changes to sidebar styling must be made in both places
10. **The `.tng-menu-toggle` button is in `manage.php` topbar only** — sub-pages have their own `.tng-topbar-toggle` button; both are handled by `menu.js` event delegation
11. **IPv6 config is conditional** — `setip6` flag must be `'yes'` for IPv6 fields to appear in `setip.php`, `setclient.php`, `hosts.php`, `forwarder.php`, `setlp.php`
12. **`options.php` uses backtick operator** for snmpd.conf — potential injection via community string (should sanitize)
13. **`dbtrust.php` keyword search** passes `$keyword` directly to `shell_exec("sudo grep '$keyword' ...")` — potential injection
14. **`manage.php.bak`** is a backup of an older version of manage.php — do not edit, it is not used
15. **`restartunbound.php` is empty** (0 bytes) — the maintenance page links to it but it does nothing; needs implementation if used
16. **Munin hostname must be dynamic** — `repairmunin.sh` and `resetmunin.sh` detect the Munin domain/host from `/etc/munin/munin.conf` (falling back to `hostname -d` / `hostname -s`). Previously hardcoded `localdomain/localhost.localdomain` which broke on servers with different hostnames. When deploying to a new server, ensure `/etc/munin/munin.conf` has the correct `[domain;host]` section header.
17. **`owner.php` and `cekport.php` have been removed** from the menu — the files still exist on disk but are no longer linked or accessible via the sidebar. The `owner.data` file is still written by `reset.php` for backward compatibility.

---

## 13. File Quick Reference (by role)

### Pages that modify Unbound config
- `options.php` → `module-config.conf`, `rpz.conf`, `unbound.conf` (sed)
- `forwarder.php` → `forwarder.conf` (via `setforwarder.sh`), `parent.conf` (via `setresolver.sh`)
- `hosts.php` → `hosts.conf` (via `sethosts.sh`)
- `setlp.php` → `lamanlabuh.conf` (direct write)
- `setwhite.php` → `whitelist.conf` (via `setwhitelist.sh`)
- `setclient.php` → `/etc/client_set`, `/etc/client6_set` (direct write)
- `setip.php` → `/etc/network/interfaces` (direct write)

### Pages that are read-only (monitoring)
- `stats.php`, `reqlist.php`, `digtest.php`, `temp.php`

### Pages that require `maintenance.php` as referer
- `reload.php`, `reset.php`, `reboot.php`, `repairmunin.php`

### Files safe to delete (regenerated)
- `*.new` (flag files, consumed by reload.php)
- `gauge.dat` (regenerated by `gauge.sh`)
- `top1.dat` (regenerated by `gauge.sh`)
- `hasilcari.txt` (regenerated by `dbtrust.php` search)
- `ipalias.data.set`, `ip6alias.data.set` (working copies for `setipalias.sh`)
- `ip6.loopback` (temp file for `setipalias.sh`)
- `nextjob.sh` (temp file for munin repair)

---

## 14. Build / Deploy Notes

- No build step — PHP files are served directly by the web server
- No `package.json`, no `composer.json`, no dependency management
- `jquery.min.js` is vendored locally (not CDN)
- `loader.js` is Google Charts loader, vendored locally
- Font Awesome is loaded via CDN `@import` in `style.css`
- The project root is assumed to be `/var/www/manage/` (hardcoded in shell scripts)
- HTTPS on port 40443 is expected by all referer checks

---

*Last updated: 2024 · TRUST-NG DNS Services · Kominfo*
