@echo off
echo ========================================
echo Solucionando Error CORS en Red
echo ========================================
echo.

REM Obtener la IP local
for /f "tokens=2 delims=:" %%a in ('ipconfig ^| findstr /c:"IPv4"') do set IP=%%a
set IP=%IP:~1%

echo 📡 Tu IP local: %IP%
echo.

echo 🔧 Paso 1: Configurando .env...
echo.

REM Verificar si existe VITE_HMR_HOST en .env
findstr /C:"VITE_HMR_HOST" .env >nul
if %errorlevel% equ 0 (
    echo ✅ VITE_HMR_HOST ya existe, actualizando...
    powershell -Command "(Get-Content .env) -replace '^VITE_HMR_HOST=.*', 'VITE_HMR_HOST=%IP%' | Set-Content .env.temp"
    move /Y .env.temp .env >nul
) else (
    echo ➕ Agregando VITE_HMR_HOST al .env...
    echo VITE_HMR_HOST=%IP% >> .env
)

echo.
echo 🔧 Paso 2: Deteniendo servicios...
taskkill /F /IM node.exe >nul 2>&1

echo.
echo 🔧 Paso 3: Limpiando caché...
call php artisan config:clear >nul

echo.
echo 🔧 Paso 4: Reconstruyendo assets...
call npm run build

echo.
echo 🔧 Paso 5: Reiniciando servicios...
echo.

REM Iniciar npm run dev con host
start "NPM Dev Server" cmd /k "npm run dev -- --host"

REM Esperar 3 segundos
timeout /t 3 /nobreak >nul

REM Iniciar Reverb
start "Laravel Reverb" cmd /k "php artisan reverb:start --host=0.0.0.0 --port=8080"

REM Esperar 2 segundos
timeout /t 2 /nobreak >nul

REM Iniciar Laravel serve
start "Laravel Server" cmd /k "php artisan serve --host=0.0.0.0 --port=8000"

echo.
echo ========================================
echo ✅ CORS Solucionado
echo ========================================
echo.
echo 📝 Configuración aplicada:
echo    VITE_HMR_HOST=%IP%
echo.
echo 🌐 Acceso desde otros PCs:
echo    http://%IP%:8000
echo.
echo ⚠️ IMPORTANTE:
echo    1. Espera 10 segundos a que Vite compile
echo    2. Recarga la página en el navegador (Ctrl + F5)
echo    3. Verifica que NO aparezcan errores CORS
echo.
pause
