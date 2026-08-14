#!/usr/bin/env bash
set -euo pipefail

DEPLOY_PATH="${DEPLOY_PATH:-/www/wwwroot/poomconnect.com}"
WEB_USER="${WEB_USER:-www}"
WEB_GROUP="${WEB_GROUP:-www}"

cd "$DEPLOY_PATH"

mkdir -p uploads/slips uploads/events uploads/events/og uploads/events/banners uploads/events/gallery uploads/logos
touch uploads/slips/.gitkeep uploads/events/.gitkeep uploads/logos/.gitkeep

chmod -R 775 uploads
if id "$WEB_USER" &>/dev/null; then
  chown -R "$WEB_USER:$WEB_GROUP" uploads 2>/dev/null || true
fi

find . -type f -name '*.php' -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;

if [ -f seed.php ]; then
  echo "WARNING: seed.php still exists on server. Delete after initial setup."
fi

echo "Post-deploy complete: $DEPLOY_PATH"
