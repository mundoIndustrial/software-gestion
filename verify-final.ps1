# VERIFICACION FINAL - LIGHTHOUSE OPTIMIZATIONS
# ============================================

$baseDir = "c:\Users\Usuario\Documents\proyecto\v10\mundoindustrial"

Write-Host "=== VERIFICACION FINAL ===" -ForegroundColor Cyan
Write-Host ""

# 1. Archivos viejos
Write-Host "1. ARCHIVOS OBSOLETOS:" -ForegroundColor Yellow
$files = @("profile.old.css", "profile.backup.css")
foreach ($f in $files) {
    $path = "$baseDir\public\css\asesores\$f"
    if (Test-Path $path) {
        Write-Host "  EXISTE: $f" -ForegroundColor Red
    } else {
        Write-Host "  ELIMINADO: $f" -ForegroundColor Green
    }
}

# 2. Assets compilados
Write-Host "`n2. BUILD OPTIMIZADO:" -ForegroundColor Yellow
$jsCount = (Get-ChildItem "$baseDir\public\build\js" -Filter "*.js").Count
$cssCount = (Get-ChildItem "$baseDir\public\build\css" -Filter "*.css").Count
Write-Host "  JS files: $jsCount" -ForegroundColor Green
Write-Host "  CSS files: $cssCount" -ForegroundColor Green

# 3. Labels accesibles
Write-Host "`n3. ACCESIBILIDAD:" -ForegroundColor Yellow
$content = Get-Content "$baseDir\resources\views\components\paso-uno.blade.php" -Raw
if ($content -contains 'aria-label="Fecha de cotización"') {
    Write-Host "  fecha input: TIENE LABEL" -ForegroundColor Green
} else {
    Write-Host "  fecha input: VERIFICAR" -ForegroundColor Yellow
}

# 4. Contraste
Write-Host "`n4. CONTRASTE MEJORADO:" -ForegroundColor Yellow
$css = Get-Content "$baseDir\public\css\tableros.css" -Raw
if ($css -match '#374151.*Improved') {
    Write-Host "  Colores: MEJORADOS #666 -> #374151" -ForegroundColor Green
}

Write-Host "`n=== PROXIMOS PASOS ===" -ForegroundColor Cyan
Write-Host "1. IMPLEMENTAR HTTPS (crítico)" -ForegroundColor Yellow
Write-Host "2. Revisar más labels en formularios" -ForegroundColor Yellow  
Write-Host "3. Ejecutar Lighthouse de nuevo" -ForegroundColor Yellow
Write-Host ""
