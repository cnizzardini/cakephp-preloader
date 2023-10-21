FROM cnizzardini/php-fpm-alpine:8.1-latest

ARG BRANCH=main

COPY php.ini /usr/local/etc/php/php.ini

WORKDIR /srv/app

COPY --from=composer /usr/bin/composer /usr/bin/composer

RUN composer create-project --prefer-dist --no-interaction cakephp/app .
RUN composer require cnizzardini/cakephp-preloader:$BRANCH
RUN sleep 5
RUN bin/cake plugin load CakePreloader
RUN sleep 1
RUN bin/cake preloader
RUN sleep 1

CMD ["php-fpm"]