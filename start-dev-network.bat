@echo off
echo ========================================
echo Iniciando servicios para RED LOCAL
echo ========================================
echo.

REM Obtener la IP local
for /f "tokens=2 delims=:" %%a in ('ipconfig ^| findstr /c:"IPv4"') do set IP=%%a
set IP=%IP:~1%

echo 📡 Tu IP local es: %IP%
echo.

REM Iniciar npm run dev en una nueva ventana
start "NPM Dev Server" cmd /k "npm run dev -- --host"

REM Esperar 3 segundos
timeout /t 3 /nobreak >nul

REM Iniciar Reverb en una nueva ventana (escuchando en 0.0.0.0)
start "Laravel Reverb" cmd /k "php artisan reverb:start --host=0.0.0.0 --port=8080"

REM Esperar 3 segundos
timeout /t 3 /nobreak >nul

REM Iniciar Laravel serve en una nueva ventana (escuchando en 0.0.0.0)
start "Laravel Server" cmd /k "php artisan serve --host=0.0.0.0 --port=8000"

echo.
echo ========================================
echo ✅ Servicios iniciados para RED LOCAL
echo ========================================
echo.
echo 📋 Servicios corriendo:
echo    - NPM Dev Server (Vite)
echo    - Laravel Reverb (WebSocket) en puerto 8080
echo    - Laravel Server (HTTP) en puerto 8000
echo.
echo 🌐 Acceso desde otros computadores:
echo    http://%IP%:8000
echo.
echo 📝 Instrucciones:
echo    1. Asegúrate de que el Firewall permita conexiones en los puertos 8000 y 8080
echo    2. Los otros computadores deben estar en la misma red
echo    3. Usa la URL: http://%IP%:8000
echo.
echo ⚠️ IMPORTANTE: Configura el .env con la IP correcta
echo    VITE_REVERB_HOST=%IP%
echo    APP_URL=http://%IP%:8000
echo.
pause
