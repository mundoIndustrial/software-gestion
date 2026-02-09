<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "===========================================\n";
echo "DEBUG PEDIDO 1 - CAMPO PRENDAS_JSON\n";
echo "===========================================\n\n";

$pedido = \App\Models\PedidoProduccion::where('numero_pedido', '1')->first();

if (!$pedido) {
    echo "❌ No se encontró el pedido\n";
    exit;
}

echo "✅ Pedido encontrado (ID: {$pedido->id})\n\n";

echo "=== CAMPOS DEL PEDIDO ===\n";
echo "numero_pedido: {$pedido->numero_pedido}\n";
echo "estado: {$pedido->estado}\n";
echo "empresa_id: {$pedido->empresa_id}\n";
echo "asesor_id: {$pedido->asesor_id}\n\n";

echo "=== CONTENIDO DE prendas_json ===\n";
if (empty($pedido->prendas_json)) {
    echo "⚠️ El campo prendas_json está VACÍO o NULL\n";
    echo "Valor: " . var_export($pedido->prendas_json, true) . "\n";
} else {
    echo "Longitud: " . strlen($pedido->prendas_json) . " caracteres\n";
    echo "Contenido RAW:\n";
    echo $pedido->prendas_json . "\n\n";
    
    echo "Decodificado:\n";
    $prendas = json_decode($pedido->prendas_json, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "❌ ERROR al decodificar JSON: " . json_last_error_msg() . "\n";
    } else {
        echo "✅ JSON válido\n";
        print_r($prendas);
    }
}

echo "\n\n=== OTROS CAMPOS JSON ===\n";

echo "\n--- insumos_json ---\n";
if (empty($pedido->insumos_json)) {
    echo "⚠️ Vacío o NULL\n";
} else {
    echo "Longitud: " . strlen($pedido->insumos_json) . " caracteres\n";
    $insumos = json_decode($pedido->insumos_json, true);
    if ($insumos) {
        echo "Total insumos: " . count($insumos) . "\n";
    }
}

echo "\n--- epp_json ---\n";
if (empty($pedido->epp_json)) {
    echo "⚠️ Vacío o NULL\n";
} else {
    echo "Longitud: " . strlen($pedido->epp_json) . " caracteres\n";
    $epp = json_decode($pedido->epp_json, true);
    if ($epp) {
        echo "Total EPP: " . count($epp) . "\n";
    }
}

echo "\n\n=== RELACIONES DEL PEDIDO ===\n";

// Intentar obtener datos de la relación prendas
try {
    $prendasRelacion = $pedido->prendas()->get();
    echo "\nRelación prendas()->get():\n";
    echo "Total: {$prendasRelacion->count()}\n";
    
    if ($prendasRelacion->count() > 0) {
        foreach ($prendasRelacion as $prenda) {
            echo "\nPrenda ID {$prenda->id}:\n";
            echo "   Nombre: {$prenda->nombre}\n";
            echo "   Cantidad: {$prenda->cantidad}\n";
            echo "   Tallas: " . ($prenda->tallas_json ?? 'N/A') . "\n";
        }
    }
} catch (\Exception $e) {
    echo "❌ Error al cargar relación prendas: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DEL DEBUG ===\n";
