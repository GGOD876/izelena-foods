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

The homepage shows editable WooCommerce products when WooCommerce is active and published products exist. Without WooCommerce, it renders the supplied demo product data so the design remains reviewable. Install and configure WooCommerce from **Plugins → Add New**, then create products, set featured images, and optionally set each product’s **Scotchimeter heat level** in the product editor.

The theme does not add payment credentials, checkout policies, shipping rules, tax rules, or legal copy. Configure those only after the client confirms the commercial model and installs the appropriate gateway extensions.

## Managed WordPress migration

1. Zip or copy `wp-content/themes/izelena-foods` into the managed host’s `wp-content/themes` directory, or upload the zip from **Appearance → Themes → Add New**.
2. Export/import the WordPress database and copy `wp-content/uploads` separately. Use the host’s migration tool when available so serialized URLs are rewritten safely.
3. Activate the theme, assign menus under **Appearance → Menus**, set the logo and customizer values, and configure WooCommerce products/media.
4. Re-save **Settings → Permalinks**, verify the static front page, test mobile navigation and Scotchimeter links, and configure HTTPS/backups before launch.

The Docker files are for local development only. Never commit `.env`, production database dumps, payment secrets, or customer uploads.
