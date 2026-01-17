#!/usr/bin/env php
<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$categorias = \DB::table('epp_categorias')->get(['id', 'codigo', 'nombre']);

echo "📋 Categorías en BD:\n\n";
foreach ($categorias as $cat) {
    echo "{$cat->id}: {$cat->codigo} - {$cat->nombre}\n";
}
