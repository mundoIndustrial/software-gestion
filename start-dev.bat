@echo off
echo Iniciando servicios de desarrollo...
echo.

REM Iniciar npm run dev en una nueva ventana
start "NPM Dev Server" cmd /k "npm run dev"

REM Esperar 2 segundos
timeout /t 2 /nobreak >nul

REM Iniciar Reverb en una nueva ventana
start "Laravel Reverb" cmd /k "php artisan reverb:start"

REM Esperar 2 segundos
timeout /t 2 /nobreak >nul

REM Iniciar Laravel serve en una nueva ventana
start "Laravel Server" cmd /k "php artisan serve"

echo.
echo ✅ Todos los servicios han sido iniciados en ventanas separadas:
echo    - NPM Dev Server (Vite)
echo    - Laravel Reverb (WebSocket)
echo    - Laravel Server (HTTP)
echo.
echo Para detener todos los servicios, cierra cada ventana o presiona Ctrl+C en cada una.
pause
