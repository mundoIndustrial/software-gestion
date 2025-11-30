<?php
require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$prendas = \App\Models\PrendaPedido::where('cantidad_talla', null)
    ->whereNotNull('descripcion')
    ->get();

$actualizadas = 0;
$sin_tallas = 0;

foreach ($prendas as $prenda) {
    $tallas = [];
    $lineas = explode("\n", $prenda->descripcion);
    
    foreach ($lineas as $linea) {
        // Patrón: "TALLA: M:3, L:1, XL:1"
        if (preg_match('/TALLA:\s*(.+?)(?:\n|$)/i', $linea, $matches)) {
            $talla_str = trim($matches[1]);
            // Parsear pares "SIZE:QTY, SIZE:QTY"
            $pares = explode(',', $talla_str);
            foreach ($pares as $par) {
                $par = trim($par);
                if (preg_match('/([A-Z0-9]+):(\d+)/i', $par, $m)) {
                    $talla = strtoupper(trim($m[1]));
                    $cantidad = (int)$m[2];
                    if (!isset($tallas[$talla])) {
                        $tallas[$talla] = 0;
                    }
                    $tallas[$talla] += $cantidad;
                }
            }
        }
    }
    
    if (!empty($tallas)) {
        $json = json_encode($tallas);
        $prenda->update(['cantidad_talla' => $json]);
        $actualizadas++;
        echo "✅ Prenda ID {$prenda->id}: " . json_encode($tallas) . "\n";
    } else {
        $sin_tallas++;
    }
}

echo "\n════════════════════════════════════════════════════════════════\n";
echo "RESULTADO:\n";
echo "  ✅ Actualizadas: $actualizadas\n";
echo "  ⚠️  Sin tallas extraídas: $sin_tallas\n";
echo "════════════════════════════════════════════════════════════════\n";
