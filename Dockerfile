FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    git unzip curl zip \
    libzip-dev libpq-dev \
    && docker-php-ext-install pdo_pgsql pgsql zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY . .

RUN composer install --no-dev --prefer-dist --no-interaction

RUN chmod -R 775 storage bootstrap/cache

# مهم: لا تشغل migrate + seed داخل build
# (هذا خطأ شائع على Render)

CMD php-fpm