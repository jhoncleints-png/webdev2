#!/bin/bash
set -e

# Default PORT to 8080
export PORT=${PORT:-8080}

echo "Starting entrypoint, PORT=$PORT"

# Fix permissions (in case they got reset)
if [ -d /var/www/html/var ]; then
  chmod -R 777 /var/www/html/var || true
  chown -R www-data:www-data /var/www/html/var || true
fi

if [ -d /var/www/html/public/build ]; then
  chmod -R 777 /var/www/html/public/build || true
fi

# Run migrations if needed
if [ -f bin/console ]; then
  php bin/console doctrine:migrations:migrate --no-interaction --env=prod --allow-no-migration || true
  # Clear cache again to ensure fresh state
  php bin/console cache:clear --env=prod --no-debug || true
fi

# Start PHP-FPM
php-fpm -D

# Render nginx template and start nginx
if [ -f /etc/nginx/conf.d/default.conf.template ]; then
  envsubst '${PORT}' < /etc/nginx/conf.d/default.conf.template > /etc/nginx/conf.d/default.conf
fi

exec nginx -g "daemon off;"