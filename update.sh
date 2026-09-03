#!/bin/bash
# TRUST-NG update.sh — deploy binary/config baru ke server prod
# Mode:
#   ./update.sh all       # binary + config + web + scripts (default)
#   ./update.sh binary    # hanya unbound, checkconf, control
#   ./update.sh config    # hanya unbound.conf (+checkconf gate)
#   ./update.sh web       # web panel files (all files except *.data)
#   ./update.sh web-changed # web panel files (only changed files)
#   ./update.sh blocklist # trigger updater manual di server
#
# Keamanan: config divalidasi unbound-checkconf SEBELUM swap;
# binary lama dibackup; rollback otomatis bila service gagal start.
set -u

DEPLOY_DIR=$(CDPATH='' cd -- "$(dirname -- "$0")" && pwd)
WEBROOT="/var/www/manage"
PANEL_SRC="$DEPLOY_DIR/manage"
MODE=${1:-all}
BACKUP=/var/backups/trustng-update-$(date +%Y%m%dT%H%M%S)
REMOTE=${REMOTE:-}
SSH_OPTS="-o ConnectTimeout=10"

run_remote() {
    if [ -n "$REMOTE" ]; then
        ssh $SSH_OPTS "root@$REMOTE" "$1"
    else
        bash -c "$1"
    fi
}

push_artifact() { # push_artifact <src> <dst>
    if [ -n "$REMOTE" ]; then
        scp $SSH_OPTS "$1" "root@$REMOTE:$2"
    else
        install -m "$( [ "${2##*.}" = "conf" ] && echo 644 || echo 755 )" "$1" "$2"
    fi
}

echo "== TRUST-NG update (mode: $MODE) =="
[ -n "$REMOTE" ] && echo "Target: root@$REMOTE"

run_remote "mkdir -p $BACKUP /usr/local/sbin /usr/local/libexec /etc/unbound/db /etc/systemd/system/unbound.service.d /usr/local/etc/unbound"
run_remote "ln -sf /etc/unbound/unbound.conf /usr/local/etc/unbound/unbound.conf 2>/dev/null || true"

# ---- Interface rename: ens18 → eth0 (panel expects eth0)
run_remote 'setup_interface_rename() {
    RULE_FILE="/etc/udev/rules.d/70-persistent-net.rules"
    IFACES_FILE="/etc/network/interfaces"
    IFACE=$(ip -o link show | awk -F": " "!/lo/{print \$2; exit}")

    [ "$IFACE" = "eth0" ] && echo "[OK] Interface sudah eth0" && return 0

    echo "[INFO] Detected: $IFACE (panel expects eth0)"
    MAC=$(cat /sys/class/net/"$IFACE"/address 2>/dev/null)
    [ -z "$MAC" ] && echo "[WARN] MAC tidak terbaca, skip" && return 0

    # udev rule
    cat > "$RULE_FILE" <<UDEV
# TRUST-NG: rename $IFACE -> eth0 (MAC=$MAC)
SUBSYSTEM=="net", ACTION=="add", DRIVERS=="?*", ATTR{address}=="$MAC", NAME="eth0"
UDEV
    chmod 0644 "$RULE_FILE"
    echo "[OK] udev rule: $RULE_FILE"

    # Update /etc/network/interfaces
    if [ -f "$IFACES_FILE" ]; then
        cp -a "$IFACES_FILE" "${IFACES_FILE}.bak.$(date +%Y%m%d%H%M%S)"
        sed -i "s/\b${IFACE}\b/eth0/g" "$IFACES_FILE"
        echo "[OK] /etc/network/interfaces: $IFACE -> eth0"
    fi

    # Apply immediately
    ip link set "$IFACE" down 2>/dev/null || true
    ip link set "$IFACE" name eth0 2>/dev/null || true
    ip link set eth0 up 2>/dev/null || true

    ip link show eth0 >/dev/null 2>&1 \
        && echo "[OK] Renamed: $IFACE -> eth0" \
        || echo "[WARN] Akan aktif setelah reboot"
}
setup_interface_rename'

do_binary=0; do_config=0; do_blocklist=0; do_web=0; do_web_changed=0
case "$MODE" in
    all)         do_binary=1; do_config=1; do_web=1 ;;
    binary)      do_binary=1 ;;
    config)      do_config=1 ;;
    web)         do_web=1 ;;
    web-changed) do_web_changed=1 ;;
    blocklist)   do_blocklist=1 ;;
    *) echo "mode tidak dikenal: $MODE (pakai all|binary|config|web|web-changed|blocklist)" >&2; exit 64 ;;
esac

if [ "$MODE" = "blocklist" ]; then
    run_remote "/usr/local/sbin/update-blocklist"
    exit $?
fi

if [ "$do_binary" = 1 ]; then
    run_remote "cp -a /usr/local/sbin/unbound /usr/local/sbin/unbound-checkconf /usr/local/sbin/unbound-control $BACKUP/ 2>/dev/null || true"
    for f in unbound unbound-checkconf unbound-control; do
        push_artifact "$DEPLOY_DIR/bin/$f" "/tmp/$f.new"
        run_remote "install -m 0755 /tmp/$f.new /usr/local/sbin/$f && rm -f /tmp/$f.new"
    done
    echo "[OK] binary terpasang (backup di $BACKUP)"
fi

if [ "$do_config" = 1 ]; then
    # validate dulu di target dengan binary yang ada
    push_artifact "$DEPLOY_DIR/conf/unbound.conf" "/etc/unbound/unbound.conf.new"
    if ! run_remote "/usr/local/sbin/unbound-checkconf /etc/unbound/unbound.conf.new"; then
        echo "REJECTED: config baru gagal checkconf — tidak diaktifkan" >&2
        run_remote "rm -f /etc/unbound/unbound.conf.new"
        exit 1
    fi
    run_remote "test -f /etc/unbound/unbound.conf && cp -a /etc/unbound/unbound.conf $BACKUP/ || true"
    run_remote "mv /etc/unbound/unbound.conf.new /etc/unbound/unbound.conf && chown root:root /etc/unbound/unbound.conf"
    echo "[OK] config terpasang (backup di $BACKUP)"
fi

if [ "$do_web" = 1 ]; then
    # backup current web files
    run_remote "mkdir -p $BACKUP/web && cp -a $WEBROOT/* $BACKUP/web/ 2>/dev/null || true"

    # deploy web files (mirror install.sh structure)
    if [ -n "$REMOTE" ]; then
        # copy files preserving structure
        find "$PANEL_SRC" -type f -print0 | while IFS= read -r -d '' source; do
            relative=${source#"$PANEL_SRC/"}
            [ -z "$relative" ] && continue
            # skip excluded files
            case "$relative" in
                *.data|*.data.set|*.db|*.dig|*.ip|*.log|*.new|*.pending|*.bak|*.lock|*.key) continue ;;
                .htpasswd|setup.mulai|recovery.key|*.md|tests_port_config.php|backup-*/*) continue ;;
            esac
            destination="$WEBROOT/$relative"
            run_remote "mkdir -p $(dirname "$destination")"
            scp $SSH_OPTS "$source" "root@$REMOTE:$destination"
        done
    else
        find "$PANEL_SRC" -type f -print0 | while IFS= read -r -d '' source; do
            relative=${source#"$PANEL_SRC/"}
            [ -z "$relative" ] && continue
            case "$relative" in
                *.data|*.data.set|*.db|*.dig|*.ip|*.log|*.new|*.pending|*.bak|*.lock|*.key) continue ;;
                .htpasswd|setup.mulai|recovery.key|*.md|tests_port_config.php|backup-*/*) continue ;;
            esac
            destination="$WEBROOT/$relative"
            install -d -m 0755 "$(dirname "$destination")"
            install -m 0644 "$source" "$destination"
        done
    fi
    run_remote "chown -R root:root $WEBROOT && find $WEBROOT -type f -name '*.sh' -exec chmod 0755 {} +"
    # Data files writable by www-data (panel runtime state)
    run_remote "for f in $WEBROOT/*.data $WEBROOT/*.db $WEBROOT/*.ip $WEBROOT/*.count $WEBROOT/whitelist.db $WEBROOT/blacklist.local.db; do [ -f \"\$f\" ] && chown www-data:www-data \"\$f\" && chmod 0664 \"\$f\"; done 2>/dev/null || true"

    # Fix PHP-FPM PATH if needed (ensure /usr/local/sbin is available)
    run_remote 'PHP_FPM_POOL="/etc/php/8.2/fpm/pool.d/www.conf"
    if [ -f "$PHP_FPM_POOL" ] && grep -q "^;env\[PATH\]" "$PHP_FPM_POOL" 2>/dev/null; then
        sed -i "s|^;env\[PATH\].*|env[PATH] = /usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin|" "$PHP_FPM_POOL"
        systemctl restart php8.2-fpm 2>/dev/null || true
        echo "[OK] PHP-FPM PATH updated"
    fi'

    echo "[OK] web panel terpasang (backup di $BACKUP/web)"
fi

if [ "$do_web_changed" = 1 ]; then
    echo "[INFO] Deploying only changed files..."
    run_remote "mkdir -p $BACKUP/web"

    # deploy only changed files
    if [ -n "$REMOTE" ]; then
        find "$PANEL_SRC" -type f -print0 | while IFS= read -r -d '' source; do
            relative=${source#"$PANEL_SRC/"}
            [ -z "$relative" ] && continue
            case "$relative" in
                *.data|*.data.set|*.db|*.dig|*.ip|*.log|*.new|*.pending|*.bak|*.lock|*.key) continue ;;
                .htpasswd|setup.mulai|recovery.key|*.md|tests_port_config.php|backup-*/*) continue ;;
            esac
            destination="$WEBROOT/$relative"
            # compare checksums
            src_md5=$(md5sum "$source" 2>/dev/null | awk '{print $1}')
            dst_md5=$(ssh $SSH_OPTS "root@$REMOTE" "md5sum '$destination' 2>/dev/null" | awk '{print $1}')
            if [ "$src_md5" != "$dst_md5" ]; then
                echo "[UPDATE] $relative"
                run_remote "mkdir -p $(dirname "$destination")"
                scp $SSH_OPTS "$source" "root@$REMOTE:$destination"
            fi
        done
    else
        find "$PANEL_SRC" -type f -print0 | while IFS= read -r -d '' source; do
            relative=${source#"$PANEL_SRC/"}
            [ -z "$relative" ] && continue
            case "$relative" in
                *.data|*.data.set|*.db|*.dig|*.ip|*.log|*.new|*.pending|*.bak|*.lock|*.key) continue ;;
                .htpasswd|setup.mulai|recovery.key|*.md|tests_port_config.php|backup-*/*) continue ;;
            esac
            destination="$WEBROOT/$relative"
            # compare checksums
            src_md5=$(md5sum "$source" 2>/dev/null | awk '{print $1}')
            dst_md5=$(md5sum "$destination" 2>/dev/null | awk '{print $1}')
            if [ "$src_md5" != "$dst_md5" ]; then
                echo "[UPDATE] $relative"
                # backup before overwrite
                [ -f "$destination" ] && cp -a "$destination" "$BACKUP/web/$relative" 2>/dev/null || true
                install -d -m 0755 "$(dirname "$destination")"
                install -m 0644 "$source" "$destination"
            fi
        done
    fi
    run_remote "chown -R root:root $WEBROOT && find $WEBROOT -type f -name '*.sh' -exec chmod 0755 {} +"
    run_remote "for f in $WEBROOT/*.data $WEBROOT/*.db $WEBROOT/*.ip $WEBROOT/*.count $WEBROOT/whitelist.db $WEBROOT/blacklist.local.db; do [ -f \"\$f\" ] && chown www-data:www-data \"\$f\" && chmod 0664 \"\$f\"; done 2>/dev/null || true"

    # Fix PHP-FPM PATH if needed
    run_remote 'PHP_FPM_POOL="/etc/php/8.2/fpm/pool.d/www.conf"
    if [ -f "$PHP_FPM_POOL" ] && grep -q "^;env\[PATH\]" "$PHP_FPM_POOL" 2>/dev/null; then
        sed -i "s|^;env\[PATH\].*|env[PATH] = /usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin|" "$PHP_FPM_POOL"
        systemctl restart php8.2-fpm 2>/dev/null || true
        echo "[OK] PHP-FPM PATH updated"
    fi'

    echo "[OK] web panel updated (changed files only)"
fi

if [ "$do_binary" = 1 ]; then
    run_remote "systemctl restart unbound"
    sleep 3
    if ! run_remote "systemctl is-active unbound"; then
        echo "SERVICE GAGAL — rollback otomatis..." >&2
        run_remote "cp -a $BACKUP/unbound $BACKUP/unbound-checkconf $BACKUP/unbound-control /usr/local/sbin/ && systemctl restart unbound"
        run_remote "systemctl is-active unbound" && echo "Rollback sukses" || echo "Rollback juga gagal — periksa manual!" >&2
        exit 1
    fi
elif [ "$do_config" = 1 ]; then
    run_remote "systemctl reload unbound || systemctl restart unbound"
fi

run_remote "unbound-control status | head -3"
echo "Update selesai."
