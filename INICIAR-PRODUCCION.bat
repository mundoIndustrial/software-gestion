@echo off
cd /d "%~dp0"
title Mundo Industrial - Servidor de Produccion
color 0C

echo.
echo ========================================
echo   MUNDO INDUSTRIAL - PRODUCCION
echo ========================================
echo.

for /f "tokens=*" %%a in ('hostname') do set HOSTNAME=%%a

echo [1/4] Detectando hostname...
echo       Hostname: %HOSTNAME%
echo.

echo [2/4] Configurando variables de entorno...
echo       - Usando .env.production
echo       - APP_ENV=production
echo       - REVERB_HOST=sistemamundoindustrial.online
echo       - REVERB_PORT=443
echo       - REVERB_SCHEME=https
echo.

echo [3/4] Compilando assets optimizados...
call npm run build
echo       Assets compilados para produccion
echo.

echo [4/4] Iniciando servicios...
echo.
echo IMPORTANTE:
echo   - Asegutate que el servidor Nginx/Apache este corriendo
echo   - Reverb debe escuchar en puerto 8080 (reverse proxy a 443)
echo   - Laravel debe usar php-fpm o similar
echo.

echo Iniciando Reverb (WebSocket Server)...
php artisan reverb:start --host=0.0.0.0 --port=8080

echo.
echo ========================================
echo   SERVIDOR DE PRODUCCION
echo ========================================
echo.
echo URLs:
echo  - https://sistemamundoindustrial.online
echo  - WebSocket: wss://sistemamundoindustrial.online:443
echo.
echo Servicio Reverb:
echo  - Escuchando en: 0.0.0.0:8080
echo  - Proxy via Nginx/Apache a: :443
echo.
echo ========================================
echo.
pause
