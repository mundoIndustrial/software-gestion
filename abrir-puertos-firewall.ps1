# Script para abrir puertos en Windows Firewall
# Ejecutar como Administrador

Write-Host "Abriendo puertos en Windows Firewall..."
Write-Host ""

# Puerto 5173 - Vite Dev Server (CSS en tiempo real)
Write-Host "[1/3] Abriendo puerto 5173 (Vite Dev Server)..."
netsh advfirewall firewall add rule name="Vite_5173" dir=in action=allow protocol=tcp localport=5173 >$null 2>&1
Write-Host "OK"

# Puerto 8000 - Laravel Server
Write-Host "[2/3] Abriendo puerto 8000 (Web Server)..."
netsh advfirewall firewall add rule name="WebServer_8000" dir=in action=allow protocol=tcp localport=8000 >$null 2>&1
Write-Host "OK"

# Puerto 8080 - Reverb WebSocket
Write-Host "[3/3] Abriendo puerto 8080 (WebSocket)..."
netsh advfirewall firewall add rule name="WebSocket_8080" dir=in action=allow protocol=tcp localport=8080 >$null 2>&1
Write-Host "OK"

Write-Host ""
Write-Host "Puertos abiertos correctamente"
Write-Host ""
pause
