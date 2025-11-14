<?php

require 'vendor/autoload.php';
require 'bootstrap/app.php';

use App\Models\RegistroPisoCorte;

// Query para los registros cerca de las 13:58
$registros = RegistroPisoCorte::where('created_at', '>=', '2025-11-14 13:57:00')
    ->where('created_at', '<=', '2025-11-14 14:00:00')
    ->orderBy('created_at')
    ->select('id', 'fecha', 'orden_produccion', 'cantidad', 'tiempo_ciclo', 'tiempo_disponible', 'meta', 'eficiencia', 'created_at', 'tiempo_parada_no_programada', 'tipo_extendido', 'actividad')
    ->get();

echo json_encode($registros, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
