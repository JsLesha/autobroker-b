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
  echo "Waiting for PostgreSQL (${DB_HOST:-pgsql}:${DB_PORT:-5432})..."
  until php -r '
    $dsn = sprintf("pgsql:host=%s;port=%s;dbname=%s", getenv("DB_HOST") ?: "pgsql", getenv("DB_PORT") ?: "5432", getenv("DB_DATABASE") ?: "autobroker");
    try { new PDO($dsn, getenv("DB_USERNAME") ?: "", getenv("DB_PASSWORD") ?: "", [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]); exit(0); }
    catch (Throwable $e) { exit(1); }
  '; do
    sleep 2
  done
  echo "PostgreSQL is up."
fi

if [ "${RUN_MIGRATIONS:-0}" = "1" ]; then
  php artisan migrate --force
fi

exec "$@"
