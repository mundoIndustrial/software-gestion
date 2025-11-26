<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "🔍 Analizando estructura de procesos en tabla_original...\n\n";

// Obtener una muestra de tabla_original
$muestra = DB::table('tabla_original')->limit(1)->first();

if (!$muestra) {
    echo "❌ No hay datos en tabla_original\n";
    exit;
}

echo "📋 Campos de fecha encontrados:\n";
echo json_encode((array)$muestra, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

echo "\n\n📊 Estructura de procesos esperada:\n";
echo "
Cada proceso tiene:
1. Fecha de inicio (cuando comienza)
2. Fecha de fin (cuando termina)
3. Encargado/responsable
4. DIAS = Diferencia entre fecha_fin - fecha_inicio

EJEMPLO:
- Proceso: Corte
  - Fecha Inicio: 2025-11-21
  - Fecha Fin: 2025-11-24
  - Días: 3 días (24-21)
  - Encargado: CARLOS

¿Cuál es la estructura correcta en tu tabla_original?
¿Cada proceso tiene fecha_inicio y fecha_fin, o solo una fecha?
";
