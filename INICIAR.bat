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

REM Detectar IP local
for /f "tokens=2 delims=:" %%a in ('ipconfig ^| findstr /R "IPv4"') do (
    set IP=%%a
    goto :done
)
:done
set IP=%IP: =%

echo [1/4] Detectando hostname e IP...
echo       Hostname: %HOSTNAME%
echo       IP Local:  %IP%
echo.

echo [2/4] Deteniendo procesos anteriores...
taskkill /F /IM node.exe >nul 2>&1
taskkill /F /IM php.exe >nul 2>&1
timeout /t 2 /nobreak >nul
echo       Procesos detenidos
echo.

echo [3/4] Limpiando cache...
php artisan config:clear >nul 2>&1
echo       Cache limpiada
echo.

echo ========================================
echo   INICIANDO SERVICIOS (Terminal unica)
echo ========================================
echo.
echo Ejecutando 3 procesos simultaneamente:
echo   1. Vite Dev Server (compilacion en tiempo real)
echo   2. Laravel Reverb (WebSocket en puerto 8080)
echo   3. Laravel Server (HTTP en puerto 8000)
echo.
echo ACCESO:
echo   - Localhost:     http://localhost:8000
echo   - IP Local:      http://%IP%:8000
echo   - WebSocket:     ws://%IP%:8080
echo.
echo Presiona Ctrl+C para detener todos los servicios
echo.

npm run start
echo ========================================
echo   SERVIDOR DETENIDO
echo ========================================
pause
