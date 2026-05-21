FROM php:8.3-fpm

WORKDIR /var/www/html

# Install system dependencies including Node.js
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
    ca-certificates \
    gnupg \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Node.js 20
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - && \
    apt-get install -y nodejs

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

# Install Composer dependencies FIRST (creates vendor directory for local npm packages)
RUN if [ -f composer.json ]; then \
    COMPOSER_ALLOW_SUPERUSER=1 composer install --no-interaction --no-dev --optimize-autoloader; \
    fi

# Install Node dependencies (vendor directory now exists for symfony/ux-turbo)
RUN if [ -f package.json ]; then \
    npm ci --no-audit --no-fund || npm install; \
    fi

# Build Webpack assets
RUN if [ -f package.json ]; then \
    npm run build; \
    fi

# Clear cache
RUN if [ -f bin/console ]; then \
    php bin/console cache:clear --env=prod --no-debug || true; \
    fi

# Ensure var directories and build directory exist and are writable
RUN mkdir -p var/cache var/log var/sessions public/build \
    && chmod -R 777 var/ public/build \
    && chown -R www-data:www-data var/ public/build/ || true

# Copy and set entrypoint
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh || true

# Expose internal HTTP port
EXPOSE 8080

ENTRYPOINT ["/entrypoint.sh"]