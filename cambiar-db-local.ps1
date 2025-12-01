# Script para cambiar la configuración de BD a localhost
# Uso: .\cambiar-db-local.ps1

$envPath = ".\.env"

if (-not (Test-Path $envPath)) {
    Write-Host "❌ Archivo .env no encontrado" -ForegroundColor Red
    exit 1
}

# Leer el archivo
$content = Get-Content $envPath -Raw

# Cambiar configuración de BD
$content = $content -replace 'DB_HOST=.*', 'DB_HOST=127.0.0.1'
$content = $content -replace 'DB_PORT=.*', 'DB_PORT=3306'
$content = $content -replace 'DB_DATABASE=.*', 'DB_DATABASE=mundo_bd'
$content = $content -replace 'DB_USERNAME=.*', 'DB_USERNAME=root'
$content = $content -replace 'DB_PASSWORD=.*', 'DB_PASSWORD='

# Guardar el archivo
Set-Content $envPath $content

Write-Host "✅ Configuración de BD actualizada a localhost" -ForegroundColor Green
Write-Host ""
Write-Host "Valores actuales:" -ForegroundColor Cyan
Write-Host "  DB_HOST=127.0.0.1"
Write-Host "  DB_PORT=3306"
Write-Host "  DB_DATABASE=mundo_bd"
Write-Host "  DB_USERNAME=root"
Write-Host "  DB_PASSWORD=(vacío)"
Write-Host ""
Write-Host "⚠️  Ajusta los valores si es necesario editando .env manualmente"
