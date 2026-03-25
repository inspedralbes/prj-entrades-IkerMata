#!/bin/sh

# Wait for database
echo "Waiting for database..."
until nc -z db 5432; do
  sleep 1
done
echo "Database is up!"

# Run migrations and seed
php artisan migrate --seed --force

# Start php-fpm
exec php-fpm
