<?php
/**
 * Script para analizar la columna META en el Excel de PRODUCCIÓN
 */

require __DIR__.'/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

$excelFile = __DIR__ . '/resources/CONTROL DE PISO PRODUCCION (respuestas).xlsx';

try {
    $spreadsheet = IOFactory::load($excelFile);
    $worksheet = $spreadsheet->getSheetByName('REGISTRO');
    
    $highestRow = $worksheet->getHighestRow();
    
    // Leer encabezados
    $headers = [];
    foreach ($worksheet->getRowIterator(1, 1) as $row) {
        $cellIterator = $row->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(false);
        foreach ($cellIterator as $cell) {
            $headers[] = trim(strtoupper($cell->getValue() ?? ''));
        }
    }
    
    // Encontrar índices
    $fechaIdx = array_search('FECHA', $headers);
    $moduloIdx = array_search('MODULO', $headers);
    $metaIdx = array_search('META', $headers);
    
    echo "=== ANÁLISIS META EN EXCEL - OCTUBRE - PRODUCCIÓN ===\n\n";
    
    $sumaMetaTotal = 0;
    $sumaMetaModulo1 = 0;
    $sumaMetaModulo2 = 0;
    $sumaMetaModulo3 = 0;
    $registrosOctubre = 0;
    $ejemplos = [];
    
    for ($row = 2; $row <= $highestRow; $row++) {
        $rowData = $worksheet->rangeToArray("A$row:P$row", null, true, true, false)[0];
        
        $fechaRaw = $rowData[$fechaIdx] ?? null;
        $modulo = $rowData[$moduloIdx] ?? null;
        $meta = $rowData[$metaIdx] ?? null;
        
        // Formatear fecha
        $fechaCell = $worksheet->getCell(chr(65 + $fechaIdx) . $row);
        $fechaValor = $fechaCell->getValue();
        
        $fechaFormateada = '';
        if (is_numeric($fechaValor) && $fechaValor > 0) {
            try {
                $dateObj = Date::excelToDateTimeObject($fechaValor);
                $fechaFormateada = $dateObj->format('Y-m-d');
            } catch (Exception $e) {
                continue;
            }
        }
        
        // Filtrar octubre 2025
        if (strpos($fechaFormateada, '2025-10') === 0) {
            $registrosOctubre++;
            $sumaMetaTotal += floatval($meta);
            
            if ($modulo === 'MODULO 1') {
                $sumaMetaModulo1 += floatval($meta);
            } elseif ($modulo === 'MODULO 2') {
                $sumaMetaModulo2 += floatval($meta);
            } elseif ($modulo === 'MODULO 3') {
                $sumaMetaModulo3 += floatval($meta);
            }
            
            // Guardar algunos ejemplos
            if (count($ejemplos) < 20) {
                $ejemplos[] = [
                    'fila' => $row,
                    'fecha' => $fechaFormateada,
                    'modulo' => $modulo,
                    'meta' => $meta
                ];
            }
        }
    }
    
    echo "RESULTADOS:\n";
    echo "Total registros octubre: $registrosOctubre\n";
    echo "Suma META total: $sumaMetaTotal\n";
    echo "Suma META MODULO 1: $sumaMetaModulo1\n";
    echo "Suma META MODULO 2: $sumaMetaModulo2\n";
    echo "Suma META MODULO 3: $sumaMetaModulo3\n\n";
    
    echo "PRIMEROS 20 EJEMPLOS:\n";
    foreach ($ejemplos as $ej) {
        echo "  Fila {$ej['fila']}: Fecha: {$ej['fecha']} | Módulo: {$ej['modulo']} | META: {$ej['meta']}\n";
    }
    
} catch (Exception $e) {
    die("❌ Error: " . $e->getMessage() . "\n");
}
