# Script de verificación para Google OAuth (PowerShell)
# Ejecutar con: .\verificar-google-oauth.ps1

Write-Host "================================================" -ForegroundColor Cyan
Write-Host "VERIFICAR CONFIGURACION DE GOOGLE OAUTH" -ForegroundColor Cyan
Write-Host "================================================" -ForegroundColor Cyan
Write-Host ""

# 1. Verificar que .env tenga las variables
Write-Host "1. Verificando variables en .env..." -ForegroundColor Yellow
$envContent = Get-Content .env -Raw

if ($envContent -match "GOOGLE_CLIENT_ID=") {
    $clientId = ($envContent | Select-String "GOOGLE_CLIENT_ID=(.*)" | ForEach-Object { $_.Matches.Groups[1].Value })
    Write-Host "✓ GOOGLE_CLIENT_ID: $($clientId.Substring(0, [Math]::Min(20, $clientId.Length)))..." -ForegroundColor Green
} else {
    Write-Host "✗ GOOGLE_CLIENT_ID no encontrado" -ForegroundColor Red
}

if ($envContent -match "GOOGLE_CLIENT_SECRET=") {
    Write-Host "✓ GOOGLE_CLIENT_SECRET: configurado" -ForegroundColor Green
} else {
    Write-Host "✗ GOOGLE_CLIENT_SECRET no encontrado" -ForegroundColor Red
}

if ($envContent -match "GOOGLE_REDIRECT_URI=") {
    $redirect = ($envContent | Select-String "GOOGLE_REDIRECT_URI=(.*)" | ForEach-Object { $_.Matches.Groups[1].Value })
    Write-Host "✓ GOOGLE_REDIRECT_URI: $redirect" -ForegroundColor Green
} else {
    Write-Host "✗ GOOGLE_REDIRECT_URI no encontrado" -ForegroundColor Red
}

Write-Host ""

# 2. Verificar que la ruta exista
Write-Host "2. Verificando rutas de Google OAuth..." -ForegroundColor Yellow
$routeContent = Get-Content routes/auth.php -Raw
if ($routeContent -match "auth/google") {
    Write-Host "✓ Rutas de Google OAuth encontradas" -ForegroundColor Green
} else {
    Write-Host "✗ Rutas de Google OAuth no encontradas" -ForegroundColor Red
}

Write-Host ""

# 3. Verificar que Socialite esté instalado
Write-Host "3. Verificando instalación de Socialite..." -ForegroundColor Yellow
$composerContent = Get-Content composer.json -Raw
if ($composerContent -match "laravel/socialite") {
    Write-Host "✓ Socialite está en composer.json" -ForegroundColor Green
} else {
    Write-Host "✗ Socialite no está instalado" -ForegroundColor Red
}

Write-Host ""

# 4. Verificar config/socialite.php
Write-Host "4. Verificando config/socialite.php..." -ForegroundColor Yellow
if (Test-Path "config/socialite.php") {
    Write-Host "✓ Archivo config/socialite.php existe" -ForegroundColor Green
} else {
    Write-Host "✗ config/socialite.php no existe" -ForegroundColor Red
}

Write-Host ""

# 5. Verificar que google_id está en User model
Write-Host "5. Verificando modelo User..." -ForegroundColor Yellow
$userContent = Get-Content app/Models/User.php -Raw
if ($userContent -match "'google_id'") {
    Write-Host "✓ google_id está en User.\$fillable" -ForegroundColor Green
} else {
    Write-Host "✗ google_id NO está en User.\$fillable" -ForegroundColor Red
}

Write-Host ""

# 6. Limpiar caché de Laravel
Write-Host "6. Limpiando caché de Laravel..." -ForegroundColor Yellow
php artisan config:clear
php artisan cache:clear
Write-Host "✓ Caché limpiado" -ForegroundColor Green

Write-Host ""
Write-Host "================================================" -ForegroundColor Green
Write-Host "VERIFICACION COMPLETADA" -ForegroundColor Green
Write-Host "================================================" -ForegroundColor Green
Write-Host ""
Write-Host "Próximos pasos:" -ForegroundColor Cyan
Write-Host "1. Abre Google Cloud Console: https://console.cloud.google.com"
Write-Host "2. Crea nuevas credenciales OAuth"
Write-Host "3. Copia el Client ID y Client Secret"
Write-Host "4. Actualiza el archivo .env con los nuevos valores"
Write-Host "5. Ejecuta: php artisan config:clear"
Write-Host "6. Prueba en: http://localhost:8000/login"
Write-Host ""
