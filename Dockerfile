FROM php:8.5-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libfreetype6-dev libjpeg62-turbo-dev libpng-dev libzip-dev unzip apache2-utils \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" exif gd zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY composer.json ./
RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader

COPY . .
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/reciperepo.ini
COPY docker/entrypoint.sh /usr/local/bin/reciperepo-entrypoint

RUN a2enmod auth_basic authn_file \
    && mkdir -p /var/www/html/public/uploads/images /var/www/html/recipes /etc/recipe-collection \
    && chown -R www-data:www-data /var/www/html/public/uploads /var/www/html/recipes \
    && chmod +x /usr/local/bin/reciperepo-entrypoint

ENTRYPOINT ["reciperepo-entrypoint"]
CMD ["apache2-foreground"]