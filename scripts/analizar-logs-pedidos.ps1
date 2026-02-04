#  Script para Analizar Logs de Creación de Pedidos
# Uso: .\scripts\analizar-logs-pedidos.ps1

param(
    [string]$LogFile = "storage/logs/laravel.log",
    [string]$Operacion = "todas",  # todas, carga-inicial, creacion-pedido, imagenes
    [int]$Ultimas = 0               # 0 = todas, N = últimas N líneas
)

Write-Host "`n╔════════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║      ANALIZADOR DE LOGS - CREACIÓN DE PEDIDOS            ║" -ForegroundColor Cyan
Write-Host "╚════════════════════════════════════════════════════════════╝`n" -ForegroundColor Cyan

# Verificar que el archivo existe
if (-not (Test-Path $LogFile)) {
    Write-Host "❌ Archivo de log no encontrado: $LogFile" -ForegroundColor Red
    exit 1
}

# Leer el archivo
$lineas = Get-Content $LogFile
if ($Ultimas -gt 0) {
    $lineas = $lineas | Select-Object -Last $Ultimas
}

# Función para extraer tiempo de los logs
function Extract-Tiempo {
    param([string]$linea)
    $match = $linea | Select-String '"tiempo_total_ms":\s*(\d+\.?\d*)'
    if ($match) {
        return [float]$match.Matches[0].Groups[1].Value
    }
    return $null
}

# Función para extraer resumen
function Extract-Resumen {
    param([string]$linea)
    $match = $linea | Select-String '"resumen":\s*"([^"]+)"'
    if ($match) {
        return $match.Matches[0].Groups[1].Value
    }
    return $null
}

# Función para mostrar log con colores
function Show-LogEntry {
    param([string]$linea)
    
    if ($linea -match '\[CREAR-PEDIDO-NUEVO\]') {
        Write-Host "  🔵 CREAR-PEDIDO-NUEVO: " -ForegroundColor Blue -NoNewline
    }
    elseif ($linea -match '\[CREAR-DESDE-COTIZACION\]') {
        Write-Host "  🟢 CREAR-DESDE-COTIZACION: " -ForegroundColor Green -NoNewline
    }
    elseif ($linea -match '\[CREAR-PEDIDO\]') {
        Write-Host "  🟠 CREAR-PEDIDO: " -ForegroundColor DarkYellow -NoNewline
    }
    elseif ($linea -match '\[RESOLVER-IMAGENES\]') {
        Write-Host "  🔴 RESOLVER-IMAGENES: " -ForegroundColor Red -NoNewline
    }
    elseif ($linea -match '\[IMAGE-UPLOAD\]') {
        Write-Host "  🟣 IMAGE-UPLOAD: " -ForegroundColor Magenta -NoNewline
    }
    elseif ($linea -match '\[MAPEO-IMAGENES\]') {
        Write-Host "  🟡 MAPEO-IMAGENES: " -ForegroundColor Yellow -NoNewline
    }
    
    # Extraer timestamp
    $timestamp = $linea -replace '^\[([^\]]+)\].*', '$1'
    Write-Host $timestamp -ForegroundColor Gray
    
    # Mostrar resumen si existe
    $resumen = Extract-Resumen $linea
    if ($resumen) {
        Write-Host "     $resumen" -ForegroundColor White
    }
}

# ANÁLISIS: CARGA INICIAL
if ($Operacion -eq "carga-inicial" -or $Operacion -eq "todas") {
    Write-Host "`n┌─ 📖 CARGA INICIAL (crear-nuevo o crear-desde-cotizacion)" -ForegroundColor Cyan
    Write-Host "└─" -ForegroundColor Cyan
    
    $cargaInicial = $lineas | Select-String 'CREAR-PEDIDO.*⏱️ INICIANDO|CREAR-PEDIDO.*✨ PÁGINA'
    if ($cargaInicial) {
        foreach ($linea in $cargaInicial) {
            Show-LogEntry $linea
        }
    } else {
        Write-Host "   No hay entradas" -ForegroundColor Gray
    }
}

# ANÁLISIS: CREACIÓN DE PEDIDO
if ($Operacion -eq "creacion-pedido" -or $Operacion -eq "todas") {
    Write-Host "`n┌─ 💾 CREACIÓN DE PEDIDO (guardado)" -ForegroundColor Yellow
    Write-Host "└─" -ForegroundColor Yellow
    
    $creacionPedido = $lineas | Select-String 'CREAR-PEDIDO.*✨ TRANSACCIÓN'
    if ($creacionPedido) {
        foreach ($linea in $creacionPedido) {
            Show-LogEntry $linea
            
            # Extraer desglose de pasos
            if ($linea -match '"desglose_pasos":\s*\{([^}]+)\}') {
                $desglose = $matches[0]
                Write-Host "    📋 Desglose de pasos:" -ForegroundColor White
                
                $pasos = @{
                    "JSON" = [regex]::Matches($linea, '"paso_1_json_ms":\s*(\d+\.?\d*)') | % {$_.Groups[1].Value}
                    "Cliente" = [regex]::Matches($linea, '"paso_2_cliente_ms":\s*(\d+\.?\d*)') | % {$_.Groups[1].Value}
                    "DTO" = [regex]::Matches($linea, '"paso_3_dto_ms":\s*(\d+\.?\d*)') | % {$_.Groups[1].Value}
                    "Pedido Base" = [regex]::Matches($linea, '"paso_5_pedido_base_ms":\s*(\d+\.?\d*)') | % {$_.Groups[1].Value}
                    "Carpetas" = [regex]::Matches($linea, '"paso_6_carpetas_ms":\s*(\d+\.?\d*)') | % {$_.Groups[1].Value}
                    "Imágenes" = [regex]::Matches($linea, '"paso_7_imagenes_ms":\s*(\d+\.?\d*)') | % {$_.Groups[1].Value}
                    "EPPs" = [regex]::Matches($linea, '"paso_7b_epps_ms":\s*(\d+\.?\d*)') | % {$_.Groups[1].Value}
                    "Cálculo" = [regex]::Matches($linea, '"paso_8_calculo_ms":\s*(\d+\.?\d*)') | % {$_.Groups[1].Value}
                }
                
                foreach ($paso in $pasos.GetEnumerator()) {
                    $tiempo = [float]$paso.Value
                    $color = if ($tiempo -gt 2000) { "Red" } elseif ($tiempo -gt 1000) { "Yellow" } else { "Green" }
                    Write-Host "       • $($paso.Key): $tiempo ms" -ForegroundColor $color
                }
            }
        }
    } else {
        Write-Host "   No hay entradas" -ForegroundColor Gray
    }
}

# ANÁLISIS: PROCESAMIENTO DE IMÁGENES
if ($Operacion -eq "imagenes" -or $Operacion -eq "todas") {
    Write-Host "`n┌─ 🖼️  PROCESAMIENTO DE IMÁGENES" -ForegroundColor Magenta
    Write-Host "└─" -ForegroundColor Magenta
    
    $imagenes = $lineas | Select-String 'RESOLVER-IMAGENES|IMAGE-UPLOAD|MAPEO-IMAGENES'
    if ($imagenes) {
        $contador = 0
        foreach ($linea in $imagenes) {
            if ($linea -match ' Extracción completada|✨ MAPEO COMPLETADO| Imagen guardada') {
                $contador++
                Write-Host "    $contador. " -ForegroundColor White -NoNewline
                Show-LogEntry $linea
            }
        }
    } else {
        Write-Host "   No hay entradas" -ForegroundColor Gray
    }
}

# ESTADÍSTICAS
Write-Host "`n┌─ 📈 ESTADÍSTICAS" -ForegroundColor Cyan
Write-Host "└─" -ForegroundColor Cyan

$tiemposTotal = @()
$lineas | Select-String '"tiempo_total_ms"' | ForEach-Object {
    $match = $_ | Select-String '"tiempo_total_ms":\s*(\d+\.?\d*)'
    if ($match) {
        $tiempo = [float]$match.Matches[0].Groups[1].Value
        $tiemposTotal += $tiempo
    }
}

if ($tiemposTotal.Count -gt 0) {
    $promedio = [math]::Round(($tiemposTotal | Measure-Object -Average).Average, 2)
    $minimo = [math]::Round(($tiemposTotal | Measure-Object -Minimum).Minimum, 2)
    $maximo = [math]::Round(($tiemposTotal | Measure-Object -Maximum).Maximum, 2)
    
    Write-Host "   Operaciones registradas: $($tiemposTotal.Count)" -ForegroundColor White
    Write-Host "  ⏱️  Tiempo promedio: $promedio ms" -ForegroundColor White
    Write-Host "  🟢 Tiempo mínimo: $minimo ms" -ForegroundColor Green
    Write-Host "  🔴 Tiempo máximo: $maximo ms" -ForegroundColor Red
    
    if ($maximo -gt 5000) {
        Write-Host "`n    ALERTA: Hay operaciones tardando > 5 segundos" -ForegroundColor Red
    }
    elseif ($promedio -gt 3000) {
        Write-Host "`n    ATENCIÓN: Promedio > 3 segundos, considerar optimizar" -ForegroundColor Yellow
    }
    else {
        Write-Host "`n   Tiempos aceptables" -ForegroundColor Green
    }
} else {
    Write-Host "   No hay datos de tiempo" -ForegroundColor Gray
}

Write-Host "`n" -ForegroundColor Cyan

# INFORMACIÓN DE USO
Write-Host "💡 Uso del script:" -ForegroundColor Yellow
Write-Host "  .\scripts\analizar-logs-pedidos.ps1                      # Todas las operaciones"
Write-Host "  .\scripts\analizar-logs-pedidos.ps1 -Operacion carga-inicial"
Write-Host "  .\scripts\analizar-logs-pedidos.ps1 -Operacion creacion-pedido"
Write-Host "  .\scripts\analizar-logs-pedidos.ps1 -Operacion imagenes"
Write-Host "  .\scripts\analizar-logs-pedidos.ps1 -Ultimas 50          # Últimas 50 líneas"
Write-Host ""
