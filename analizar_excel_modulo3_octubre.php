<?php
/**
 * Script para analizar MÓDULO 3 - OCTUBRE - HORA 08 desde el Excel
 */

require __DIR__.'/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

$excelFile = __DIR__ . '/resources/CONTROL DE PISO POLOS (Respuestas).xlsx';

if (!file_exists($excelFile)) {
    die("❌ No se encontró el archivo Excel: $excelFile\n");
}

echo "=== ANÁLISIS MÓDULO 3 - OCTUBRE - HORA 08 DESDE EXCEL ===\n\n";

try {
    $spreadsheet = IOFactory::load($excelFile);
    $worksheet = $spreadsheet->getSheetByName('REGISTRO');
    
    if (!$worksheet) {
        die("❌ No se encontró la hoja 'REGISTRO'\n");
    }
    
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
    
    // Encontrar índices de columnas
    $fechaIdx = array_search('FECHA', $headers);
    $moduloIdx = array_search('MODULO', $headers);
    $horaIdx = array_search('HORA', $headers);
    $cantidadIdx = array_search('CANTIDAD PRODUCIDA', $headers);
    $ordenIdx = array_search('ORDEN DE PRODUCCIÓN', $headers);
    
    echo "Índices de columnas:\n";
    echo "  FECHA: $fechaIdx\n";
    echo "  MODULO: $moduloIdx\n";
    echo "  HORA: $horaIdx\n";
    echo "  CANTIDAD PRODUCIDA: $cantidadIdx\n";
    echo "  ORDEN DE PRODUCCIÓN: $ordenIdx\n\n";
    
    // Filtrar registros de MÓDULO 3 - OCTUBRE - HORA 08
    $registrosEncontrados = [];
    $totalCantidad = 0;
    
    for ($row = 2; $row <= $highestRow; $row++) {
        $rowData = $worksheet->rangeToArray("A$row:Q$row", null, true, true, false)[0];
        
        $fechaRaw = $rowData[$fechaIdx] ?? null;
        $modulo = $rowData[$moduloIdx] ?? null;
        $hora = $rowData[$horaIdx] ?? null;
        $cantidad = $rowData[$cantidadIdx] ?? null;
        $orden = $rowData[$ordenIdx] ?? null;
        
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
        
        // Filtrar: MÓDULO 3, OCTUBRE 2025, HORA 08
        if ($modulo === 'MODULO 3' && 
            $hora === 'HORA 08' && 
            strpos($fechaFormateada, '2025-10') === 0) {
            
            $registrosEncontrados[] = [
                'fila' => $row,
                'fecha' => $fechaFormateada,
                'orden' => $orden,
                'cantidad' => $cantidad
            ];
            
            $totalCantidad += intval($cantidad);
        }
    }
    
    echo "REGISTROS ENCONTRADOS EN EXCEL:\n";
    echo "Total registros: " . count($registrosEncontrados) . "\n";
    echo "Total cantidad: $totalCantidad\n\n";
    
    echo "DETALLE:\n";
    foreach ($registrosEncontrados as $reg) {
        echo "  Fila {$reg['fila']}: Fecha: {$reg['fecha']} | Orden: {$reg['orden']} | Cantidad: {$reg['cantidad']}\n";
    }
    
    echo "\n=== FIN DE ANÁLISIS ===\n";
    
} catch (Exception $e) {
    die("❌ Error: " . $e->getMessage() . "\n");
}
