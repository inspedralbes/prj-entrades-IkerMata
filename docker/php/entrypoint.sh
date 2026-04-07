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
# insert.sql pot fallar si el esquema Laravel (users amb 'nom', etc.) difereix; no bloqueja l'arrencada
PGPASSWORD=secret psql --username=laravel --dbname=laravel -h db -f /docker-entrypoint-initdb.d/insert.sql || echo "AVIS: insert.sql no s'ha pogut aplicar (normal si ja hi ha dades Laravel)."

# Start php-fpm
exec php-fpm
