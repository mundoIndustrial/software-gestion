<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$rows = Illuminate\Support\Facades\DB::table('prenda_recibo_completado')
    ->where(function($q){
        $q->where('id_recibo', 1057)
          ->orWhere('numero_recibo', 18);
    })
    ->orderByDesc('id')
    ->get();

echo "=== completados 1057/18 ===\n";
echo json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

$user = App\Models\User::find(128);
$svc = app(App\Infrastructure\Services\Operario\ObtenerPrendasRecibosListadoService::class);
$prendas = $svc->obtenerPrendasConRecibos($user, 'bodega');

echo "=== prendas bodega lider-reflectivo ===\n";
foreach ($prendas as $p) {
    $r = $p['recibos'][0] ?? [];
    echo json_encode([
        'numero_pedido' => $p['numero_pedido'] ?? null,
        'encargado_costura' => $p['encargado_costura'] ?? null,
        'recibo_id' => $r['id'] ?? null,
        'consecutivo_actual' => $r['consecutivo_actual'] ?? null,
        'completado_costura' => $r['completado_costura'] ?? null,
        'area' => $r['area'] ?? null
    ], JSON_UNESCAPED_UNICODE) . "\n";
}
