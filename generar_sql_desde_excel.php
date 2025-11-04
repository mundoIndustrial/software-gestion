<?php
/**
 * Generador de Scripts SQL desde Excel (CSV)
 * 
 * USO:
 * 1. Exporta tu hoja de Excel como CSV
 * 2. Ejecuta: php generar_sql_desde_excel.php archivo.csv
 * 3. Se generará un archivo SQL listo para ejecutar
 */

if ($argc < 2) {
    echo "❌ Uso: php generar_sql_desde_excel.php archivo.csv\n";
    echo "\n";
    echo "📝 Formato esperado del CSV:\n";
    echo "   - Primera fila: Nombre de prenda\n";
    echo "   - Segunda fila: Descripción\n";
    echo "   - Tercera fila: Referencia\n";
    echo "   - Luego encabezados: Letra, Operación, SAM, Máquina, Operario, Sección\n";
    echo "   - Resto: Operaciones\n";
    exit(1);
}

$archivo = $argv[1];

if (!file_exists($archivo)) {
    echo "❌ El archivo no existe: {$archivo}\n";
    exit(1);
}

echo "📂 Leyendo archivo: {$archivo}\n";

// Leer CSV
$rows = [];
if (($handle = fopen($archivo, "r")) !== FALSE) {
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $rows[] = $data;
    }
    fclose($handle);
}

if (empty($rows)) {
    echo "❌ El archivo está vacío\n";
    exit(1);
}

echo "📊 Filas leídas: " . count($rows) . "\n";

// Extraer información de la prenda
$nombrePrenda = null;
$descripcion = null;
$referencia = null;
$tipo = 'pantalon';
$totalOperarios = 10;
$turnos = 1;
$horasPorTurno = 8.0;

// Buscar datos en las primeras filas
for ($i = 0; $i < min(10, count($rows)); $i++) {
    $row = $rows[$i];
    
    if (empty($row)) continue;

    $key = strtolower(trim($row[0] ?? ''));
    $value = trim($row[1] ?? '');

    if (stripos($key, 'prenda') !== false || stripos($key, 'nombre') !== false) {
        $nombrePrenda = $nombrePrenda ?? $value;
    }
    if (stripos($key, 'descripcion') !== false) {
        $descripcion = $value;
    }
    if (stripos($key, 'referencia') !== false) {
        $referencia = $value;
    }
    if (stripos($key, 'tipo') !== false) {
        $tipo = strtolower($value);
    }
    if (stripos($key, 'operarios') !== false) {
        $totalOperarios = (int) $value;
    }
    if (stripos($key, 'turnos') !== false) {
        $turnos = (int) $value;
    }
    if (stripos($key, 'horas') !== false) {
        $horasPorTurno = (float) $value;
    }
}

// Valores por defecto
if (!$nombrePrenda) {
    $nombrePrenda = 'Prenda Importada ' . date('Y-m-d');
}
if (!$referencia) {
    $referencia = 'REF-' . strtoupper(substr(md5($nombrePrenda), 0, 10));
}
if (!$descripcion) {
    $descripcion = $nombrePrenda;
}

echo "\n👕 Prenda: {$nombrePrenda}\n";
echo "📝 Referencia: {$referencia}\n";
echo "👥 Operarios: {$totalOperarios} | Turnos: {$turnos} | Horas: {$horasPorTurno}\n";

// Buscar encabezados de operaciones
$headerRow = null;
$startRow = null;

for ($i = 0; $i < count($rows); $i++) {
    $row = $rows[$i];
    if (empty($row)) continue;

    $hasLetra = false;
    $hasOperacion = false;
    $hasSam = false;

    foreach ($row as $cell) {
        $cellLower = strtolower(trim($cell ?? ''));
        if (in_array($cellLower, ['letra', 'op', 'n°', 'no', '#'])) $hasLetra = true;
        if (in_array($cellLower, ['operacion', 'operación', 'descripcion', 'descripción'])) $hasOperacion = true;
        if (in_array($cellLower, ['sam', 'tiempo', 'min'])) $hasSam = true;
    }

    if ($hasOperacion && $hasSam) {
        $headerRow = $row;
        $startRow = $i + 1;
        break;
    }
}

if (!$headerRow || !$startRow) {
    echo "❌ No se encontraron encabezados de operaciones\n";
    exit(1);
}

// Mapear columnas
$colLetra = null;
$colOperacion = null;
$colSam = null;
$colMaquina = null;
$colOperario = null;
$colSeccion = null;
$colPrecedencia = null;

foreach ($headerRow as $index => $header) {
    $headerLower = strtolower(trim($header ?? ''));
    
    if (in_array($headerLower, ['letra', 'op', 'n°', 'no', '#'])) $colLetra = $index;
    if (in_array($headerLower, ['operacion', 'operación', 'descripcion', 'descripción'])) $colOperacion = $index;
    if (in_array($headerLower, ['sam', 'tiempo', 'min'])) $colSam = $index;
    if (in_array($headerLower, ['maquina', 'máquina', 'maq'])) $colMaquina = $index;
    if (in_array($headerLower, ['operario', 'trabajador'])) $colOperario = $index;
    if (in_array($headerLower, ['seccion', 'sección', 'área', 'area'])) $colSeccion = $index;
    if (in_array($headerLower, ['precedencia', 'prec', 'dep'])) $colPrecedencia = $index;
}

if ($colOperacion === null || $colSam === null) {
    echo "❌ No se encontraron las columnas necesarias (Operación y SAM)\n";
    exit(1);
}

echo "\n📋 Columnas detectadas:\n";
echo "   Operación: Col " . ($colOperacion + 1) . "\n";
echo "   SAM: Col " . ($colSam + 1) . "\n";

// Leer operaciones
$operaciones = [];
$letraActual = 'A';

for ($i = $startRow; $i < count($rows); $i++) {
    $row = $rows[$i];
    
    if (empty($row) || !isset($row[$colOperacion]) || !isset($row[$colSam])) {
        continue;
    }

    $operacion = trim($row[$colOperacion] ?? '');
    $sam = $row[$colSam] ?? 0;

    // Saltar filas vacías o totales
    if (empty($operacion) || stripos($operacion, 'total') !== false) {
        continue;
    }

    // Limpiar SAM
    $sam = preg_replace('/[^0-9.,]/', '', $sam);
    $sam = str_replace(',', '.', $sam);
    $sam = (float) $sam;

    if ($sam <= 0) {
        continue;
    }

    $letra = $colLetra !== null && isset($row[$colLetra]) ? trim($row[$colLetra]) : $letraActual++;
    $maquina = $colMaquina !== null && isset($row[$colMaquina]) ? trim($row[$colMaquina]) : '';
    $operario = $colOperario !== null && isset($row[$colOperario]) ? trim($row[$colOperario]) : '';
    $seccion = $colSeccion !== null && isset($row[$colSeccion]) ? trim($row[$colSeccion]) : 'OTRO';
    $precedencia = $colPrecedencia !== null && isset($row[$colPrecedencia]) ? trim($row[$colPrecedencia]) : '';

    $operaciones[] = [
        'letra' => $letra,
        'operacion' => $operacion,
        'sam' => $sam,
        'maquina' => $maquina,
        'operario' => $operario,
        'seccion' => strtoupper($seccion),
        'precedencia' => $precedencia,
    ];
}

if (empty($operaciones)) {
    echo "❌ No se encontraron operaciones válidas\n";
    exit(1);
}

$samTotal = array_sum(array_column($operaciones, 'sam'));
echo "\n✅ Operaciones encontradas: " . count($operaciones) . "\n";
echo "⏱️  SAM Total: " . round($samTotal, 1) . "\n";

// Generar SQL
$sqlFile = pathinfo($archivo, PATHINFO_FILENAME) . '_import.sql';
$sql = "-- ===============================================\n";
$sql .= "-- 👕 IMPORTACIÓN: " . strtoupper($nombrePrenda) . "\n";
$sql .= "-- ===============================================\n";
$sql .= "-- Generado automáticamente desde: {$archivo}\n";
$sql .= "-- Fecha: " . date('Y-m-d H:i:s') . "\n";
$sql .= "-- SAM Total: " . round($samTotal, 1) . "\n";
$sql .= "-- Operaciones: " . count($operaciones) . "\n";
$sql .= "-- ===============================================\n\n";

$sql .= "-- 1️⃣ Insertar la prenda\n";
$sql .= "INSERT INTO prendas (nombre, descripcion, referencia, tipo, activo, created_at, updated_at)\n";
$sql .= "SELECT nombre, descripcion, referencia, tipo, activo, created_at, updated_at\n";
$sql .= "FROM (\n";
$sql .= "    SELECT\n";
$sql .= "        " . sqlString($nombrePrenda) . " AS nombre,\n";
$sql .= "        " . sqlString($descripcion) . " AS descripcion,\n";
$sql .= "        " . sqlString($referencia) . " AS referencia,\n";
$sql .= "        " . sqlString($tipo) . " AS tipo,\n";
$sql .= "        1 AS activo,\n";
$sql .= "        NOW() AS created_at,\n";
$sql .= "        NOW() AS updated_at\n";
$sql .= ") AS tmp\n";
$sql .= "WHERE NOT EXISTS (\n";
$sql .= "    SELECT 1 FROM prendas WHERE referencia = " . sqlString($referencia) . "\n";
$sql .= ");\n\n";

$sql .= "-- 2️⃣ Obtener el ID de la prenda\n";
$sql .= "SET @prenda_id = (SELECT id FROM prendas WHERE referencia = " . sqlString($referencia) . ");\n\n";

$sql .= "-- 3️⃣ Crear el balanceo\n";
$sql .= "INSERT INTO balanceos (\n";
$sql .= "    prenda_id, version, total_operarios, turnos, horas_por_turno,\n";
$sql .= "    tiempo_disponible_horas, tiempo_disponible_segundos, sam_total,\n";
$sql .= "    meta_teorica, meta_real, operario_cuello_botella, tiempo_cuello_botella,\n";
$sql .= "    sam_real, meta_sugerida_85, activo, created_at, updated_at\n";
$sql .= ")\n";
$sql .= "VALUES (\n";
$sql .= "    @prenda_id, '1.0', {$totalOperarios}, {$turnos}, {$horasPorTurno},\n";
$sql .= "    0.0, 0.0, 0.0,\n";
$sql .= "    NULL, NULL, NULL, NULL,\n";
$sql .= "    NULL, NULL, 1, NOW(), NOW()\n";
$sql .= ");\n\n";

$sql .= "-- 4️⃣ Obtener el ID del balanceo\n";
$sql .= "SET @balanceo_id = LAST_INSERT_ID();\n\n";

$sql .= "-- 5️⃣ Insertar operaciones (" . count($operaciones) . " operaciones)\n";
$sql .= "INSERT INTO operaciones_balanceo\n";
$sql .= "(balanceo_id, letra, operacion, precedencia, maquina, sam, operario, op, seccion, orden, created_at, updated_at)\n";
$sql .= "VALUES\n";

$valores = [];
foreach ($operaciones as $index => $op) {
    $valores[] = sprintf(
        "(@balanceo_id, %s, %s, %s, %s, %s, %s, NULL, %s, %d, NOW(), NOW())",
        sqlString($op['letra']),
        sqlString($op['operacion']),
        sqlString($op['precedencia'] ?: ''),
        sqlString($op['maquina']),
        $op['sam'],
        $op['operario'] ? sqlString($op['operario']) : 'NULL',
        sqlString($op['seccion']),
        $index
    );
}

$sql .= implode(",\n", $valores) . ";\n\n";

$sql .= "-- 6️⃣ Calcular métricas\n";
$sql .= "UPDATE balanceos b\n";
$sql .= "SET b.sam_total = (\n";
$sql .= "  SELECT SUM(o.sam) FROM operaciones_balanceo o WHERE o.balanceo_id = b.id\n";
$sql .= ")\n";
$sql .= "WHERE b.id = @balanceo_id;\n\n";

$sql .= "-- ✅ Verificación\n";
$sql .= "SELECT\n";
$sql .= "  b.id AS balanceo_id,\n";
$sql .= "  p.nombre AS prenda,\n";
$sql .= "  ROUND(b.sam_total, 1) AS sam_total,\n";
$sql .= "  COUNT(o.id) AS total_operaciones\n";
$sql .= "FROM balanceos b\n";
$sql .= "JOIN prendas p ON b.prenda_id = p.id\n";
$sql .= "LEFT JOIN operaciones_balanceo o ON o.balanceo_id = b.id\n";
$sql .= "WHERE b.id = @balanceo_id\n";
$sql .= "GROUP BY b.id, p.nombre, b.sam_total;\n";

// Guardar archivo
file_put_contents($sqlFile, $sql);

echo "\n✅ Script SQL generado: {$sqlFile}\n";
echo "💡 Ejecuta el script en MySQL para importar el balanceo\n";

function sqlString($value) {
    if ($value === null || $value === '') {
        return "''";
    }
    return "'" . addslashes($value) . "'";
}
