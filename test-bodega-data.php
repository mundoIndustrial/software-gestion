<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\BodegaDetallesTalla;
use App\Models\BodegaNota;

echo "=== VERIFICANDO DATOS EN BODEGA ===\n\n";

// Verificar bodega_detalles_talla - TODOS LOS CAMPOS
$detalles = BodegaDetallesTalla::all();
echo "Total registros en bodega_detalles_talla: " . $detalles->count() . "\n";
if ($detalles->count() > 0) {
    echo "Detalle COMPLETO de todos los registros:\n";
    $detalles->each(function($item, $index) {
        echo "\n--- Registro #" . ($index + 1) . " ---\n";
        echo "  id: {$item->id}\n";
        echo "  numero_pedido: {$item->numero_pedido}\n";
        echo "  talla: {$item->talla}\n";
        echo "  prenda_nombre: '{$item->prenda_nombre}'\n";
        echo "  asesor: '{$item->asesor}'\n";
        echo "  empresa: '{$item->empresa}'\n";
        echo "  cantidad: {$item->cantidad}\n";
        echo "  pendientes: '{$item->pendientes}'\n";
        echo "  observaciones_bodega: '{$item->observaciones_bodega}'\n";
        echo "  fecha_pedido: {$item->fecha_pedido}\n";
        echo "  fecha_entrega: {$item->fecha_entrega}\n";
        echo "  estado_bodega: '{$item->estado_bodega}'\n";
        echo "  area: '{$item->area}'\n";
        echo "  usuario_bodega_nombre: '{$item->usuario_bodega_nombre}'\n";
        echo "  created_at: {$item->created_at}\n";
        echo "  updated_at: {$item->updated_at}\n";
    });
}

echo "\n";

// Verificar bodega_notas
$notas = BodegaNota::all();
echo "Total registros en bodega_notas: " . $notas->count() . "\n";
if ($notas->count() > 0) {
    echo "Primeros 3 registros detallados:\n";
    $notas->take(3)->each(function($item, $index) {
        echo "\n--- Nota #" . ($index + 1) . " ---\n";
        echo "  numero_pedido: {$item->numero_pedido}\n";
        echo "  talla: {$item->talla}\n";
        echo "  usuario_nombre: {$item->usuario_nombre}\n";
        echo "  contenido: " . substr($item->contenido, 0, 80) . "...\n";
        echo "  created_at: {$item->created_at}\n";
    });
}

echo "\n=== FIN VERIFICACIÓN ===\n";

