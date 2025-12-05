<?php
/**
 * Script para verificar tipos de columnas de fecha en la base de datos
 * Identifica qué campos están como DATE y deberían ser DATETIME
 */

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Tablas donde buscamos campos de fecha
$tablas = [
    'pedidos_produccion',
    'tabla_original',
    'tabla_original_bodega',
    'cotizaciones',
    'registros_por_orden',
    'registros_por_orden_bodega',
    'entregas_pedido_costura',
    'entregas_bodega_costura',
    'entrega_pedido_corte',
    'entrega_bodega_corte',
    'registro_piso_produccion',
    'registro_piso_polo',
    'registro_piso_corte',
    'reportes',
    'procesos',
    'materiales_orden_insumos',
];

echo "\n╔════════════════════════════════════════════════════════════════════╗\n";
echo "║           ANÁLISIS DE CAMPOS DE FECHA EN LA BASE DE DATOS          ║\n";
echo "╚════════════════════════════════════════════════════════════════════╝\n\n";

$camposProblematicos = [];

foreach ($tablas as $tabla) {
    if (!Schema::hasTable($tabla)) {
        echo "❌ Tabla no existe: $tabla\n";
        continue;
    }

    $columnas = DB::select("SELECT COLUMN_NAME, COLUMN_TYPE, DATA_TYPE, IS_NULLABLE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '$tabla' AND TABLE_SCHEMA = '" . env('DB_DATABASE') . "'");

    $hayProblemas = false;
    foreach ($columnas as $col) {
        // Buscar columnas de fecha/hora que sean DATE en lugar de DATETIME
        if (
            (strpos($col->COLUMN_NAME, 'fecha') !== false || 
             strpos($col->COLUMN_NAME, 'date') !== false ||
             strpos($col->COLUMN_NAME, 'creacion') !== false ||
             strpos($col->COLUMN_NAME, 'completado') !== false ||
             strpos($col->COLUMN_NAME, 'entrega') !== false ||
             strpos($col->COLUMN_NAME, 'control') !== false ||
             strpos($col->COLUMN_NAME, 'estimada') !== false ||
             strpos($col->COLUMN_NAME, 'despacho') !== false ||
             strpos($col->COLUMN_NAME, 'pago') !== false ||
             strpos($col->COLUMN_NAME, 'proceso') !== false ||
             strpos($col->COLUMN_NAME, 'inicio') !== false ||
             strpos($col->COLUMN_NAME, 'fin') !== false ||
             strpos($col->COLUMN_NAME, 'anulacion') !== false ||
             strpos($col->COLUMN_NAME, 'accion') !== false ||
             strpos($col->COLUMN_NAME, 'llegada') !== false) 
            && $col->DATA_TYPE === 'date'
        ) {
            if (!$hayProblemas) {
                echo "┌─ Tabla: $tabla\n";
                $hayProblemas = true;
            }

            echo "│  ⚠️  Campo: {$col->COLUMN_NAME}\n";
            echo "│      Tipo actual: {$col->COLUMN_TYPE}\n";
            echo "│      Debe ser: DATETIME\n";
            echo "│      Nullable: {$col->IS_NULLABLE}\n";

            $camposProblematicos[$tabla][] = [
                'columna' => $col->COLUMN_NAME,
                'tipo' => $col->COLUMN_TYPE,
                'nullable' => $col->IS_NULLABLE
            ];
        }
    }

    if ($hayProblemas) {
        echo "└\n";
    }
}

echo "\n╔════════════════════════════════════════════════════════════════════╗\n";
echo "║                            RESUMEN                                 ║\n";
echo "╚════════════════════════════════════════════════════════════════════╝\n\n";

if (empty($camposProblematicos)) {
    echo "✅ Todas las tablas tienen los tipos correctos.\n";
} else {
    echo "⚠️  Encontrados " . count($camposProblematicos) . " tabla(s) con problemas:\n\n";

    $totalCampos = 0;
    foreach ($camposProblematicos as $tabla => $campos) {
        echo "📋 $tabla:\n";
        foreach ($campos as $campo) {
            echo "   - {$campo['columna']} ({$campo['tipo']} → DATETIME)\n";
            $totalCampos++;
        }
        echo "\n";
    }

    echo "📊 Total de campos a actualizar: $totalCampos\n";
    echo "\n═══════════════════════════════════════════════════════════════════\n";
    echo "\n💡 PRÓXIMOS PASOS:\n";
    echo "\n1. Crear una migración para cambiar DATE a DATETIME\n";
    echo "2. Ejecutar la migración\n";
    echo "3. Revisar que los modelos tengan los casts correctos\n\n";

    echo "📝 Campos críticos que necesitan cambio URGENTE:\n";
    $criticos = [
        'pedidos_produccion' => [
            'fecha_de_creacion_de_orden',
            'fecha_estimada_de_entrega',
            'fecha_inicio',
            'fecha_fin'
        ],
        'tabla_original' => [
            'control_de_calidad',
            'corte',
            'costura',
            'lavanderia'
        ]
    ];

    foreach ($criticos as $tabla => $campos) {
        if (isset($camposProblematicos[$tabla])) {
            echo "  ⚡ $tabla\n";
            foreach ($campos as $campo) {
                foreach ($camposProblematicos[$tabla] as $campo_db) {
                    if ($campo_db['columna'] === $campo) {
                        echo "     - $campo\n";
                    }
                }
            }
        }
    }
}

echo "\n═══════════════════════════════════════════════════════════════════\n\n";
