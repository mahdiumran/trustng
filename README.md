# TRUST-NG

TRUST-NG is a self-hosted DNS control panel for a Debian-based Linux resolver. It combines a PHP web dashboard with Unbound, Trust+ blocklist management, system monitoring, and maintenance controls.

> **Status:** This repository is being prepared for deployment. Run it only on a dedicated test or production host where you understand the privileged system changes made by the installer.

## Repository layout

```text
.
├── manage/       # PHP, JavaScript, CSS, images, and panel helper scripts
├── installer/    # Unbound bundle, systemd units, and deployment documentation
├── templates/    # Safe empty/default runtime files
├── docs/         # Deployment, security, and release guidance
└── scripts/      # Repository validation tools
```

The current checkout is the web package itself. When consumed from the combined distribution, copy `manage/` to the configured webroot (default `/var/www/manage`) and run the relevant installer from `installer/` as root.

## Requirements

- Debian 12 or a compatible Debian-based Linux host
- Root access for installation and service configuration
- PHP 8.x with `pdo_sqlite`, `fileinfo`, and session support
- PHP-FPM and nginx configured for HTTPS
- `curl`, `dig`/`dnsutils`, `python3`, `systemd`, `nftables`, `openssl`, `sudo`, and `dos2unix`
- Unbound and its control socket; the patched Unbound bundle is documented in `installer/`
- Optional monitoring dependencies: Munin, Munin Node, and `lm-sensors`
- The custom TRUST-NG `/usr/bin/s` and `/usr/bin/r` utilities for live statistics/request views

## Install

The installer is deliberately idempotent and must be reviewed before execution:

```sh
sudo -i
cd /path/to/trustdns
less installer/README.md
sh installer/unbound-install.sh
```

Install the panel files into the webroot separately if the host does not already contain them. The installer preserves existing Unbound configuration and initializes missing runtime files; it does not make a backup a substitute for a tested rollback plan.

After installation, open:

```text
https://SERVER-IP:40443/
```

On first boot, the panel creates or detects its setup marker and requires an administrator password. The authentication database is stored outside the webroot at `/var/lib/trustng-auth/auth.db`.

## Runtime state and Git safety

Configuration files are intentionally not tracked in Git. The panel writes mutable state beside the web files at runtime, while authentication data stays outside the webroot. Examples include `*.data`, `*.ip`, `*.dig`, `*.new`, pending port files, `.htpasswd`, `recovery.key`, blacklist databases, owner/contact data, SNMP community values, metrics, and search results.

The root `.gitignore` excludes these files. Before publishing, inspect the staged file list and run:

```sh
sh scripts/validate-repository.sh
```

Never commit a production `.htpasswd`, auth database, private contact data, blocklist database, or generated logs.

## Web application

The panel is served directly; there is no Composer or Node build. Configure nginx/PHP-FPM to use the panel webroot and, for the session guard, set the PHP `auto_prepend_file` to:

```text
/path/to/manage/includes/auth_guard.php
```

The default deployment assumes HTTPS and port `40443`, although the port helpers support validated changes through the panel. Review `AGENTS.md` and `docs/deployment.md` before changing paths or service permissions.

## Tests and checks

```sh
sh scripts/validate-repository.sh
php tests_port_config.php
```

The checks do not replace a clean-host installation test. Exercise first-boot login, dashboard AJAX requests, a configuration save, reload, and Unbound health checks in a disposable VM before release.

## Security notes

The panel intentionally performs privileged operations through narrowly configured `sudo` commands and service helpers. Do not expose it to the public Internet without network controls, HTTPS, a strong unique password, and a reviewed sudoers policy. Treat all installer scripts as root code: read them before running them.

Known operational dependencies and limitations are documented in `AGENTS.md`, including the custom statistics binaries, hard-coded service paths in some legacy helpers, and the requirement that blocklist updates use atomic replacement rather than in-place writes.
