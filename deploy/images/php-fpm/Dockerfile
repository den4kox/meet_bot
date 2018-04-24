FROM php:7.2.2-fpm

RUN apt-get update && apt-get upgrade -y libmcrypt-dev mysql-client zip git \
mcrypt && docker-php-ext-install pdo_mysql
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

CMD bash -c "cd /var/www/meetbot && composer install"
