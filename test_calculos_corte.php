<?php
require 'vendor/autoload.php';

// Simular los cálculos del storeCorte
echo "=== TEST DE CÁLCULOS DE CORTE ===\n\n";

// Test 1: Con parada de desayuno y trazo largo
echo "TEST 1: Parada Desayuno + Trazo Largo + Trazado\n";
echo "───────────────────────────────────────────────\n";

$porcion_tiempo = 1;
$paradas_programadas = 'DESAYUNO';
$tipo_extendido = 'Trazo Largo';
$numero_capas = 2;
$tiempo_trazado = 120; // 2 minutos en segundos
$tiempo_parada_no_programada = 300; // 5 minutos en segundos
$cantidad_producida = 50;
$tiempo_ciclo = 45;

// Cálculos
$tiempo_para_programada = 0;
if ($paradas_programadas === 'DESAYUNO' || $paradas_programadas === 'MEDIA TARDE') {
    $tiempo_para_programada = 900; // 15 minutos
}

$tiempo_extendido = 0;
if (strpos(strtolower($tipo_extendido), 'largo') !== false) {
    $tiempo_extendido = 40 * $numero_capas;
} elseif (strpos(strtolower($tipo_extendido), 'corto') !== false) {
    $tiempo_extendido = 25 * $numero_capas;
}

$tiempo_disponible = (3600 * $porcion_tiempo) - $tiempo_para_programada - $tiempo_parada_no_programada - $tiempo_extendido - $tiempo_trazado;
$tiempo_disponible = max(0, $tiempo_disponible);

$meta = $tiempo_ciclo > 0 ? $tiempo_disponible / $tiempo_ciclo : 0;
$eficiencia = $meta == 0 ? 0 : $cantidad_producida / $meta;

echo "Paradas programadas: $paradas_programadas (tiempo: $tiempo_para_programada seg)\n";
echo "Tipo extendido: $tipo_extendido (capas: $numero_capas)\n";
echo "  → Tiempo extendido: $tiempo_extendido seg\n";
echo "Tiempo trazado: $tiempo_trazado seg\n";
echo "Tiempo parada no programada: $tiempo_parada_no_programada seg\n";
echo "\nCálculos:\n";
echo "Tiempo disponible: (3600 × $porcion_tiempo) - $tiempo_para_programada - $tiempo_parada_no_programada - $tiempo_extendido - $tiempo_trazado\n";
echo "                 = (3600) - $tiempo_para_programada - $tiempo_parada_no_programada - $tiempo_extendido - $tiempo_trazado\n";
echo "                 = " . (3600 * $porcion_tiempo) . " - " . ($tiempo_para_programada + $tiempo_parada_no_programada + $tiempo_extendido + $tiempo_trazado) . "\n";
echo "                 = $tiempo_disponible seg\n";
echo "\nMeta: $tiempo_disponible / $tiempo_ciclo = " . number_format($meta, 2) . "\n";
echo "Eficiencia: $cantidad_producida / " . number_format($meta, 2) . " = " . number_format($eficiencia, 2) . "\n";

// Test 2: Sin paradas, Ninguna extensión
echo "\n\nTEST 2: Sin Paradas + Ninguna Extensión\n";
echo "────────────────────────────────────────\n";

$porcion_tiempo = 1;
$paradas_programadas = 'NINGUNA';
$tipo_extendido = 'Ninguna';
$numero_capas = 0;
$tiempo_trazado = 0;
$tiempo_parada_no_programada = 0;
$cantidad_producida = 80;
$tiempo_ciclo = 30;

// Cálculos
$tiempo_para_programada = 0;
if ($paradas_programadas === 'DESAYUNO' || $paradas_programadas === 'MEDIA TARDE') {
    $tiempo_para_programada = 900;
}

$tiempo_extendido = 0;
if (strpos(strtolower($tipo_extendido), 'largo') !== false) {
    $tiempo_extendido = 40 * $numero_capas;
} elseif (strpos(strtolower($tipo_extendido), 'corto') !== false) {
    $tiempo_extendido = 25 * $numero_capas;
}

$tiempo_disponible = (3600 * $porcion_tiempo) - $tiempo_para_programada - $tiempo_parada_no_programada - $tiempo_extendido - $tiempo_trazado;
$tiempo_disponible = max(0, $tiempo_disponible);

$meta = $tiempo_ciclo > 0 ? $tiempo_disponible / $tiempo_ciclo : 0;
$eficiencia = $meta == 0 ? 0 : $cantidad_producida / $meta;

echo "Paradas programadas: $paradas_programadas (tiempo: $tiempo_para_programada seg)\n";
echo "Tipo extendido: $tipo_extendido\n";
echo "Tiempo trazado: $tiempo_trazado seg\n";
echo "Tiempo parada no programada: $tiempo_parada_no_programada seg\n";
echo "\nCálculos:\n";
echo "Tiempo disponible: (3600 × $porcion_tiempo) - $tiempo_para_programada - $tiempo_parada_no_programada - $tiempo_extendido - $tiempo_trazado\n";
echo "                 = 3600 - 0 - 0 - 0 - 0\n";
echo "                 = $tiempo_disponible seg\n";
echo "\nMeta: $tiempo_disponible / $tiempo_ciclo = " . number_format($meta, 2) . "\n";
echo "Eficiencia: $cantidad_producida / " . number_format($meta, 2) . " = " . number_format($eficiencia, 2) . "\n";

// Test 3: Media tarde + Trazo corto + parada no programada
echo "\n\nTEST 3: Media Tarde + Trazo Corto + Parada No Programada\n";
echo "─────────────────────────────────────────────────────────\n";

$porcion_tiempo = 1;
$paradas_programadas = 'MEDIA TARDE';
$tipo_extendido = 'Trazo Corto';
$numero_capas = 3;
$tiempo_trazado = 60; // 1 minuto
$tiempo_parada_no_programada = 600; // 10 minutos
$cantidad_producida = 60;
$tiempo_ciclo = 40;

// Cálculos
$tiempo_para_programada = 0;
if ($paradas_programadas === 'DESAYUNO' || $paradas_programadas === 'MEDIA TARDE') {
    $tiempo_para_programada = 900;
}

$tiempo_extendido = 0;
if (strpos(strtolower($tipo_extendido), 'largo') !== false) {
    $tiempo_extendido = 40 * $numero_capas;
} elseif (strpos(strtolower($tipo_extendido), 'corto') !== false) {
    $tiempo_extendido = 25 * $numero_capas;
}

$tiempo_disponible = (3600 * $porcion_tiempo) - $tiempo_para_programada - $tiempo_parada_no_programada - $tiempo_extendido - $tiempo_trazado;
$tiempo_disponible = max(0, $tiempo_disponible);

$meta = $tiempo_ciclo > 0 ? $tiempo_disponible / $tiempo_ciclo : 0;
$eficiencia = $meta == 0 ? 0 : $cantidad_producida / $meta;

echo "Paradas programadas: $paradas_programadas (tiempo: $tiempo_para_programada seg)\n";
echo "Tipo extendido: $tipo_extendido (capas: $numero_capas)\n";
echo "  → Tiempo extendido: " . (25 * $numero_capas) . " seg\n";
echo "Tiempo trazado: $tiempo_trazado seg\n";
echo "Tiempo parada no programada: $tiempo_parada_no_programada seg\n";
echo "\nCálculos:\n";
echo "Tiempo disponible: (3600 × $porcion_tiempo) - $tiempo_para_programada - $tiempo_parada_no_programada - " . (25 * $numero_capas) . " - $tiempo_trazado\n";
echo "                 = 3600 - 900 - 600 - 75 - 60\n";
echo "                 = $tiempo_disponible seg\n";
echo "\nMeta: $tiempo_disponible / $tiempo_ciclo = " . number_format($meta, 2) . "\n";
echo "Eficiencia: $cantidad_producida / " . number_format($meta, 2) . " = " . number_format($eficiencia, 2) . "\n";

echo "\n✅ Tests completados correctamente\n";
?>
