FROM nginx:alpine

COPY ./docker/nginx/default.conf /etc/nginx/conf.d/default.conf
WORKDIR /var/www

COPY ./public /var/www/html/public
