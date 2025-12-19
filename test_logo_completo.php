<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PedidoProduccion;
use App\Models\LogoCotizacion;
use App\Models\LogoPedido;
use App\Models\LogoPedidoImagen;

echo "\n===================================\n";
echo "  PRUEBA COMPLETA SISTEMA LOGO\n";
echo "===================================\n\n";

// 1. Verificar que exista una cotización LOGO
echo "1️⃣  Buscando cotización LOGO...\n";
$logoCotizacion = LogoCotizacion::first();

if (!$logoCotizacion) {
    echo "❌ No hay cotización LOGO disponible\n";
    exit(1);
}

echo "✅ Cotización LOGO encontrada:\n";
echo "   ID: " . $logoCotizacion->id . "\n";
echo "   Descripción: " . substr($logoCotizacion->descripcion ?? '', 0, 50) . "\n\n";

// 2. Verificar que exista un pedido
echo "2️⃣  Buscando pedido de producción...\n";
$pedido = PedidoProduccion::first();

if (!$pedido) {
    echo "❌ No hay pedido disponible\n";
    exit(1);
}

echo "✅ Pedido encontrado:\n";
echo "   ID: " . $pedido->id . "\n";
echo "   Referencia: " . $pedido->referencia . "\n\n";

// 3. Verificar estructura de tablas
echo "3️⃣  Verificando estructura de tablas...\n";

$tablas = \DB::select("SHOW TABLES LIKE 'logo%'");
foreach ($tablas as $tabla) {
    $nombreTabla = array_values((array) $tabla)[0];
    echo "   ✅ Tabla: " . $nombreTabla . "\n";
}

// 4. Contar registros en cada tabla
echo "\n4️⃣  Contando registros...\n";

$logoPedidosCount = LogoPedido::count();
$logoPedidoImagenesCount = LogoPedidoImagen::count();

echo "   LogoPedidos: " . $logoPedidosCount . " registros\n";
echo "   LogoPedidoImágenes: " . $logoPedidoImagenesCount . " registros\n\n";

// 5. Mostrar últimos registros
if ($logoPedidosCount > 0) {
    echo "5️⃣  Últimos LOGO pedidos:\n";
    $ultimos = LogoPedido::latest()->take(3)->get();
    
    foreach ($ultimos as $logo) {
        echo "\n   📦 LOGO Pedido #" . $logo->numero_pedido . "\n";
        echo "      - ID: " . $logo->id . "\n";
        echo "      - Pedido ID: " . $logo->pedido_id . "\n";
        echo "      - Logo Cotización ID: " . $logo->logo_cotizacion_id . "\n";
        echo "      - Descripción: " . substr($logo->descripcion ?? '', 0, 40) . "\n";
        echo "      - Técnicas: " . count($logo->tecnicas ?? []) . "\n";
        echo "      - Ubicaciones: " . count($logo->ubicaciones ?? []) . "\n";
        echo "      - Imágenes: " . $logo->imagenes()->count() . "\n";
        
        // Mostrar imágenes
        foreach ($logo->imagenes as $imagen) {
            echo "         📷 Imagen #" . $imagen->orden . ": " . $imagen->nombre_archivo . "\n";
            echo "            Ruta: " . $imagen->ruta_original . "\n";
        }
    }
}

echo "\n6️⃣  Verificando relaciones...\n";

if ($logoPedidosCount > 0) {
    $logoPrueba = LogoPedido::first();
    
    echo "   LogoPedido encontrado: ID " . $logoPrueba->id . "\n";
    
    // Verificar relación con Pedido
    $pedidoRelacion = $logoPrueba->pedidoProduccion;
    if ($pedidoRelacion) {
        echo "   ✅ Relación con PedidoProduccion OK (ID: " . $pedidoRelacion->id . ")\n";
    } else {
        echo "   ❌ Relación con PedidoProduccion FALLA\n";
    }
    
    // Verificar relación con LogoCotizacion
    $cotizacionRelacion = $logoPrueba->logoCotizacion;
    if ($cotizacionRelacion) {
        echo "   ✅ Relación con LogoCotizacion OK (ID: " . $cotizacionRelacion->id . ")\n";
    } else {
        echo "   ❌ Relación con LogoCotizacion FALLA\n";
    }
    
    // Verificar relación con imágenes
    $imagenes = $logoPrueba->imagenes;
    echo "   ✅ Relación con Imágenes OK (" . $imagenes->count() . " imágenes)\n";
}

echo "\n7️⃣  Verificando almacenamiento de archivos...\n";

$directorioLogo = storage_path('app/logo_pedidos');
if (is_dir($directorioLogo)) {
    $subdirectorios = glob($directorioLogo . '/*', GLOB_ONLYDIR);
    echo "   ✅ Directorio /logo_pedidos existe\n";
    echo "   Subdirectorios: " . count($subdirectorios) . "\n";
    
    foreach ($subdirectorios as $subdir) {
        $archivos = glob($subdir . '/*');
        $nombreDir = basename($subdir);
        echo "      📁 Logo #" . $nombreDir . ": " . count($archivos) . " archivos\n";
    }
} else {
    echo "   ℹ️  Directorio /logo_pedidos no existe (se creará al guardar imágenes)\n";
}

echo "\n✅ Prueba completada\n\n";
