FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libicu-dev libzip-dev \
    && docker-php-ext-install intl opcache pdo_mysql zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_HOME=/tmp/composer

# Serve public/ and send every request that isn't a real file to Symfony's front controller.
# Listen on 8080 so Apache can run as an unprivileged user.
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri 's/^Listen 80$/Listen 8080/' /etc/apache2/ports.conf \
    && sed -ri 's/<VirtualHost \*:80>/<VirtualHost *:8080>/' /etc/apache2/sites-available/*.conf \
    && printf '<Directory ${APACHE_DOCUMENT_ROOT}>\n    FallbackResource /index.php\n</Directory>\n' \
        > /etc/apache2/conf-available/symfony.conf \
    && a2enconf symfony \
    && a2enmod rewrite

# Run as the host user so files written to the bind mount (var/, vendor/) stay editable on the host
ARG UID=1000
ARG GID=1000
RUN groupmod -o -g ${GID} www-data && usermod -o -u ${UID} -g www-data www-data
USER www-data
