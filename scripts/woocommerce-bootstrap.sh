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
if [ "${WOOCOMMERCE_SEED:-1}" = "1" ]; then
  SEED_CSV="${WOOCOMMERCE_SEED_CSV:-/opt/izelena/scripts/sales-catalogue-import.csv}" \
    WP_PATH="$WP_PATH" /opt/izelena/scripts/woocommerce-seed.sh
  if [ "${WOOCOMMERCE_PUBLISH_SEED:-1}" = "1" ]; then
    IFS=',' read -ra publish_slugs <<< "${WOOCOMMERCE_PUBLISH_SLUGS:-}"
    for slug in "${publish_slugs[@]}"; do
      product_id="$(wp post list --post_type=product --name="$slug" --post_status=any --field=ID --format=ids | awk '{print $1}')"
      if [ -z "$product_id" ]; then
        echo "Seeded product not found for publishing: $slug" >&2
        exit 1
      fi
      wp post update "$product_id" --post_status=publish >/dev/null
      wp wc product update "$product_id" --catalog_visibility=visible --user=1 >/dev/null
      variation_ids="$(wp post list --post_type=product_variation --post_parent="$product_id" --post_status=any --field=ID --format=ids)"
      if [ -n "$variation_ids" ]; then wp post update $variation_ids --post_status=publish >/dev/null; fi
    done
    echo "Published approved seeded products: ${publish_slugs[*]}"
  fi
else
  echo "Catalogue seed is available at /opt/izelena/scripts/sales-catalogue-import.csv. Set WOOCOMMERCE_SEED=1 to apply it."
fi
