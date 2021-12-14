FROM php:7.4-fpm-alpine3.13

ARG DEBIAN_FRONTEND=noninteractive

EXPOSE 80

COPY certificates/adp_rootca.crt /usr/local/share/ca-certificates/adp_rootca.crt
RUN chmod 644 /usr/local/share/ca-certificates/adp_rootca.crt \
  && update-ca-certificates --fresh

RUN apk update && apk upgrade

RUN apk add bash

RUN apk add nginx

RUN mkdir -p /run/nginx

RUN apk add postgresql-dev bzip2-dev freetype libpng libjpeg-turbo freetype-dev libpng-dev jpeg-dev libjpeg libjpeg-turbo-dev libintl gettext gettext-dev gmp gmp-dev icu-dev libxml2-dev libxslt-dev libzip libzip-dev yarn && \
    docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install pdo_pgsql pgsql bcmath bz2 calendar exif gd gettext gmp intl pcntl shmop soap sockets sysvmsg sysvsem sysvshm xmlrpc xsl zip

COPY . /app

COPY Docker/docker-entrypoint.sh /app/docker-entrypoint.sh

COPY Docker/nginx/nginx.conf /etc/nginx/nginx.conf
RUN rm /etc/nginx/http.d/default.conf
COPY Docker/nginx/conf.d/makkelijkemarkt-dashboard.conf /etc/nginx/conf.d/makkelijkemarkt-dashboard.conf

COPY Docker/php/php.ini /usr/local/etc/php/php.ini
COPY Docker/php/conf.d/10-opcache.ini /usr/local/etc/php/conf.d/10-opcache.ini

WORKDIR /app

COPY Docker/adp_rootca.crt /usr/local/share/ca-certificates/extras/
RUN update-ca-certificates

RUN curl -sS https://getcomposer.org/installer | php -- && php composer.phar install --prefer-dist --no-scripts

RUN yarn && yarn encore prod

RUN mkdir /app/var \
    && mkdir /app/var/log \
    && mkdir /app/var/cache \
    && chown -R www-data:www-data /app/var/cache \
    && chmod 770 /app/var/cache \
    && chown -R www-data:www-data /app/var/log \
    && chmod 770 /app/var/log \
    && chmod 777 /app/docker-entrypoint.sh

CMD /app/docker-entrypoint.sh
