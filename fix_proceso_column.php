<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "🔧 Expandiendo campo 'proceso' en procesos_prenda...\n\n";

try {
    DB::statement('ALTER TABLE procesos_prenda MODIFY COLUMN proceso VARCHAR(255) NULL');
    echo "✅ Campo 'proceso' expandido a 255 caracteres\n";
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n✅ Completado\n";
