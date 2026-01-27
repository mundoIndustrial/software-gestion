@echo off
cd /d "%~dp0"
title Mundo Industrial - Acceso Remoto
color 0B

echo.
echo ========================================
echo   MUNDO INDUSTRIAL - ACCESO REMOTO
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

echo [1/3] Detectando IP del servidor...
echo       Hostname: %HOSTNAME%
echo       IP Local:  %IP%
echo.

echo [2/3] Información de conexión...
echo.

echo ========================================
echo   ACCESO DESDE OTROS DISPOSITIVOS
echo ========================================
echo.
echo Usa estas URL en tu navegador desde
echo cualquier dispositivo en la misma red:
echo.
echo ACCESO:
echo   - Localhost:     http://localhost:8000
echo   - IP Local:      http://%IP%:8000
echo   - Tablet/Móvil:  http://%IP%:8000
echo   - WebSocket:     ws://%IP%:8080
echo.

echo [3/3] Instrucciones:
echo.
echo 1. Asegúrate que el servidor ejecuta INICIAR.bat
echo 2. Abre un navegador en otro dispositivo
echo 3. Ingresa: http://%IP%:8000
echo.
echo Presiona Ctrl+C en el servidor para detener
echo.
pause
