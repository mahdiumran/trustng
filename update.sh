#!/bin/bash
# TRUST-NG update.sh — deploy binary/config baru ke server prod
# Mode:
#   ./update.sh all       # binary + config + scripts (default)
#   ./update.sh binary    # hanya unbound, checkconf, control
#   ./update.sh config    # hanya unbound.conf (+checkconf gate)
#   ./update.sh blocklist # trigger updater manual di server
#
# Keamanan: config divalidasi unbound-checkconf SEBELUM swap;
# binary lama dibackup; rollback otomatis bila service gagal start.
set -u

DEPLOY_DIR=$(CDPATH='' cd -- "$(dirname -- "$0")" && pwd)
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

run_remote "mkdir -p $BACKUP /usr/local/sbin /usr/local/libexec /etc/unbound/db /etc/systemd/system/unbound.service.d"

do_binary=0; do_config=0; do_blocklist=0
case "$MODE" in
    all)       do_binary=1; do_config=1 ;;
    binary)    do_binary=1 ;;
    config)    do_config=1 ;;
    blocklist) do_blocklist=1 ;;
    *) echo "mode tidak dikenal: $MODE (pakai all|binary|config|blocklist)" >&2; exit 64 ;;
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
