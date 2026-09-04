FROM php:8.2-fpm-alpine

RUN apk add --no-cache \
    bash \
    curl \
    git \
    unzip \
    libzip-dev \
    oniguruma-dev \
    mysql-client \
    $PHPIZE_DEPS \
    && docker-php-ext-install pdo pdo_mysql mbstring zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY composer.json composer.lock* ./
RUN composer install --no-dev --no-interaction --no-progress --optimize-autoloader --no-scripts || true

COPY . .

RUN composer dump-autoload --optimize \
    && chown -R www-data:www-data /var/www \
    && chmod +x /var/www/docker-entrypoint.sh

USER www-data

EXPOSE 9000

ENTRYPOINT ["/var/www/docker-entrypoint.sh"]
CMD ["php-fpm"]
