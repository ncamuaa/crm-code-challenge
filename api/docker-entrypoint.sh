#!/usr/bin/env bash
set -e

echo "Waiting for database (${DB_HOST}:${DB_PORT})..."
until (echo > /dev/tcp/${DB_HOST}/${DB_PORT}) >/dev/null 2>&1; do
  sleep 1
done
echo "Database is up."

echo "Waiting for Elasticsearch (${ELASTICSEARCH_HOST}:${ELASTICSEARCH_PORT})..."
until (echo > /dev/tcp/${ELASTICSEARCH_HOST}/${ELASTICSEARCH_PORT}) >/dev/null 2>&1; do
  sleep 1
done
echo "Elasticsearch is up."

echo "Running database migrations..."
php artisan migrate --force

echo "Ensuring Elasticsearch index exists..."
php artisan search:setup || true

echo "Starting php-fpm..."
exec "$@"
