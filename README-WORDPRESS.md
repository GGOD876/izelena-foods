# Izelena Foods WordPress theme

This repository keeps the approved React/Vite prototype intact and adds a classic, WooCommerce-compatible WordPress theme at `wp-content/themes/izelena-foods`.

## Local WordPress

1. Copy `.env.example` to `.env` and replace the development passwords if desired.
2. Start WordPress and MariaDB:

   ```bash
   docker compose up -d
   ```

3. Open [http://localhost:8080](http://localhost:8080) (or the `WORDPRESS_PORT` in `.env`) and complete the WordPress installer. Use `db` as the database host if the installer asks.
4. In **Appearance → Themes**, activate **Izelena Foods**. The theme is bind-mounted into the container, so edits are visible after a browser refresh.
5. In **Appearance → Customize**, set the logo, tagline, announcement, contact details, and homepage copy. Set a static front page if you want WordPress to use a page explicitly; the theme also supports the default latest-posts front page.

The database and WordPress uploads are stored in named Docker volumes. To include the optional database browser, run:

```bash
docker compose --profile tools up -d
```

phpMyAdmin is then available at [http://localhost:8081](http://localhost:8081) (or `PHPMYADMIN_PORT`). It is intentionally opt-in and should not be exposed on production hosting.

## WooCommerce

The active theme uses published WooCommerce products, variations, prices, stock, and server-backed cart state when WooCommerce is active. Demo products are available only when the explicit non-production `IZELENA_DEMO_MODE` flag is enabled. Install/configure the client-approved WooCommerce version with the repeatable bootstrap contract:

```bash
WOOCOMMERCE_INSTALL=1 WOOCOMMERCE_VERSION=<client-approved-version> docker compose up -d --build
```

Compose builds the repository `Dockerfile`, uses the custom entrypoint, and passes the external `db` service through to WordPress. The same bootstrap can be run in a prepared container with `WP_PATH=/var/www/html WOOCOMMERCE_VERSION=<version> /usr/local/bin/woocommerce-bootstrap.sh`. It fails on an installed-version mismatch rather than silently upgrading. Use `scripts/woocommerce-seed.example.csv` as the required import shape, then reconcile every product, variation, SKU, price, stock value, image, heat level, tax class, and shipping class against the approved client matrix. Payment, shipping, tax, SMTP, and production hosting remain explicit client configuration gates.

After the client matrix is approved, run `WP_PATH=/var/www/html SEED_CSV=/path/catalogue.csv /opt/izelena/scripts/woocommerce-seed.sh`. The repository seed is `scripts/sales-catalogue-import.csv`; its product photos are bundled at `/opt/izelena/product-images` during the image build, so the import does not depend on database-specific attachment IDs. Set `WOOCOMMERCE_SEED=1` alongside `WOOCOMMERCE_INSTALL=1` to apply that seed during container setup. The import rejects missing or `CLIENT_INPUT` values, creates/updates draft variable products and variations, maps approved categories and global attributes/terms, applies product and variation images, sale price, weight, dimensions, tax, and shipping fields, and writes a field-by-field reconciliation CSV with expected values, persisted values, and `PASS`/mismatch status. For a promotion rehearsal, pass `IZELENA_SEED_BASELINE=/path/previous-reconciliation.csv` so valid positive persisted product/variation IDs are compared to the prior approved run. It exits non-zero when reconciliation mismatches remain. It does not publish products or infer missing commercial data.

Checkout is false by default. It can only be released after the client-approved environment defines all of these `wp-config.php` constants as `true`: `IZELENA_CHECKOUT_RELEASED`, `IZELENA_PAYMENT_READY`, `IZELENA_SHIPPING_READY`, `IZELENA_TAX_READY`, `IZELENA_EMAIL_READY`, and `IZELENA_HOSTING_READY`. The checkout page redirects to the cart until every gate is present.

The theme does not add payment credentials, checkout policies, shipping rules, tax rules, or legal copy. Configure those only after the client confirms the commercial model and installs the appropriate gateway extensions.

## Managed WordPress migration

1. Zip or copy `wp-content/themes/izelena-foods` into the managed host’s `wp-content/themes` directory, or upload the zip from **Appearance → Themes → Add New**.
2. Export/import the WordPress database and copy `wp-content/uploads` separately. Use the host’s migration tool when available so serialized URLs are rewritten safely.
3. Activate the theme, assign menus under **Appearance → Menus**, set the logo and customizer values, and configure WooCommerce products/media.
4. Re-save **Settings → Permalinks**, verify the static front page, test mobile navigation and Scotchimeter links, and configure HTTPS/backups before launch.

The Docker files are for local development only. Never commit `.env`, production database dumps, payment secrets, or customer uploads.
