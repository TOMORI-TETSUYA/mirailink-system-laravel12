ARG PHP_IMAGE=php:8.4.23-fpm-bookworm
FROM ${PHP_IMAGE}

ARG APP_USER=laravel
ARG APP_UID=1000
ARG APP_GID=1000

ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_HOME=/tmp/composer

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        curl \
        git \
        libicu-dev \
        libonig-dev \
        libxml2-dev \
        libzip-dev \
        unzip \
    && docker-php-ext-install \
        bcmath \
        intl \
        opcache \
        pdo_mysql \
        zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN groupadd --gid "${APP_GID}" "${APP_USER}" \
    && useradd \
        --uid "${APP_UID}" \
        --gid "${APP_GID}" \
        --create-home \
        --shell /bin/bash \
        "${APP_USER}"

WORKDIR /var/www/html/life-Insurance_app

COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-app.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/99-opcache.ini
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh

RUN mkdir -p /var/www/html/life-Insurance_app \
    && chmod +x /usr/local/bin/entrypoint.sh \
    && chown -R "${APP_USER}:${APP_USER}" /var/www/html

USER ${APP_USER}

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
