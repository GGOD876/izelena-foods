FROM wordpress:6.8-php8.3-apache

RUN apt-get update \
    && DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends curl mariadb-server mariadb-client \
    && curl -fsSL https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar -o /usr/local/bin/wp \
    && chmod +x /usr/local/bin/wp \
    && rm -rf /var/lib/apt/lists/*

COPY wp-content/themes/izelena-foods /usr/src/wordpress/wp-content/themes/izelena-foods
COPY wordpress.htaccess /usr/src/wordpress/.htaccess
COPY render-start.sh /usr/local/bin/render-start.sh

RUN chmod +x /usr/local/bin/render-start.sh \
    && mkdir -p /var/lib/mysql /run/mysqld \
    && chown -R mysql:mysql /var/lib/mysql /run/mysqld

ENTRYPOINT ["/usr/local/bin/render-start.sh"]
