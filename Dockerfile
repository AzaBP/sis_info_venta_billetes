# Usamos una imagen oficial de PHP con Apache
FROM php:8.2-apache

# Instalamos dependencias del sistema y extensiones para PostgreSQL y MongoDB
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libssl-dev \
    pkg-config \
    && docker-php-ext-install pdo pdo_pgsql pgsql \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb

# Habilitamos el mod_rewrite de Apache (muy útil para URLs amigables o frameworks)
RUN a2enmod rewrite

# Copiamos todo el código de tu repositorio a la carpeta pública de Apache
COPY . /var/www/html/

# Damos los permisos correctos al usuario de Apache
RUN chown -R www-data:www-data /var/www/html