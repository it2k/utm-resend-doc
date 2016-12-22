FROM php:7.0-cli

MAINTAINER Egor Zyuskin <egor@zyuskin.ru>

RUN mkdir -p /var/www/html

WORKDIR /var/www/html

ADD . /var/www/html

VOLUME /var/www/html/app/config/

EXPOSE 80

CMD [ "php", "-S", "0.0.0.0:80", "-t", "/var/www/html/web" ]