#!/bin/sh
# Démarrage prod : port Render + caches Laravel (avec les VRAIES variables).
set -e

export PORT="${PORT:-8080}"
sed -i "s/__PORT__/${PORT}/g" /etc/nginx/sites-enabled/default

cd /var/www/html
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache || true

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/app.conf
