<?php

/**
 * Script para asignar cotizaciones al usuario actual
 * Ejecutar: php asignar_cotizaciones.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Cotizacion;
use Illuminate\Support\Facades\DB;

echo "\n";
echo "========================================\n";
echo "  ASIGNAR COTIZACIONES\n";
echo "========================================\n\n";

// IDs
$asesorOrigenId = 6;   // Usuario "yus" que tiene las cotizaciones
$asesorDestinoId = 92; // Usuario actual

echo "Transfiriendo cotizaciones...\n";
echo "  Desde: Asesor ID $asesorOrigenId\n";
echo "  Hacia: Asesor ID $asesorDestinoId\n\n";

// Ver cotizaciones antes
$cotizacionesAntes = Cotizacion::where('asesor_id', $asesorOrigenId)->count();
echo "Cotizaciones del asesor $asesorOrigenId: $cotizacionesAntes\n";

// Transferir
$actualizadas = DB::table('cotizaciones')
    ->where('asesor_id', $asesorOrigenId)
    ->update(['asesor_id' => $asesorDestinoId]);

echo "✅ Cotizaciones transferidas: $actualizadas\n\n";

// Verificar
$cotizacionesDespues = Cotizacion::where('asesor_id', $asesorDestinoId)->count();
echo "Cotizaciones del asesor $asesorDestinoId ahora: $cotizacionesDespues\n\n";

echo "✅ Proceso completado\n";
echo "Recarga la página en el navegador para ver las cotizaciones\n\n";
