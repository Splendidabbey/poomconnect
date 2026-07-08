#!/usr/bin/env bash
set -euo pipefail

DEPLOY_PATH="${DEPLOY_PATH:-/var/www/poomconnect}"
WEB_USER="${WEB_USER:-www-data}"

cd "$DEPLOY_PATH"

mkdir -p uploads/slips uploads/events uploads/logos
touch uploads/slips/.gitkeep uploads/events/.gitkeep uploads/logos/.gitkeep

chmod -R 775 uploads
if id "$WEB_USER" &>/dev/null; then
  chown -R "$WEB_USER:$WEB_USER" uploads 2>/dev/null || true
fi

find . -type f -name '*.php' -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;

if [ -f seed.php ]; then
  echo "WARNING: seed.php still exists on server. Delete after initial setup."
fi

echo "Post-deploy complete: $DEPLOY_PATH"
