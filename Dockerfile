FROM php:8.2-cli

# تثبيت المتطلبات الأساسية
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    zip \
    libzip-dev \
    libpq-dev \
    && docker-php-ext-configure zip \
    && docker-php-ext-install pdo pdo_pgsql pgsql zip

# تثبيت Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# نسخ المشروع
COPY . .

# تثبيت dependencies
RUN composer install --no-dev --prefer-dist --no-interaction

# صلاحيات (اختياري لكن مفيد)
RUN chmod -R 775 storage bootstrap/cache

EXPOSE 10000

# تشغيل Laravel
CMD php artisan config:clear && \
    php artisan config:cache && \
    php artisan migrate --force && \
    php artisan db:seed --force && \
    php artisan serve --host=0.0.0.0 --port=10000