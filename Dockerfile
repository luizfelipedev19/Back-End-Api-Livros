FROM php:8.2-apache

RUN apt-get update && apt-get install -y unzip git curl \
    && docker-php-ext-install pdo pdo_mysql mysqli \
    && a2enmod rewrite

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . /var/www/html

RUN composer install

RUN chown -R www-data:www-data /var/www/html

RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

EXPOSE 80