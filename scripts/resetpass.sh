#!/bin/bash
# TRUST-NG resetpass.sh — reset password panel dari terminal
# Usage:
#   trustng-resetpass.sh                 # interaktif (prompt 2x, min 12 char)
#   trustng-resetpass.sh --generate      # password acak 16 char, tampil sekali
#   trustng-resetpass.sh --password 'X'  # set langsung
set -eu
DB=/var/lib/trustng-auth/auth.db

gen_password() { head -c 64 /dev/urandom | base64 | tr -dc 'A-Za-z0-9' | head -c 16; }

set_password() {
    local PASS="$1"
    local LEN=${#PASS}
    if [ "$LEN" -lt 12 ]; then
        echo "ERROR: Password minimal 12 karakter" >&2; exit 1
    fi
    local HASH
    HASH=$(php -r 'echo password_hash($argv[1], PASSWORD_DEFAULT);' "$PASS")
    sqlite3 "$DB" "INSERT INTO users(username,password_hash,pw_version,updated_at)
        VALUES('admin','$HASH',1,strftime('%s','now'))
        ON CONFLICT(username) DO UPDATE SET
        password_hash='$HASH', pw_version=pw_version+1, updated_at=strftime('%s','now');"
    chown www-data:www-data "$DB"
    # sinkron user sistem admin
    printf 'admin:%s\n' "$PASS" | chpasswd 2>/dev/null || true
}

case "${1:-}" in
    --generate)
        P=$(gen_password)
        set_password "$P"
        echo "Password baru (tampilkan sekali, simpan sekarang):"
        echo ""
        echo "    $P"
        echo ""
        echo "Semua sesi aktif telah dikeluarkan."
        ;;
    --password)
        [ -n "${2:-}" ] || { echo "Usage: $0 --password 'PASSWORD'" >&2; exit 1; }
        set_password "$2"
        echo "Password diubah. Semua sesi aktif telah dikeluarkan."
        ;;
    "")
        read -rsp "Password baru (min 12 karakter): " P1; echo
        read -rsp "Ulangi password: " P2; echo
        if [ "$P1" != "$P2" ]; then echo "ERROR: konfirmasi tidak cocok" >&2; exit 1; fi
        set_password "$P1"
        echo "Password diubah. Semua sesi aktif telah dikeluarkan."
        ;;
    *)
        echo "Usage: $0 [--generate|--password 'PASSWORD']" >&2
        exit 64
        ;;
esac
