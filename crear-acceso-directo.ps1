# Script para crear acceso directo de INICIAR.bat con ícono personalizado

# Rutas
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
$batFile = Join-Path $scriptPath "INICIAR.bat"
$iconFile = Join-Path $scriptPath "public\mundo_icon.ico"
$desktopPath = [Environment]::GetFolderPath("Desktop")
$shortcutPath = Join-Path $desktopPath "Mundo Industrial - Servidor.lnk"

# Verificar que existe el ícono
if (-not (Test-Path $iconFile)) {
    Write-Host "ERROR: No se encuentra mundo_icon.ico en public\" -ForegroundColor Red
    pause
    exit
}

Write-Host "Usando icono: mundo_icon.ico" -ForegroundColor Green
Write-Host ""

# Crear objeto WScript Shell
$WScriptShell = New-Object -ComObject WScript.Shell

# Crear acceso directo
$Shortcut = $WScriptShell.CreateShortcut($shortcutPath)
$Shortcut.TargetPath = $batFile
$Shortcut.WorkingDirectory = $scriptPath
$Shortcut.IconLocation = $iconFile
$Shortcut.Description = "Iniciar servidor de desarrollo Mundo Industrial"
$Shortcut.WindowStyle = 1  # 1 = Normal, 3 = Maximized, 7 = Minimized

# Guardar acceso directo
$Shortcut.Save()

# Forzar actualización del caché de íconos de Windows
Write-Host "Actualizando cache de iconos..." -ForegroundColor Yellow
Start-Sleep -Milliseconds 500

# Refrescar el escritorio
$code = @"
[System.Runtime.InteropServices.DllImport("Shell32.dll")] 
private static extern int SHChangeNotify(int eventId, int flags, IntPtr item1, IntPtr item2);
public static void Refresh() {
    SHChangeNotify(0x8000000, 0x1000, IntPtr.Zero, IntPtr.Zero);
}
"@
Add-Type -MemberDefinition $code -Namespace WinAPI -Name Explorer
[WinAPI.Explorer]::Refresh()

Write-Host "========================================" -ForegroundColor Green
Write-Host "  ACCESO DIRECTO CREADO EXITOSAMENTE" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""
Write-Host "Ubicacion: $shortcutPath" -ForegroundColor Cyan
Write-Host "Icono: mundo_icon.ico" -ForegroundColor Cyan
Write-Host ""
Write-Host "El acceso directo se encuentra en tu escritorio." -ForegroundColor Yellow
Write-Host "Si el icono no aparece, presiona F5 en el escritorio." -ForegroundColor Yellow
Write-Host ""

# Preguntar si desea crear también en la carpeta actual
$response = Read-Host "¿Deseas crear tambien un acceso directo en esta carpeta? (S/N)"
if ($response -eq "S" -or $response -eq "s") {
    $localShortcutPath = Join-Path $scriptPath "Mundo Industrial - Servidor.lnk"
    $LocalShortcut = $WScriptShell.CreateShortcut($localShortcutPath)
    $LocalShortcut.TargetPath = $batFile
    $LocalShortcut.WorkingDirectory = $scriptPath
    $LocalShortcut.IconLocation = $iconFile
    $LocalShortcut.Description = "Iniciar servidor de desarrollo Mundo Industrial"
    $LocalShortcut.WindowStyle = 1
    $LocalShortcut.Save()
    
    Write-Host ""
    Write-Host "Acceso directo local creado: $localShortcutPath" -ForegroundColor Green
}

Write-Host ""
Write-Host "Presiona cualquier tecla para salir..." -ForegroundColor Gray
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
