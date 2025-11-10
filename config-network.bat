@echo off
echo ========================================
echo Configurando para RED LOCAL
echo ========================================
echo.

REM Obtener la IP local
for /f "tokens=2 delims=:" %%a in ('ipconfig ^| findstr /c:"IPv4"') do set IP=%%a
set IP=%IP:~1%

echo 📡 Tu IP local detectada: %IP%
echo.

REM Verificar si existe el archivo .env
if not exist .env (
    echo ❌ Error: No se encontró el archivo .env
    echo    Copia .env.example a .env primero
    pause
    exit /b 1
)

echo 🔧 Configurando variables de entorno...
echo.

REM Crear archivo temporal con las nuevas configuraciones
echo # Configuración para RED LOCAL > .env.network
echo APP_URL=http://%IP%:8000 >> .env.network
echo. >> .env.network
echo VITE_REVERB_HOST=%IP% >> .env.network
echo VITE_HMR_HOST=%IP% >> .env.network
echo REVERB_HOST=%IP% >> .env.network
echo REVERB_SERVER_HOST=%IP% >> .env.network
echo. >> .env.network

echo ✅ Configuración generada en .env.network
echo.
echo 📋 Configuración sugerida:
type .env.network
echo.
echo.

REM Preguntar si quiere aplicar los cambios
set /p APPLY="¿Deseas aplicar estos cambios al archivo .env? (S/N): "

if /i "%APPLY%"=="S" (
    echo.
    echo 📝 Aplicando cambios...
    
    REM Hacer backup del .env actual
    copy .env .env.backup >nul
    echo ✅ Backup creado: .env.backup
    
    REM Actualizar las variables en .env
    powershell -Command "(Get-Content .env) -replace '^APP_URL=.*', 'APP_URL=http://%IP%:8000' | Set-Content .env.temp"
    powershell -Command "(Get-Content .env.temp) -replace '^VITE_REVERB_HOST=.*', 'VITE_REVERB_HOST=%IP%' | Set-Content .env.temp2"
    powershell -Command "(Get-Content .env.temp2) -replace '^VITE_HMR_HOST=.*', 'VITE_HMR_HOST=%IP%' | Set-Content .env.temp3"
    powershell -Command "(Get-Content .env.temp3) -replace '^REVERB_HOST=.*', 'REVERB_HOST=%IP%' | Set-Content .env.temp4"
    powershell -Command "(Get-Content .env.temp4) -replace '^REVERB_SERVER_HOST=.*', 'REVERB_SERVER_HOST=%IP%' | Set-Content .env"
    
    REM Limpiar archivos temporales
    del .env.temp .env.temp2 .env.temp3 .env.temp4 .env.network >nul 2>&1
    
    echo ✅ Configuración aplicada correctamente
    echo.
    echo 🔄 Limpiando caché...
    call php artisan config:clear
    call npm run build
    
    echo.
    echo ========================================
    echo ✅ CONFIGURACIÓN COMPLETADA
    echo ========================================
    echo.
    echo 📝 Próximos pasos:
    echo    1. Ejecuta: start-dev-network.bat
    echo    2. Configura el Firewall para permitir puertos 8000 y 8080
    echo    3. Accede desde otros PCs: http://%IP%:8000
    echo.
) else (
    echo.
    echo ❌ Cambios NO aplicados
    echo    Puedes aplicarlos manualmente editando el archivo .env
    echo.
    del .env.network >nul 2>&1
)

pause
