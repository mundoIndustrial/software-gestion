<?php

require_once 'vendor/autoload.php';

// Iniciar Laravel
$app = require_once 'bootstrap/app.php';

use App\Models\BodegaDetallesTalla;

echo "=== DEBUG DE DATOS EPP ===\n\n";

// Buscar todos los items con area = 'EPP'
$eppItems = BodegaDetallesTalla::where('area', 'EPP')->get();

echo "Items con area = 'EPP':\n";
foreach ($eppItems as $item) {
    echo "- Pedido: {$item->numero_pedido}, Talla: {$item->talla}, Prenda: {$item->prenda_nombre}\n";
    echo "  Estado bodega: '{$item->estado_bodega}'\n";
    echo "  EPP Estado: '{$item->epp_estado}'\n";
    echo "  Costura Estado: '{$item->costura_estado}'\n";
    echo "  Fecha: {$item->created_at}\n\n";
}

echo "Total items EPP: " . $eppItems->count() . "\n\n";

// Buscar items con area = 'EPP' y estado_bodega = 'Pendiente'
$eppPendientes = BodegaDetallesTalla::where('area', 'EPP')
    ->where(function($query) {
        $query->where('estado_bodega', 'Pendiente')
              ->orWhereNull('estado_bodega');
    })
    ->get();

echo "Items EPP con estado_bodega = 'Pendiente' (o nulo):\n";
foreach ($eppPendientes as $item) {
    echo "- Pedido: {$item->numero_pedido}, Talla: {$item->talla}, Prenda: {$item->prenda_nombre}\n";
    echo "  Estado bodega: '{$item->estado_bodega}'\n\n";
}

echo "Total items EPP Pendientes: " . $eppPendientes->count() . "\n\n";

// Comparar con Costura
$costuraItems = BodegaDetallesTalla::where('area', 'Costura')->get();
echo "Items con area = 'Costura': " . $costuraItems->count() . "\n";

$costuraPendientes = BodegaDetallesTalla::where('area', 'Costura')
    ->where(function($query) {
        $query->where('estado_bodega', 'Pendiente')
              ->orWhereNull('estado_bodega');
    })
    ->get();
echo "Items Costura con estado_bodega = 'Pendiente': " . $costuraPendientes->count() . "\n\n";

?>
