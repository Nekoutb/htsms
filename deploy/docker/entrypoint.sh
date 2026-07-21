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

# The official PHP-FPM image starts its master process as root so it can open
# container log descriptors and then drops pool workers to www-data. CLI
# workers do not need those master-process privileges and run as www-data.
case "$1" in
  php-fpm|php-fpm*) exec "$@" ;;
  *) exec su-exec www-data "$@" ;;
esac
