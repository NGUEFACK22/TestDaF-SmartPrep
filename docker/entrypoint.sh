#!/bin/sh
# Démarrage prod : port Render + caches Laravel (avec les VRAIES variables).
set -e

export PORT="${PORT:-8080}"
sed -i "s/__PORT__/${PORT}/g" /etc/nginx/sites-enabled/default

# DIAGNOSTIC : écrit /diag.html (statique, accessible même si PHP tombe).
{
    echo "<h3>Diagnostic SYNPHONIE</h3><pre>"
    echo "Generated: $(date -u)"
    echo "PHP: $(php -r 'echo PHP_VERSION;' 2>&1)"
    echo "Extensions: $(php -r 'echo implode(" ", get_loaded_extensions());' 2>&1)"
    echo "APP_ENV=${APP_ENV}"
    echo "APP_DEBUG=${APP_DEBUG}"
    echo "APP_URL=${APP_URL}"
    echo "APP_KEY_SET=$([ -n "$APP_KEY" ] && echo yes || echo NO)"
    echo "SESSION_DRIVER=${SESSION_DRIVER}"
    echo "CACHE_STORE=${CACHE_STORE}"
    echo "QUEUE_CONNECTION=${QUEUE_CONNECTION}"
    echo "DB_CONNECTION=${DB_CONNECTION}"
    echo "DB_HOST=${DB_HOST}"
    echo "DB_PORT=${DB_PORT}"
    echo "DB_DATABASE=${DB_DATABASE}"
    echo "DB_USERNAME=${DB_USERNAME}"
    echo "DB_PASSWORD_SET=$([ -n "$DB_PASSWORD" ] && echo yes || echo NO)"
    echo "AI_PROVIDER=${AI_PROVIDER}"
    echo "GROQ_KEY_SET=$([ -n "$GROQ_API_KEY" ] && echo yes || echo NO)"
    echo "</pre>"
} > /var/www/html/public/diag.html

cd /var/www/html
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache || true

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/app.conf