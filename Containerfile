FROM php:8.4-apache

# Instalar extensiones y herramientas
RUN apt-get update && apt-get install -y libpq-dev git unzip zip \
    && docker-php-ext-install mysqli pdo pdo_mysql pdo_pgsql pgsql \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Habilitar mod_rewrite y AllowOverride en Apache
RUN a2enmod rewrite \
    && sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Ajustar directorio de trabajo
WORKDIR /var/www/html


