@echo off
REM Cambiar al directorio del proyecto Laravel
cd /d c:\xampp\htdocs\mundoindustrial

echo.
echo ========================================
echo   CONFIGURACION AUTOMATICA DE RED
echo ========================================
echo.

REM Obtener la dirección IP local
for /f "tokens=2 delims=:" %%i in ('ipconfig ^| findstr "Dirección IPv4"') do set IP=%%i
REM Limpiar espacios en blanco
set IP=%IP:~1%

echo [1/3] Detectando IP local...
echo       IP detectada: %IP%
echo.

REM Verificar si existe el archivo .env
if not exist ".env" (
    echo [!] ADVERTENCIA: No se encontro el archivo .env
    echo     Copiando .env.example a .env...
    copy .env.example .env >nul
    echo     Archivo .env creado
    echo.
)

REM Leer IP actual del .env (si existe la variable)
set CURRENT_IP=
for /f "tokens=2 delims==" %%a in ('findstr /C:"REVERB_HOST=" .env 2^>nul') do set CURRENT_IP=%%a

echo [2/3] Verificando configuracion del .env...

REM Comparar IPs y actualizar si es necesario
if "%CURRENT_IP%"=="%IP%" (
    echo       Configuracion correcta - IP: %IP%
    echo       No se requieren cambios
) else (
    if "%CURRENT_IP%"=="" (
        echo       Primera configuracion detectada
    ) else (
        echo       IP cambio de %CURRENT_IP% a %IP%
    )
    echo       Actualizando .env...
    
    powershell -Command "(Get-Content .env) -replace 'APP_URL=.*', 'APP_URL=http://%IP%:8000' | Set-Content .env" 2>nul
    powershell -Command "(Get-Content .env) -replace 'REVERB_HOST=.*', 'REVERB_HOST=%IP%' | Set-Content .env" 2>nul
    powershell -Command "(Get-Content .env) -replace 'VITE_REVERB_HOST=.*', 'VITE_REVERB_HOST=%IP%' | Set-Content .env" 2>nul
    
    echo       Archivo .env actualizado correctamente
)

echo.
echo [3/3] Iniciando servicios...
echo.

REM Iniciar todos los servicios en una sola ventana
start "Laravel Dev Server [%IP%]" cmd /k "cd /d c:\xampp\htdocs\mundoindustrial && set VITE_HMR_HOST=%IP% && echo ======================================== && echo   SERVICIOS LARAVEL INICIADOS && echo ======================================== && echo. && echo Iniciando Laravel Reverb (WebSockets)... && start /b php artisan reverb:start --host=0.0.0.0 --port=8080 && timeout /t 2 /nobreak >nul && echo Iniciando Vite Dev Server (HMR)... && start /b npm run dev -- --host 0.0.0.0 && timeout /t 3 /nobreak >nul && echo Iniciando Laravel Server... && echo. && echo ======================================== && echo   TODOS LOS SERVICIOS ACTIVOS && echo ======================================== && echo. && echo [✓] Laravel Server:  http://%IP%:8000 && echo [✓] Vite Dev Server:  http://%IP%:5173 && echo [✓] Reverb WebSocket: ws://%IP%:8080 && echo. && echo Acceso desde red local: http://%IP%:8000 && echo. && echo Presiona Ctrl+C para detener todos los servicios && echo ======================================== && echo. && php artisan serve --host=0.0.0.0 --port=8000"

REM Esperar a que se abra la ventana
timeout /t 2 /nobreak >nul

REM Abrir el navegador
start http://%IP%:8000

echo.
echo ========================================
echo   SERVICIOS INICIADOS CORRECTAMENTE
echo ========================================
echo.
echo Acceso Local:
echo   http://localhost:8000
echo.
echo Acceso desde Otros Computadores:
echo   http://%IP%:8000
echo.
echo Servicios Activos:
echo   [✓] Laravel Server  - Puerto 8000
echo   [✓] Vite Dev Server - Puerto 5173
echo   [✓] Reverb WebSocket - Puerto 8080
echo.
echo IMPORTANTE:
echo - Se abrio UNA ventana con todos los servicios
echo - Para detener, cierra esa ventana o presiona Ctrl+C
echo - Asegurate de que el firewall permita los puertos
echo.
echo Presiona cualquier tecla para cerrar esta ventana...
pause >nul
