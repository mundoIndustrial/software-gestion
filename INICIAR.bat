@echo off
cd /d "%~dp0"
title Mundo Industrial - Servidor de Desarrollo
color 0A

echo.
echo ========================================
echo   MUNDO INDUSTRIAL - DESARROLLO
echo ========================================
echo.

for /f "tokens=*" %%a in ('hostname') do set HOSTNAME=%%a

echo [1/5] Detectando hostname...
echo       Hostname: %HOSTNAME%
echo.

echo [2/5] Deteniendo procesos anteriores...
taskkill /F /IM node.exe >nul 2>&1
taskkill /F /IM php.exe >nul 2>&1
timeout /t 2 /nobreak >nul
echo       Procesos detenidos
echo.

echo [3/5] Configurando variables de entorno...
echo       - Usando .env (DESARROLLO)
echo       - APP_ENV=local
echo       - REVERB_HOST=localhost
echo       - REVERB_PORT=8080
echo.

echo [4/5] Limpiando cache...
php artisan config:clear >nul 2>&1
echo       Cache limpiada
echo.

echo [5/5] Iniciando servicios...
echo.
echo Ejecutando 3 procesos simultaneamente:
echo   1. Vite Dev Server (compilacion en tiempo real)
echo   2. Laravel Reverb (WebSocket en puerto 8080)
echo   3. Laravel Server (HTTP en puerto 8000)
echo.

start "Vite Dev Server" cmd /k npm run dev
timeout /t 3 /nobreak >nul

start "Laravel Reverb" cmd /k php artisan reverb:start --host=0.0.0.0 --port=8080
timeout /t 3 /nobreak >nul

start "Laravel Server" cmd /k php artisan serve --host=0.0.0.0 --port=8000

echo.
echo ========================================
echo   SERVIDOR DE DESARROLLO INICIADO
echo ========================================
echo.
echo  URLs:
echo  - LOCAL:     http://localhost:8000
echo  - RED:       http://%HOSTNAME%:8000
echo  - Vite HMR:  http://localhost:5173
echo.
echo  Puertos:
echo  - HTTP:      8000
echo  - WebSocket: 8080
echo  - Vite:      5173
echo.
echo Consola de Debug:
echo  - Abre navegador: F12 (Consola)
echo  - Logs WebSocket automaticamente
echo.
echo ========================================
echo.
pause
