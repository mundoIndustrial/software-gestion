@echo off
echo ========================================
echo 🧪 Prueba de Tiempo Real - Mundo Industrial
echo ========================================
echo.

REM Verificar si Reverb está corriendo
echo 📡 Verificando servidor Reverb...
netstat -ano | findstr ":8080" >nul
if %errorlevel% equ 0 (
    echo ✅ Reverb está corriendo en el puerto 8080
) else (
    echo ❌ Reverb NO está corriendo
    echo.
    echo Iniciando Reverb...
    start "Laravel Reverb" cmd /k "php artisan reverb:start"
    timeout /t 3 /nobreak >nul
)

echo.
echo 🔄 Emitiendo eventos de prueba...
php test-broadcast-realtime.php

echo.
echo ========================================
echo 📝 Instrucciones:
echo.
echo 1. Abre tu navegador en: http://127.0.0.1:8000/tableros/fullscreen?section=produccion
echo 2. Abre la consola del navegador (F12)
echo 3. Deberías ver mensajes de eventos recibidos
echo.
echo Si no ves los eventos:
echo - Verifica que Reverb esté corriendo (debe haber una ventana abierta)
echo - Recarga la página del navegador
echo - Revisa la consola del navegador para errores
echo ========================================
echo.
pause
