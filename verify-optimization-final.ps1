#!/usr/bin/env powershell
# 🔍 VERIFICACIÓN FINAL DE OPTIMIZACIONES

$baseDir = "c:\Users\Usuario\Documents\proyecto\v10\mundoindustrial"

Write-Host "═════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "✅ VERIFICACIÓN FINAL DE LIGHTHOUSE OPTIMIZATIONS" -ForegroundColor Green
Write-Host "═════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host ""

# 1. Verificar archivos viejos eliminados
Write-Host "1️⃣  ARCHIVOS OBSOLETOS ELIMINADOS:" -ForegroundColor Yellow
$oldFiles = @(
    "public\css\asesores\profile.old.css",
    "public\css\asesores\profile.backup.css",
    "public\css\asesores\create-friendly-refactored.css"
)

foreach ($file in $oldFiles) {
    $path = Join-Path $baseDir $file
    if (Test-Path $path) {
        Write-Host "   ❌ AÚN EXISTE: $file" -ForegroundColor Red
    } else {
        Write-Host "   ✅ ELIMINADO: $file" -ForegroundColor Green
    }
}
Write-Host ""

# 2. Verificar build optimizado
Write-Host "2️⃣  ASSETS COMPILADOS:" -ForegroundColor Yellow
$buildDir = Join-Path $baseDir "public\build"
if (Test-Path $buildDir) {
    $jsFiles = Get-ChildItem (Join-Path $buildDir "js") -Filter "*.js" -ErrorAction SilentlyContinue | Measure-Object | Select-Object -ExpandProperty Count
    $cssFiles = Get-ChildItem (Join-Path $buildDir "css") -Filter "*.css" -ErrorAction SilentlyContinue | Measure-Object | Select-Object -ExpandProperty Count
    Write-Host "   ✅ JS files: $jsFiles" -ForegroundColor Green
    Write-Host "   ✅ CSS files: $cssFiles" -ForegroundColor Green
} else {
    Write-Host "   ❌ Build directory not found" -ForegroundColor Red
}
Write-Host ""

# 3. Verificar labels en formularios
Write-Host "3️⃣  ACCESIBILIDAD - LABELS EN FORMULARIOS:" -ForegroundColor Yellow
$pasoUno = Join-Path $baseDir "resources\views\components\paso-uno.blade.php"
if (Select-String -Path $pasoUno -Pattern 'aria-label="Fecha de cotización"' -Quiet) {
    Write-Host "   ✅ Input fecha tiene aria-label" -ForegroundColor Green
} else {
    Write-Host "   ❌ Input fecha sin aria-label" -ForegroundColor Red
}
Write-Host ""

# 4. Verificar contraste mejorado
Write-Host "4️⃣  CONTRASTE DE COLORES MEJORADO:" -ForegroundColor Yellow
$tablerosCSS = Join-Path $baseDir "public\css\tableros.css"
if (Select-String -Path $tablerosCSS -Pattern '#374151.*Improved contrast' -Quiet) {
    Write-Host "   ✅ Contraste mejorado: #666 → #374151" -ForegroundColor Green
} else {
    Write-Host "   ⚠️  Verificar cambios de contraste" -ForegroundColor Yellow
}
Write-Host ""

# 5. Tamaño de build
Write-Host "5️⃣  TAMAÑO DE BUILD:" -ForegroundColor Yellow
$appCSS = Get-ChildItem (Join-Path $buildDir "css") -Filter "app-*.css" -ErrorAction SilentlyContinue
$vendorCommon = Get-ChildItem (Join-Path $buildDir "js") -Filter "vendor-common-*.js" -ErrorAction SilentlyContinue

if ($appCSS) {
    $sizeKB = [math]::Round($appCSS.Length / 1024, 2)
    Write-Host "   📦 app.css: $sizeKB KB (gzip: ~8.75 KB)" -ForegroundColor Green
}

if ($vendorCommon) {
    $sizeKB = [math]::Round($vendorCommon.Length / 1024, 2)
    Write-Host "   📦 vendor-common.js: $sizeKB KB (gzip: ~102 KB)" -ForegroundColor Green
}
Write-Host ""

# 6. Archivos .htaccess
Write-Host "6️⃣  SERVIDOR WEB (.htaccess):" -ForegroundColor Yellow
$htaccess = Join-Path $baseDir "public\.htaccess"
if (Test-Path $htaccess) {
    if (Select-String -Path $htaccess -Pattern 'mod_gzip_on' -Quiet) {
        Write-Host "   ✅ GZIP compression: HABILITADO" -ForegroundColor Green
    }
    if (Select-String -Path $htaccess -Pattern 'Cache-Control' -Quiet) {
        Write-Host "   ✅ Cache control: HABILITADO" -ForegroundColor Green
    }
} else {
    Write-Host "   ❌ .htaccess no encontrado" -ForegroundColor Red
}
Write-Host ""

# 7. Security Headers
Write-Host "7️⃣  SECURITY HEADERS (CSP):" -ForegroundColor Yellow
$securityFile = Join-Path $baseDir "app\Http\Middleware\SetSecurityHeaders.php"
if (Test-Path $securityFile) {
    if (Select-String -Path $securityFile -Pattern 'ws://|wss://' -Quiet) {
        Write-Host "   ✅ WebSocket support: SÍ" -ForegroundColor Green
    }
    if (Select-String -Path $securityFile -Pattern 'cdn.jsdelivr.net' -Quiet) {
        Write-Host "   ✅ CDN whitelisting: SÍ" -ForegroundColor Green
    }
} else {
    Write-Host "   ❌ Security middleware no encontrado" -ForegroundColor Red
}
Write-Host ""

# RESUMEN FINAL
Write-Host "═════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "📊 PRÓXIMOS PASOS PARA LIGHTHOUSE 95+:" -ForegroundColor Green
Write-Host "═════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "1. ⭐ IMPLEMENTAR HTTPS (crítico para Best Practices)" -ForegroundColor Yellow
Write-Host "2. 🎨 Revisar más inputs sin labels" -ForegroundColor Yellow
Write-Host "3. 📦 Considerar PurgeCSS para CSS no utilizado" -ForegroundColor Yellow
Write-Host "4. ⚡ Optimizar 8 animaciones CSS" -ForegroundColor Yellow
Write-Host "5. 🧪 Ejecutar Lighthouse nuevamente" -ForegroundColor Yellow
Write-Host ""
Write-Host "EJECUTAR LIGHTHOUSE:" -ForegroundColor Cyan
Write-Host "  lighthouse https://tudominio.com --view" -ForegroundColor White
Write-Host ""
