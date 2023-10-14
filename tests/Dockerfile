FROM cnizzardini/php-fpm-alpine:8.1-latest

ARG BRANCH=main

COPY php.ini /usr/local/etc/php/php.ini

WORKDIR /srv/app

COPY --from=composer /usr/bin/composer /usr/bin/composer

RUN composer create-project --prefer-dist --no-interaction cakephp/app:5.0 .
RUN composer config minimum-stability dev
RUN composer config prefer-stable true
RUN composer update
RUN composer require cnizzardini/cakephp-preloader:$BRANCH
RUN bin/cake version
RUN bin/cake plugin loaded
RUN ls vendor/cnizzardini/cakephp-preloader
RUN bin/cake plugin load CakePreloader
RUN bin/cake preloader

CMD ["php-fpm"]