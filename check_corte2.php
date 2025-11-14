<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$registro = \App\Models\RegistroPisoCorte::find(7273);

echo "\n=== REGISTRO 7273 (SIN CÁLCULOS) ===\n";
echo json_encode($registro->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo "\n\n";

$registro2 = \App\Models\RegistroPisoCorte::find(7272);
echo "=== REGISTRO 7272 (CON CÁLCULOS) ===\n";
echo json_encode($registro2->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo "\n";
