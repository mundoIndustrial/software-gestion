@echo off
echo ========================================
echo 🔧 Solución Rápida - Caché de Vite
echo ========================================
echo.

echo 1️⃣ Deteniendo npm dev server...
taskkill /F /IM node.exe 2>nul
timeout /t 2 /nobreak >nul

echo.
echo 2️⃣ Limpiando caché de Laravel...
php artisan config:clear
php artisan cache:clear

echo.
echo 3️⃣ Reconstruyendo assets...
call npm run build

echo.
echo 4️⃣ Reiniciando Reverb...
taskkill /F /FI "WINDOWTITLE eq Laravel Reverb*" 2>nul
timeout /t 2 /nobreak >nul
start "Laravel Reverb" cmd /k "php artisan reverb:start"

echo.
echo 5️⃣ Iniciando npm dev server...
start "NPM Dev Server" cmd /k "npm run dev"

echo.
echo ========================================
echo ✅ Proceso completado
echo ========================================
echo.
echo 📝 Instrucciones:
echo    1. Espera 5-10 segundos a que Vite compile
echo    2. Recarga la página en el navegador (Ctrl + F5)
echo    3. Abre la consola del navegador (F12)
echo    4. Verifica que VITE_REVERB_APP_KEY sea: mundo-industrial-key
echo.
pause
