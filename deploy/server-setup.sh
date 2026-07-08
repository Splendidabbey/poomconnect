#!/usr/bin/env bash
#
# One-time VPS setup for Poom Connect
# Run on Ubuntu 22.04+ as root: bash deploy/server-setup.sh
#
set -euo pipefail

DEPLOY_USER="${DEPLOY_USER:-deploy}"
DEPLOY_PATH="${DEPLOY_PATH:-/var/www/poomconnect}"
DOMAIN="${DOMAIN:-poomconnect.com}"
DB_NAME="${DB_NAME:-poomconnect}"
DB_USER="${DB_USER:-poomconnect_user}"
DB_PASS="${DB_PASS:-$(openssl rand -base64 24)}"

echo "==> Installing packages..."
export DEBIAN_FRONTEND=noninteractive
apt-get update -qq
apt-get install -y -qq nginx mysql-server php-fpm php-mysql php-mbstring php-xml php-gd php-curl php-zip rsync git ufw

echo "==> Creating deploy user..."
if ! id "$DEPLOY_USER" &>/dev/null; then
  useradd -m -s /bin/bash "$DEPLOY_USER"
fi
mkdir -p "$DEPLOY_PATH"
mkdir -p "/home/$DEPLOY_USER/.ssh"
chmod 700 "/home/$DEPLOY_USER/.ssh"

usermod -aG www-data "$DEPLOY_USER" 2>/dev/null || true

echo "==> Setting up MySQL database..."
mysql -e "CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
mysql -e "GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"

echo "==> Creating database.local.php..."
mkdir -p "$DEPLOY_PATH/config"
cat > "$DEPLOY_PATH/config/database.local.php" <<EOF
<?php
declare(strict_types=1);
define('DB_HOST', 'localhost');
define('DB_NAME', '${DB_NAME}');
define('DB_USER', '${DB_USER}');
define('DB_PASS', '${DB_PASS}');
define('DB_CHARSET', 'utf8mb4');
EOF
chmod 640 "$DEPLOY_PATH/config/database.local.php"
chown "$DEPLOY_USER:www-data" "$DEPLOY_PATH/config/database.local.php"

echo "==> Nginx site config..."
cat > "/etc/nginx/sites-available/poomconnect" <<EOF
server {
    listen 80;
    server_name ${DOMAIN} www.${DOMAIN} ${DOMAIN%%.*}.*;

    root ${DEPLOY_PATH};
    index index.php;

    client_max_body_size 10M;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php-fpm.sock;
    }

    location ~* ^/uploads/.*\.php$ {
        deny all;
    }

    location ~ /\. {
        deny all;
    }
}
EOF

ln -sf /etc/nginx/sites-available/poomconnect /etc/nginx/sites-enabled/poomconnect
rm -f /etc/nginx/sites-enabled/default
nginx -t
systemctl reload nginx
systemctl enable nginx php*-fpm mysql

echo "==> Firewall..."
ufw allow OpenSSH
ufw allow 'Nginx Full'
ufw --force enable

chown -R "$DEPLOY_USER:www-data" "$DEPLOY_PATH"
chmod -R 775 "$DEPLOY_PATH/uploads" 2>/dev/null || mkdir -p "$DEPLOY_PATH/uploads" && chmod -R 775 "$DEPLOY_PATH/uploads"

echo ""
echo "=============================================="
echo " VPS setup complete"
echo "=============================================="
echo " Deploy path:  $DEPLOY_PATH"
echo " Deploy user:  $DEPLOY_USER"
echo " DB name:      $DB_NAME"
echo " DB user:      $DB_USER"
echo " DB password:  $DB_PASS"
echo ""
echo " NEXT STEPS:"
echo " 1. Add deploy user's SSH public key to:"
echo "    /home/$DEPLOY_USER/.ssh/authorized_keys"
echo " 2. Import schema:"
echo "    mysql -u $DB_USER -p $DB_NAME < $DEPLOY_PATH/database.sql"
echo " 3. Add GitHub secrets (see DEPLOY.md)"
echo " 4. Push to main branch to trigger deploy"
echo " 5. Optional: certbot --nginx -d $DOMAIN"
echo "=============================================="
