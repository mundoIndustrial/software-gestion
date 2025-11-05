<?php

require __DIR__.'/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$archivo = 'resources/clasico (1).xlsx';

echo "=== LECTURA RAW DE EXCEL ===\n\n";

$spreadsheet = IOFactory::load($archivo);

// Buscar la hoja "JEANS CERAMICA ITALIA"
foreach ($spreadsheet->getAllSheets() as $worksheet) {
    if (stripos($worksheet->getTitle(), 'CERAMICA') !== false) {
        echo "Hoja encontrada: " . $worksheet->getTitle() . "\n\n";
        
        $sheet = $worksheet->toArray(null, true, true, true);
        
        // Buscar la columna SAM
        $headerRow = null;
        $samCol = null;
        
        foreach ($sheet as $rowNum => $row) {
            foreach ($row as $col => $cell) {
                if (strtoupper(trim($cell ?? '')) === 'SAM') {
                    $samCol = $col;
                    $headerRow = $rowNum;
                    break 2;
                }
            }
        }
        
        if (!$samCol) {
            echo "No se encontró columna SAM\n";
            exit;
        }
        
        echo "Columna SAM: {$samCol}\n";
        echo "Fila de encabezado: {$headerRow}\n\n";
        
        echo "Valores SAM (RAW):\n";
        echo str_repeat("=", 60) . "\n";
        
        $suma = 0;
        $contador = 0;
        
        foreach ($sheet as $rowNum => $row) {
            if ($rowNum <= $headerRow) continue;
            
            $samValue = $row[$samCol] ?? null;
            
            if ($samValue === null || $samValue === '') continue;
            if (!is_numeric($samValue)) continue;
            if ($samValue < 0) continue;
            
            $contador++;
            $suma += $samValue;
            
            // Mostrar el valor con máxima precisión
            echo sprintf("%2d. %s (tipo: %s, precisión: %.15f)\n", 
                $contador,
                var_export($samValue, true),
                gettype($samValue),
                (float)$samValue
            );
        }
        
        echo str_repeat("=", 60) . "\n";
        echo "\nTotal de valores: {$contador}\n";
        echo "Suma (máxima precisión): " . sprintf("%.15f", $suma) . "\n";
        echo "Suma redondeada a 1 decimal: " . round($suma, 1) . "\n";
        
        break;
    }
}
