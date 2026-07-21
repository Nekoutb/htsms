#!/bin/sh
set -eu
mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
if [ -d /srv/public ]; then
  cp -R public/. /srv/public/
  chown -R www-data:www-data /srv/public
fi
php artisan package:discover --ansi
if [ "${HTSMS_RUN_MIGRATIONS:-false}" = "true" ]; then
  php artisan migrate --force
fi
php artisan config:cache
php artisan route:cache
php artisan view:cache
exec su-exec www-data "$@"
