# TRUST-NG deployment guide

## 1. Prepare a clean host

Supported deployments target Debian 12 x86_64. Run `bash install.sh` as root on a disposable VM first; installation configures network interface naming and can restart networking, SSH, nginx, Unbound, nftables, and SNMP.

## 2. Install the panel

From the repository root, run:

```sh
sudo bash install.sh
```

The installer copies only application source files to `/var/www/manage`, preserves existing mutable state on repeated runs, and creates missing runtime files with the required `www-data` permissions.

The authentication SQLite database belongs outside the webroot at `/var/lib/trustng-auth/auth.db`. The first-boot marker `setup.mulai` forces password setup through `login.php?setup=1`.

## 3. Configure nginx and PHP-FPM

Serve the panel over HTTPS on the fixed TRUST-NG panel port `40443`. SSH port and panel port changes are handled by the server administrator outside the web UI.

Configure PHP-FPM with the panel authentication guard as an auto-prepend file:

```text
auto_prepend_file=/var/www/manage/includes/auth_guard.php
```

Keep `login.php`, `logout.php`, and the nginx `munin_auth.php` subrequest endpoint exempt as implemented by `includes/auth_guard.php`. Restrict the vhost to the management network or a VPN; do not expose the control panel publicly without additional access controls.

## 4. Install the DNS service bundle

Run `bash install.sh`. The installer is intended to run as root, is idempotent for existing configuration and state, validates `unbound.conf`, installs the patched binary bundle, configures blocklist and metrics timers, and performs service health checks.

The installer may create or modify:

- `/etc/unbound/` and its database/config fragments
- systemd drop-ins and timers
- nginx panel/Munin integration
- `/var/lib/trustng-auth/`
- sudoers permissions for narrowly scoped panel actions
- Munin runtime directories and service state

Review the exact version of each artifact before production use. Do not overwrite an existing production Unbound configuration without a backup.

## 5. Verify

Run repository checks before deployment:

```sh
sh scripts/validate-repository.sh
php tests_port_config.php
```

On a test host, verify in order:

1. `php -m` contains `pdo_sqlite` and `fileinfo`.
2. `systemctl is-active nginx php*-fpm unbound` succeeds.
3. `https://SERVER-IP:40443/login.php` loads.
4. First boot accepts a new strong password and removes `setup.mulai`.
5. The dashboard loads and its AJAX endpoints return same-origin JSON.
6. A harmless configuration change creates the expected `*.new` flag.
7. Maintenance → Reload consumes the flag and returns to the panel.
8. `dig @127.0.0.1 example.com A` resolves normally.
9. A known test blocklist entry is blocked only when the blocklist and lamanlabuh configuration are installed.

## 6. Backups and rollback

Before changing a host, back up `/etc/unbound`, nginx configuration, `/var/lib/trustng-auth`, and the panel state files. The Unbound update helper documents its own backup location. Restore configuration and reload services only after running the corresponding syntax checks.
