FROM php:8.3-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends libzip-dev unzip git \
    && docker-php-ext-install sockets bcmath zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
