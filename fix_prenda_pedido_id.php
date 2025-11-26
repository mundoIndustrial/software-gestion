<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "🔧 Haciendo prenda_pedido_id nullable...\n\n";

try {
    DB::statement('ALTER TABLE procesos_prenda MODIFY COLUMN prenda_pedido_id BIGINT UNSIGNED NULL');
    echo "✅ Columna prenda_pedido_id hecha nullable\n";
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n✅ Completado\n";
