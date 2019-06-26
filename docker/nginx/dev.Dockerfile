FROM nginx:latest

COPY ./docker/nginx/default.conf /etc/nginx/conf.d/default.conf
WORKDIR /var/www
