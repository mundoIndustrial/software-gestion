@echo off
REM Cambiar al directorio del proyecto Laravel
cd /d c:\xampp\htdocs\mundoindustrial

REM Obtener la dirección IP local
for /f "tokens=2 delims=:" %%i in ('ipconfig ^| findstr "Dirección IPv4"') do set IP=%%i
REM Limpiar espacios en blanco
set IP=%IP:~1%

REM Iniciar Laravel Reverb (WebSockets) en una nueva ventana
start "Laravel Reverb" cmd /k "cd /d c:\xampp\htdocs\mundoindustrial && php artisan reverb:start"

REM Esperar 2 segundos para que Reverb inicie
timeout /t 2 /nobreak >nul

REM Iniciar Vite (npm run dev) en una nueva ventana
start "Vite Dev Server" cmd /k "cd /d c:\xampp\htdocs\mundoindustrial && npm run dev"

REM Esperar 2 segundos para que Vite inicie
timeout /t 2 /nobreak >nul

REM Iniciar el servidor Laravel en una nueva ventana
start "Laravel Server" cmd /k "cd /d c:\xampp\htdocs\mundoindustrial && php artisan serve --host=0.0.0.0 --port=8000"

REM Esperar 3 segundos para que el servidor inicie
timeout /t 3 /nobreak >nul

REM Abrir el navegador en localhost
start http://localhost:8000

REM Mostrar mensaje con la URL para compartir
echo.
echo ========================================
echo SERVIDORES INICIADOS CORRECTAMENTE
echo ========================================
echo.
echo [1] Laravel Reverb (WebSockets) - Ejecutandose
echo [2] Vite Dev Server (npm run dev) - Ejecutandose
echo [3] Laravel Server - http://localhost:8000
echo.
echo Accede localmente en: http://localhost:8000
echo Comparte esta URL en la red: http://%IP%:8000
echo.
echo IMPORTANTE: Se abrieron 3 ventanas de terminal.
echo Para detener todos los servicios, cierra las 3 ventanas.
echo.
echo Presiona cualquier tecla para cerrar esta ventana...
pause >nul
