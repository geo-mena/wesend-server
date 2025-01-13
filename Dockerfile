FROM php:8.1.10-fpm-alpine

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN set -ex && apk --no-cache add postgresql-dev
RUN docker-php-ext-install pdo_pgsql
RUN docker-php-ext-install pgsql

RUN apk add --no-cache zip libzip-dev
RUN apk add --no-cache tzdata
RUN docker-php-ext-install zip
RUN docker-php-ext-install bcmath

RUN apk add --no-cache libjpeg-turbo-dev libpng-dev freetype-dev && \
    docker-php-ext-configure gd --with-jpeg --with-freetype && \
    docker-php-ext-install gd && \
    apk del libjpeg-turbo-dev libpng-dev freetype-dev

#Install libs
RUN apk add --no-cache \
    imagemagick \
    imagemagick-dev \
    ghostscript \
    && apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS \
    && pecl install imagick \
    && docker-php-ext-enable imagick \
    && docker-php-ext-install exif \
    && docker-php-ext-enable gd \
    && apk del .build-deps \
    && rm -rf /tmp/*

RUN apk --no-cache add pcre-dev ${PHPIZE_DEPS}
RUN pecl install redis-5.3.7
RUN printf '[PHP]\ndate.timezone = "America/Guayaquil"\n' > /usr/local/etc/php/conf.d/tzone.ini
RUN apk add --no-cache postgresql-client
RUN docker-php-ext-enable redis

COPY . .

# Establecer las credenciales temporalmente solo para composer install
ENV AWS_ACCESS_KEY_ID=a6060c070e9a53280e938ce56b33795c \
    AWS_SECRET_ACCESS_KEY=9fd96cffd5b507cd52b2f6b9cb887e62e19f66119cc54a52060c2b69ddd0e02d \
    AWS_DEFAULT_REGION=us-east-1 \
    AWS_ENDPOINT=https://bf920ae0738cfcaa994cc90c85a84d1d.r2.cloudflarestorage.com \
    AWS_USE_PATH_STYLE_ENDPOINT=true

RUN composer install --no-interaction --no-dev --optimize-autoloader

# Limpiar las credenciales después de composer install
ENV AWS_ACCESS_KEY_ID= \
    AWS_SECRET_ACCESS_KEY= \
    AWS_DEFAULT_REGION= \
    AWS_ENDPOINT= \
    AWS_USE_PATH_STYLE_ENDPOINT=

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage

RUN php artisan storage:link

EXPOSE 80

CMD php artisan serve --host=0.0.0.0 --port=$PORT