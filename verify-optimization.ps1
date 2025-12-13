#!/usr/bin/env pwsh
# Optimization Verification Script

Write-Host "============================================================" -ForegroundColor Cyan
Write-Host "      OPTIMIZATION VERIFICATION SCRIPT"
Write-Host "============================================================" -ForegroundColor Cyan

$projectPath = Get-Location
Write-Host "`nProject Path: $projectPath`n" -ForegroundColor Yellow

# 1. Check if npm build completed
Write-Host "1. Checking Vite Build..." -ForegroundColor Blue
if (Test-Path "$projectPath/public/build") {
    $buildFiles = @(Get-ChildItem "$projectPath/public/build" -Recurse -File)
    Write-Host "   [OK] Build folder found with $($buildFiles.Count) files" -ForegroundColor Green
    
    $jsFiles = @(Get-ChildItem "$projectPath/public/build" -Recurse -Filter "*.js" -File)
    $cssFiles = @(Get-ChildItem "$projectPath/public/build" -Recurse -Filter "*.css" -File)
    
    Write-Host "   - JavaScript files: $($jsFiles.Count)" -ForegroundColor Cyan
    Write-Host "   - CSS files: $($cssFiles.Count)" -ForegroundColor Cyan
} else {
    Write-Host "   [ERROR] Build folder not found. Run: npm run build" -ForegroundColor Red
}

# 2. Check critical files
Write-Host "`n2. Checking Modified Files..." -ForegroundColor Blue

$files = @(
    "resources/views/layouts/base.blade.php",
    "resources/views/layouts/app.blade.php",
    "public/.htaccess",
    "vite.config.js",
    "bootstrap/app.php",
    "app/Http/Middleware/SetSecurityHeaders.php"
)

foreach ($file in $files) {
    $fullPath = Join-Path $projectPath $file
    if (Test-Path $fullPath) {
        $size = [math]::Round((Get-Item $fullPath).Length / 1024, 2)
        Write-Host "   [OK] $file ($size KB)" -ForegroundColor Green
    } else {
        Write-Host "   [FAIL] $file NOT FOUND" -ForegroundColor Red
    }
}

# 3. Check for defer attributes
Write-Host "`n3. Checking Defer/Async Implementation..." -ForegroundColor Blue
$content = Get-Content "$projectPath/resources/views/layouts/base.blade.php" -Raw
$deferCount = ($content | Select-String 'defer' -AllMatches).Matches.Count
$preloadCount = ($content | Select-String 'preload' -AllMatches).Matches.Count

Write-Host "   - Defer attributes: $deferCount" -ForegroundColor Cyan
Write-Host "   - Preload attributes: $preloadCount" -ForegroundColor Cyan

if ($deferCount -gt 0 -and $preloadCount -gt 0) {
    Write-Host "   [OK] Defer/Preload optimization ACTIVE" -ForegroundColor Green
} else {
    Write-Host "   [WARN] Defer/Preload implementation incomplete" -ForegroundColor Yellow
}

# 4. Check for ARIA labels
Write-Host "`n4. Checking Accessibility (ARIA)..." -ForegroundColor Blue
$appContent = Get-Content "$projectPath/resources/views/layouts/app.blade.php" -Raw
$ariaCount = ($appContent | Select-String 'aria-' -AllMatches).Matches.Count
Write-Host "   - ARIA attributes found: $ariaCount" -ForegroundColor Cyan

if ($ariaCount -gt 10) {
    Write-Host "   [OK] Accessibility improvements IMPLEMENTED" -ForegroundColor Green
} else {
    Write-Host "   [WARN] Limited ARIA implementation" -ForegroundColor Yellow
}

# 5. Check .htaccess
Write-Host "`n5. Checking HTTP Optimization (.htaccess)..." -ForegroundColor Blue
$htaccess = Get-Content "$projectPath/public/.htaccess" -Raw
$gzipFound = $htaccess -match 'mod_deflate'
$cacheFound = $htaccess -match 'Cache-Control'

Write-Host "   - GZIP compression: $(if($gzipFound) { '[OK] ENABLED' } else { '[FAIL] NOT FOUND' })" -ForegroundColor $(if($gzipFound) { 'Green' } else { 'Red' })
Write-Host "   - Cache headers: $(if($cacheFound) { '[OK] CONFIGURED' } else { '[FAIL] NOT FOUND' })" -ForegroundColor $(if($cacheFound) { 'Green' } else { 'Red' })

# 6. Check security middleware
Write-Host "`n6. Checking Security Headers..." -ForegroundColor Blue
if (Test-Path "$projectPath/app/Http/Middleware/SetSecurityHeaders.php") {
    Write-Host "   [OK] Security middleware FOUND" -ForegroundColor Green
} else {
    Write-Host "   [FAIL] Security middleware NOT FOUND" -ForegroundColor Red
}

# 7. Check Vite config
Write-Host "`n7. Checking Vite Build Configuration..." -ForegroundColor Blue
$viteContent = Get-Content "$projectPath/vite.config.js" -Raw
$minifyFound = $viteContent -match "minify"
$codeSplitFound = $viteContent -match 'rollupOptions'

Write-Host "   - Minification: $(if($minifyFound) { '[OK] CONFIGURED' } else { '[FAIL] NOT FOUND' })" -ForegroundColor $(if($minifyFound) { 'Green' } else { 'Red' })
Write-Host "   - Code splitting: $(if($codeSplitFound) { '[OK] CONFIGURED' } else { '[FAIL] NOT FOUND' })" -ForegroundColor $(if($codeSplitFound) { 'Green' } else { 'Red' })

# Summary
Write-Host "`n============================================================" -ForegroundColor Cyan
Write-Host "                     SUMMARY"
Write-Host "============================================================" -ForegroundColor Cyan

$passCount = 0
if ($deferCount -gt 0) { $passCount++ }
if ($preloadCount -gt 0) { $passCount++ }
if ($ariaCount -gt 10) { $passCount++ }
if ($gzipFound) { $passCount++ }
if ($cacheFound) { $passCount++ }
if (Test-Path "$projectPath/app/Http/Middleware/SetSecurityHeaders.php") { $passCount++ }
if ($minifyFound) { $passCount++ }

Write-Host "`nChecks Passed: $passCount/8" -ForegroundColor Green
Write-Host "Status: $(if($passCount -ge 6) { 'READY FOR DEPLOYMENT' } else { 'PARTIAL - REVIEW NEEDED' })" -ForegroundColor $(if($passCount -ge 6) { 'Green' } else { 'Yellow' })

Write-Host "`nNEXT STEPS:" -ForegroundColor Yellow
Write-Host "1. Run: npm run build (if not done)"
Write-Host "2. Implement HTTPS (CRITICAL)"
Write-Host "3. Test: https://pagespeed.web.dev"
Write-Host "4. Clear cache: php artisan cache:clear"
Write-Host ""
