this im gonna push this

FROM php:8.3-fpm

WORKDIR /var/www/html

# System dependencies + Node.js (required for Webpack Encore at build time)
RUN apk add --no-cache \
    git curl zip unzip \
    icu-dev libxml2-dev oniguruma-dev \
    nginx gettext ca-certificates nodejs npm

RUN docker-php-ext-install intl xml pdo pdo_mysql mbstring opcache

RUN curl -sS https://getcomposer.org/installer | php -- \
    --install-dir=/usr/local/bin --filename=composer

COPY . .

COPY nginx.conf /etc/nginx/conf.d/default.conf.template
COPY nginx-main.conf /etc/nginx/nginx.conf

# PHP deps first (vendor/ is required for @symfony/ux-turbo npm file: dependency)
RUN COMPOSER_ALLOW_SUPERUSER=1 composer install \
    --no-interaction --no-dev --optimize-autoloader

# Install all npm deps (including devDependencies for webpack/encore)
ENV NPM_CONFIG_PRODUCTION=false
RUN npm ci --no-audit --no-fund

# Production frontend build -> public/build/entrypoints.json
ENV NODE_ENV=production
RUN npm run build \
    && test -f public/build/entrypoints.json \
    || (echo "ERROR: Webpack build did not produce public/build/entrypoints.json" && exit 1)

RUN rm -rf node_modules

RUN php bin/console cache:clear --env=prod --no-debug

RUN mkdir -p var/cache var/log var/sessions \
    && chmod -R 777 var/ public/build \
    && chown -R www-data:www-data var/ public/build || true

COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 8080

ENTRYPOINT ["/entrypoint.sh"]
