@echo off
REM ================================================================
REM Script para iniciar queue worker para procesar broadcasts
REM ================================================================

echo.
echo ============================================================
echo  🚀 INICIANDO QUEUE WORKER - Broadcasts
echo ============================================================
echo.

REM Cambiar a directorio del proyecto
cd /d "%~dp0"

REM Verificar que esté en el directorio correcto
if not exist "artisan" (
    echo ERROR: No se encontró artisan. Asegúrate de estar en la raíz del proyecto.
    pause
    exit /b 1
)

echo 📋 Configuración:
echo   - Driver: database
echo   - Queue: broadcasts
echo   - Sleep: 3 segundos
echo   - Tries: 1 (sin reintentos)
echo.

echo ⏳ Iniciando worker...
echo.

REM Ejecutar el worker
php artisan queue:work database --queue=broadcasts --sleep=3 --tries=1 --verbose

REM Si algo falla
if errorlevel 1 (
    echo.
    echo ❌ ERROR al ejecutar queue:work
    echo Verifica que:
    echo   1. PHP esté correctamente instalado
    echo   2. La BD esté disponible
    echo   3. Tengas permisos para escribir en storage/
    pause
    exit /b 1
)

pause
