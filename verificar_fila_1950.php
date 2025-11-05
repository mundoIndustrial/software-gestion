<?php
/**
 * Script para verificar la fila 1950 del Excel
 */

require __DIR__.'/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

$excelFile = __DIR__ . '/resources/CONTROL DE PISO POLOS (Respuestas).xlsx';

try {
    $spreadsheet = IOFactory::load($excelFile);
    $worksheet = $spreadsheet->getSheetByName('REGISTRO');
    
    $row = 1950;
    
    echo "=== ANÁLISIS FILA 1950 ===\n\n";
    
    // Leer encabezados
    $headers = [];
    foreach ($worksheet->getRowIterator(1, 1) as $headerRow) {
        $cellIterator = $headerRow->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(false);
        foreach ($cellIterator as $cell) {
            $headers[] = trim($cell->getValue() ?? '');
        }
    }
    
    // Leer datos de la fila 1950
    $rowData = $worksheet->rangeToArray("A$row:Q$row", null, true, true, false)[0];
    
    echo "DATOS DE LA FILA 1950:\n";
    foreach ($headers as $i => $header) {
        $valor = $rowData[$i] ?? 'NULL';
        
        // Si es la columna de fecha, intentar formatearla
        if (strtoupper($header) === 'FECHA' && is_numeric($valor)) {
            try {
                $dateObj = Date::excelToDateTimeObject($valor);
                $valorFormateado = $dateObj->format('Y-m-d');
                echo "  $header: $valor (formateado: $valorFormateado)\n";
            } catch (Exception $e) {
                echo "  $header: $valor\n";
            }
        } else {
            echo "  $header: $valor\n";
        }
    }
    
    echo "\n";
    
    // Verificar si este registro está en la base de datos
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
    
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'],
        $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "BÚSQUEDA EN BASE DE DATOS:\n";
    $stmt = $pdo->query("
        SELECT *
        FROM registro_piso_polo
        WHERE fecha = '2025-10-14'
          AND modulo = 'MODULO 3'
          AND hora = 'HORA 08'
    ");
    
    $found = false;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "  Encontrado: ID {$row['id']} | Orden: {$row['orden_produccion']} | Cantidad: {$row['cantidad']}\n";
        $found = true;
    }
    
    if (!$found) {
        echo "  ❌ NO SE ENCONTRÓ ESTE REGISTRO EN LA BASE DE DATOS\n";
        echo "  Este es el registro que falta (34 unidades)\n";
    }
    
} catch (Exception $e) {
    die("❌ Error: " . $e->getMessage() . "\n");
}
