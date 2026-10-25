FROM dunglas/frankenphp:1.13.3-php8.5.12-trixie AS base

RUN install-php-extensions pdo_pgsql sockets \
    && apt-get update \
    && apt-get install -y --no-install-recommends unzip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2.10.3 /usr/bin/composer /usr/local/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_HOME=/tmp/composer

WORKDIR /app

RUN groupadd --gid 10001 app \
    && useradd --uid 10001 --gid app --create-home --shell /usr/sbin/nologin app \
    && mkdir -p /data/caddy /config/caddy \
    && chown -R app:app /data/caddy /config/caddy

FROM base AS dependencies

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --classmap-authoritative

FROM base AS app

COPY --from=dependencies --chown=app:app /app/vendor /app/vendor
COPY --chown=app:app docker/Caddyfile /etc/caddy/Caddyfile
COPY --chown=app:app . /app

RUN composer dump-autoload --no-dev --classmap-authoritative --no-interaction

USER app

CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]

FROM base AS quality-dependencies

COPY composer.json composer.lock ./

RUN composer install --no-interaction --no-progress --prefer-dist

FROM base AS quality

COPY --from=quality-dependencies --chown=app:app /app/vendor /app/vendor

COPY --chown=app:app . /app

USER app
