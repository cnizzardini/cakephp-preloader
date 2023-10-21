FROM cnizzardini/php-fpm-alpine:8.1-latest

ARG BRANCH=main

COPY php.ini /usr/local/etc/php/php.ini

WORKDIR /srv/app

COPY --from=composer /usr/bin/composer /usr/bin/composer

RUN composer create-project --prefer-dist --no-interaction cakephp/app .
RUN composer require cnizzardini/cakephp-preloader:$BRANCH
COPY plugins.php /srv/app/config/plugins.php
RUN bin/cake plugin load CakePreloader
RUN bin/cake preloader

CMD ["php-fpm"]