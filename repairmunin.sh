#!/usr/bin/env bash

# ============================================================
# Munin Full Graph Rebuild
#
# WARNING:
# This script intentionally removes Munin RRD history.
# All historical graph data will be lost.
# ============================================================

set -u

LOG_FILE="/var/log/munin/repairmunin.log"
MANAGE_DIR="/var/www/manage"

MUNIN_WWW="/var/cache/munin/www"
MUNIN_LIB="/var/lib/munin"

MUNIN_CRON="/bin/munin-cron"
MUNIN_CRON_OLD="/bin/munin-cron.old"
MUNIN_CRON_NEW="/bin/munin-cron.new"

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*" | tee -a "$LOG_FILE"
}

error() {
    log "ERROR: $*"
}

# ------------------------------------------------------------
# Root check
# ------------------------------------------------------------

if [ "$(id -u)" -ne 0 ]; then
    echo "ERROR: Run this script as root."
    exit 1
fi

mkdir -p "$(dirname "$LOG_FILE")"

log "============================================================"
log "Munin FULL graph rebuild started"
log "============================================================"

# ------------------------------------------------------------
# Detect Munin domain / host
# ------------------------------------------------------------

MUNIN_DOMAIN=""
MUNIN_HOST=""

if [ -f /etc/munin/munin.conf ]; then

    MUNIN_DOMAIN=$(
        grep -m1 '^\[' /etc/munin/munin.conf 2>/dev/null |
        sed 's/^\[//;s/\]//;s/;.*//;s/:.*//'
    )

    MUNIN_HOST=$(
        grep -m1 '^\[' /etc/munin/munin.conf 2>/dev/null |
        sed 's/^\[//;s/\]//;s/.*;//;s/.*://'
    )

fi

# ------------------------------------------------------------
# Fallback
# ------------------------------------------------------------

if [ -z "$MUNIN_DOMAIN" ] || [ "$MUNIN_DOMAIN" = "$MUNIN_HOST" ]; then
    MUNIN_DOMAIN=$(hostname -d 2>/dev/null)

    [ -z "$MUNIN_DOMAIN" ] && MUNIN_DOMAIN="localdomain"
fi

if [ -z "$MUNIN_HOST" ]; then
    MUNIN_HOST=$(hostname -s 2>/dev/null || hostname)
fi

MUNIN_CACHE="${MUNIN_WWW}/${MUNIN_DOMAIN}/${MUNIN_HOST}"
MUNIN_LIB_HOST="${MUNIN_LIB}/${MUNIN_DOMAIN}"

log "Munin domain : $MUNIN_DOMAIN"
log "Munin host   : $MUNIN_HOST"
log "Munin cache  : $MUNIN_CACHE"
log "Munin RRD    : $MUNIN_LIB_HOST"

# ------------------------------------------------------------
# Validate cron files
# ------------------------------------------------------------

if [ ! -f "$MUNIN_CRON_OLD" ]; then
    error "$MUNIN_CRON_OLD not found"
    exit 1
fi

if [ ! -f "$MUNIN_CRON_NEW" ]; then
    error "$MUNIN_CRON_NEW not found"
    exit 1
fi

# ------------------------------------------------------------
# Backup current munin-cron
# ------------------------------------------------------------

if [ -f "$MUNIN_CRON" ]; then
    log "Backing up current munin-cron"

    cp -f "$MUNIN_CRON" "$MUNIN_CRON_OLD.backup" 2>/dev/null || {
        error "Failed to backup current munin-cron"
    }
fi

# ------------------------------------------------------------
# Use repair version of munin-cron
# ------------------------------------------------------------

log "Installing repair munin-cron..."

cp -f "$MUNIN_CRON_OLD" "$MUNIN_CRON" || {
    error "Failed to install $MUNIN_CRON_OLD"
    exit 1
}

chmod +x "$MUNIN_CRON"

# ------------------------------------------------------------
# Remove old graph/cache
# ------------------------------------------------------------

log "Removing old Munin graph cache..."

if [ -d "$MUNIN_CACHE" ]; then
    rm -rf "${MUNIN_CACHE:?}/"* 2>/dev/null || {
        error "Failed to remove cache contents"
    }

    rm -rf "$MUNIN_CACHE" 2>/dev/null || {
        error "Failed to remove cache directory"
    }
fi

# ------------------------------------------------------------
# Remove old RRD history
# ------------------------------------------------------------

log "WARNING: Removing all Munin RRD history..."

if [ -d "$MUNIN_LIB_HOST" ]; then

    find "$MUNIN_LIB_HOST" \
        -type f \
        -name "*.rrd" \
        -delete 2>/dev/null || {
        error "Failed to remove some RRD files"
    }

fi

# ------------------------------------------------------------
# Remove stale HTML temporary files
# ------------------------------------------------------------

log "Removing stale Munin HTML temporary files..."

find "$MUNIN_WWW" \
    -type f \
    -name "*.new" \
    -delete 2>/dev/null || true

# ------------------------------------------------------------
# Fix ownership
# ------------------------------------------------------------

log "Fixing Munin cache ownership..."

mkdir -p "$MUNIN_WWW"

chown -R munin:munin "$MUNIN_WWW" || {
    error "Failed to change ownership of $MUNIN_WWW"
}

chmod 755 "$MUNIN_WWW"

# RRD directory should also be writable by Munin

mkdir -p "$MUNIN_LIB"

chown -R munin:munin "$MUNIN_LIB" || {
    error "Failed to change ownership of $MUNIN_LIB"
}

# ------------------------------------------------------------
# Test Munin cache write permission
# ------------------------------------------------------------

TEST_FILE="${MUNIN_WWW}/.munin-repair-test"

if sudo -u munin touch "$TEST_FILE" 2>/dev/null; then
    rm -f "$TEST_FILE"
    log "Munin cache write permission: OK"
else
    error "Munin cannot write to $MUNIN_WWW"
fi

# ------------------------------------------------------------
# Run Munin rebuild
#
# munin-cron.old performs:
#
# munin-update
# munin-limits
# munin-html
# munin-graph
# index.html.new -> index.html
# ------------------------------------------------------------

log "Running Munin rebuild..."

if sudo -u munin "$MUNIN_CRON" >> "$LOG_FILE" 2>&1; then
    log "Munin rebuild: SUCCESS"
else
    error "Munin rebuild: FAILED"
fi

# ------------------------------------------------------------
# Verify index.html
# ------------------------------------------------------------

if [ -f "$MUNIN_WWW/index.html" ]; then
    log "index.html: OK"
else
    error "index.html NOT FOUND"
fi

# ------------------------------------------------------------
# Verify temporary index
# ------------------------------------------------------------

if [ -f "$MUNIN_WWW/index.html.new" ]; then
    log "WARNING: index.html.new still exists"

    if [ ! -f "$MUNIN_WWW/index.html" ]; then
        log "Promoting index.html.new to index.html"

        cp -f \
            "$MUNIN_WWW/index.html.new" \
            "$MUNIN_WWW/index.html"
    fi
fi

# ------------------------------------------------------------
# Verify RRD
# ------------------------------------------------------------

RRD_COUNT=0

if [ -d "$MUNIN_LIB_HOST" ]; then
    RRD_COUNT=$(
        find "$MUNIN_LIB_HOST" \
            -type f \
            -name "*.rrd" \
            2>/dev/null |
        wc -l
    )
fi

log "RRD files generated: $RRD_COUNT"

if [ "$RRD_COUNT" -eq 0 ]; then
    error "No RRD files generated"
fi

# ------------------------------------------------------------
# Prepare restore job
# ------------------------------------------------------------

mkdir -p "$MANAGE_DIR"

cat > "$MANAGE_DIR/nextjob.sh" <<'EOF'
#!/usr/bin/env bash

sleep 310

if [ -f /bin/munin-cron.new ]; then
    cp -f /bin/munin-cron.new /bin/munin-cron
    chmod +x /bin/munin-cron
fi
EOF

chmod +x "$MANAGE_DIR/nextjob.sh"

log "Created restore job: $MANAGE_DIR/nextjob.sh"

# ------------------------------------------------------------
# Execute delayed restore in background
# ------------------------------------------------------------

nohup "$MANAGE_DIR/nextjob.sh" \
    >/dev/null 2>&1 &

log "munin-cron.new will be restored in 310 seconds"

# ------------------------------------------------------------
# Reload Apache
# ------------------------------------------------------------

log "Testing Apache configuration..."

if apache2ctl configtest >> "$LOG_FILE" 2>&1; then

    if systemctl reload apache2 >> "$LOG_FILE" 2>&1; then
        log "Apache reload: SUCCESS"
    else
        error "Apache reload failed"
    fi

else

    error "Apache configuration test failed"
fi

# ------------------------------------------------------------
# Final summary
# ------------------------------------------------------------

log "============================================================"
log "Munin FULL graph rebuild finished"
log "Domain       : $MUNIN_DOMAIN"
log "Host         : $MUNIN_HOST"
log "RRD count    : $RRD_COUNT"
log "Index        : $MUNIN_WWW/index.html"
log "Cache        : $MUNIN_CACHE"
log "Log          : $LOG_FILE"
log "============================================================"
