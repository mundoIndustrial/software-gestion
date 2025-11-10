<?php
/**
 * Script para generar SQL desde el Excel de CONTROL DE PISO POLOS
 * Lee la hoja REGISTRO y genera un solo INSERT con múltiples VALUES
 */

require __DIR__.'/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

$excelFile = __DIR__ . '/resources/CONTROL DE PISO POLOS (Respuestas).xlsx';

if (!file_exists($excelFile)) {
    die("❌ No se encontró el archivo Excel: $excelFile\n");
}

echo "=== GENERANDO SQL DESDE EXCEL - REGISTRO_PISO_POLO ===\n\n";

try {
    // Cargar el archivo Excel
    $spreadsheet = IOFactory::load($excelFile);
    $worksheet = $spreadsheet->getSheetByName('REGISTRO');
    
    if (!$worksheet) {
        die("❌ No se encontró la hoja 'REGISTRO' en el Excel\n");
    }
    
    $highestRow = $worksheet->getHighestRow();
    $highestColumn = $worksheet->getHighestColumn();
    
    echo "📄 Archivo: CONTROL DE PISO POLOS (Respuestas).xlsx\n";
    echo "📋 Hoja: REGISTRO\n";
    echo "📊 Total filas: $highestRow\n";
    echo "📊 Total columnas: $highestColumn\n\n";
    
    // Leer encabezados
    $headers = [];
    $headerRow = 1;
    foreach ($worksheet->getRowIterator($headerRow, $headerRow) as $row) {
        $cellIterator = $row->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(false);
        foreach ($cellIterator as $cell) {
            $headers[] = trim(strtoupper($cell->getValue() ?? ''));
        }
    }
    
    echo "ENCABEZADOS ENCONTRADOS:\n";
    foreach ($headers as $i => $header) {
        echo ($i + 1) . ". $header\n";
    }
    echo "\n";
    
    // Mapeo de columnas
    $mapaColumnas = [
        "MARCA TEMPORAL" => null, // Ignorar
        "FECHA" => "fecha",
        "MODULO" => "modulo",
        "ORDEN DE PRODUCCIÓN" => "orden_produccion",
        "HORA" => "hora",
        "TIEMPO DE CICLO" => "tiempo_ciclo",
        "PORCIÓN DE TIEMPO" => "porcion_tiempo",
        "CANTIDAD PRODUCIDA" => "cantidad",
        "PARADAS PROGRAMADAS" => "paradas_programadas",
        "PARADAS NO PROGRAMADAS" => "paradas_no_programadas",
        "TIEMPO DE PARADA NO PROGRAMADA" => "tiempo_parada_no_programada",
        "NÚMERO DE OPERARIOS" => "numero_operarios",
        "TIEMPO PARA PROG" => "tiempo_para_programada",
        "TIEMPO DISP" => "tiempo_disponible",
        "META" => "meta",
        "EFICIENCIA" => "eficiencia"
    ];
    
    // Crear mapeo de índices
    $columnIndexMap = [];
    foreach ($mapaColumnas as $excelHeader => $sqlColumn) {
        $index = array_search($excelHeader, $headers);
        if ($index !== false && $sqlColumn !== null) {
            $columnIndexMap[$sqlColumn] = $index;
        }
    }
    
    // Generar VALUES
    $valuesArray = [];
    $totalProcesadas = 0;
    $filasDescartadas = [];
    
    for ($row = 2; $row <= $highestRow; $row++) {
        $rowData = $worksheet->rangeToArray("A$row:$highestColumn$row", null, true, true, false)[0];
        
        // Extraer datos según el mapeo
        $fechaRaw = isset($columnIndexMap['fecha']) ? $rowData[$columnIndexMap['fecha']] : null;
        $modulo = isset($columnIndexMap['modulo']) ? $rowData[$columnIndexMap['modulo']] : null;
        $orden_produccion = isset($columnIndexMap['orden_produccion']) ? $rowData[$columnIndexMap['orden_produccion']] : null;
        $hora = isset($columnIndexMap['hora']) ? $rowData[$columnIndexMap['hora']] : null;
        $tiempo_ciclo = isset($columnIndexMap['tiempo_ciclo']) ? $rowData[$columnIndexMap['tiempo_ciclo']] : null;
        $porcion_tiempo = isset($columnIndexMap['porcion_tiempo']) ? $rowData[$columnIndexMap['porcion_tiempo']] : null;
        $cantidad = isset($columnIndexMap['cantidad']) ? $rowData[$columnIndexMap['cantidad']] : null;
        $paradas_programadas = isset($columnIndexMap['paradas_programadas']) ? $rowData[$columnIndexMap['paradas_programadas']] : null;
        $paradas_no_programadas = isset($columnIndexMap['paradas_no_programadas']) ? $rowData[$columnIndexMap['paradas_no_programadas']] : null;
        $tiempo_parada_no_programada = isset($columnIndexMap['tiempo_parada_no_programada']) ? $rowData[$columnIndexMap['tiempo_parada_no_programada']] : null;
        $numero_operarios = isset($columnIndexMap['numero_operarios']) ? $rowData[$columnIndexMap['numero_operarios']] : null;
        $tiempo_para_programada = isset($columnIndexMap['tiempo_para_programada']) ? $rowData[$columnIndexMap['tiempo_para_programada']] : null;
        $tiempo_disponible = isset($columnIndexMap['tiempo_disponible']) ? $rowData[$columnIndexMap['tiempo_disponible']] : null;
        $meta = isset($columnIndexMap['meta']) ? $rowData[$columnIndexMap['meta']] : null;
        $eficiencia = isset($columnIndexMap['eficiencia']) ? $rowData[$columnIndexMap['eficiencia']] : null;
        
        // Validación: debe tener fecha (orden de producción puede ser 0, null o vacío)
        if (empty($fechaRaw)) {
            $filasDescartadas[] = "Fila $row: Sin fecha";
            continue;
        }
        
        // Si orden_produccion es 0, null o vacío, convertir a string '0' o 'SIN ORDEN'
        if ($orden_produccion === null || $orden_produccion === '' || $orden_produccion === 0) {
            $orden_produccion = '0';
        }
        
        // Formatear fecha - obtener directamente del objeto celda para fechas de Excel
        $fechaCell = $worksheet->getCell(chr(65 + $columnIndexMap['fecha']) . $row);
        $fechaFormateada = formatearFechaDesdeExcel($fechaCell, $fechaRaw);
        
        // Construir el VALUES para este registro
        // META y EFICIENCIA se calculan automáticamente con triggers, NO se insertan
        $values = sprintf(
            "('%s', '%s', '%s', '%s', %s, %s, %s, '%s', '%s', %s, %s, %s, %s, NOW(), NOW())",
            $fechaFormateada,
            escaparTexto($modulo),
            escaparTexto($orden_produccion),
            escaparTexto($hora),
            toDecimalOrNull($tiempo_ciclo),
            toDecimalOrNull($porcion_tiempo),
            toIntOrNull($cantidad),
            escaparTexto($paradas_programadas),
            escaparTexto($paradas_no_programadas),
            toDecimalOrNull($tiempo_parada_no_programada),
            toIntOrNull($numero_operarios),
            toDecimalOrNull($tiempo_para_programada),
            toDecimalOrNull($tiempo_disponible)
        );
        
        $valuesArray[] = $values;
        $totalProcesadas++;
    }
    
    // Generar SQL
    $sqlContent = "-- SQL generado desde: CONTROL DE PISO POLOS (Respuestas).xlsx\n";
    $sqlContent .= "-- Hoja: REGISTRO\n";
    $sqlContent .= "-- Fecha de generación: " . date('Y-m-d H:i:s') . "\n";
    $sqlContent .= "-- Total registros: $totalProcesadas\n\n";
    
    $sqlContent .= "-- ELIMINAR TODOS LOS REGISTROS ACTUALES\n";
    $sqlContent .= "TRUNCATE TABLE registro_piso_polo;\n\n";
    
    if (count($valuesArray) > 0) {
        $sqlContent .= "-- INSERTAR TODOS LOS REGISTROS EN UN SOLO INSERT\n";
        $sqlContent .= "-- NOTA: meta y eficiencia se calculan automáticamente con triggers\n";
        $sqlContent .= "INSERT INTO registro_piso_polo\n";
        $sqlContent .= "(fecha, modulo, orden_produccion, hora, tiempo_ciclo, porcion_tiempo, cantidad, paradas_programadas, paradas_no_programadas, tiempo_parada_no_programada, numero_operarios, tiempo_para_programada, tiempo_disponible, created_at, updated_at)\n";
        $sqlContent .= "VALUES\n";
        $sqlContent .= implode(",\n", $valuesArray);
        $sqlContent .= ";\n";
    }
    
    // Guardar archivo SQL
    $sqlFile = __DIR__ . '/insert_polo_desde_excel_' . date('Ymd_His') . '.sql';
    file_put_contents($sqlFile, $sqlContent);
    
    echo "✅ SQL generado exitosamente\n";
    echo "📄 Archivo: $sqlFile\n";
    echo "✅ Registros procesados: $totalProcesadas\n";
    echo "❌ Filas descartadas: " . count($filasDescartadas) . "\n\n";
    
    if (count($filasDescartadas) > 0) {
        echo "FILAS DESCARTADAS:\n";
        foreach (array_slice($filasDescartadas, 0, 10) as $descartada) {
            echo "  - $descartada\n";
        }
        if (count($filasDescartadas) > 10) {
            echo "  ... y " . (count($filasDescartadas) - 10) . " más\n";
        }
    }
    
    echo "\n📌 Para ejecutar el SQL, usa:\n";
    echo "   php artisan db:seed --class=ExecuteSqlFile --file=$sqlFile\n";
    echo "   O ejecuta manualmente el archivo SQL en tu base de datos\n";
    
} catch (Exception $e) {
    die("❌ Error: " . $e->getMessage() . "\n");
}

// === FUNCIONES AUXILIARES ===

function formatearFechaDesdeExcel($cell, $valorRaw) {
    // Intentar obtener el valor formateado de la celda
    if ($cell->isFormula()) {
        $valorCalculado = $cell->getCalculatedValue();
    } else {
        $valorCalculado = $cell->getValue();
    }
    
    // Si es un número de serie de Excel (fecha)
    if (is_numeric($valorCalculado) && $valorCalculado > 0) {
        try {
            $dateObj = Date::excelToDateTimeObject($valorCalculado);
            return $dateObj->format('Y-m-d');
        } catch (Exception $e) {
            // Continuar con otros métodos
        }
    }
    
    // Si el valor raw es un string con formato de fecha
    if (is_string($valorRaw)) {
        $valorRaw = trim($valorRaw);
        
        // Formato DD/MM/YYYY o D/M/YYYY
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})/', $valorRaw, $matches)) {
            $dia = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            $mes = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
            $anio = $matches[3];
            return "$anio-$mes-$dia";
        }
        
        // Formato YYYY-MM-DD
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $valorRaw)) {
            return substr($valorRaw, 0, 10);
        }
    }
    
    return '';
}

function formatearFecha($fecha) {
    if ($fecha instanceof DateTime) {
        return $fecha->format('Y-m-d');
    }
    
    // Si es un número de serie de Excel
    if (is_numeric($fecha)) {
        try {
            $dateObj = Date::excelToDateTimeObject($fecha);
            return $dateObj->format('Y-m-d');
        } catch (Exception $e) {
            return '';
        }
    }
    
    // Si es un string
    $fecha = trim($fecha);
    
    // Formato YYYY-MM-DD
    if (preg_match('/^\d{4}-\d{2}-\d{2}/', $fecha)) {
        return substr($fecha, 0, 10);
    }
    
    // Formato DD/MM/YYYY o D/M/YYYY
    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})/', $fecha, $matches)) {
        $dia = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
        $mes = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
        $anio = $matches[3];
        return "$anio-$mes-$dia";
    }
    
    // Intentar parsear con strtotime
    $timestamp = strtotime($fecha);
    if ($timestamp !== false) {
        return date('Y-m-d', $timestamp);
    }
    
    return '';
}

function limpiarNumero($valor) {
    if ($valor === null || $valor === '') return null;
    
    $s = trim($valor);
    
    if ($s === '' || strtoupper($s) === 'N/A' || strtoupper($s) === 'NA') {
        return null;
    }
    
    // Reemplazar comas por puntos
    $s = str_replace(',', '.', $s);
    
    // Eliminar todo excepto dígitos, puntos y signo negativo
    $s = preg_replace('/[^\d.\-]/', '', $s);
    
    return $s === '' ? null : $s;
}

function toIntOrNull($valor) {
    $limpio = limpiarNumero($valor);
    if ($limpio === null) return 'NULL';
    
    $num = intval($limpio);
    return $num;
}

function toDecimalOrNull($valor) {
    $limpio = limpiarNumero($valor);
    if ($limpio === null) return 'NULL';
    
    $num = floatval($limpio);
    if (is_nan($num)) return 'NULL';
    
    // Preservar hasta 9 decimales
    return $num;
}

function escaparTexto($texto) {
    if ($texto === null || $texto === '') return '';
    return addslashes(trim($texto));
}
