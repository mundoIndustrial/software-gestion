@echo off
echo ========================================
echo   CONFIGURAR SSL PARA FIREBASE
echo ========================================
echo.

REM Crear carpeta C:\php si no existe
if not exist "C:\php" (
    echo Creando carpeta C:\php...
    mkdir "C:\php"
)

REM Verificar si el archivo cacert.pem está en Descargas
set "DOWNLOADS=%USERPROFILE%\Downloads\cacert.pem"

if exist "%DOWNLOADS%" (
    echo Encontrado cacert.pem en Descargas
    echo Copiando a C:\php\cacert.pem...
    copy "%DOWNLOADS%" "C:\php\cacert.pem"
    echo.
    echo ✓ Archivo copiado exitosamente!
    echo.
) else (
    echo.
    echo ❌ ERROR: No se encontró cacert.pem en Descargas
    echo.
    echo Por favor:
    echo 1. Ve a: https://curl.se/ca/cacert.pem
    echo 2. Guarda el archivo como cacert.pem en tu carpeta Descargas
    echo 3. Ejecuta este script de nuevo
    echo.
    pause
    exit /b 1
)

echo ========================================
echo   SIGUIENTE PASO: EDITAR php.ini
echo ========================================
echo.
echo Ahora necesitas editar tu archivo php.ini
echo.
echo Ejecuta este comando para encontrarlo:
echo   php --ini
echo.
echo Luego agrega estas líneas:
echo   curl.cainfo = "C:\php\cacert.pem"
echo   openssl.cafile="C:\php\cacert.pem"
echo.
echo ========================================
pause
