@echo off
REM 🧪 Script de Configuración de Pruebas de Permisos para Windows
REM ==============================================================

echo 🚀 Configurando entorno de pruebas de permisos...

REM Verificar que estamos en el directorio correcto
if not exist "artisan" (
    echo ❌ No se encontró el archivo artisan. Ejecuta este script desde la raíz del proyecto Laravel.
    pause
    exit /b 1
)

echo ✅ Directorio correcto verificado

REM Verificar PHPUnit
if not exist "vendor\bin\phpunit.bat" (
    echo ❌ PHPUnit no encontrado. Ejecuta 'composer install' primero.
    pause
    exit /b 1
)

echo ✅ PHPUnit encontrado

REM Crear archivo .env.testing si no existe
if not exist ".env.testing" (
    echo 📝 Creando archivo .env.testing...
    
    REM Generar clave de aplicación
    for /f "tokens=*" %%i in ('herd php artisan key:generate --show') do set APP_KEY=%%i
    
    (
        echo APP_NAME="Laravel Multitenant Testing"
        echo APP_ENV=testing
        echo APP_KEY=%APP_KEY%
        echo APP_DEBUG=true
        echo APP_TIMEZONE=UTC
        echo APP_URL=http://localhost
        echo.
        echo APP_LOCALE=en
        echo APP_FALLBACK_LOCALE=en
        echo APP_FAKER_LOCALE=en_US
        echo.
        echo APP_MAINTENANCE_DRIVER=file
        echo.
        echo BCRYPT_ROUNDS=4
        echo.
        echo LOG_CHANNEL=stack
        echo LOG_STACK=single
        echo LOG_DEPRECATIONS_CHANNEL=null
        echo LOG_LEVEL=debug
        echo.
        echo DB_CONNECTION=sqlite
        echo DB_DATABASE=:memory:
        echo.
        echo SESSION_DRIVER=array
        echo SESSION_LIFETIME=120
        echo SESSION_ENCRYPT=false
        echo SESSION_PATH=/
        echo SESSION_DOMAIN=null
        echo.
        echo BROADCAST_CONNECTION=log
        echo FILESYSTEM_DISK=local
        echo QUEUE_CONNECTION=sync
        echo.
        echo CACHE_STORE=array
        echo CACHE_PREFIX=
        echo.
        echo MEMCACHED_HOST=127.0.0.1
        echo.
        echo REDIS_CLIENT=phpredis
        echo REDIS_HOST=127.0.0.1
        echo REDIS_PASSWORD=null
        echo REDIS_PORT=6379
        echo.
        echo MAIL_MAILER=array
        echo MAIL_HOST=127.0.0.1
        echo MAIL_PORT=2525
        echo MAIL_USERNAME=null
        echo MAIL_PASSWORD=null
        echo MAIL_ENCRYPTION=null
        echo MAIL_FROM_ADDRESS="hello@example.com"
        echo MAIL_FROM_NAME="${APP_NAME}"
        echo.
        echo AWS_ACCESS_KEY_ID=
        echo AWS_SECRET_ACCESS_KEY=
        echo AWS_DEFAULT_REGION=us-east-1
        echo AWS_BUCKET=
        echo AWS_USE_PATH_STYLE_ENDPOINT=false
        echo.
        echo VITE_APP_NAME="${APP_NAME}"
    ) > .env.testing
    
    echo ✅ Archivo .env.testing creado
) else (
    echo ✅ Archivo .env.testing ya existe
)

REM Crear directorios necesarios
echo 📁 Creando directorios necesarios...
if not exist "storage\app\test-coverage" mkdir "storage\app\test-coverage"
if not exist "storage\app\test-reports" mkdir "storage\app\test-reports"
if not exist "storage\logs" mkdir "storage\logs"
echo ✅ Directorios creados

REM Ejecutar migraciones en entorno de testing
echo 🗄️ Ejecutando migraciones en entorno de testing...
herd php artisan migrate:fresh --env=testing --force
if %errorlevel% neq 0 (
    echo ❌ Error ejecutando migraciones
    pause
    exit /b 1
)
echo ✅ Migraciones ejecutadas correctamente

REM Crear datos de prueba
echo 🌱 Creando datos de prueba...
herd php artisan db:seed --class=TestPermissionsSeeder --env=testing
if %errorlevel% neq 0 (
    echo ⚠️ Error creando datos de prueba (puede ser normal si el seeder no existe aún)
) else (
    echo ✅ Datos de prueba creados
)

REM Verificar que las pruebas se pueden ejecutar
echo 🔍 Verificando configuración de pruebas...
vendor\bin\phpunit.bat --version
if %errorlevel% neq 0 (
    echo ❌ Error en configuración de PHPUnit
    pause
    exit /b 1
)
echo ✅ PHPUnit configurado correctamente

echo.
echo ✅ ¡Configuración completada!
echo.
echo 📋 Comandos disponibles:
echo ========================
echo • Ejecutar todas las pruebas de permisos:
echo   herd php artisan test:permissions
echo.
echo • Ejecutar con cobertura:
echo   herd php artisan test:permissions --coverage
echo.
echo • Ejecutar suite específica:
echo   vendor\bin\phpunit.bat --testsuite=Permissions
echo.
echo • Ejecutar prueba específica:
echo   vendor\bin\phpunit.bat tests\Feature\UserPermissionsTest.php
echo.
echo • Ver reporte de cobertura:
echo   start storage\app\test-coverage\index.html
echo.
echo 📁 Archivos importantes:
echo ========================
echo • Configuración: .env.testing
echo • Documentación: docs\TESTING_PERMISSIONS.md
echo • Reportes: storage\app\test-reports\
echo • Cobertura: storage\app\test-coverage\
echo.
echo ✅ ¡Listo para ejecutar pruebas de permisos!
echo.
pause
