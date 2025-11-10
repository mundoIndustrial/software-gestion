<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Tela;
use App\Models\Maquina;
use App\Models\TiempoCiclo;

echo "=== VERIFICANDO TELA DRILL ===\n\n";

$telas = Tela::where('nombre_tela', 'LIKE', '%DRILL%')->get();
echo "Telas encontradas con DRILL: " . $telas->count() . "\n";
foreach ($telas as $tela) {
    echo "  - ID: {$tela->id}, Nombre: {$tela->nombre_tela}\n";
}

echo "\n=== VERIFICANDO MÁQUINA BANANA ===\n\n";

$banana = Maquina::where('nombre_maquina', 'BANANA')->first();
if ($banana) {
    echo "Máquina BANANA encontrada - ID: {$banana->id}\n";
} else {
    echo "Máquina BANANA NO encontrada\n";
}

echo "\n=== VERIFICANDO TIEMPOS DE CICLO PARA DRILL ===\n\n";

foreach ($telas as $tela) {
    $tiempos = TiempoCiclo::where('tela_id', $tela->id)->get();
    echo "Tela: {$tela->nombre_tela} (ID: {$tela->id})\n";
    foreach ($tiempos as $tiempo) {
        $maquina = Maquina::find($tiempo->maquina_id);
        echo "  - Máquina: {$maquina->nombre_maquina} (ID: {$tiempo->maquina_id}), Tiempo: {$tiempo->tiempo_ciclo}\n";
    }
    echo "\n";
}
