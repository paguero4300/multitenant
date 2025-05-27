#!/bin/bash

# 🧪 Script de Configuración de Pruebas de Permisos
# =================================================

echo "🚀 Configurando entorno de pruebas de permisos..."

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Función para mostrar mensajes
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[✅]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[⚠️]${NC} $1"
}

print_error() {
    echo -e "${RED}[❌]${NC} $1"
}

# Verificar que estamos en el directorio correcto
if [ ! -f "artisan" ]; then
    print_error "No se encontró el archivo artisan. Ejecuta este script desde la raíz del proyecto Laravel."
    exit 1
fi

print_status "Verificando dependencias..."

# Verificar PHPUnit
if ! command -v vendor/bin/phpunit &> /dev/null; then
    print_error "PHPUnit no encontrado. Ejecuta 'composer install' primero."
    exit 1
fi

print_success "PHPUnit encontrado"

# Crear archivo .env.testing si no existe
if [ ! -f ".env.testing" ]; then
    print_status "Creando archivo .env.testing..."
    cat > .env.testing << EOF
APP_NAME="Laravel Multitenant Testing"
APP_ENV=testing
APP_KEY=base64:$(php artisan key:generate --show)
APP_DEBUG=true
APP_TIMEZONE=UTC
APP_URL=http://localhost

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

APP_MAINTENANCE_DRIVER=file

BCRYPT_ROUNDS=4

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=sqlite
DB_DATABASE=:memory:

SESSION_DRIVER=array
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync

CACHE_STORE=array
CACHE_PREFIX=

MEMCACHED_HOST=127.0.0.1

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=array
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="\${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

VITE_APP_NAME="\${APP_NAME}"
EOF
    print_success "Archivo .env.testing creado"
else
    print_success "Archivo .env.testing ya existe"
fi

# Crear directorios necesarios
print_status "Creando directorios necesarios..."
mkdir -p storage/app/test-coverage
mkdir -p storage/app/test-reports
mkdir -p storage/logs
print_success "Directorios creados"

# Ejecutar migraciones en entorno de testing
print_status "Ejecutando migraciones en entorno de testing..."
php artisan migrate:fresh --env=testing --force
if [ $? -eq 0 ]; then
    print_success "Migraciones ejecutadas correctamente"
else
    print_error "Error ejecutando migraciones"
    exit 1
fi

# Crear datos de prueba
print_status "Creando datos de prueba..."
php artisan db:seed --class=TestPermissionsSeeder --env=testing
if [ $? -eq 0 ]; then
    print_success "Datos de prueba creados"
else
    print_warning "Error creando datos de prueba (puede ser normal si el seeder no existe aún)"
fi

# Verificar que las pruebas se pueden ejecutar
print_status "Verificando configuración de pruebas..."
vendor/bin/phpunit --version
if [ $? -eq 0 ]; then
    print_success "PHPUnit configurado correctamente"
else
    print_error "Error en configuración de PHPUnit"
    exit 1
fi

# Ejecutar una prueba simple para verificar
print_status "Ejecutando prueba de verificación..."
vendor/bin/phpunit tests/Feature/ExampleTest.php --testdox
if [ $? -eq 0 ]; then
    print_success "Prueba de verificación exitosa"
else
    print_warning "Prueba de verificación falló (puede ser normal si no existe ExampleTest)"
fi

print_success "¡Configuración completada!"
echo ""
echo "📋 Comandos disponibles:"
echo "========================"
echo "• Ejecutar todas las pruebas de permisos:"
echo "  php artisan test:permissions"
echo ""
echo "• Ejecutar con cobertura:"
echo "  php artisan test:permissions --coverage"
echo ""
echo "• Ejecutar suite específica:"
echo "  vendor/bin/phpunit --testsuite=Permissions"
echo ""
echo "• Ejecutar prueba específica:"
echo "  vendor/bin/phpunit tests/Feature/UserPermissionsTest.php"
echo ""
echo "• Ver reporte de cobertura:"
echo "  open storage/app/test-coverage/index.html"
echo ""
echo "📁 Archivos importantes:"
echo "========================"
echo "• Configuración: .env.testing"
echo "• Documentación: docs/TESTING_PERMISSIONS.md"
echo "• Reportes: storage/app/test-reports/"
echo "• Cobertura: storage/app/test-coverage/"
echo ""
print_success "¡Listo para ejecutar pruebas de permisos!"
