<?php
/**
 * Script para verificar dónde se guardó el borrador
 */

use Illuminate\Support\Facades\DB;

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Consultar borradores recientes
echo "========================================\n";
echo "🔍 VERIFICANDO BORRADORES GUARDADOS\n";
echo "========================================\n\n";

$borradores = DB::table('cotizaciones')
    ->where('es_borrador', 1)
    ->orWhere('es_borrador', true)
    ->orWhere('estado', 'BORRADOR')
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get();

if ($borradores->isEmpty()) {
    echo "❌ NO SE ENCONTRARON BORRADORES\n\n";
} else {
    echo "✅ BORRADORES ENCONTRADOS:\n\n";
    
    foreach ($borradores as $borrador) {
        echo "─── BORRADOR {$borrador->id} ───\n";
        echo "Número: " . ($borrador->numero_cotizacion ?? 'NO ASIGNADO') . "\n";
        echo "Cliente: " . ($borrador->cliente_id ?? 'SIN CLIENTE') . "\n";
        echo "Tipo Cotización ID: {$borrador->tipo_cotizacion_id}\n";
        echo "Estado: {$borrador->estado}\n";
        echo "es_borrador: {$borrador->es_borrador}\n";
        echo "Asesor ID: {$borrador->asesor_id}\n";
        echo "Creado: {$borrador->created_at}\n";
        echo "Actualizado: {$borrador->updated_at}\n";
        echo "\n";
    }
}

// Verificar tabla de tipos de cotización
echo "========================================\n";
echo "📋 TIPOS DE COTIZACIÓN EN BD\n";
echo "========================================\n\n";

$tiposTable = DB::getSchemaBuilder()->hasTable('tipo_cotizacion');
echo "¿Existe tabla tipo_cotizacion? " . ($tiposTable ? "✅ SÍ\n" : "❌ NO\n");

$tiposTable2 = DB::getSchemaBuilder()->hasTable('tipo_cotizaciones');
echo "¿Existe tabla tipo_cotizaciones? " . ($tiposTable2 ? "✅ SÍ\n" : "❌ NO\n");

// Intentar obtener tipos de la tabla correcta
try {
    $tipos = DB::table('tipo_cotizacion')->get();
    echo "\nTipos disponibles:\n";
    foreach ($tipos as $tipo) {
        echo "  ID: {$tipo->id} | Código: {$tipo->codigo} | Nombre: {$tipo->nombre}\n";
    }
} catch (\Exception $e) {
    echo "Error consultando tipos: " . $e->getMessage() . "\n";
}

echo "\n✅ Script completado\n";

