<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$svc = $app->make(App\Infrastructure\Services\Operario\ObtenerPrendasRecibosListadoService::class);
$rows = $svc->obtenerPrendasConRecibosBodegaVistaCostura(true);
foreach ($rows as $r) {
    if ((string)($r['numero_pedido'] ?? '') === '17') {
        echo json_encode($r, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), PHP_EOL;
    }
}
