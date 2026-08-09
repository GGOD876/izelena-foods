#!/usr/bin/env bash
set -euo pipefail

: "${WP_PATH:?Set WP_PATH to the WordPress path}"
: "${SEED_CSV:?Set SEED_CSV to the approved product matrix CSV}"
SEED_REPORT="${SEED_REPORT:-$(dirname "$SEED_CSV")/woocommerce-reconciliation.csv}"

# WP-CLI's eval-file command treats --csv/--report as its own parameters on
# current releases. Pass the reviewed paths through the environment instead;
# woocommerce-seed.php already supports this contract and optional baseline
# reconciliation remains available through IZELENA_SEED_BASELINE.
IZELENA_SEED_CSV="$SEED_CSV" IZELENA_SEED_REPORT="$SEED_REPORT" \
  wp --path="$WP_PATH" --allow-root eval-file /opt/izelena/scripts/woocommerce-seed.php
