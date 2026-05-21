FROM php:8.3-fpm

WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    curl \
    zip \
    unzip \
    libicu-dev \
    libxml2-dev \
    libonig-dev \
    nginx \
    gettext-base \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install \
    intl \
    xml \
    pdo \
    pdo_mysql \
    mbstring \
    opcache

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- \
    --install-dir=/usr/local/bin \
    --filename=composer

# Copy project files
COPY . .

# Copy Nginx configs (if present)
COPY nginx.conf /etc/nginx/conf.d/default.conf.template
COPY nginx-main.conf /etc/nginx/nginx.conf

# Install Composer dependencies (production)
RUN if [ -f composer.json ]; then \
    COMPOSER_ALLOW_SUPERUSER=1 composer install --no-interaction --no-dev --optimize-autoloader --no-scripts; \
    fi

# Ensure var directories exist and are writable
RUN mkdir -p var/cache var/log var/sessions \
    && chmod -R 777 var/ \
    && chown -R www-data:www-data var/ || true

# Copy and set entrypoint
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh || true

# Expose internal HTTP port
EXPOSE 8080

ENTRYPOINT ["/entrypoint.sh"]
