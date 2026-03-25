#!/bin/sh

# Wait for database
echo "Waiting for database..."
until nc -z db 5432; do
  sleep 1
done
echo "Database is up!"

# Run SQL init scripts
echo "Running SQL scripts..."
PGPASSWORD=secret psql -v ON_ERROR_STOP=1 --username=laravel --dbname=laravel -h db -f /docker-entrypoint-initdb.d/init.sql
PGPASSWORD=secret psql -v ON_ERROR_STOP=1 --username=laravel --dbname=laravel -h db -f /docker-entrypoint-initdb.d/insert.sql || true

# Start php-fpm
exec php-fpm
