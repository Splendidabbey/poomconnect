#!/usr/bin/env bash
# Fix ownership so aaPanel PHP (www) and the site FTP user can write.
# Run as root:
#   bash /www/wwwroot/poomconnect.com/deploy/aapanel-permissions.sh

set -euo pipefail

DEPLOY_PATH="${DEPLOY_PATH:-/www/wwwroot/poomconnect.com}"
WEB_USER="${WEB_USER:-www}"
WEB_GROUP="${WEB_GROUP:-www}"

if [ "$(id -u)" -ne 0 ]; then
  echo "Run this as root in aaPanel Terminal."
  exit 1
fi

echo "==> Fixing permissions for ${DEPLOY_PATH}"

mkdir -p \
  "${DEPLOY_PATH}/api/mobile" \
  "${DEPLOY_PATH}/uploads/slips" \
  "${DEPLOY_PATH}/uploads/events" \
  "${DEPLOY_PATH}/uploads/events/og" \
  "${DEPLOY_PATH}/uploads/events/banners" \
  "${DEPLOY_PATH}/uploads/events/gallery" \
  "${DEPLOY_PATH}/uploads/logos"

chown -R "${WEB_USER}:${WEB_GROUP}" "${DEPLOY_PATH}"
find "${DEPLOY_PATH}" -type d -exec chmod 755 {} \;
find "${DEPLOY_PATH}" -type f -exec chmod 644 {} \;
chmod -R 775 "${DEPLOY_PATH}/uploads"

if [ -f "${DEPLOY_PATH}/.env" ]; then
  chmod 640 "${DEPLOY_PATH}/.env"
  chown "${WEB_USER}:${WEB_GROUP}" "${DEPLOY_PATH}/.env"
fi

if [ -f "${DEPLOY_PATH}/config/database.local.php" ]; then
  chmod 640 "${DEPLOY_PATH}/config/database.local.php"
  chown "${WEB_USER}:${WEB_GROUP}" "${DEPLOY_PATH}/config/database.local.php"
fi

echo "==> Done. Site is owned by ${WEB_USER}:${WEB_GROUP}. Re-run the GitHub deploy workflow."
