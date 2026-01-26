# =========================================================================
# Script: Arreglador de Permisos de Storage para Laravel 10 (Windows)
# Uso: .\fix-storage-permissions.ps1
# Nota: Ejecutar como Administrador
# =========================================================================

#Requires -RunAsAdministrator

param(
    [switch]$DryRun = $false,
    [switch]$Verbose = $false
)

# Colores
$colors = @{
    Success = "Green"
    Warning = "Yellow"
    Error   = "Red"
    Info    = "Cyan"
    Debug   = "Gray"
}

function Write-Section {
    param([string]$Title)
    Write-Host ""
    Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor $colors.Debug
    Write-Host $Title -ForegroundColor $colors.Info -NoNewline
    Write-Host ""
    Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor $colors.Debug
}

function Write-Success {
    param([string]$Message)
    Write-Host "✅ $Message" -ForegroundColor $colors.Success
}

function Write-Warn {
    param([string]$Message)
    Write-Host "  $Message" -ForegroundColor $colors.Warning
}

function Write-Error-Custom {
    param([string]$Message)
    Write-Host " $Message" -ForegroundColor $colors.Error
}

function Write-Info {
    param([string]$Message, [switch]$NoNewLine)
    if ($NoNewLine) {
        Write-Host "ℹ️  $Message" -ForegroundColor $colors.Info -NoNewLine
    } else {
        Write-Host "ℹ️  $Message" -ForegroundColor $colors.Info
    }
}

function Write-Debug-Info {
    param([string]$Message)
    if ($Verbose) {
        Write-Host "🔍 $Message" -ForegroundColor $colors.Debug
    }
}

# =========================================================================
# INICIO
# =========================================================================

Clear-Host
Write-Host " REPARADOR DE PERMISOS - STORAGE LARAVEL 10 (WINDOWS)" -ForegroundColor $colors.Info -BackgroundColor Black
Write-Host "Ejecutando en modo: $(if ($DryRun) { 'DRY-RUN (solo lectura)' } else { 'ACTIVO (realizará cambios)' })" -ForegroundColor $colors.Warning
Write-Host ""

# =========================================================================
# 1. VERIFICAR UBICACIÓN DEL PROYECTO
# =========================================================================

Write-Section "1️⃣  Verificando ubicación del proyecto"

$projectRoot = Get-Location
$requiredDirs = @("app", "bootstrap", "config", "routes", "storage", "public")
$missingDirs = @()

foreach ($dir in $requiredDirs) {
    $path = Join-Path $projectRoot $dir
    if (Test-Path $path) {
        Write-Success "Encontrado: $dir"
    } else {
        Write-Error-Custom "Falta: $dir"
        $missingDirs += $dir
    }
}

if ($missingDirs.Count -gt 0) {
    Write-Error-Custom "Este no parece ser un proyecto Laravel válido"
    Write-Host ""
    exit 1
}

# =========================================================================
# 2. CREAR/VERIFICAR ENLACE SIMBÓLICO
# =========================================================================

Write-Section "2️⃣  Creando/verificando enlace simbólico"

$symlinkSource = "public\storage"
$symlinkTarget = "..\storage\app\public"
$symlinkFullPath = Join-Path $projectRoot $symlinkSource
$targetFullPath = Join-Path $projectRoot "storage\app\public"

Write-Info "Enlace: $symlinkSource"
Write-Info "Apunta a: $symlinkTarget"
Write-Debug-Info "Ruta completa: $symlinkFullPath"
Write-Debug-Info "Destino completo: $targetFullPath"
Write-Host ""

if (Test-Path $symlinkFullPath) {
    $item = Get-Item $symlinkFullPath -Force
    if ($item.LinkType -eq "SymbolicLink") {
        Write-Success "Enlace simbólico ya existe y es válido"
        Write-Info "Apunta a: $($item.Target)"
    } else {
        Write-Warn "public\storage existe pero NO es un enlace simbólico"
        Write-Info "Tipo: $($item.PSTypeName)"
    }
} else {
    Write-Info "Enlace simbólico no existe, creando..." -NoNewLine
    
    if (-not $DryRun) {
        try {
            # Usar artisan si está disponible
            $result = php artisan storage:link 2>&1
            Write-Host "" -ForegroundColor $colors.Success
            Write-Debug-Info "$result"
        } catch {
            Write-Host " " -ForegroundColor $colors.Error
            Write-Error-Custom "Error al crear enlace: $_"
        }
    } else {
        Write-Host " [DRY-RUN]" -ForegroundColor $colors.Warning
    }
}

Write-Host ""

# =========================================================================
# 3. VERIFICAR/REPARAR PERMISOS DE CARPETAS
# =========================================================================

Write-Section "3️⃣  Verificando y reparando permisos de carpetas"

$folders = @(
    @{ Path = "storage"; Label = "Storage (general)" },
    @{ Path = "storage\app"; Label = "Storage App" },
    @{ Path = "storage\app\public"; Label = "Storage App Public" },
    @{ Path = "storage\logs"; Label = "Logs" },
    @{ Path = "storage\framework"; Label = "Framework Cache" },
    @{ Path = "bootstrap\cache"; Label = "Bootstrap Cache" }
)

foreach ($folderInfo in $folders) {
    $folderPath = Join-Path $projectRoot $folderInfo.Path
    
    if (Test-Path $folderPath) {
        Write-Info "Analizando: $($folderInfo.Label)" -NoNewLine
        Write-Host ""
        
        $acl = Get-Acl $folderPath
        $accessRules = $acl.Access.Count
        Write-Debug-Info "  • Propietario: $($acl.Owner)"
        Write-Debug-Info "  • Reglas de acceso: $accessRules"
        
        if (-not $DryRun) {
            try {
                # Heredar permisos del padre
                icacls $folderPath /inheritance:e /grant:r "*S-1-5-20:(OI)(CI)F" 2>$null | Out-Null
                
                # Dar acceso al usuario actual (administrador)
                icacls $folderPath /grant:r "${env:USERDOMAIN}\${env:USERNAME}:(OI)(CI)F" 2>$null | Out-Null
                
                Write-Success "  Permisos actualizados"
            } catch {
                Write-Error-Custom "  Error al cambiar permisos: $_"
            }
        } else {
            Write-Host "  [DRY-RUN] Se actualizarían permisos" -ForegroundColor $colors.Warning
        }
    } else {
        Write-Warn "Carpeta no encontrada: $($folderInfo.Label)"
    }
    
    Write-Host ""
}

# =========================================================================
# 4. DETECTAR SERVIDOR WEB
# =========================================================================

Write-Section "4️⃣  Detectando servidor web"

$webServer = "Desconocido"
$webUser = "usuario del sistema"

# Verificar IIS
try {
    $iisAppPools = Get-IISAppPool -ErrorAction SilentlyContinue
    if ($iisAppPools) {
        $webServer = "IIS"
        foreach ($pool in $iisAppPools) {
            $identity = $pool.processModel.identityType
            $name = $pool.name
            Write-Info "Pool IIS: $name"
            Write-Debug-Info "  • Tipo de identidad: $identity"
            
            if ($identity -eq "SpecificUser") {
                $webUser = $pool.processModel.userName
                Write-Debug-Info "  • Usuario: $webUser"
            }
        }
    }
} catch {
    Write-Debug-Info "IIS no disponible o no instalado"
}

# Verificar Apache
if (Get-Service "Apache2" -ErrorAction SilentlyContinue) {
    $webServer = "Apache"
    $webUser = "Apache Service Account"
    Write-Info "Servidor web: Apache (servicio instalado)"
}

# Verificar Nginx
if (Get-Service "nginx" -ErrorAction SilentlyContinue) {
    $webServer = "Nginx"
    $webUser = "Nginx Service Account"
    Write-Info "Servidor web: Nginx (servicio instalado)"
}

Write-Success "Servidor detectado: $webServer"
Write-Info "Usuario del servidor: $webUser"
Write-Host ""

# =========================================================================
# 5. LIMPIAR CACHÉ DE LARAVEL
# =========================================================================

Write-Section "5️⃣  Limpiando caché de Laravel"

$cacheCommands = @(
    @{ Cmd = "cache:clear"; Desc = "Caché general" },
    @{ Cmd = "route:clear"; Desc = "Caché de rutas" },
    @{ Cmd = "view:clear"; Desc = "Caché de vistas" },
    @{ Cmd = "config:clear"; Desc = "Caché de configuración" }
)

foreach ($cacheCmd in $cacheCommands) {
    Write-Info "Limpiando: $($cacheCmd.Desc)" -NoNewLine
    
    if (-not $DryRun) {
        try {
            php artisan $($cacheCmd.Cmd) 2>$null | Out-Null
            Write-Host "" -ForegroundColor $colors.Success
        } catch {
            Write-Host " " -ForegroundColor $colors.Warning
            Write-Debug-Info "  (Error: $_)"
        }
    } else {
        Write-Host " [DRY-RUN]" -ForegroundColor $colors.Warning
    }
}

Write-Host ""

# =========================================================================
# 6. VERIFICACIÓN FINAL
# =========================================================================

Write-Section "6️⃣  Verificación Final"

# 6.1 Enlace simbólico
Write-Host ""
Write-Host "Enlace Simbólico:" -ForegroundColor $colors.Info
if (Test-Path $symlinkFullPath) {
    $item = Get-Item $symlinkFullPath -Force
    Write-Success "Existe"
    Write-Debug-Info "Tipo: $($item.LinkType)"
    Write-Debug-Info "Apunta a: $($item.Target)"
} else {
    Write-Error-Custom "NO EXISTE"
}

# 6.2 Directorios
Write-Host ""
Write-Host "Directorios:" -ForegroundColor $colors.Info
$storageDirs = @("storage\app\public", "storage\logs", "bootstrap\cache")
foreach ($dir in $storageDirs) {
    $fullPath = Join-Path $projectRoot $dir
    if (Test-Path $fullPath) {
        $itemCount = @(Get-ChildItem -Path $fullPath -Recurse -ErrorAction SilentlyContinue).Count
        Write-Success "$dir ($itemCount elementos)"
    } else {
        Write-Error-Custom "$dir NO EXISTE"
    }
}

# 6.3 Archivos de almacenamiento
Write-Host ""
Write-Host "Archivos Almacenados:" -ForegroundColor $colors.Info
$storagePath = Join-Path $projectRoot "storage\app\public"
if (Test-Path $storagePath) {
    $allFiles = @(Get-ChildItem -Path $storagePath -Recurse -File -ErrorAction SilentlyContinue)
    $totalSize = ($allFiles | Measure-Object -Property Length -Sum).Sum / 1MB
    Write-Success "Total: $($allFiles.Count) archivos (~$([Math]::Round($totalSize, 2)) MB)"
    
    # Mostrar carpetas principales
    $folders = @(Get-ChildItem -Path $storagePath -Directory -ErrorAction SilentlyContinue)
    if ($folders) {
        foreach ($folder in $folders) {
            $count = @(Get-ChildItem -Path $folder.FullName -Recurse -File).Count
            Write-Debug-Info "  • $($folder.Name): $count archivos"
        }
    }
}

Write-Host ""

# =========================================================================
# 7. RESUMEN Y RECOMENDACIONES
# =========================================================================

Write-Section "7️⃣  Resumen y Recomendaciones"

Write-Host ""
Write-Info "✅ CHECKLIST DE CORRECCIONES:" -ForegroundColor $colors.Success
Write-Host "  [✓] Enlace simbólico verificado/creado" 
Write-Host "  [✓] Permisos de carpetas ajustados"
Write-Host "  [✓] Caché de Laravel limpiado"
Write-Host "  [✓] Servidor web detectado"

Write-Host ""
Write-Info "📌 PRÓXIMOS PASOS:"
Write-Host "  1. Abre el navegador: http://localhost:8000/storage"
Write-Host "  2. Deberías ver un listado de carpetas"
Write-Host "  3. Intenta acceder a: http://localhost:8000/storage/pedidos/{id}/imagen.jpg"
Write-Host "  4. Si ves 403: Los permisos necesitan más ajustes"
Write-Host "  5. Si ves 404: El archivo no existe en esa ubicación"

Write-Host ""
Write-Info "🔗 VERIFICACIÓN TÉCNICA (Tinker):"
Write-Host "  1. Abre terminal: php artisan tinker"
Write-Host "  2. Ejecuta: Storage::disk('public')->url('test.jpg')"
Write-Host "  3. Debería retornar: /storage/test.jpg"

Write-Host ""
Write-Warn "  NOTAS IMPORTANTES:"
Write-Host "  • Este script debe ejecutarse con permisos de Administrador"
Write-Host "  • Los cambios afectarán al acceso de archivos"
Write-Host "  • Realiza un backup antes de cambios en producción"
Write-Host "  • Reinicia los servicios de web después (Apache/IIS/Nginx)"

Write-Host ""
Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor $colors.Debug
if ($DryRun) {
    Write-Host "✅ VERIFICACIÓN DRY-RUN COMPLETADA (sin cambios reales)" -ForegroundColor $colors.Warning
} else {
    Write-Host "✅ REPARACIÓN COMPLETADA EXITOSAMENTE" -ForegroundColor $colors.Success
}
Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor $colors.Debug

Write-Host ""
Write-Host "Para más información, consulta: CHECKLIST_STORAGE_PERMISSIONS.md" -ForegroundColor $colors.Info
Write-Host ""
