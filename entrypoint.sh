#!/bin/bash
set -e

echo "Migrating..."
php bin/console doctrine:migrations:migrate --env=prod --no-interaction

echo "Ensuring JWT keypair exists..."
mkdir -p /app/config/jwt
php bin/console lexik:jwt:generate-keypair --env=prod --skip-if-exists --no-interaction

echo "Starting WebSocket server..."
php bin/console --env=prod app:websocket:serve --host=127.0.0.1 --port=8081 &

echo "Starting Socket.IO server..."
node /app/socket-server.js &

mkdir -p /app/var/sessions
chown -R www-data:www-data /app/var/sessions
chmod -R 775 /app/var/sessions

echo "Clearing cache..."
php bin/console cache:clear --env=prod --no-warmup || true

echo "Starting PHP-FPM..."
php-fpm -F &

# A short sleep is fine to let FPM bind to its socket/port
sleep 2 

echo "Starting Nginx..."
nginx -g "daemon off;" &

# Wait for ANY of the background processes to exit
wait -n

# If we reach this point, either Nginx or PHP-FPM has crashed.
echo "A critical process crashed. Exiting..."
exit 1