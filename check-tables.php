#!/usr/bin/env php
<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n═══════════════════════════════════════════════════════════\n";
echo "📊 ANÁLISIS DE TABLAS EN LA BASE DE DATOS\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// Obtener todas las tablas
$tables = DB::select("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME");

echo "📋 TODAS LAS TABLAS:\n";
$tableNames = [];
foreach($tables as $t) {
    echo "  • {$t->TABLE_NAME}\n";
    $tableNames[] = $t->TABLE_NAME;
}

echo "\n───────────────────────────────────────────────────────────\n";
echo "🎯 TABLAS CON 'ENTREGA':\n";
$entregaTables = array_filter($tableNames, fn($t) => stripos($t, 'entrega') !== false);
if(empty($entregaTables)) {
    echo "  ❌ NO hay tablas con 'entrega' en el nombre\n";
} else {
    foreach($entregaTables as $t) {
        echo "  ✓ $t\n";
    }
}

echo "\n═══════════════════════════════════════════════════════════\n";
