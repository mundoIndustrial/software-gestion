#!/usr/bin/env php
<?php

require 'vendor/autoload.php';

use Illuminate\Support\Facades\Http;

// Inicializar Laravel
$app = require 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Http\Kernel::class)->handle(
    $request = \Illuminate\Http\Request::capture()
);

// Los datos para crear un nuevo registro de corte
$data = [
    'fecha' => date('Y-m-d'),
    'orden_produccion' => 'TEST-' . time(),
    'tela_id' => 417,
    'hora_id' => 1,
    'operario_id' => 3, // PAOLA
    'actividad' => 'Corte',
    'maquina_id' => 1,
    'tiempo_ciclo' => 80,
    'porcion_tiempo' => 1.0,
    'cantidad' => 40, // Campo correcto en la BD
    'paradas_programadas' => 'NINGUNA',
    'paradas_no_programadas' => 'TEST',
    'tiempo_parada_no_programada' => 300, // 5 minutos
    'tipo_extendido' => 'Ninguna',
    'numero_capas' => 0,
    'trazado' => 'NINGUNA',
    'tiempo_trazado' => 0,
];

echo "📝 Datos para crear:\n";
echo json_encode($data, JSON_PRETTY_PRINT) . "\n\n";

// Nota: Este es solo un test, requeriría autenticación en producción
// En su lugar, vamos a verificar directamente usando el modelo

use App\Models\RegistroPisoCorte;

$registro = RegistroPisoCorte::create($data);

echo "✅ Registro creado:\n";
echo "ID: " . $registro->id . "\n";
echo "Cantidad: " . $registro->cantidad . "\n";
echo "Tiempo Disponible: " . $registro->tiempo_disponible . "\n";
echo "Meta: " . $registro->meta . "\n";
echo "Eficiencia: " . $registro->eficiencia . "\n";

// Recargar para ver los datos persistidos
$registro->refresh();

echo "\n✅ Datos persistidos en BD:\n";
echo "Tiempo Disponible: " . $registro->tiempo_disponible . "\n";
echo "Meta: " . $registro->meta . "\n";
echo "Eficiencia: " . $registro->eficiencia . "\n";

?>
