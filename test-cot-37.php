<?php
require 'vendor/autoload.php';
require 'bootstrap/app.php';

$app = app();

$cot = App\Models\Cotizacion::find(37);
if (!$cot) {
    echo "❌ Cotización 37 no encontrada\n";
    exit;
}

echo "✅ Cotización 37 encontrada\n";
echo "ID: " . $cot->id . "\n";

if ($cot->logoCotizacion) {
    echo "\n📸 Logo Cotización encontrado:\n";
    $imagenes = $cot->logoCotizacion->imagenes;
    if ($imagenes) {
        echo "Tipo de dato: " . gettype($imagenes) . "\n";
        if (is_array($imagenes)) {
            echo "Cantidad: " . count($imagenes) . "\n";
            foreach ($imagenes as $idx => $img) {
                echo "  [$idx] => $img\n";
            }
        } else {
            echo "Contenido: " . $imagenes . "\n";
        }
    } else {
        echo "Sin imágenes\n";
    }
} else {
    echo "\n❌ Sin LogoCotizacion\n";
}
