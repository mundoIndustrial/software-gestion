<?php

require __DIR__.'/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$archivo = __DIR__ . '/resources/clasico (1).xlsx';

if (!file_exists($archivo)) {
    die("Archivo no encontrado: $archivo\n");
}

$spreadsheet = IOFactory::load($archivo);
$totalHojas = $spreadsheet->getSheetCount();

echo "📊 Total de hojas en el Excel: $totalHojas\n\n";

echo "📋 Lista de hojas:\n";
for ($i = 0; $i < $totalHojas; $i++) {
    $sheet = $spreadsheet->getSheet($i);
    $nombre = $sheet->getTitle();
    echo ($i + 1) . ". $nombre\n";
}
