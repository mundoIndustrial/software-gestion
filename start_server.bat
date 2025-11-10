@echo off
REM ========================================
REM   SERVIDOR DE DESARROLLO LARAVEL
REM   Configuracion automatica para red local
REM ========================================

REM Cambiar al directorio del proyecto Laravel
cd /d c:\xampp\htdocs\mundoindustrial

echo.
echo ========================================
echo   CONFIGURACION AUTOMATICA DE RED
echo ========================================
echo.

REM Obtener la dirección IP local
for /f "tokens=2 delims=:" %%i in ('ipconfig ^| findstr "Dirección IPv4"') do set IP=%%i
REM Limpiar espacios en blanco al inicio y al final
for /f "tokens=* delims= " %%a in ("%IP%") do set IP=%%a

echo [1/4] Detectando IP local...
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

echo [2/4] Verificando configuracion del .env...

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
echo [3/4] Iniciando servicios en orden...
echo.

REM ========================================
REM PASO 1: Iniciar Laravel Reverb (WebSockets)
REM ========================================
echo       [1/3] Iniciando Laravel Reverb (WebSockets)...
start "Reverb WebSocket [%IP%:8080]" cmd /k "cd /d c:\xampp\htdocs\mundoindustrial && echo ======================================== && echo   LARAVEL REVERB - WEBSOCKETS && echo ======================================== && echo. && echo Servidor WebSocket iniciado en: && echo   - Local:  ws://localhost:8080 && echo   - Red:    ws://%IP%:8080 && echo. && echo Estado: ACTIVO && echo. && php artisan reverb:start --host=0.0.0.0 --port=8080"

REM Esperar a que Reverb inicie completamente
timeout /t 3 /nobreak >nul

REM ========================================
REM PASO 2: Iniciar Laravel Server
REM ========================================
echo       [2/3] Iniciando Laravel Server...
start "Laravel Server [%IP%:8000]" cmd /k "cd /d c:\xampp\htdocs\mundoindustrial && echo ======================================== && echo   LARAVEL DEVELOPMENT SERVER && echo ======================================== && echo. && echo Servidor Laravel iniciado en: && echo   - Local:  http://localhost:8000 && echo   - Red:    http://%IP%:8000 && echo. && echo Estado: ACTIVO && echo. && php artisan serve --host=0.0.0.0 --port=8000"

REM Esperar a que Laravel Server inicie
timeout /t 3 /nobreak >nul

REM ========================================
REM PASO 3: Iniciar Vite Dev Server (HMR) - DESACTIVADO PARA PRUEBA
REM ========================================
echo       [3/3] Vite Dev Server DESACTIVADO (prueba)
REM start "Vite Dev Server [%IP%:5173]" cmd /k "cd /d c:\xampp\htdocs\mundoindustrial && set VITE_HMR_HOST=%IP% && echo ======================================== && echo   VITE DEVELOPMENT SERVER && echo ======================================== && echo. && echo Servidor Vite iniciado en: && echo   - Local:  http://localhost:5173 && echo   - Red:    http://%IP%:5173 && echo. && echo HMR Host: %IP% && echo Estado: ACTIVO && echo. && npm run dev -- --host 0.0.0.0"

REM Esperar a que Vite inicie completamente
REM timeout /t 5 /nobreak >nul

echo.
echo [4/4] Abriendo navegador...
echo.

REM Abrir el navegador con la IP local
start http://%IP%:8000

echo.
echo ========================================
echo   SERVICIOS INICIADOS CORRECTAMENTE
echo ========================================
echo.
echo ACCESO LOCAL (este computador):
echo   http://localhost:8000
echo.
echo ACCESO DESDE OTROS DISPOSITIVOS:
echo   http://%IP%:8000
echo.
echo ========================================
echo   SERVICIOS ACTIVOS
echo ========================================
echo.
echo [✓] Laravel Server    - http://%IP%:8000
echo [X] Vite Dev Server   - DESACTIVADO (prueba)
echo [✓] Reverb WebSocket  - ws://%IP%:8080
echo.
echo ========================================
echo   INSTRUCCIONES
echo ========================================
echo.
echo 1. Se abrieron 2 ventanas (Vite desactivado para prueba)
echo 2. NO cierres ninguna ventana mientras uses el software
echo 3. Para detener todo: cierra las 2 ventanas
echo.
echo 4. Desde otro dispositivo en la misma red:
echo    - Abre un navegador
echo    - Ve a: http://%IP%:8000
echo.
echo 5. Asegurate de que el firewall permita:
echo    - Puerto 8000 (Laravel)
echo    - Puerto 5173 (Vite)
echo    - Puerto 8080 (WebSockets)
echo.
echo ========================================
echo.
echo Presiona cualquier tecla para cerrar esta ventana...
echo (Los servicios seguiran corriendo)
echo.
pause >nul
