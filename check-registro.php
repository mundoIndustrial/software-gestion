<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\RegistroPisoCorte;

echo "=== VERIFICANDO REGISTRO 4213 ===\n\n";

$registro = RegistroPisoCorte::where('orden_produccion', '4213')->first();

if ($registro) {
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
    
    echo "\n=== CÁLCULO CORRECTO ===\n";
    $tiempo_disponible_calculado = (3600 * $registro->porcion_tiempo) - 
                                    ($registro->tiempo_para_programada + 
                                     ($registro->tiempo_parada_no_programada ?? 0) + 
                                     ($registro->tiempo_extendido ?? 0) + 
                                     ($registro->tiempo_trazado ?? 0));
    
    echo "Tiempo disponible calculado: {$tiempo_disponible_calculado}\n";
    
    if ($registro->tiempo_ciclo > 0) {
        $meta_con_45 = $tiempo_disponible_calculado / 45;
        $meta_con_97 = $tiempo_disponible_calculado / 97;
        
        echo "Meta con tiempo_ciclo 45: {$meta_con_45}\n";
        echo "Meta con tiempo_ciclo 97: {$meta_con_97}\n";
    }
} else {
    echo "No se encontró el registro con orden 4213\n";
}
