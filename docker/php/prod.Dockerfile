FROM php:7.3.8-fpm-alpine3.9

COPY . /var/www/html

RUN docker-php-ext-install pdo_mysql

RUN apk add --no-cache supervisor

COPY ./docker/php/crontab /etc/cron.d/crontab
RUN chmod 0644 /etc/cron.d/crontab
RUN crontab /etc/cron.d/crontab
RUN touch /var/log/cron.log

# See: https://github.com/docker-library/php/blob/a9f19e9df5f7a5b74d72a97439ca5b77b87faa35/7.3/alpine3.9/fpm/Dockerfile
WORKDIR /var/www/html

COPY ./docker/php/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
