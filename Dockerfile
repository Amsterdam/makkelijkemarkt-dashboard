FROM php:7.0.15-fpm-alpine

ARG DEBIAN_FRONTEND=noninteractive

EXPOSE 80

RUN apk update && apk upgrade

RUN apk add bash

RUN apk add nginx && mkdir /run/nginx

RUN apk add postgresql-dev bzip2-dev libpng-dev libintl gettext gettext-dev gmp gmp-dev icu-dev libmcrypt-dev libxml2-dev libxslt-dev && \
    docker-php-ext-install pdo_pgsql pgsql bcmath bz2 calendar exif gd gettext gmp intl mcrypt pcntl shmop soap sockets sysvmsg sysvsem sysvshm wddx xmlrpc xsl zip

COPY . /app

COPY Docker/docker-entrypoint.sh /app/docker-entrypoint.sh

COPY Docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY Docker/nginx/conf.d/makkelijkemarkt-dashboard.conf /etc/nginx/conf.d/makkelijkemarkt-dashboard.conf

COPY Docker/php/php.ini /usr/local/etc/php/php.ini
COPY Docker/php/conf.d/10-opcache.ini /usr/local/etc/php/conf.d/10-opcache.ini

WORKDIR /app

RUN curl -sS https://getcomposer.org/installer | php && php composer.phar install --prefer-dist --no-scripts

RUN chown -R www-data:www-data /app/app/cache \
    && chmod 770 /app/app/cache \
    && chown -R www-data:www-data /app/app/logs \
    && chmod 770 /app/app/logs \
    && chmod 777 /app/docker-entrypoint.sh

CMD /app/docker-entrypoint.sh
