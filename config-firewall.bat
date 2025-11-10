@echo off
echo ========================================
echo Configurando Firewall de Windows
echo ========================================
echo.
echo ⚠️ Este script requiere permisos de ADMINISTRADOR
echo.

REM Verificar si se está ejecutando como administrador
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo ❌ Error: Este script debe ejecutarse como ADMINISTRADOR
    echo.
    echo 📝 Instrucciones:
    echo    1. Click derecho en este archivo
    echo    2. Selecciona "Ejecutar como administrador"
    echo.
    pause
    exit /b 1
)

echo ✅ Ejecutando con permisos de administrador
echo.

echo 🔧 Configurando reglas del Firewall...
echo.

REM Eliminar reglas existentes si existen
netsh advfirewall firewall delete rule name="Laravel Server (Puerto 8000)" >nul 2>&1
netsh advfirewall firewall delete rule name="Laravel Reverb (Puerto 8080)" >nul 2>&1
netsh advfirewall firewall delete rule name="Vite Dev Server (Puerto 5173)" >nul 2>&1

echo 1️⃣ Agregando regla para Laravel Server (Puerto 8000)...
netsh advfirewall firewall add rule name="Laravel Server (Puerto 8000)" dir=in action=allow protocol=TCP localport=8000

echo 2️⃣ Agregando regla para Laravel Reverb (Puerto 8080)...
netsh advfirewall firewall add rule name="Laravel Reverb (Puerto 8080)" dir=in action=allow protocol=TCP localport=8080

echo 3️⃣ Agregando regla para Vite Dev Server (Puerto 5173)...
netsh advfirewall firewall add rule name="Vite Dev Server (Puerto 5173)" dir=in action=allow protocol=TCP localport=5173

echo.
echo ========================================
echo ✅ Firewall configurado correctamente
echo ========================================
echo.
echo 📋 Reglas agregadas:
echo    ✅ Puerto 8000 - Laravel Server
echo    ✅ Puerto 8080 - Laravel Reverb (WebSocket)
echo    ✅ Puerto 5173 - Vite Dev Server
echo.
echo 📝 Próximos pasos:
echo    1. Ejecuta: config-network.bat (para configurar .env)
echo    2. Ejecuta: start-dev-network.bat (para iniciar servicios)
echo    3. Accede desde otros PCs usando tu IP local
echo.

pause
