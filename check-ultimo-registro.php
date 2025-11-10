<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\RegistroPisoCorte;

echo "=== ÚLTIMO REGISTRO DE CORTE ===\n\n";

$registro = RegistroPisoCorte::orderBy('id', 'desc')->first();

if ($registro) {
    echo "ID: {$registro->id}\n";
    echo "Orden: {$registro->orden_produccion}\n";
    echo "Tiempo ciclo: {$registro->tiempo_ciclo}\n";
    echo "Tiempo disponible: {$registro->tiempo_disponible}\n";
    echo "Meta: {$registro->meta}\n";
    echo "Eficiencia: {$registro->eficiencia}\n";
    echo "Tela ID: {$registro->tela_id}\n";
    echo "Maquina ID: {$registro->maquina_id}\n";
    echo "Cantidad: {$registro->cantidad}\n";
    echo "Porcion tiempo: {$registro->porcion_tiempo}\n";
    echo "Tiempo extendido: {$registro->tiempo_extendido}\n";
    echo "Tiempo para programada: {$registro->tiempo_para_programada}\n";
    echo "Tiempo parada no programada: {$registro->tiempo_parada_no_programada}\n";
    echo "Tiempo trazado: {$registro->tiempo_trazado}\n";
    echo "Numero capas: {$registro->numero_capas}\n";
    echo "Tipo extendido: {$registro->tipo_extendido}\n";
    
    echo "\n=== VERIFICACIÓN DE CÁLCULOS ===\n";
    
    // Calcular tiempo_extendido
    $tiempo_extendido_calculado = 0;
    if (stripos($registro->tipo_extendido, 'largo') !== false) {
        $tiempo_extendido_calculado = 40 * $registro->numero_capas;
    } elseif (stripos($registro->tipo_extendido, 'corto') !== false) {
        $tiempo_extendido_calculado = 25 * $registro->numero_capas;
    }
    
    echo "Tiempo extendido calculado: {$tiempo_extendido_calculado}\n";
    echo "Tiempo extendido en DB: {$registro->tiempo_extendido}\n";
    
    $tiempo_disponible_calculado = (3600 * $registro->porcion_tiempo) - 
                                    ($registro->tiempo_para_programada + 
                                     ($registro->tiempo_parada_no_programada ?? 0) + 
                                     $tiempo_extendido_calculado + 
                                     ($registro->tiempo_trazado ?? 0));
    
    echo "Tiempo disponible calculado: {$tiempo_disponible_calculado}\n";
    echo "Tiempo disponible en DB: {$registro->tiempo_disponible}\n";
    
    if ($registro->tiempo_ciclo > 0) {
        $meta_calculada = $tiempo_disponible_calculado / $registro->tiempo_ciclo;
        echo "Meta calculada: {$meta_calculada}\n";
        echo "Meta en DB: {$registro->meta}\n";
        
        echo "\n=== SI USÁRAMOS TIEMPO CICLO 97 ===\n";
        $meta_con_97 = $tiempo_disponible_calculado / 97;
        echo "Meta con tiempo_ciclo 97: {$meta_con_97}\n";
    }
} else {
    echo "No hay registros de corte\n";
}
