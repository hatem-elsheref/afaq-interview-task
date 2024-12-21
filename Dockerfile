FROM php:8.3-fpm

LABEL authors="Hatem"

WORKDIR /var/www

RUN apt-get update
RUN apt-get upgrade
RUN apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libonig-dev \
    libzip-dev \
    zip \
    unzip \
    curl \
    git

RUN docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY . .

#RUN composer install --optimize-autoloader --no-dev
RUN composer install

RUN chown -R www-data:www-data /var/www
RUN chmod -R 775 /var/www/storage
RUN chmod -R 775 /var/www/bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]
