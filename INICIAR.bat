@echo off
title Mundo Industrial - Servidor de Desarrollo
color 0A

echo.
echo ========================================
echo   MUNDO INDUSTRIAL - SERVIDOR
echo ========================================
echo.

REM Obtener la IP local
for /f "tokens=2 delims=:" %%a in ('ipconfig ^| findstr /c:"IPv4"') do set IP=%%a
set IP=%IP:~1%

echo [1/6] Detectando IP local...
echo       Tu IP: %IP%
echo.

echo [2/6] Deteniendo procesos anteriores...
taskkill /F /IM node.exe >nul 2>&1
taskkill /F /IM php.exe >nul 2>&1
timeout /t 2 /nobreak >nul
echo       Procesos detenidos
echo.

echo [3/6] Configurando variables de entorno...
REM Actualizar VITE_HMR_HOST
findstr /C:"VITE_HMR_HOST" .env >nul
if %errorlevel% equ 0 (
    powershell -Command "(Get-Content .env) -replace '^VITE_HMR_HOST=.*', 'VITE_HMR_HOST=%IP%' | Set-Content .env.temp" >nul
    move /Y .env.temp .env >nul
) else (
    echo VITE_HMR_HOST=%IP% >> .env
)

REM Actualizar VITE_REVERB_HOST
findstr /C:"VITE_REVERB_HOST" .env >nul
if %errorlevel% equ 0 (
    powershell -Command "(Get-Content .env) -replace '^VITE_REVERB_HOST=.*', 'VITE_REVERB_HOST=%IP%' | Set-Content .env.temp" >nul
    move /Y .env.temp .env >nul
) else (
    echo VITE_REVERB_HOST=%IP% >> .env
)

REM Actualizar REVERB_HOST (importante para WebSocket)
findstr /C:"REVERB_HOST" .env >nul
if %errorlevel% equ 0 (
    powershell -Command "(Get-Content .env) -replace '^REVERB_HOST=.*', 'REVERB_HOST=%IP%' | Set-Content .env.temp" >nul
    move /Y .env.temp .env >nul
) else (
    echo REVERB_HOST=%IP% >> .env
)

REM Actualizar APP_URL
findstr /C:"APP_URL" .env >nul
if %errorlevel% equ 0 (
    powershell -Command "(Get-Content .env) -replace '^APP_URL=.*', 'APP_URL=http://%IP%:8000' | Set-Content .env.temp" >nul
    move /Y .env.temp .env >nul
) else (
    echo APP_URL=http://%IP%:8000 >> .env
)

echo       Variables configuradas:
echo       - VITE_HMR_HOST=%IP%
echo       - VITE_REVERB_HOST=%IP%
echo       - REVERB_HOST=%IP%
echo       - APP_URL=http://%IP%:8000
echo.

echo [4/6] Limpiando cache...
call php artisan config:clear >nul 2>&1
echo       Cache limpiada
echo.

echo [5/6] Compilando assets (esto puede tardar 10-15 segundos)...
call npm run build
echo       Assets compilados
echo.

echo [6/6] Iniciando servicios...
echo.

REM Iniciar Reverb en segundo plano
echo       - Laravel Reverb (WebSocket)...
start /B php artisan reverb:start --host=0.0.0.0 --port=8080 >nul 2>&1

REM Esperar 2 segundos
timeout /t 2 /nobreak >nul

REM Iniciar Laravel serve en segundo plano
echo       - Laravel Server (HTTP)...
start /B php artisan serve --host=0.0.0.0 --port=8000 >nul 2>&1

echo.
echo ========================================
echo   SERVIDOR INICIADO CORRECTAMENTE
echo ========================================
echo.
echo  Acceso LOCAL:     http://localhost:8000
echo  Acceso en RED:    http://%IP%:8000
echo.
echo  Estado: ACTIVO
echo  WebSocket: Puerto 8080
echo  HTTP: Puerto 8000
echo.
echo ========================================
echo.
echo  Presiona Ctrl+C para DETENER el servidor
echo.
echo ========================================
echo.

REM Mantener la ventana abierta y esperar
:loop
timeout /t 60 /nobreak >nul
goto loop
