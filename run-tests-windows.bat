@echo off
REM 🧪 Script para ejecutar pruebas de permisos en Windows
REM ======================================================

setlocal enabledelayedexpansion

echo 🧪 Ejecutando pruebas de permisos en Windows...

REM Verificar argumentos
set TEST_TYPE=%1
if "%TEST_TYPE%"=="" set TEST_TYPE=all

echo 📋 Tipo de prueba: %TEST_TYPE%

REM Preparar entorno
echo 🔧 Preparando entorno de testing...
herd php artisan migrate:fresh --env=testing --force
if %errorlevel% neq 0 (
    echo ❌ Error preparando base de datos
    pause
    exit /b 1
)

herd php artisan db:seed --class=TestPermissionsSeeder --env=testing
echo ✅ Entorno preparado

REM Ejecutar pruebas según el tipo
if "%TEST_TYPE%"=="all" (
    echo 🚀 Ejecutando todas las pruebas de permisos...
    herd php artisan test:permissions
) else if "%TEST_TYPE%"=="coverage" (
    echo 📊 Ejecutando pruebas con cobertura...
    herd php artisan test:permissions --coverage
) else if "%TEST_TYPE%"=="permissions" (
    echo 👥 Ejecutando pruebas de permisos de usuario...
    vendor\bin\pest tests\Feature\PestUserPermissionsTest.php --verbose
) else if "%TEST_TYPE%"=="dashboards" (
    echo 📊 Ejecutando pruebas de acceso a dashboards...
    vendor\bin\pest tests\Feature\DashboardAccessTest.php --verbose
) else if "%TEST_TYPE%"=="middleware" (
    echo 🛡️ Ejecutando pruebas de middleware...
    vendor\bin\pest tests\Feature\MiddlewareTest.php --verbose
) else if "%TEST_TYPE%"=="security" (
    echo 🔒 Ejecutando pruebas de seguridad...
    vendor\bin\pest tests\Feature\SecurityTest.php --verbose
) else if "%TEST_TYPE%"=="suite" (
    echo 📦 Ejecutando todas las pruebas de permisos...
    vendor\bin\pest tests\Feature\*PermissionsTest.php --verbose
) else (
    echo ❌ Tipo de prueba no reconocido: %TEST_TYPE%
    echo.
    echo Tipos disponibles:
    echo   all        - Todas las pruebas
    echo   coverage   - Con reporte de cobertura
    echo   permissions - Solo pruebas de permisos de usuario
    echo   dashboards - Solo pruebas de dashboards
    echo   middleware - Solo pruebas de middleware
    echo   security   - Solo pruebas de seguridad
    echo   suite      - Suite completa de permisos
    echo.
    echo Ejemplo: run-tests-windows.bat coverage
    pause
    exit /b 1
)

if %errorlevel% equ 0 (
    echo.
    echo ✅ ¡Pruebas completadas exitosamente!

    if "%TEST_TYPE%"=="coverage" (
        echo 📊 Reporte de cobertura disponible en:
        echo    storage\app\test-coverage\index.html
        echo.
        set /p OPEN_COVERAGE="¿Abrir reporte de cobertura? (s/n): "
        if /i "!OPEN_COVERAGE!"=="s" (
            start storage\app\test-coverage\index.html
        )
    )
) else (
    echo.
    echo ❌ Algunas pruebas fallaron
    echo 📋 Revisa la salida anterior para más detalles
)

echo.
pause
