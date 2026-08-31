# TRUST-NG installer bundle

This directory contains deployment helpers for the panel in the repository root. Run these scripts only after reading them and reviewing the target host.

## Install the web panel

From the repository root on a Debian-based host:

```sh
sudo WEBROOT=/var/www/manage installer/install-panel.sh
```

The script copies PHP/JavaScript/CSS/images and shell helpers but deliberately skips mutable state, credentials, generated output, backups, and repository documentation. Existing application files are updated; existing runtime files are initialized only when absent. Set `WEBROOT=/srv/trustng/manage` when the nginx/PHP-FPM configuration uses another path.

## DNS service bundle

The panel expects Unbound, its control socket, the custom `/usr/bin/s` and `/usr/bin/r` helpers, and optional Munin integration. The patched Unbound binary/update bundle is maintained separately in the deployment project and is not silently copied into this repository. Before release, either add reviewed, redistributable artifacts under this directory or document the exact external release and checksum.

The installer must not overwrite an existing `/etc/unbound/unbound.conf`, nginx vhost, panel state, or authentication database without an explicit backup and migration plan.

## Required host integration

- Configure nginx HTTPS for the panel (default port `40443`).
- Configure PHP-FPM with `auto_prepend_file=/var/www/manage/includes/auth_guard.php`.
- Create `/var/lib/trustng-auth` outside the webroot and make it writable by PHP-FPM.
- Install and configure Unbound before enabling dashboard/service actions.
- Add only narrowly scoped sudoers rules required by the panel.

See `../docs/deployment.md` for the complete checklist and `../docs/release-checklist.md` before publishing a release.
