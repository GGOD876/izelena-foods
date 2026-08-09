#!/usr/bin/env bash
set -euo pipefail

WP_PATH="${WP_PATH:-/var/www/html}"
: "${WOOCOMMERCE_VERSION:?Set WOOCOMMERCE_VERSION to the client-approved pinned WooCommerce version}"

wp() { command wp --path="$WP_PATH" --allow-root "$@"; }

if wp plugin is-installed woocommerce; then
  installed="$(wp plugin get woocommerce --field=version)"
  if [[ "$installed" != "$WOOCOMMERCE_VERSION" ]]; then
    echo "WooCommerce version mismatch: installed $installed, expected $WOOCOMMERCE_VERSION" >&2
    exit 1
  fi
else
  wp plugin install woocommerce --version="$WOOCOMMERCE_VERSION" --activate
fi

wp plugin activate woocommerce

wp theme activate izelena-foods
wp option update woocommerce_currency "${WOOCOMMERCE_CURRENCY:-JMD}"
wp rewrite flush --hard

echo "WooCommerce $WOOCOMMERCE_VERSION is installed and active."
if [ "${WOOCOMMERCE_SEED:-0}" = "1" ]; then
  SEED_CSV="${WOOCOMMERCE_SEED_CSV:-/opt/izelena/scripts/sales-catalogue-import.csv}" \
    WP_PATH="$WP_PATH" /opt/izelena/scripts/woocommerce-seed.sh
else
  echo "Catalogue seed is available at /opt/izelena/scripts/sales-catalogue-import.csv. Set WOOCOMMERCE_SEED=1 to apply it."
fi
