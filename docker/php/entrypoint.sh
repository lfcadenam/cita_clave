#!/bin/sh
set -e

echo "🚀 Iniciando contenedor Cita Clave Backend..."

# Esperar a que la base de datos MySQL responda
echo "⏳ Verificando conexión a la base de datos MySQL ($DB_HOST:$DB_PORT)..."
MAX_TRIES=60
COUNT=0

until php -r "
try {
    \$dbh = new PDO('mysql:host=' . getenv('DB_HOST') . ';port=' . (getenv('DB_PORT') ?: 3306) . ';dbname=' . getenv('DB_DATABASE'), getenv('DB_USERNAME'), getenv('DB_PASSWORD'));
    exit(0);
} catch (Exception \$e) {
    exit(1);
}
"; do
    COUNT=$((COUNT + 1))
    if [ "$COUNT" -ge "$MAX_TRIES" ]; then
        echo "❌ Error: La base de datos no estuvo disponible a tiempo. Abortando."
        exit 1
    fi
    echo "   ... esperando a MySQL ($COUNT/$MAX_TRIES)"
    sleep 2
done

echo "✅ Conexión con MySQL establecida exitosamente."

# Ajustar permisos de carpetas de almacenamiento y caché
mkdir -p /var/www/backend/storage/framework/cache/data \
         /var/www/backend/storage/framework/sessions \
         /var/www/backend/storage/framework/views \
         /var/www/backend/storage/app/public \
         /var/www/backend/bootstrap/cache

chown -R www-data:www-data /var/www/backend/storage /var/www/backend/bootstrap/cache
chmod -R 775 /var/www/backend/storage /var/www/backend/bootstrap/cache

# Enlace simbólico de storage para archivos públicos
if [ ! -L /var/www/backend/public/storage ]; then
    echo "🔗 Creando enlace simbólico de storage..."
    php artisan storage:link --force || true
fi

# Ejecutar migraciones
echo "📦 Ejecutando migraciones de base de datos..."
php artisan migrate --force

# Ejecutar seeder del Super Admin exclusivamente
echo "👤 Asegurando existencia del Super Admin global..."
php artisan db:seed --class="Database\\Seeders\\SuperAdminSeeder" --force

# Caché y optimización en producción
if [ "$APP_ENV" = "production" ]; then
    echo "⚡ Optimizando caché de Laravel para producción..."
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
    php artisan event:cache || true
fi

echo "🎉 Backend listo. Arrancando proceso principal..."
exec "$@"