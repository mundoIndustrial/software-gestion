<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);

// Usar DB
use Illuminate\Support\Facades\DB;

$cot = DB::table('cotizaciones')
  ->join('tipo_cotizaciones', 'cotizaciones.tipo_cotizacion_id', '=', 'tipo_cotizaciones.id')
  ->where('cotizaciones.id', 187)
  ->select('cotizaciones.numero', 'tipo_cotizaciones.codigo', 'tipo_cotizaciones.nombre')
  ->first();

if ($cot) {
  echo "Cotización 187:\n";
  echo "  Número: " . $cot->numero . "\n";
  echo "  Código: [" . $cot->codigo . "]\n";
  echo "  Nombre: " . $cot->nombre . "\n";
} else {
  echo "Cotización 187 no encontrada\n";
}
?>
