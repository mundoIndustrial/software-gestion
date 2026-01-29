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

echo [1/6] Detectando hostname e IP...
echo       Hostname: %HOSTNAME%
echo       IP Local:  %IP%
echo.

echo [2/6] Permitiendo conexiones remotas...
netsh advfirewall firewall add rule name="Vite_5173" dir=in action=allow protocol=tcp localport=5173 >nul 2>&1
netsh advfirewall firewall add rule name="WebServer_8000" dir=in action=allow protocol=tcp localport=8000 >nul 2>&1
netsh advfirewall firewall add rule name="WebSocket_8080" dir=in action=allow protocol=tcp localport=8080 >nul 2>&1
echo       Puertos configurados
echo.

echo [3/6] Creando archivo de configuracion temporal...
(
    echo const ip = '%IP%';
    echo export default {
    echo   viteHmrHost: ip,
    echo   viteHmrPort: 5173,
    echo   appUrl: 'http://' + ip + ':8000',
    echo };
) > vite-remote-config.js
echo       Archivo creado: vite-remote-config.js
echo.

echo [4/6] Actualizando .env y .env.development...
REM Usar PowerShell de forma más segura para actualizar archivos
powershell -NoProfile -Command "$ip = '%IP%'; $env_files = @('.env', '.env.development'); foreach ($file in $env_files) { if (Test-Path $file) { $content = Get-Content $file -Raw; $content = $content -replace 'VITE_HMR_HOST=.*', ('VITE_HMR_HOST=' + $ip); $content = $content -replace 'APP_URL=.*', ('APP_URL=http://' + $ip + ':8000'); [System.IO.File]::WriteAllText((Resolve-Path $file).Path, $content, [System.Text.UTF8Encoding]::new($false)); } } Write-Host '✓ Archivos .env actualizados con IP: %IP%'"
echo.

echo [5/6] Deteniendo procesos anteriores...
taskkill /F /IM node.exe >nul 2>&1
taskkill /F /IM php.exe >nul 2>&1
timeout /t 2 /nobreak >nul
echo       Procesos detenidos
echo.

echo [6/6] Limpiando cache...
php artisan config:clear >nul 2>&1
echo       Cache limpiada
echo.

echo ========================================
echo   INICIANDO SERVICIOS (Terminal unica)
echo ========================================
echo.
echo Ejecutando 3 procesos simultaneamente:
echo   1. Vite Dev Server (IP: %IP%:5173)
echo   2. Laravel Reverb (WebSocket: %IP%:8080)
echo   3. Laravel Server (HTTP: %IP%:8000)
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
