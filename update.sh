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
# Permission fix early (stats 0 on new deploy) — idempotent, also for binary-only mode
run_remote '
mkdir -p /etc/tmpfiles.d
echo "d /etc/unbound/run 0755 unbound unbound -" > /etc/tmpfiles.d/trustng-unbound.conf
systemd-tmpfiles --create 2>/dev/null || true
chown unbound:unbound /etc/unbound/run /etc/unbound/db 2>/dev/null || true
chmod 0755 /etc/unbound/run 2>/dev/null || true
adduser www-data unbound 2>/dev/null || true
mkdir -p /var/lib/trustng-auth /var/lib/trustng-metrics
chown -R www-data:www-data /var/lib/trustng-auth 2>/dev/null || true
chown -R www-data:www-data /var/lib/trustng-metrics 2>/dev/null || true
chmod 0750 /var/lib/trustng-auth 2>/dev/null || true
'

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
    run_remote "cp -a /usr/local/sbin/unbound /usr/local/sbin/unbound-checkconf /usr/local/sbin/unbound-control /usr/local/sbin/unbound-anchor /usr/local/sbin/unbound-host $BACKUP/ 2>/dev/null || true"
    for f in unbound unbound-checkconf unbound-control; do
        push_artifact "$DEPLOY_DIR/bin/$f" "/tmp/$f.new"
        run_remote "install -m 0755 /tmp/$f.new /usr/local/sbin/$f && rm -f /tmp/$f.new"
    done
    # Deploy optional binaries if present
    for f in unbound-anchor unbound-host; do
        [ -f "$DEPLOY_DIR/bin/$f" ] && push_artifact "$DEPLOY_DIR/bin/$f" "/tmp/$f.new" && run_remote "install -m 0755 /tmp/$f.new /usr/local/sbin/$f && rm -f /tmp/$f.new"
    done
    # Deploy trustng-metrics-sampler if present
    [ -f "$DEPLOY_DIR/scripts/trustng-metrics-sampler" ] && push_artifact "$DEPLOY_DIR/scripts/trustng-metrics-sampler" "/tmp/trustng-metrics-sampler.new" && run_remote "install -m 0755 /tmp/trustng-metrics-sampler.new /usr/local/sbin/trustng-metrics-sampler && rm -f /tmp/trustng-metrics-sampler.new"
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
    run_remote "chown root:root $WEBROOT; chmod 0755 $WEBROOT; find $WEBROOT -type d -exec chmod 0755 {} + 2>/dev/null; find $WEBROOT -type f -exec chmod 0644 {} + 2>/dev/null; find $WEBROOT -type f -name '*.sh' -exec chmod 0755 {} + 2>/dev/null; chown -R root:root $WEBROOT"
    # Data files writable by www-data (panel runtime state)
    run_remote "for f in $WEBROOT/*.data $WEBROOT/*.db $WEBROOT/*.ip $WEBROOT/*.count $WEBROOT/whitelist.db $WEBROOT/blacklist.local.db $WEBROOT/includes/unbound.php; do [ -f \"\$f\" ] && chown www-data:www-data \"\$f\" 2>/dev/null || true; [ -f \"\$f\" ] && chmod 0644 \"\$f\" 2>/dev/null || true; done; for f in $WEBROOT/*.data $WEBROOT/*.ip; do [ -f \"\$f\" ] && chmod 0664 \"\$f\" 2>/dev/null || true; done"

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
    run_remote "chown root:root $WEBROOT; chmod 0755 $WEBROOT; find $WEBROOT -type d -exec chmod 0755 {} + 2>/dev/null; find $WEBROOT -type f -exec chmod 0644 {} + 2>/dev/null; find $WEBROOT -type f -name '*.sh' -exec chmod 0755 {} + 2>/dev/null; chown -R root:root $WEBROOT"
    run_remote "for f in $WEBROOT/*.data $WEBROOT/*.db $WEBROOT/*.ip $WEBROOT/*.count $WEBROOT/whitelist.db $WEBROOT/blacklist.local.db $WEBROOT/includes/unbound.php; do [ -f \"\$f\" ] && chown www-data:www-data \"\$f\" 2>/dev/null || true; [ -f \"\$f\" ] && chmod 0644 \"\$f\" 2>/dev/null || true; done; for f in $WEBROOT/*.data $WEBROOT/*.ip; do [ -f \"\$f\" ] && chmod 0664 \"\$f\" 2>/dev/null || true; done"

    # Fix PHP-FPM PATH if needed
    run_remote 'PHP_FPM_POOL="/etc/php/8.2/fpm/pool.d/www.conf"
    if [ -f "$PHP_FPM_POOL" ] && grep -q "^;env\[PATH\]" "$PHP_FPM_POOL" 2>/dev/null; then
        sed -i "s|^;env\[PATH\].*|env[PATH] = /usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin|" "$PHP_FPM_POOL"
        systemctl restart php8.2-fpm 2>/dev/null || true
        echo "[OK] PHP-FPM PATH updated"
    fi'

    echo "[OK] web panel updated (changed files only)"
fi

# ---- Trust anchor fix (/var/lib/unbound/root.key missing on fresh deploy)
if [ "$MODE" != "blocklist" ]; then
    run_remote '
    install -d -o unbound -g unbound -m 0755 /var/lib/unbound /etc/unbound/key 2>/dev/null || mkdir -p /var/lib/unbound /etc/unbound/key; chown unbound:unbound /var/lib/unbound /etc/unbound/key 2>/dev/null || true
    if [ ! -s /etc/unbound/key/root.key ]; then
        unbound-anchor -a /etc/unbound/key/root.key 2>/dev/null || /usr/local/sbin/unbound-anchor -a /etc/unbound/key/root.key 2>/dev/null || true
        [ -s /etc/unbound/key/root.key ] || cp -f /var/lib/unbound/root.key /etc/unbound/key/root.key 2>/dev/null || true
        [ -s /etc/unbound/key/root.key ] || cp -f /usr/share/dns/root.key /etc/unbound/key/root.key 2>/dev/null || true
        [ -s /etc/unbound/key/root.key ] || printf ". IN DS 20326 8 2 683D2D0ACB5C2EED8C6783AFA516D0BE8A937AC3504823D56FA7010615E84B1C\n" > /etc/unbound/key/root.key
        chown unbound:unbound /etc/unbound/key/root.key 2>/dev/null || true; chmod 0644 /etc/unbound/key/root.key 2>/dev/null || true
    fi
    if [ ! -s /var/lib/unbound/root.key ]; then
        cp -f /etc/unbound/key/root.key /var/lib/unbound/root.key 2>/dev/null || true
        [ -s /var/lib/unbound/root.key ] || unbound-anchor -a /var/lib/unbound/root.key 2>/dev/null || /usr/local/sbin/unbound-anchor -a /var/lib/unbound/root.key 2>/dev/null || true
        [ -s /var/lib/unbound/root.key ] || cp -f /usr/share/dns/root.key /var/lib/unbound/root.key 2>/dev/null || true
        [ -s /var/lib/unbound/root.key ] || printf ". IN DS 20326 8 2 683D2D0ACB5C2EED8C6783AFA516D0BE8A937AC3504823D56FA7010615E84B1C\n" > /var/lib/unbound/root.key
        chown unbound:unbound /var/lib/unbound/root.key 2>/dev/null || true; chmod 0644 /var/lib/unbound/root.key 2>/dev/null || true
        echo "[OK] /var/lib/unbound/root.key ensured"
    fi
    cmp -s /etc/unbound/key/root.key /var/lib/unbound/root.key 2>/dev/null || cp -f /etc/unbound/key/root.key /var/lib/unbound/root.key 2>/dev/null || true
    '
fi

# ---- Permission fix final (deploy baru stats 0 + munin) — always run unless blocklist-only
if [ "$MODE" != "blocklist" ]; then
    run_remote '
    # symlink & tmpfiles (stats 0 root cause #1 & #2)
    mkdir -p /usr/local/etc/unbound && ln -sf /etc/unbound/unbound.conf /usr/local/etc/unbound/unbound.conf
    mkdir -p /etc/tmpfiles.d
    echo "d /etc/unbound/run 0755 unbound unbound -" > /etc/tmpfiles.d/trustng-unbound.conf
    systemd-tmpfiles --create 2>/dev/null || true
    chown unbound:unbound /etc/unbound/run /etc/unbound/db 2>/dev/null || true
    chmod 0755 /etc/unbound/run 2>/dev/null || true
    # socket perms & group (root cause #3) — panel + munin
    chown unbound:unbound /etc/unbound/run/unbound.sock 2>/dev/null || true
    chmod 0660 /etc/unbound/run/unbound.sock 2>/dev/null || true
    usermod -a -G unbound www-data 2>/dev/null || adduser www-data unbound 2>/dev/null || true
    adduser munin unbound 2>/dev/null || true
    usermod -a -G unbound munin 2>/dev/null || true
    usermod -a -G unbound nobody 2>/dev/null || true
    cat > /etc/munin/plugin-conf.d/zzz-unbound <<MUNINCONF
[unbound*]
user root
env.unbound_conf /etc/unbound/unbound.conf
env.unbound_control /usr/local/sbin/unbound-control
MUNINCONF
    chmod 0644 /etc/munin/plugin-conf.d/zzz-unbound
    systemctl restart munin-node 2>/dev/null || true
    # sudoers fallback for unbound-control (install.sh parity)
    cat > /etc/sudoers.d/trustng-panel <<SUDO
# TRUST-NG panel — NOPASSWD sudo rules for www-data group
%www-data ALL=(root) NOPASSWD: /usr/sbin/service
%www-data ALL=(root) NOPASSWD: /usr/bin/systemctl
%www-data ALL=(root) NOPASSWD: /sbin/reboot
%www-data ALL=(root) NOPASSWD: /usr/sbin/sysctl
%www-data ALL=(root) NOPASSWD: /usr/sbin/ifconfig
%www-data ALL=(root) NOPASSWD: /usr/sbin/chpasswd
%www-data ALL=(root) NOPASSWD: /usr/bin/kill
%www-data ALL=(root) NOPASSWD: /usr/sbin/sshd -t
%www-data ALL=(root) NOPASSWD: /usr/sbin/nginx -t
%www-data ALL=(root) NOPASSWD: /usr/local/sbin/unbound-control
%www-data ALL=(root) NOPASSWD: /usr/sbin/unbound-control
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
SUDO
    chmod 440 /etc/sudoers.d/trustng-panel; visudo -cf /etc/sudoers.d/trustng-panel 2>/dev/null || true
    # PHP-FPM must pick up new groups + PATH
    PHP_FPM_POOL="/etc/php/8.2/fpm/pool.d/www.conf"
    if [ -f "$PHP_FPM_POOL" ] && grep -q "^;env\[PATH\]" "$PHP_FPM_POOL" 2>/dev/null; then
        sed -i "s|^;env\[PATH\].*|env[PATH] = /usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin|" "$PHP_FPM_POOL"
    fi
    systemctl restart php8.2-fpm 2>/dev/null || true
    systemctl reload nginx 2>/dev/null || true
    echo "[OK] permissions & PHP-FPM groups refreshed"
    '
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

run_remote "unbound-control -c /etc/unbound/unbound.conf status 2>&1 | head -3; echo "---"; sudo -u www-data unbound-control -c /etc/unbound/unbound.conf stats_noreset 2>&1 | head -3"
echo "Update selesai."
