# Usamos una imagen oficial de PHP con Apache
FROM php:8.2-apache

# Copiamos Composer desde la imagen oficial para poder instalar dependencias durante el build
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER=1

# Instalamos dependencias del sistema y extensiones para PostgreSQL y MongoDB
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libssl-dev \
    git \
    unzip \
    pkg-config \
    && docker-php-ext-install pdo pdo_pgsql pgsql \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb

# Habilitamos el mod_rewrite de Apache (muy útil para URLs amigables o frameworks)
RUN a2enmod rewrite

WORKDIR /var/www/html/

# Copiamos primero la definición de dependencias para aprovechar cache y luego instalamos vendor/
COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-dev --prefer-dist --optimize-autoloader --ignore-platform-reqs

# Copiamos el resto del proyecto
COPY . /var/www/html/

# Damos los permisos correctos al usuario de Apache
RUN chown -R www-data:www-data /var/www/html