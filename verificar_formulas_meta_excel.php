<?php
/**
 * Script para verificar si la columna META tiene fórmulas
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
    
    echo "=== VERIFICAR FÓRMULAS EN META - OCTUBRE ===\n\n";
    echo "Índice de columna META: $metaIdx\n";
    echo "Letra de columna META: " . chr(65 + $metaIdx) . "\n\n";
    
    $sumaMetaValorCalculado = 0;
    $sumaMetaValorMostrado = 0;
    $registrosConFormula = 0;
    $registrosOctubre = 0;
    $ejemplosFormulas = [];
    
    for ($row = 2; $row <= $highestRow; $row++) {
        $rowData = $worksheet->rangeToArray("A$row:P$row", null, true, true, false)[0];
        
        $fechaRaw = $rowData[$fechaIdx] ?? null;
        $modulo = $rowData[$moduloIdx] ?? null;
        
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
            
            // Obtener la celda de META
            $metaCell = $worksheet->getCell(chr(65 + $metaIdx) . $row);
            
            $valorMostrado = $metaCell->getValue();
            $valorCalculado = $metaCell->getCalculatedValue();
            $tieneFormula = $metaCell->isFormula();
            
            if ($tieneFormula) {
                $registrosConFormula++;
                if (count($ejemplosFormulas) < 10) {
                    $ejemplosFormulas[] = [
                        'fila' => $row,
                        'fecha' => $fechaFormateada,
                        'modulo' => $modulo,
                        'formula' => $metaCell->getValue(),
                        'valor_calculado' => $valorCalculado
                    ];
                }
            }
            
            $sumaMetaValorMostrado += floatval($valorMostrado);
            $sumaMetaValorCalculado += floatval($valorCalculado);
        }
    }
    
    echo "RESULTADOS:\n";
    echo "Total registros octubre: $registrosOctubre\n";
    echo "Registros con fórmula en META: $registrosConFormula\n";
    echo "Suma META (valor mostrado): $sumaMetaValorMostrado\n";
    echo "Suma META (valor calculado): $sumaMetaValorCalculado\n\n";
    
    if (count($ejemplosFormulas) > 0) {
        echo "EJEMPLOS DE FÓRMULAS:\n";
        foreach ($ejemplosFormulas as $ej) {
            echo "  Fila {$ej['fila']}: {$ej['modulo']} | Fórmula: {$ej['formula']} | Valor: {$ej['valor_calculado']}\n";
        }
    } else {
        echo "No se encontraron fórmulas en la columna META\n";
    }
    
} catch (Exception $e) {
    die("❌ Error: " . $e->getMessage() . "\n");
}
