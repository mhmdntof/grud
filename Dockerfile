FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    unzip \
    git \
    curl \
    libzip-dev \
    zip

RUN docker-php-ext-install pdo pdo_mysql zip

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY . .
ENV COMPOSER_MEMORY_LIMIT=-1
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

RUN php artisan config:clear || true
RUN php artisan cache:clear || true
RUN php artisan optimize:clear || true

RUN cp .env.example .env || true

RUN php artisan key:generate || true

EXPOSE 10000

CMD php artisan serve --host=0.0.0.0 --port=10000