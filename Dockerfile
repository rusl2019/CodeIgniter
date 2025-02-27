FROM php:8.0.30-apache
WORKDIR /var/www/html/

RUN apt-get update && apt-get upgrade -y
RUN apt install -y zlib1g* libpng* libzip-dev zip

RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli
RUN docker-php-ext-install gd     && docker-php-ext-enable gd
RUN docker-php-ext-install zip    && docker-php-ext-enable zip

RUN cp /usr/local/etc/php/php.ini-development /usr/local/etc/php/php.ini

RUN a2enmod rewrite
