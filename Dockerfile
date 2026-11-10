FROM php:8.3-fpm-bookworm
RUN apt-get update && apt-get install -y --no-install-recommends git unzip libzip-dev libicu-dev libsqlite3-dev \
    && docker-php-ext-install pdo_mysql pdo_sqlite bcmath intl zip pcntl \
    && pecl install redis-6.1.0 && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /var/www/html
COPY docker/php.ini /usr/local/etc/php/conf.d/app.ini
COPY docker/entrypoint.sh /usr/local/bin/app-entrypoint
RUN chmod +x /usr/local/bin/app-entrypoint
ENTRYPOINT ["app-entrypoint"]
CMD ["php-fpm"]
