@echo off
echo ========================================
echo 🔧 Corrigiendo caché de Vite
echo ========================================
echo.

echo 1️⃣ Deteniendo procesos de npm/node...
taskkill /F /IM node.exe 2>nul
timeout /t 2 /nobreak >nul

echo.
echo 2️⃣ Limpiando caché de npm...
call npm cache clean --force

echo.
echo 3️⃣ Eliminando node_modules y package-lock.json...
if exist node_modules rmdir /s /q node_modules
if exist package-lock.json del /f /q package-lock.json

echo.
echo 4️⃣ Reinstalando dependencias...
call npm install

echo.
echo 5️⃣ Limpiando configuración de Laravel...
php artisan config:clear
php artisan cache:clear

echo.
echo 6️⃣ Reconstruyendo assets con Vite...
call npm run build

echo.
echo ========================================
echo ✅ Proceso completado
echo ========================================
echo.
echo 📝 Próximos pasos:
echo    1. Reinicia el servidor Reverb: php artisan reverb:start
echo    2. Inicia el servidor de desarrollo: php artisan serve
echo    3. Recarga la página en el navegador
echo.
pause
