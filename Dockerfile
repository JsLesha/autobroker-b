FROM php:8.3-fpm-bookworm

ARG DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y --no-install-recommends \
    curl \
    git \
    unzip \
    libreoffice-core-nogui \
    libreoffice-writer-nogui \
    libicu-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libzip-dev \
    libonig-dev \
    libpq-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_pgsql \
        pgsql \
        pdo_mysql \
        mbstring \
        zip \
        intl \
        bcmath \
        pcntl \
        gd \
        exif \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY docker/php/local.ini /usr/local/etc/php/conf.d/zzz-local.ini

WORKDIR /var/www/html

COPY docker/entrypoint.sh /usr/local/bin/app-entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/app-entrypoint.sh \
    && chmod +x /usr/local/bin/app-entrypoint.sh \
    && chown -R www-data:www-data /var/www/html

ENTRYPOINT ["/usr/local/bin/app-entrypoint.sh"]
CMD ["php-fpm"]
