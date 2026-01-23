#!/usr/bin/env powershell
# Script para ayudar en la migración de PedidoProduccion → Pedidos

# ============================================================
# FASE 1: CREAR ESTRUCTURA
# ============================================================

# Carpetas a crear en app/Domain/Pedidos/
$carpetas = @(
    "Commands",
    "CommandHandlers", 
    "Queries",
    "QueryHandlers",
    "DTOs",
    "Listeners",
    "Validators",
    "Strategies",
    "Traits",
    "Facades",
    "Aggregates"
)

Write-Host "📁 Creando estructura de directorios..." -ForegroundColor Cyan

foreach ($carpeta in $carpetas) {
    $path = "app/Domain/Pedidos/$carpeta"
    if (!(Test-Path $path)) {
        New-Item -ItemType Directory -Path $path -Force | Out-Null
        Write-Host "✅ Creada: $path" -ForegroundColor Green
    } else {
        Write-Host "⚠️  Existe: $path" -ForegroundColor Yellow
    }
}

# ============================================================
# FASE 2: BUSCAR IMPORTS DE PedidoProduccion
# ============================================================

Write-Host "`n🔍 Buscando referencias a PedidoProduccion en app/..." -ForegroundColor Cyan

$archivos = Get-ChildItem -Path "app" -Recurse -Include "*.php" | 
    Where-Object { $_.FullName -notmatch "PedidoProduccion" }

$referencias = @()

foreach ($archivo in $archivos) {
    $contenido = Get-Content $archivo.FullName -Raw
    if ($contenido -match "PedidoProduccion") {
        $referencias += @{
            Archivo = $archivo.FullName -replace "^.*app\\", "app\"
            Matches = [regex]::Matches($contenido, "PedidoProduccion").Count
        }
    }
}

if ($referencias.Count -gt 0) {
    Write-Host "`n📋 Archivos con referencias a PedidoProduccion:" -ForegroundColor Yellow
    foreach ($ref in $referencias) {
        Write-Host "  - $($ref.Archivo) ($($ref.Matches) referencias)" -ForegroundColor Yellow
    }
} else {
    Write-Host "✅ No hay referencias a PedidoProduccion" -ForegroundColor Green
}

# ============================================================
# FASE 3: LISTAR ARCHIVOS A MOVER
# ============================================================

Write-Host "`n📦 Estructura de PedidoProduccion a migrar:" -ForegroundColor Cyan

$sourceDir = "app/Domain/PedidoProduccion"
if (Test-Path $sourceDir) {
    Get-ChildItem -Path $sourceDir -Directory | ForEach-Object {
        $carpeta = $_.Name
        $archivos = Get-ChildItem -Path $_.FullName -File -Recurse | Measure-Object | Select-Object -ExpandProperty Count
        Write-Host "  📂 $carpeta/ ($archivos archivos)" -ForegroundColor Cyan
    }
} else {
    Write-Host "⚠️  No existe: $sourceDir" -ForegroundColor Yellow
}

# ============================================================
# FASE 4: VALIDACIÓN PRE-MIGRACIÓN
# ============================================================

Write-Host "`n✅ Pre-migración - Checklist:" -ForegroundColor Cyan

$checks = @(
    @{ Name = "Estructura Pedidos existe"; Path = "app/Domain/Pedidos" },
    @{ Name = "Estructura PedidoProduccion existe"; Path = "app/Domain/PedidoProduccion" },
    @{ Name = "composer.json existe"; Path = "composer.json" },
    @{ Name = "Tests existen"; Path = "tests" }
)

foreach ($check in $checks) {
    if (Test-Path $check.Path) {
        Write-Host "  ✅ $($check.Name)" -ForegroundColor Green
    } else {
        Write-Host "  ❌ $($check.Name) - NO ENCONTRADO" -ForegroundColor Red
    }
}

Write-Host "`n🎯 Siguiente paso: Ejecutar FASE 1 del plan manualmente o ejecutar:" -ForegroundColor Cyan
Write-Host "   php artisan test tests/Unit/Domain/Pedidos/" -ForegroundColor Yellow

Write-Host "`n📌 Recuerda:
- Hacer commit antes de cada fase
- Ejecutar tests después de cada fase
- Usar git diff para verificar cambios de namespace" -ForegroundColor Magenta
