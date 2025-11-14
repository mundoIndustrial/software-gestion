<?php

require 'vendor/autoload.php';

// Set up Laravel app
$app = require_once 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Http\Kernel');
$response = $kernel->handle($request = \Illuminate\Http\Request::capture());

// Now we can use models
use App\Models\User;

$operarios = User::take(10)->get(['id', 'name', 'email']);

echo "=== OPERARIOS DISPONIBLES ===\n";
foreach ($operarios as $op) {
    echo "ID: {$op->id} | Nombre: {$op->name}\n";
}

// Verificar relaciones
use App\Models\Hora;
use App\Models\Maquina;
use App\Models\Tela;

echo "\n=== HORAS DISPONIBLES ===\n";
$horas = Hora::take(5)->get(['id', 'hora']);
foreach ($horas as $hora) {
    echo "ID: {$hora->id} | Hora: {$hora->hora}\n";
}

echo "\n=== MÁQUINAS DISPONIBLES ===\n";
$maquinas = Maquina::take(5)->get(['id', 'nombre_maquina']);
foreach ($maquinas as $maquina) {
    echo "ID: {$maquina->id} | Máquina: {$maquina->nombre_maquina}\n";
}

echo "\n=== TELAS DISPONIBLES ===\n";
$telas = Tela::take(5)->get(['id', 'nombre_tela']);
foreach ($telas as $tela) {
    echo "ID: {$tela->id} | Tela: {$tela->nombre_tela}\n";
}
?>
