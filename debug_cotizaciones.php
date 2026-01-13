<?php

/**
 * Script de Debug para Cotizaciones
 * Ejecutar: php debug_cotizaciones.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Cotizacion;
use Illuminate\Support\Facades\DB;

echo "\n";
echo "========================================\n";
echo "  DEBUG DE COTIZACIONES\n";
echo "========================================\n\n";

// 1. Ver TODAS las cotizaciones
echo "📊 TODAS LAS COTIZACIONES EN LA BASE DE DATOS:\n";
echo "------------------------------------------------\n";
$todasCotizaciones = Cotizacion::select('id', 'numero_cotizacion', 'estado', 'asesor_id', 'created_at')
    ->orderBy('created_at', 'desc')
    ->get();

if ($todasCotizaciones->isEmpty()) {
    echo "❌ No hay cotizaciones en la base de datos\n\n";
} else {
    echo "Total: " . $todasCotizaciones->count() . " cotizaciones\n\n";
    
    foreach ($todasCotizaciones as $cot) {
        echo sprintf(
            "  ID: %-4s | Número: %-20s | Estado: %-25s | Asesor ID: %-4s | Fecha: %s\n",
            $cot->id,
            $cot->numero_cotizacion,
            $cot->estado,
            $cot->asesor_id,
            $cot->created_at->format('Y-m-d H:i')
        );
    }
    echo "\n";
}

// 2. Ver estados únicos
echo "📋 ESTADOS ÚNICOS DE COTIZACIONES:\n";
echo "------------------------------------------------\n";
$estados = DB::table('cotizaciones')
    ->select('estado', DB::raw('COUNT(*) as total'))
    ->groupBy('estado')
    ->get();

foreach ($estados as $estado) {
    echo sprintf("  %-30s : %d cotizaciones\n", $estado->estado, $estado->total);
}
echo "\n";

// 3. Ver cotizaciones por asesor
echo "👤 COTIZACIONES POR ASESOR:\n";
echo "------------------------------------------------\n";
$porAsesor = DB::table('cotizaciones')
    ->join('users', 'cotizaciones.asesor_id', '=', 'users.id')
    ->select('users.id', 'users.name', DB::raw('COUNT(*) as total'))
    ->groupBy('users.id', 'users.name')
    ->get();

foreach ($porAsesor as $asesor) {
    echo sprintf("  Asesor ID: %-4s | Nombre: %-30s | Total: %d\n", $asesor->id, $asesor->name, $asesor->total);
}
echo "\n";

// 4. Ver cotizaciones APROBADAS (las que deberían aparecer)
echo "✅ COTIZACIONES APROBADAS (las que deberían aparecer):\n";
echo "------------------------------------------------\n";
$aprobadas = Cotizacion::whereIn('estado', ['APROBADA_COTIZACIONES', 'APROBADO_PARA_PEDIDO'])
    ->select('id', 'numero_cotizacion', 'estado', 'asesor_id')
    ->get();

if ($aprobadas->isEmpty()) {
    echo "❌ No hay cotizaciones con estado APROBADA_COTIZACIONES o APROBADO_PARA_PEDIDO\n";
    echo "\n";
    echo "💡 SOLUCIÓN: Las cotizaciones deben tener uno de estos estados:\n";
    echo "   - APROBADA_COTIZACIONES\n";
    echo "   - APROBADO_PARA_PEDIDO\n";
    echo "\n";
} else {
    echo "Total: " . $aprobadas->count() . " cotizaciones aprobadas\n\n";
    
    foreach ($aprobadas as $cot) {
        echo sprintf(
            "  ID: %-4s | Número: %-20s | Estado: %-25s | Asesor ID: %-4s\n",
            $cot->id,
            $cot->numero_cotizacion,
            $cot->estado,
            $cot->asesor_id
        );
    }
    echo "\n";
}

// 5. Sugerencias
echo "========================================\n";
echo "  SUGERENCIAS\n";
echo "========================================\n\n";

if ($aprobadas->isEmpty() && !$todasCotizaciones->isEmpty()) {
    echo "🔧 Para que las cotizaciones aparezcan, actualiza su estado:\n\n";
    echo "SQL para actualizar:\n";
    echo "UPDATE cotizaciones SET estado = 'APROBADA_COTIZACIONES' WHERE id IN (";
    echo $todasCotizaciones->pluck('id')->implode(', ');
    echo ");\n\n";
}

echo "✅ Script completado\n\n";
