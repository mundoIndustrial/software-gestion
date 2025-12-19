<?php

/**
 * Script de Verificación - LOGO Pedidos Implementation
 * 
 * Verifica que todos los componentes necesarios estén presentes
 * Uso: php check_logo_implementation.php
 */

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "🎨  VERIFICACIÓN DE IMPLEMENTACIÓN LOGO PEDIDOS\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$checks = [
    'Modelos' => [
        'LogoPedido' => file_exists(__DIR__ . '/app/Models/LogoPedido.php'),
        'LogoPedidoImagen' => file_exists(__DIR__ . '/app/Models/LogoPedidoImagen.php'),
    ],
    'Migraciones' => [
        'create_logo_pedidos_table' => glob(__DIR__ . '/database/migrations/*create_logo_pedidos_table.php'),
        'create_logo_pedido_imagenes_table' => glob(__DIR__ . '/database/migrations/*create_logo_pedido_imagenes_table.php'),
    ],
    'Rutas' => [
        'routes/asesores/pedidos.php' => file_exists(__DIR__ . '/routes/asesores/pedidos.php'),
    ],
    'Controlador' => [
        'PedidoProduccionController' => file_exists(__DIR__ . '/app/Http/Controllers/Asesores/PedidoProduccionController.php'),
    ],
    'JavaScript' => [
        'crear-pedido-editable.js' => file_exists(__DIR__ . '/public/js/crear-pedido-editable.js'),
    ],
];

$allGood = true;

foreach ($checks as $category => $items) {
    echo "📋 $category\n";
    echo str_repeat('─', 60) . "\n";
    
    foreach ($items as $name => $result) {
        if (is_array($result)) {
            $exists = !empty($result);
        } else {
            $exists = $result;
        }
        
        $status = $exists ? '✅ SÍ' : '❌ NO';
        echo "   $status   $name\n";
        
        if (!$exists) {
            $allGood = false;
        }
    }
    echo "\n";
}

echo "═══════════════════════════════════════════════════════════════\n";

if ($allGood) {
    echo "✅ TODOS LOS COMPONENTES ESTÁN PRESENTES\n";
    echo "\n📝 PRÓXIMOS PASOS:\n";
    echo "   1. Ejecutar: php artisan migrate\n";
    echo "   2. Verificar tablas: php artisan tinker\n";
    echo "   3. Probar en navegador\n";
} else {
    echo "❌ FALTAN COMPONENTES - Revisar los marcados con ❌\n";
    echo "\n📝 ACCIONES RECOMENDADAS:\n";
    echo "   1. Verificar que los archivos existen en las rutas correctas\n";
    echo "   2. Ejecutar nuevamente para rechecquear\n";
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "\n";
