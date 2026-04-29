FROM php:8.3-fpm

# Instalamos las librerías necesarias para que PHP hable con PostgreSQL
RUN apt-get update && apt-get install -y \
    libpq-dev \
    curl \
    && docker-php-ext-install pdo pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

