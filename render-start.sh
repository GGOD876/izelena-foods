#!/bin/bash
set -e

DB_NAME="${WORDPRESS_DB_NAME:-wordpress}"
DB_USER="${WORDPRESS_DB_USER:-wordpress}"
DB_PASSWORD="${WORDPRESS_DB_PASSWORD:-change-me}"
ROOT_PASSWORD="${MARIADB_ROOT_PASSWORD:-change-me-root}"
PORT_NUMBER="${PORT:-80}"
SITE_URL="${WORDPRESS_SITE_URL:-http://localhost:${PORT_NUMBER}}"
SITE_TITLE="${WORDPRESS_SITE_TITLE:-Izelena Foods}"
ADMIN_USER="${WORDPRESS_ADMIN_USER:-izelena-admin}"
ADMIN_PASSWORD="${WORDPRESS_ADMIN_PASSWORD:-}"
ADMIN_EMAIL="${WORDPRESS_ADMIN_EMAIL:-info@izelenafoods.com}"

if [ -z "${WORDPRESS_DB_HOST:-}" ] || [ "$WORDPRESS_DB_HOST" = "127.0.0.1:3306" ]; then
  if [ ! -d /var/lib/mysql/mysql ]; then
    mariadb-install-db --user=mysql --datadir=/var/lib/mysql >/dev/null
  fi
  mysqld_safe --datadir=/var/lib/mysql --bind-address=127.0.0.1 >/tmp/mariadb.log 2>&1 &
  for attempt in $(seq 1 30); do
    if mysqladmin ping -uroot --silent >/dev/null 2>&1; then break; fi
    sleep 1
  done
  mysql -uroot <<SQL
ALTER USER 'root'@'localhost' IDENTIFIED BY '${ROOT_PASSWORD}';
CREATE DATABASE IF NOT EXISTS ${DB_NAME};
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';
ALTER USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';
GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
SQL
  export WORDPRESS_DB_HOST="127.0.0.1:3306"
  export WORDPRESS_DB_NAME="$DB_NAME"
  export WORDPRESS_DB_USER="$DB_USER"
  export WORDPRESS_DB_PASSWORD="$DB_PASSWORD"
fi

sed -ri "s/^Listen 80$/Listen ${PORT_NUMBER}/" /etc/apache2/ports.conf
find /etc/apache2/sites-enabled -type f -exec sed -ri "s/<VirtualHost \*:80>/<VirtualHost *:${PORT_NUMBER}>/" {} +

/usr/local/bin/docker-entrypoint.sh apache2-foreground &
APACHE_PID=$!

for attempt in $(seq 1 60); do
  if [ -f /var/www/html/wp-config.php ]; then break; fi
  sleep 1
done

if [ -n "$ADMIN_PASSWORD" ] && ! wp core is-installed --allow-root --path=/var/www/html >/dev/null 2>&1; then
  wp core install \
    --url="$SITE_URL" \
    --title="$SITE_TITLE" \
    --admin_user="$ADMIN_USER" \
    --admin_password="$ADMIN_PASSWORD" \
    --admin_email="$ADMIN_EMAIL" \
    --skip-email \
    --allow-root \
    --path=/var/www/html
fi

wordpress_ready=0
for attempt in $(seq 1 15); do
  if wp core is-installed --allow-root --path=/var/www/html >/dev/null 2>&1; then
    wordpress_ready=1
    break
  fi
  sleep 2
done
if [ "$wordpress_ready" != "1" ]; then
  echo "WordPress was not ready for WooCommerce bootstrap after 30 seconds." >&2
  exit 1
fi

wp theme activate izelena-foods --allow-root --path=/var/www/html >/dev/null 2>&1 || true
WOOCOMMERCE_VERSION="${WOOCOMMERCE_VERSION:-11.0.0}" \
  WOOCOMMERCE_SEED=1 \
  WOOCOMMERCE_PUBLISH_SEED=1 \
  WOOCOMMERCE_PUBLISH_SLUGS="${WOOCOMMERCE_PUBLISH_SLUGS:-jerk-seasoning,jerk-bbq-sauce,mango-salsa,spicy-mango-salsa,sorrel-pepper-sauce}" \
  WP_PATH=/var/www/html WOOCOMMERCE_CURRENCY="${WOOCOMMERCE_CURRENCY:-JMD}" /usr/local/bin/woocommerce-bootstrap.sh

wait "$APACHE_PID"
