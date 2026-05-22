<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = App\Models\User::find(132);
Illuminate\Support\Facades\Auth::login($user);

$request = Illuminate\Http\Request::create('/operario/dashboard', 'GET', [
    'filtro' => 'bodega',
    'encargado' => 'control-calidad',
]);

$uc = $app->make(App\Application\Operario\UseCases\GetOperarioDashboardUseCase::class);
$dto = $uc->execute($request);

$out = [];
foreach (collect($dto->prendasConRecibos)->values() as $p) {
    $r = collect($p['recibos'] ?? [])->first() ?? [];
    $out[] = [
        'numero_pedido' => $p['numero_pedido'] ?? null,
        'cliente' => $p['cliente'] ?? null,
        'tipo' => $r['tipo_recibo'] ?? null,
        'area' => $r['area'] ?? null,
        'consecutivo' => $r['consecutivo_actual'] ?? null,
        'encargado_costura' => $r['encargado_costura'] ?? null,
    ];
}

echo json_encode($out, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE), PHP_EOL;
