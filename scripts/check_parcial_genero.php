<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$parcialId = isset($argv[1]) ? (int) $argv[1] : 0;

if ($parcialId <= 0) {
    fwrite(STDERR, "Uso: php scripts/check_parcial_genero.php <recibo_por_partes_id>\n");
    exit(1);
}

$rows = DB::table('recibos_por_partes_tallas')
    ->where('recibo_por_partes_id', $parcialId)
    ->orderBy('id')
    ->get(['id', 'recibo_por_partes_id', 'talla', 'genero', 'color_nombre', 'cantidad', 'created_at']);

echo json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
