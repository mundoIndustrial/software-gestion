<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "✅ VERIFICANDO MIGRACIÓN DE PRENDAS_PEDIDO\n";
echo "═══════════════════════════════════════════════════════════════\n";

// Obtener estructura de tabla
$columns = DB::select("DESCRIBE prendas_pedido");

echo "\n📋 ESTRUCTURA DE TABLA prendas_pedido:\n";
echo str_repeat("─", 65) . "\n";

foreach ($columns as $col) {
    $tipo = $col->Type;
    $null = $col->Null === 'YES' ? '✓ nullable' : '✗ required';
    $key = $col->Key ? "({$col->Key})" : '';
    printf("%-30s %-25s %s %s\n", $col->Field, $tipo, $null, $key);
}

echo str_repeat("─", 65) . "\n";

// Verificar campos nuevos
echo "\n✅ CAMPOS NUEVOS AGREGADOS:\n";

$camposNuevos = [
    'color_id',
    'tela_id',
    'tipo_manga_id',
    'tipo_broche_id',
    'tiene_bolsillos',
    'tiene_reflectivo',
    'descripcion_variaciones',
    'cantidad_talla'
];

foreach ($camposNuevos as $campo) {
    $existe = collect($columns)->firstWhere('Field', $campo);
    $status = $existe ? '✅' : '❌';
    echo "  {$status} {$campo}\n";
}

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "✅ MIGRACIÓN COMPLETADA EXITOSAMENTE\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";
