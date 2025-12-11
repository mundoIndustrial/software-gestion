@echo off
title Mundo Industrial - Servidor de Desarrollo
color 0A

echo.
echo ========================================
echo   MUNDO INDUSTRIAL - SERVIDOR
echo ========================================
echo.

for /f "tokens=*" %%a in ('hostname') do set HOSTNAME=%%a

echo [1/6] Detectando hostname...
echo       Hostname: %HOSTNAME%
echo.

echo [2/6] Deteniendo procesos anteriores...
taskkill /F /IM node.exe >nul 2>&1
taskkill /F /IM php.exe >nul 2>&1
timeout /t 2 /nobreak >nul
echo       Procesos detenidos
echo.

echo [3/6] Configurando variables de entorno...
echo       Variables configuradas
echo.

echo [4/6] Limpiando cache...
php artisan config:clear >nul 2>&1
echo       Cache limpiada
echo.

echo [5/6] Compilando assets...
call npm run build
echo       Assets compilados
echo.

echo [6/6] Iniciando servicios...
echo.
echo       - Laravel Reverb (WebSocket)...
start /B php artisan reverb:start --host=0.0.0.0 --port=8080

timeout /t 2 /nobreak >nul

echo       - Queue Worker (Cotizaciones)...
start /B php artisan queue:work --queue=cotizaciones --verbose

timeout /t 2 /nobreak >nul

echo       - Laravel Server (HTTP)...
php artisan serve --host=0.0.0.0 --port=8000

echo.
echo ========================================
echo   SERVIDOR INICIADO
echo ========================================
echo.
echo  LOCAL:  http://localhost:8000
echo  RED:    http://%HOSTNAME%:8000
echo.
echo  WebSocket: Puerto 8080
echo  HTTP: Puerto 8000
echo  Queue Worker: Activo
echo.
echo ========================================
