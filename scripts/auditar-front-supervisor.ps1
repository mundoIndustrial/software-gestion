param(
    [string]$Root = "."
)

$ErrorActionPreference = "Stop"

function Get-CountPattern {
    param(
        [string]$Path,
        [string]$Pattern
    )

    if (-not (Test-Path $Path)) {
        return 0
    }

    return (Select-String -Path $Path -Pattern $Pattern -AllMatches | Measure-Object).Count
}

function Get-CountPatternInFiles {
    param(
        [string]$Directory,
        [string]$Filter,
        [string]$Pattern
    )

    if (-not (Test-Path $Directory)) {
        return 0
    }

    $files = Get-ChildItem -Path $Directory -Recurse -Filter $Filter -File -ErrorAction SilentlyContinue
    if (-not $files) {
        return 0
    }

    return ($files | Select-String -Pattern $Pattern -AllMatches | Measure-Object).Count
}

function New-Metric {
    param(
        [string]$Name,
        [int]$Value
    )

    [PSCustomObject]@{
        metric = $Name
        value  = $Value
    }
}

$indexView = Join-Path $Root "resources\views\supervisor-pedidos\index.blade.php"
$layoutView = Join-Path $Root "resources\views\supervisor-pedidos\layout.blade.php"
$supervisorViewsDir = Join-Path $Root "resources\views\supervisor-pedidos"
$sharedPedidosViewsDir = Join-Path $Root "resources\views\shared\pedidos"

$metrics = @()

# Scripts totales
$metrics += New-Metric "index_script_tags" (Get-CountPattern -Path $indexView -Pattern "<script")
$metrics += New-Metric "layout_script_tags" (Get-CountPattern -Path $layoutView -Pattern "<script")

# Scripts inline aproximados (bloques <script> sin src)
$metrics += New-Metric "index_inline_script_blocks" (Get-CountPattern -Path $indexView -Pattern "<script(?![^>]*src)")
$metrics += New-Metric "layout_inline_script_blocks" (Get-CountPattern -Path $layoutView -Pattern "<script(?![^>]*src)")

# Acoplamiento directo con asesores
$metrics += New-Metric "index_asesores_script_refs" (Get-CountPattern -Path $indexView -Pattern "js/asesores/")
$metrics += New-Metric "layout_asesores_script_refs" (Get-CountPattern -Path $layoutView -Pattern "js/asesores/")
$metrics += New-Metric "index_asesores_blade_includes" (Get-CountPattern -Path $indexView -Pattern "@include\('asesores\.")
$metrics += New-Metric "supervisor_asesores_blade_includes_recursive" (Get-CountPatternInFiles -Directory $supervisorViewsDir -Filter "*.blade.php" -Pattern "@include\('asesores\.")
$metrics += New-Metric "shared_pedidos_bridge_includes" (Get-CountPatternInFiles -Directory $sharedPedidosViewsDir -Filter "*.blade.php" -Pattern "@include\('asesores\.")

# Dependencias de tracking y modulos heredados
$metrics += New-Metric "index_ordersjs_refs" (Get-CountPattern -Path $indexView -Pattern "js/ordersjs/")
$metrics += New-Metric "index_crear_pedido_refs" (Get-CountPattern -Path $indexView -Pattern "js/modulos/crear-pedido/")

$timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"

Write-Host ""
Write-Host "=== Auditoria Front Supervisor Pedidos ==="
Write-Host "Fecha: $timestamp"
Write-Host "Root:  $((Resolve-Path $Root).Path)"
Write-Host ""

$metrics | Format-Table -AutoSize

Write-Host ""
Write-Host "Resumen rapido:"

$indexScripts = ($metrics | Where-Object { $_.metric -eq "index_script_tags" }).value
$layoutScripts = ($metrics | Where-Object { $_.metric -eq "layout_script_tags" }).value
$asesoresRefs = ($metrics | Where-Object { $_.metric -eq "index_asesores_script_refs" }).value
$asesoresIncludes = ($metrics | Where-Object { $_.metric -eq "index_asesores_blade_includes" }).value
$supervisorRecursiveIncludes = ($metrics | Where-Object { $_.metric -eq "supervisor_asesores_blade_includes_recursive" }).value
$sharedBridgeIncludes = ($metrics | Where-Object { $_.metric -eq "shared_pedidos_bridge_includes" }).value

Write-Host "- Scripts en index: $indexScripts"
Write-Host "- Scripts en layout: $layoutScripts"
Write-Host "- Referencias JS a asesores (index): $asesoresRefs"
Write-Host "- Includes Blade de asesores (index): $asesoresIncludes"
Write-Host "- Includes Blade de asesores (supervisor recursivo): $supervisorRecursiveIncludes"
Write-Host "- Includes puente en shared/pedidos: $sharedBridgeIncludes"
Write-Host ""
