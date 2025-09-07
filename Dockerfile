FROM php:8.4-cli-alpine AS builder

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY composer.json composer.lock* ./
RUN composer install --no-dev --optimize-autoloader

FROM php:8.4-cli

WORKDIR /var/www

COPY --from=builder /var/www/vendor ./vendor
COPY . .

EXPOSE 8080

CMD ["php", "-S", "0.0.0.0:8080", "-t", "public"]