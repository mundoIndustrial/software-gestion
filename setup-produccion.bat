@echo off
REM Script para configurar la aplicación en producción (servermi:8000)

echo.
echo ========================================
echo Configuración para Producción - servermi
echo ========================================
echo.

REM 1. Limpiar caché
echo [1/4] Limpiando caché...
php artisan cache:clear
php artisan config:clear
php artisan view:clear
echo ✓ Caché limpiado
echo.

REM 2. Compilar assets
echo [2/4] Compilando assets con Vite...
call npm run build
echo ✓ Assets compilados
echo.

REM 3. Migrar base de datos (opcional)
echo [3/4] ¿Deseas ejecutar migraciones? (S/N)
set /p migrate="Respuesta: "
if /i "%migrate%"=="S" (
    php artisan migrate --force
    echo ✓ Migraciones ejecutadas
) else (
    echo ⊘ Migraciones omitidas
)
echo.

REM 4. Instrucciones finales
echo [4/4] Configuración completada
echo.
echo ========================================
echo PRÓXIMOS PASOS:
echo ========================================
echo.
echo 1. Asegúrate de que .env tiene:
echo    APP_URL=http://servermi:8000
echo    VITE_HMR_HOST=servermi
echo.
echo 2. Inicia el servidor:
echo    php artisan serve --host=0.0.0.0 --port=8000
echo.
echo 3. Accede a:
echo    http://servermi:8000
echo.
echo ========================================
echo.
pause
