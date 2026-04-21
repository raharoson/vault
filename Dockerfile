FROM php:8.4-fpm-alpine AS base

RUN apk add --no-cache \
        postgresql-dev \
        postgresql-client \
        icu-dev \
        libzip-dev \
        oniguruma-dev \
        openssl \
    && docker-php-ext-install \
        pdo_pgsql \
        intl \
        opcache \
        mbstring \
        zip \
    && docker-php-ext-enable opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# ── Dev ────────────────────────────────────────────────────────────────────────
# Sources montées via volume — pas de COPY ici
FROM base AS dev

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]

# ── Prod ───────────────────────────────────────────────────────────────────────
FROM base AS prod

COPY . .
RUN composer install --no-dev --optimize-autoloader --classmap-authoritative \
    && php bin/console cache:warmup --env=prod

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]
