#!/bin/bash
set -e

export PORT=${PORT:-8080}

echo "Starting entrypoint, PORT=$PORT"

if [ -d /var/www/html/var ]; then
  chmod -R 777 /var/www/html/var || true
  chown -R www-data:www-data /var/www/html/var || true
fi

if [ -d /var/www/html/public/build ]; then
  chmod -R 777 /var/www/html/public/build || true
fi

# Rebuild assets if a stale/cached image was deployed without Webpack output
if [ ! -f /var/www/html/public/build/entrypoints.json ] && [ -f /var/www/html/package.json ]; then
  echo "Missing public/build/entrypoints.json — building frontend assets..."
  cd /var/www/html
  export NPM_CONFIG_PRODUCTION=false
  if [ ! -d node_modules ]; then
    npm ci --no-audit --no-fund || npm install --no-audit --no-fund
  fi
  NODE_ENV=production npm run build
  rm -rf node_modules || true
fi

if [ -f bin/console ]; then
  php bin/console doctrine:migrations:migrate --no-interaction --env=prod --allow-no-migration || true
  php bin/console cache:clear --env=prod --no-debug || true
fi

php-fpm -D

if [ -f /etc/nginx/conf.d/default.conf.template ]; then
  envsubst '${PORT}' < /etc/nginx/conf.d/default.conf.template > /etc/nginx/conf.d/default.conf
fi

exec nginx -g "daemon off;"
