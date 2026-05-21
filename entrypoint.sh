#!/bin/bash
set -e

# Default PORT to 8080
export PORT=${PORT:-8080}

echo "Starting entrypoint, PORT=$PORT"

# Fix permissions
if [ -d /var/www/html/var ]; then
  chmod -R 777 /var/www/html/var || true
  chown -R www-data:www-data /var/www/html/var || true
fi

# Install JS assets if Symfony binary exists
if [ -f bin/console ]; then
  php bin/console importmap:install || true
  php bin/console cache:clear --env=prod --no-debug || true
  php bin/console doctrine:migrations:migrate --no-interaction --env=prod || true
fi

# Start PHP-FPM
php-fpm -D

# Render nginx template and start nginx
if [ -f /etc/nginx/conf.d/default.conf.template ]; then
  envsubst '${PORT}' < /etc/nginx/conf.d/default.conf.template > /etc/nginx/conf.d/default.conf
fi

exec nginx -g "daemon off;"
