#!/bin/bash
set -e

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
APP_ROOT="$(dirname "$(dirname "$SCRIPT_DIR")")"

CRON_SOURCE="${APP_ROOT}/cron"
CRON_TARGET="/etc/cron.d"

echo ">>> Installing cronjobs from $CRON_SOURCE ..."

if [ ! -d "$CRON_SOURCE" ]; then
    echo "No cron directory found at $CRON_SOURCE"
    exit 0
fi

for file in "$CRON_SOURCE"/*; do
    [ -f "$file" ] || continue

    target="$CRON_TARGET/$(basename "$file")"

    cp "$file" "$target"
    chown root:root "$target"
    chmod 0644 "$target"

    echo "Installed cronjob: $(basename "$file")"
done
