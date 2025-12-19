<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$db = $app['db'];
try {
    $db->statement('DROP TABLE IF EXISTS logo_pedido_imagenes');
    echo "✅ Tabla logo_pedido_imagenes eliminada\n";
} catch (Exception $e) {
    echo "⚠️ " . $e->getMessage() . "\n";
}

try {
    $db->statement('DROP TABLE IF EXISTS logo_pedidos');
    echo "✅ Tabla logo_pedidos eliminada\n";
} catch (Exception $e) {
    echo "⚠️ " . $e->getMessage() . "\n";
}
