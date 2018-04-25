FROM php:7.2.2-fpm

RUN apt-get update && apt-get upgrade -y libmcrypt-dev mysql-client zip git \
mcrypt && docker-php-ext-install pdo_mysql