FROM wordpress:6.9-php8.3-apache

RUN apt-get update \
    && DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends curl mariadb-server mariadb-client \
    && curl -fsSL https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar -o /usr/local/bin/wp \
    && chmod +x /usr/local/bin/wp \
    && rm -rf /var/lib/apt/lists/*

COPY wp-content/themes/izelena-foods /usr/src/wordpress/wp-content/themes/izelena-foods
COPY scripts/woocommerce-bootstrap.sh /usr/local/bin/woocommerce-bootstrap.sh
COPY scripts /opt/izelena/scripts
COPY sauce-images /opt/izelena/product-images
COPY wp-content/themes/izelena-foods/assets/heartbeat-of-jamaican-cooking-jerk-marinade.jpg /opt/izelena/product-images/jerk-seasoning.jpg
COPY wp-content/themes/izelena-foods/assets/sweet-heat-smoky-finish-bbq-jerk-sauce.jpg /opt/izelena/product-images/jerk-bbq-sauce.jpg
COPY wp-content/themes/izelena-foods/assets/sweet-island-sunshine-mango-sauce.jpg /opt/izelena/product-images/mango-salsa.jpg
COPY wp-content/themes/izelena-foods/assets/sweet-meets-fire-spicy-mango-sauce.jpg /opt/izelena/product-images/spicy-mango-salsa.jpg
COPY wp-content/themes/izelena-foods/assets/tangy-spicy-unforgettable-sorrel-pepper-sauce.jpg /opt/izelena/product-images/sorrel-pepper-sauce.jpg
COPY wordpress.htaccess /usr/src/wordpress/.htaccess
COPY render-start.sh /usr/local/bin/render-start.sh

RUN chmod +x /usr/local/bin/render-start.sh \
    && chmod +x /usr/local/bin/woocommerce-bootstrap.sh \
    && chmod +x /opt/izelena/scripts/*.sh \
    && mkdir -p /var/lib/mysql /run/mysqld \
    && chown -R mysql:mysql /var/lib/mysql /run/mysqld

ENTRYPOINT ["/usr/local/bin/render-start.sh"]
