# 🧪 Script de Configuración de Pruebas de Permisos para Windows
# ==============================================================

Write-Host "🚀 Configurando entorno de pruebas de permisos..." -ForegroundColor Blue

# Función para mostrar mensajes con colores
function Write-Status {
    param($Message)
    Write-Host "[INFO] $Message" -ForegroundColor Cyan
}

function Write-Success {
    param($Message)
    Write-Host "[✅] $Message" -ForegroundColor Green
}

function Write-Warning {
    param($Message)
    Write-Host "[⚠️] $Message" -ForegroundColor Yellow
}

function Write-Error {
    param($Message)
    Write-Host "[❌] $Message" -ForegroundColor Red
}

# Verificar que estamos en el directorio correcto
if (-not (Test-Path "artisan")) {
    Write-Error "No se encontró el archivo artisan. Ejecuta este script desde la raíz del proyecto Laravel."
    exit 1
}

Write-Status "Verificando dependencias..."

# Verificar PHPUnit
if (-not (Test-Path "vendor\bin\phpunit.bat")) {
    Write-Error "PHPUnit no encontrado. Ejecuta 'composer install' primero."
    exit 1
}

Write-Success "PHPUnit encontrado"

# Crear archivo .env.testing si no existe
if (-not (Test-Path ".env.testing")) {
    Write-Status "Creando archivo .env.testing..."
    
    # Generar clave de aplicación
    $appKey = & php artisan key:generate --show
    
    $envContent = @"
APP_NAME="Laravel Multitenant Testing"
APP_ENV=testing
APP_KEY=$appKey
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
MAIL_FROM_NAME="`${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

VITE_APP_NAME="`${APP_NAME}"
"@
    
    $envContent | Out-File -FilePath ".env.testing" -Encoding UTF8
    Write-Success "Archivo .env.testing creado"
} else {
    Write-Success "Archivo .env.testing ya existe"
}

# Crear directorios necesarios
Write-Status "Creando directorios necesarios..."
$directories = @(
    "storage\app\test-coverage",
    "storage\app\test-reports",
    "storage\logs"
)

foreach ($dir in $directories) {
    if (-not (Test-Path $dir)) {
        New-Item -ItemType Directory -Path $dir -Force | Out-Null
    }
}
Write-Success "Directorios creados"

# Ejecutar migraciones en entorno de testing
Write-Status "Ejecutando migraciones en entorno de testing..."
try {
    & herd php artisan migrate:fresh --env=testing --force
    if ($LASTEXITCODE -eq 0) {
        Write-Success "Migraciones ejecutadas correctamente"
    } else {
        Write-Error "Error ejecutando migraciones"
        exit 1
    }
} catch {
    Write-Error "Error ejecutando migraciones: $_"
    exit 1
}

# Crear datos de prueba
Write-Status "Creando datos de prueba..."
try {
    & herd php artisan db:seed --class=TestPermissionsSeeder --env=testing
    if ($LASTEXITCODE -eq 0) {
        Write-Success "Datos de prueba creados"
    } else {
        Write-Warning "Error creando datos de prueba (puede ser normal si el seeder no existe aún)"
    }
} catch {
    Write-Warning "Error creando datos de prueba: $_"
}

# Verificar que las pruebas se pueden ejecutar
Write-Status "Verificando configuración de pruebas..."
try {
    & vendor\bin\phpunit.bat --version
    if ($LASTEXITCODE -eq 0) {
        Write-Success "PHPUnit configurado correctamente"
    } else {
        Write-Error "Error en configuración de PHPUnit"
        exit 1
    }
} catch {
    Write-Error "Error verificando PHPUnit: $_"
    exit 1
}

# Ejecutar una prueba simple para verificar
Write-Status "Ejecutando prueba de verificación..."
try {
    if (Test-Path "tests\Feature\ExampleTest.php") {
        & vendor\bin\phpunit.bat tests\Feature\ExampleTest.php --testdox
        if ($LASTEXITCODE -eq 0) {
            Write-Success "Prueba de verificación exitosa"
        } else {
            Write-Warning "Prueba de verificación falló"
        }
    } else {
        Write-Warning "ExampleTest.php no encontrado (normal)"
    }
} catch {
    Write-Warning "Error en prueba de verificación: $_"
}

Write-Success "¡Configuración completada!"
Write-Host ""
Write-Host "📋 Comandos disponibles:" -ForegroundColor Yellow
Write-Host "========================" -ForegroundColor Yellow
Write-Host "• Ejecutar todas las pruebas de permisos:"
Write-Host "  herd php artisan test:permissions"
Write-Host ""
Write-Host "• Ejecutar con cobertura:"
Write-Host "  herd php artisan test:permissions --coverage"
Write-Host ""
Write-Host "• Ejecutar suite específica:"
Write-Host "  vendor\bin\phpunit.bat --testsuite=Permissions"
Write-Host ""
Write-Host "• Ejecutar prueba específica:"
Write-Host "  vendor\bin\phpunit.bat tests\Feature\UserPermissionsTest.php"
Write-Host ""
Write-Host "• Ver reporte de cobertura:"
Write-Host "  start storage\app\test-coverage\index.html"
Write-Host ""
Write-Host "📁 Archivos importantes:" -ForegroundColor Yellow
Write-Host "========================" -ForegroundColor Yellow
Write-Host "• Configuración: .env.testing"
Write-Host "• Documentación: docs\TESTING_PERMISSIONS.md"
Write-Host "• Reportes: storage\app\test-reports\"
Write-Host "• Cobertura: storage\app\test-coverage\"
Write-Host ""
Write-Success "¡Listo para ejecutar pruebas de permisos!"
