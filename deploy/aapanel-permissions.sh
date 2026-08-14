#!/usr/bin/env bash
# Fix upload permissions on aaPanel / typical Linux VPS.
# Run as root on the server:
#   DEPLOY_PATH=/www/wwwroot/poomconnect.com bash deploy/aapanel-permissions.sh

set -euo pipefail

DEPLOY_PATH="${DEPLOY_PATH:-/www/wwwroot/poomconnect.com}"
WEB_USER="${WEB_USER:-www}"
WEB_GROUP="${WEB_GROUP:-www}"

echo "==> Fixing permissions for ${DEPLOY_PATH}"

mkdir -p \
  "${DEPLOY_PATH}/uploads/slips" \
  "${DEPLOY_PATH}/uploads/events" \
  "${DEPLOY_PATH}/uploads/events/og" \
  "${DEPLOY_PATH}/uploads/events/banners" \
  "${DEPLOY_PATH}/uploads/events/gallery" \
  "${DEPLOY_PATH}/uploads/logos"

touch \
  "${DEPLOY_PATH}/uploads/slips/.gitkeep" \
  "${DEPLOY_PATH}/uploads/events/.gitkeep" \
  "${DEPLOY_PATH}/uploads/logos/.gitkeep"

chown -R "${WEB_USER}:${WEB_GROUP}" "${DEPLOY_PATH}/uploads"
chmod -R 775 "${DEPLOY_PATH}/uploads"

echo "==> Done. uploads/events should now be writable by PHP (${WEB_USER})."
