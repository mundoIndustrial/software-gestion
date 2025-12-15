@echo off
REM Script de sincronización de código con VPS
REM Uso: sync-vps.bat <ip_vps> <user>
REM Ejemplo: sync-vps.bat 192.168.1.100 root

setlocal enabledelayedexpansion

if "%~1"=="" (
    echo.
    echo Error: Debes proporcionar la IP del VPS
    echo Uso: sync-vps.bat ^<ip_vps^> [usuario]
    echo Ejemplo: sync-vps.bat 192.168.1.100 root
    echo.
    exit /b 1
)

set VPS_IP=%~1
set VPS_USER=%~2
if "!VPS_USER!"=="" set VPS_USER=root
set REMOTE_PATH=/var/www/sistemamundoindustrial

cls
color 0A
echo.
echo ========================================
echo   SINCRONIZAR CON VPS
echo ========================================
echo.
echo Datos:
echo   - IP VPS: %VPS_IP%
echo   - Usuario: %VPS_USER%
echo   - Ruta remota: %REMOTE_PATH%
echo.

REM Verificar que Git está instalado
git --version >nul 2>&1
if %errorlevel% neq 0 (
    echo Error: Git no está instalado o no está en el PATH
    exit /b 1
)

REM Verificar que SCP está disponible (comes con Git Bash)
where scp >nul 2>&1
if %errorlevel% neq 0 (
    echo Error: SCP no está disponible
    echo Instala Git for Windows si no lo tienes
    exit /b 1
)

echo [1/3] Subiendo archivos al VPS...
echo.

REM Usar SCP para subir archivos
REM Excluir directorios innecesarios
scp -r -B -P 22 ^
    -o "StrictHostKeyChecking=no" ^
    -o "UserKnownHostsFile=/dev/null" ^
    ^
    --exclude .env ^
    --exclude .git ^
    --exclude node_modules ^
    --exclude storage/logs ^
    --exclude bootstrap/cache ^
    --exclude .env.* ^
    --exclude *.log ^
    --exclude vendor ^
    ^
    . %VPS_USER%@%VPS_IP%:%REMOTE_PATH%

if %errorlevel% neq 0 (
    echo.
    echo Error: Falló la sincronización
    exit /b 1
)

echo.
echo [2/3] Ejecutando actualización en VPS...
echo.

REM Ejecutar script de actualización en el VPS
ssh -o "StrictHostKeyChecking=no" -o "UserKnownHostsFile=/dev/null" %VPS_USER%@%VPS_IP% ^
    "cd %REMOTE_PATH% && bash actualizar-vps.sh"

if %errorlevel% neq 0 (
    echo.
    echo Error: Falló la ejecución del script
    exit /b 1
)

echo.
echo [3/3] Verificando estado...
echo.

ssh -o "StrictHostKeyChecking=no" -o "UserKnownHostsFile=/dev/null" %VPS_USER%@%VPS_IP% ^
    "supervisorctl status"

echo.
echo ========================================
echo   SINCRONIZACIÓN COMPLETADA
echo ========================================
echo.
echo Sitio: https://sistemamundoindustrial.online
echo.
