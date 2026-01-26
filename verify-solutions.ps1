# SCRIPT DE VERIFICACIÓN RÁPIDA - Soluciones Implementadas
# Verifica que todas las correcciones están en lugar correcto
# Uso: .\verify-solutions.ps1

Write-Host "======================================"
Write-Host "VERIFICACIÓN RÁPIDA DE SOLUCIONES"
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host ""

# Contador de verificaciones
$PASS = 0
$FAIL = 0

# VERIFICACIÓN 1: Método calcularCantidadTotalPrendas
Write-Host "1️⃣  Verificando calcularCantidadTotalPrendas()..." -ForegroundColor Yellow
$content = Get-Content "app\Infrastructure\Http\Controllers\Asesores\CrearPedidoEditableController.php" -Raw

if ($content -match "pedidos_procesos_prenda_tallas as pppt") {
    Write-Host "✓ PASS: Query a tabla correcta" -ForegroundColor Green
    $PASS++
} else {
    Write-Host "✗ FAIL: Query no está actualizada" -ForegroundColor Red
    $FAIL++
}

if ($content -match "procesos_prenda_detalle as ppd") {
    Write-Host "✓ PASS: JOINs a tablas correctas" -ForegroundColor Green
    $PASS++
} else {
    Write-Host "✗ FAIL: JOINs no encontrados" -ForegroundColor Red
    $FAIL++
}

Write-Host ""

# VERIFICACIÓN 2: Método editarEPPFormulario
Write-Host "2️⃣  Verificando editarEPPFormulario()..." -ForegroundColor Yellow
$jsContent = Get-Content "public\js\modulos\crear-pedido\epp\services\epp-service.js" -Raw

if ($jsContent -match "editarEPPFormulario\(id, nombre, codigo, categoria, cantidad, observaciones, imagenes\)") {
    Write-Host "✓ PASS: Firma correcta con todos los parámetros" -ForegroundColor Green
    $PASS++
} else {
    Write-Host "✗ FAIL: Firma no tiene parámetros correctos" -ForegroundColor Red
    $FAIL++
}

if ($jsContent -match "PARAMETROS COMPLETOS: id, nombre, codigo, categoria") {
    Write-Host "✓ PASS: Comentario de parámetros documentado" -ForegroundColor Green
    $PASS++
} else {
    Write-Host "⚠ WARN: Comentario no encontrado (no es crítico)" -ForegroundColor Yellow
}

Write-Host ""

# VERIFICACIÓN 3: Validación defensiva en obtenerDatosFactura
Write-Host "3️⃣  Verificando validación defensiva en obtenerDatosFactura()..." -ForegroundColor Yellow
$repoContent = Get-Content "app\Domain\Pedidos\Repositories\PedidoProduccionRepository.php" -Raw

if ($repoContent -match "if \(!\`\$epp\)") {
    Write-Host "✓ PASS: Guard defensivo para EPP null" -ForegroundColor Green
    $PASS++
} else {
    Write-Host "✗ FAIL: Guard defensivo no encontrado" -ForegroundColor Red
    $FAIL++
}

if ($repoContent -match "EPP sin relación válida, saltando") {
    Write-Host "✓ PASS: Logging de EPP sin relación" -ForegroundColor Green
    $PASS++
} else {
    Write-Host "✗ FAIL: Logging no encontrado" -ForegroundColor Red
    $FAIL++
}

Write-Host ""

# VERIFICACIÓN 4: Sintaxis PHP
Write-Host "4️⃣  Verificando sintaxis PHP..." -ForegroundColor Yellow
$phpPath = "app\Infrastructure\Http\Controllers\Asesores\CrearPedidoEditableController.php"
$output = & php -l $phpPath 2>&1

if ($output -match "No syntax errors") {
    Write-Host "✓ PASS: CrearPedidoEditableController.php sin errores" -ForegroundColor Green
    $PASS++
} else {
    Write-Host "✗ FAIL: CrearPedidoEditableController.php tiene errores" -ForegroundColor Red
    Write-Host $output
    $FAIL++
}

$phpPath2 = "app\Domain\Pedidos\Repositories\PedidoProduccionRepository.php"
$output2 = & php -l $phpPath2 2>&1

if ($output2 -match "No syntax errors") {
    Write-Host "✓ PASS: PedidoProduccionRepository.php sin errores" -ForegroundColor Green
    $PASS++
} else {
    Write-Host "✗ FAIL: PedidoProduccionRepository.php tiene errores" -ForegroundColor Red
    Write-Host $output2
    $FAIL++
}

Write-Host ""

# VERIFICACIÓN 5: Base de datos
Write-Host "5️⃣  Verificando estructura de BD..." -ForegroundColor Yellow
Write-Host " Verificación manual requerida:" -ForegroundColor Yellow
Write-Host "  - Ejecutar: SELECT COUNT(*) FROM pedidos_procesos_prenda_tallas;"
Write-Host "  - Ejecutar: SELECT COUNT(*) FROM prenda_pedido_tallas;"
Write-Host "  - Esperado: Primera > 0, Segunda = 0"

Write-Host ""

# RESUMEN
Write-Host "======================================"
Write-Host "RESUMEN"
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "✓ Pasadas: $PASS" -ForegroundColor Green
Write-Host "✗ Fallidas: $FAIL" -ForegroundColor Red
Write-Host ""

if ($FAIL -eq 0) {
    Write-Host "🎉 TODAS LAS VERIFICACIONES PASARON" -ForegroundColor Green
    Write-Host "Sistema esta listo para testing"
    exit 0
} else {
    Write-Host "ALGUNAS VERIFICACIONES FALLARON" -ForegroundColor Red
    Write-Host "Revisar cambios antes de continuar"
    exit 1
}
