<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Application\Operario\UseCases\GetPedidoDataOperarioUseCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

$numeroPedido = isset($argv[1]) ? (int) $argv[1] : 0;
$prendaId = isset($argv[2]) ? (int) $argv[2] : 0;
$parcialId = isset($argv[3]) ? (int) $argv[3] : 0;
$consecutivoParcial = isset($argv[4]) ? trim((string) $argv[4]) : '';
$tipoRecibo = strtoupper(trim((string) ($argv[5] ?? 'PARCIAL')));

if ($numeroPedido <= 0 || $prendaId <= 0 || $parcialId <= 0) {
    fwrite(STDERR, "Uso: php scripts/diagnose_control_calidad_parcial.php <numero_pedido> <prenda_id> <parcial_id> <consecutivo_parcial> [tipo_recibo]\n");
    exit(1);
}

$request = Request::create('/control-calidad/api/pedido/' . $numeroPedido, 'GET', [
    'tipo_recibo' => $tipoRecibo,
    'prenda_id' => $prendaId,
    'parcial_id' => $parcialId,
    'consecutivo_parcial' => $consecutivoParcial,
]);

/** @var GetPedidoDataOperarioUseCase $useCase */
$useCase = app(GetPedidoDataOperarioUseCase::class);
$result = $useCase->execute($numeroPedido, $request);
$payload = $result['payload'] ?? [];
$data = $payload['data'] ?? [];
$prendas = $data['prendas'] ?? [];

$parcialDb = DB::table('recibo_por_partes')->where('id', $parcialId)->first();
$tallasParcialDb = DB::table('recibos_por_partes_tallas')
    ->where('recibo_por_partes_id', $parcialId)
    ->get(['id', 'talla', 'genero', 'color_nombre', 'cantidad']);

$tallasPrendaDb = DB::table('prenda_pedido_tallas')
    ->where('prenda_pedido_id', $prendaId)
    ->get(['id', 'talla', 'genero', 'tipo_talla', 'cantidad', 'colores']);

$tallasPrendaColoresDb = DB::table('prenda_pedido_talla_colores as pptc')
    ->join('prenda_pedido_tallas as ppt', 'ppt.id', '=', 'pptc.prenda_pedido_talla_id')
    ->where('ppt.prenda_pedido_id', $prendaId)
    ->get([
        'pptc.id',
        'pptc.prenda_pedido_talla_id',
        'pptc.tela_nombre',
        'pptc.color_nombre',
        'pptc.cantidad',
        'ppt.talla as talla_base',
        'ppt.genero as genero_base',
    ]);

$prendaEncontrada = null;
foreach ($prendas as $prenda) {
    $idPrenda = (int) ($prenda['id'] ?? $prenda['prenda_id'] ?? $prenda['prenda_pedido_id'] ?? 0);
    if ($idPrenda === $prendaId) {
        $prendaEncontrada = $prenda;
        break;
    }
}

$reciboParcial = $prendaEncontrada['recibos']['PARCIAL'] ?? null;
$tallasParcialApi = $reciboParcial['tallas'] ?? [];
$tallaColoresParcialApi = $reciboParcial['talla_colores'] ?? [];
$descripcionPrenda = (string) ($prendaEncontrada['descripcion'] ?? '');
$tallasEnDescripcion = null;
if ($descripcionPrenda !== '' && preg_match('/(<strong>TALLAS?<\/strong><br>.*?)(?=<\/div>|$)/is', $descripcionPrenda, $matches)) {
    $tallasEnDescripcion = trim((string) $matches[1]);
}

$analisis = [
    'parametros' => [
        'numero_pedido' => $numeroPedido,
        'prenda_id' => $prendaId,
        'parcial_id' => $parcialId,
        'consecutivo_parcial' => $consecutivoParcial,
        'tipo_recibo' => $tipoRecibo,
    ],
    'resultado_api' => [
        'status' => $result['status'] ?? null,
        'tiene_success' => (bool) ($payload['success'] ?? false),
        'prendas_total' => is_array($prendas) ? count($prendas) : 0,
        'prenda_encontrada' => $prendaEncontrada !== null,
        'keys_prenda' => $prendaEncontrada ? array_keys($prendaEncontrada) : [],
        'keys_recibos' => $prendaEncontrada && isset($prendaEncontrada['recibos']) && is_array($prendaEncontrada['recibos'])
            ? array_keys($prendaEncontrada['recibos'])
            : [],
    ],
    'recibo_parcial_api' => [
        'existe' => $reciboParcial !== null,
        'pedido_parcial_id' => $reciboParcial['pedido_parcial_id'] ?? null,
        'id' => $reciboParcial['id'] ?? null,
        'tipo_recibo' => $reciboParcial['tipo_recibo'] ?? null,
        'es_parcial' => $reciboParcial['es_parcial'] ?? null,
        'tallas_count' => is_array($tallasParcialApi) ? count($tallasParcialApi) : 0,
        'talla_colores_count' => is_array($tallaColoresParcialApi) ? count($tallaColoresParcialApi) : 0,
        'tallas' => $tallasParcialApi,
        'talla_colores' => $tallaColoresParcialApi,
    ],
    'descripcion_prenda' => [
        'tiene_descripcion' => $descripcionPrenda !== '',
        'longitud' => strlen($descripcionPrenda),
        'bloque_tallas_detectado' => $tallasEnDescripcion !== null,
        'bloque_tallas' => $tallasEnDescripcion,
        'fragmento_inicio' => substr($descripcionPrenda, 0, 800),
    ],
    'db' => [
        'parcial' => $parcialDb,
        'recibos_por_partes_tallas' => $tallasParcialDb,
        'prenda_pedido_tallas' => $tallasPrendaDb,
        'prenda_pedido_talla_colores' => $tallasPrendaColoresDb,
    ],
];

echo json_encode($analisis, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
