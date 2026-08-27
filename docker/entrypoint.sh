#!/bin/bash
set -eo pipefail

cd /var/www/html

if [ "${COMPOSER_INSTALL:-1}" != "0" ]; then
  if [ ! -f vendor/autoload.php ]; then
    composer install --no-interaction --prefer-dist --optimize-autoloader
  fi
fi

if [ ! -f .env ]; then
  cp .env.example .env
fi

if [ "${WAIT_FOR_DB:-1}" != "0" ]; then
  echo "Waiting for MySQL (${DB_HOST:-mysql}:${DB_PORT:-3306})..."
  until php -r '
    $dsn = sprintf("mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4", getenv("DB_HOST") ?: "mysql", getenv("DB_PORT") ?: "3306", getenv("DB_DATABASE") ?: "autobroker");
    try { new PDO($dsn, getenv("DB_USERNAME") ?: "", getenv("DB_PASSWORD") ?: "", [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]); exit(0); }
    catch (Throwable $e) { exit(1); }
  '; do
    sleep 2
  done
  echo "MySQL is up."
fi

if [ "${RUN_MIGRATIONS:-0}" = "1" ]; then
  php artisan migrate --force
fi

exec "$@"
