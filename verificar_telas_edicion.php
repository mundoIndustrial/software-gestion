<?php
// Script para verificar si colores_telas tiene fotos en editar-datos

$response = file_get_contents('http://localhost/asesores/pedidos/2760/editar-datos');
$data = json_decode($response, true);

if (isset($data['data']['prendas'][0]['colores_telas'])) {
    echo "✅ ColorTelas encontrados: " . count($data['data']['prendas'][0]['colores_telas']) . "\n";
    foreach ($data['data']['prendas'][0]['colores_telas'] as $idx => $ct) {
        $fotosCount = isset($ct['fotos']) ? count($ct['fotos']) : 0;
        echo "  CT[$idx]: ID=" . $ct['id'] . ", Tela=" . ($ct['tela_nombre'] ?? 'N/A') . ", Fotos=$fotosCount\n";
        if ($fotosCount > 0) {
            echo "    Primer foto: " . $ct['fotos'][0]['ruta_webp'] . "\n";
        }
    }
} else {
    echo "❌ No hay colores_telas\n";
}
