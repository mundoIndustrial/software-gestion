@echo off
REM Solicitar permisos de administrador
net session >nul 2>&1
if %errorlevel% neq 0 (
    powershell -Command "Start-Process cmd -ArgumentList '/c \"%~f0\"' -Verb RunAs" >nul 2>&1
    exit /b
)

chcp 65001 >nul
title Abrir Puertos - Windows Firewall
color 0A

echo.
echo ========================================
echo   ABRIR PUERTOS - MUNDO INDUSTRIAL
echo ========================================
echo.

echo [1/3] Abriendo puerto 5173 (Vite Dev Server)...
netsh advfirewall firewall add rule name="Vite_5173" dir=in action=allow protocol=tcp localport=5173 >nul 2>&1
if %errorlevel% equ 0 (
    echo       OK - Puerto 5173 abierto
) else (
    echo       Ya estaba abierto o error
)

echo [2/3] Abriendo puerto 8000 (Web Server)...
netsh advfirewall firewall add rule name="WebServer_8000" dir=in action=allow protocol=tcp localport=8000 >nul 2>&1
if %errorlevel% equ 0 (
    echo       OK - Puerto 8000 abierto
) else (
    echo       Ya estaba abierto o error
)

echo [3/3] Abriendo puerto 8080 (WebSocket)...
netsh advfirewall firewall add rule name="WebSocket_8080" dir=in action=allow protocol=tcp localport=8080 >nul 2>&1
if %errorlevel% equ 0 (
    echo       OK - Puerto 8080 abierto
) else (
    echo       Ya estaba abierto o error
)

echo.
echo ========================================
echo.
echo Los puertos estan abiertos.
echo.
echo Ahora puedes acceder desde otros dispositivos:
echo   http://192.168.0.171:8000
echo.
pause
