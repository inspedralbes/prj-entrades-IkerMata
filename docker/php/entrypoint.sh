#!/bin/sh
# sense set -e: init.sql pot fallar si la BD ja existe (recreació contenidor amb volum)

echo "Waiting for database..."
until nc -z db 5432; do
  sleep 1
done
echo "Database is up!"

echo "Running SQL scripts..."
if PGPASSWORD=secret psql -v ON_ERROR_STOP=1 --username=laravel --dbname=laravel -h db -f /docker-entrypoint-initdb.d/init.sql; then
  echo "init.sql aplicat."
else
  echo "AVIS: init.sql ha fallat o ja estava aplicat; es continua amb php-fpm."
fi

PGPASSWORD=secret psql --username=laravel --dbname=laravel -h db -f /docker-entrypoint-initdb.d/insert.sql || echo "AVIS: insert.sql no aplicat (normal si ja hi ha dades)."

# Laravel: escriptura a storage/ i bootstrap/cache (evita "Permission denied" a laravel.log)
if [ -d /var/www ]; then
  cd /var/www || exit 1
  mkdir -p storage/logs storage/framework/sessions storage/framework/views storage/framework/cache/data bootstrap/cache
  chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
  chmod -R ug+rwx storage bootstrap/cache 2>/dev/null || true
fi

exec php-fpm
