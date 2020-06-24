FROM php:7.4.7-fpm-alpine3.12

# install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer

RUN apk update && apk add --no-cache bash git zip unzip vim curl su-exec supervisor

# Xdebug installation
RUN apk add autoconf build-base
RUN pecl install xdebug
RUN docker-php-ext-enable xdebug

RUN docker-php-ext-install pdo_mysql

COPY crontab /etc/cron.d/crontab
RUN chmod 0644 /etc/cron.d/crontab
RUN crontab /etc/cron.d/crontab
RUN touch /var/log/cron.log

WORKDIR /var/www/html

COPY ./supervisord.conf /etc/supervisor/conf.d/supervisord.conf

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
