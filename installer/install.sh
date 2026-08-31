#!/usr/bin/env bash
# TRUST-NG panel installer entrypoint.
set -eu

SCRIPT_DIR=$(CDPATH='' cd -- "$(dirname -- "$0")" && pwd)
exec "$SCRIPT_DIR/install-panel.sh" "$@"