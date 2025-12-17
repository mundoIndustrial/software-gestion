<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Cotizacion;

echo "===== VERIFICANDO COT-00014 CON NUEVA RELACIÓN =====" . PHP_EOL;
echo PHP_EOL;

$cot = Cotizacion::with(['cliente', 'asesor', 'prendas.variantes'])->where('numero_cotizacion', 'COT-00014')->first();

if (!$cot) {
    echo "✗ Cotización no encontrada" . PHP_EOL;
    exit;
}

echo "📋 Cotización:" . PHP_EOL;
echo "   Número: " . $cot->numero_cotizacion . PHP_EOL;
echo "   Cliente: " . ($cot->cliente ? $cot->cliente->nombre : 'N/A') . PHP_EOL;
echo "   Asesor: " . ($cot->asesor ? $cot->asesor->name : 'N/A') . PHP_EOL;
echo PHP_EOL;

echo "👗 Prendas:" . PHP_EOL;
foreach ($cot->prendas as $index => $prenda) {
    echo "   Prenda " . ($index + 1) . ":" . PHP_EOL;
    echo "      Nombre: " . $prenda->nombre_producto . PHP_EOL;
    echo "      Descripción: " . substr($prenda->descripcion, 0, 50) . "..." . PHP_EOL;
    echo "      Tallas: " . (is_object($prenda->tallas) && method_exists($prenda->tallas, 'count') ? $prenda->tallas->count() : (is_array($prenda->tallas) ? count($prenda->tallas) : 'N/A')) . PHP_EOL;
    echo "      Fotos: " . (is_object($prenda->fotos) && method_exists($prenda->fotos, 'count') ? $prenda->fotos->count() : (is_array($prenda->fotos) ? count($prenda->fotos) : '0')) . PHP_EOL;
    echo "      Variantes: " . $prenda->variantes->count() . PHP_EOL;
    echo PHP_EOL;
}

echo "✅ Cotización cargada correctamente con " . $cot->prendas->count() . " prenda(s)" . PHP_EOL;
