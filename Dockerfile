FROM php:8.3-fpm

ENV DEBIAN_FRONTEND=noninteractive

WORKDIR /var/www/html

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        nginx \
        gettext-base \
        unzip \
        zip \
        libzip-dev \
    && docker-php-ext-install \
        bcmath \
        pdo_mysql \
        zip \
    && rm -f /etc/nginx/sites-enabled/default \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

COPY . .

RUN composer dump-autoload --optimize \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwx storage bootstrap/cache \
    && ln -sf /dev/stdout /var/log/nginx/access.log \
    && ln -sf /dev/stderr /var/log/nginx/error.log \
    && chmod +x docker/php/entrypoint.sh docker/php/railway-start.sh

EXPOSE 8080

ENTRYPOINT ["docker/php/entrypoint.sh"]
CMD ["docker/php/railway-start.sh"]
