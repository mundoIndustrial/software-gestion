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
    $desc = $prenda->descripcion;
    
    // Patrón 1: "TALLA: SIZE:QTY, SIZE:QTY" (nuevo formato)
    if (preg_match('/TALLA:\s*([^,\n]+(?:,\s*[A-Z0-9]+:\d+)*)/i', $desc, $m)) {
        $talla_str = $m[1];
        $pares = explode(',', $talla_str);
        foreach ($pares as $par) {
            if (preg_match('/([A-Z0-9]+):(\d+)/i', $par, $m2)) {
                $talla = strtoupper(trim($m2[1]));
                $cantidad = (int)$m2[2];
                if (!isset($tallas[$talla])) $tallas[$talla] = 0;
                $tallas[$talla] += $cantidad;
            }
        }
    }
    
    // Patrón 2: "TALLA, SIZE:QTY, SIZE:QTY" (formato antiguo con coma después de TALLA)
    if (empty($tallas) && preg_match('/TALLA\s*,\s*(.+?)(?:\n|$)/i', $desc, $m)) {
        $talla_str = trim($m[1]);
        // Limitar el string para evitar capturar demasiado
        $talla_str = preg_replace('/\s*(MODELO|PASAR|URGENTE|ANCHO|METRAJE|$).*/i', '', $talla_str);
        
        $pares = preg_split('/\s*,\s*/', trim($talla_str));
        foreach ($pares as $par) {
            if (preg_match('/([A-Z0-9]+):\s*(\d+)/i', $par, $m2)) {
                $talla = strtoupper(trim($m2[1]));
                $cantidad = (int)$m2[2];
                if (!isset($tallas[$talla])) $tallas[$talla] = 0;
                $tallas[$talla] += $cantidad;
            }
        }
    }
    
    // Patrón 3: "TIPO TALLA SIZE:QTY, SIZE:QTY" (ej: DAMA TALLA L:4, M:4 CABALLERO TALLA M:1)
    if (empty($tallas)) {
        // Buscar todos los bloques de "...TALLA SIZE:QTY..."
        preg_match_all('/(?:DAMA|CABALLERO|MUJER|HOMBRE)?\s*TALLA\s+([A-Z0-9]+\s*:\s*\d+(?:\s*,\s*[A-Z0-9]+\s*:\s*\d+)*)/i', $desc, $matches);
        
        if (!empty($matches[1])) {
            foreach ($matches[1] as $match) {
                $pares = preg_split('/\s*,\s*/', trim($match));
                foreach ($pares as $par) {
                    if (preg_match('/([A-Z0-9]+)\s*:\s*(\d+)/i', $par, $m2)) {
                        $talla = strtoupper(trim($m2[1]));
                        $cantidad = (int)$m2[2];
                        if (!isset($tallas[$talla])) $tallas[$talla] = 0;
                        $tallas[$talla] += $cantidad;
                    }
                }
            }
        }
    }
    
    if (!empty($tallas)) {
        $json = json_encode($tallas);
        $prenda->update(['cantidad_talla' => $json]);
        $actualizadas++;
    } else {
        $sin_tallas++;
    }
}

echo "Resultado: $actualizadas prendas actualizadas, $sin_tallas sin tallas\n";
