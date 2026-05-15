<?php

declare(strict_types=1);

use App\Infrastructure\Services\Operario\ObtenerPrendasRecibosService;
use App\Models\User;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$nombreOperario = $argv[1] ?? 'tatiana';
$consecutivoObjetivo = (int) ($argv[2] ?? 68);
$parcialIdObjetivo = (int) ($argv[3] ?? 51);

$normalizado = strtolower(trim($nombreOperario));

$usuario = User::query()
    ->whereRaw('LOWER(TRIM(name)) = ?', [$normalizado])
    ->first();

if (!$usuario) {
    echo json_encode([
        'ok' => false,
        'error' => 'Usuario no encontrado',
        'nombre_operario_buscado' => $nombreOperario,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit(1);
}

$roles = method_exists($usuario, 'roles')
    ? $usuario->roles()->pluck('name')->values()->all()
    : [];

$completados = DB::table('prenda_recibo_completado')
    ->where(function ($q) use ($normalizado) {
        $q->whereRaw('LOWER(TRIM(nombre_operario)) = ?', [$normalizado]);
    })
    ->orWhere(function ($q) use ($consecutivoObjetivo, $parcialIdObjetivo) {
        $q->where('numero_recibo', $consecutivoObjetivo)
            ->where('id_parcial', $parcialIdObjetivo);
    })
    ->orderByDesc('fecha_completado')
    ->get([
        'id',
        'id_recibo',
        'numero_recibo',
        'area',
        'nombre_operario',
        'fecha_completado',
        'id_parcial',
    ]);

$completadosObjetivoGlobal = DB::table('prenda_recibo_completado')
    ->where(function ($q) use ($consecutivoObjetivo, $parcialIdObjetivo) {
        $q->where('numero_recibo', $consecutivoObjetivo)
            ->orWhere('id_parcial', $parcialIdObjetivo)
            ->orWhere('id_recibo', $parcialIdObjetivo);
    })
    ->orderByDesc('fecha_completado')
    ->get([
        'id',
        'id_recibo',
        'numero_recibo',
        'area',
        'nombre_operario',
        'fecha_completado',
        'id_parcial',
    ]);

$reciboNormalObjetivo = DB::table('consecutivos_recibos_pedidos')
    ->where('id', 912)
    ->orWhere('consecutivo_actual', $consecutivoObjetivo)
    ->get([
        'id',
        'pedido_produccion_id',
        'prenda_id',
        'tipo_recibo',
        'origen_recibo',
        'consecutivo_actual',
        'area',
        'activo',
        'notas',
        'created_at',
    ]);

$parcialObjetivo = DB::table('recibo_por_partes')
    ->where('id', $parcialIdObjetivo)
    ->orWhere('consecutivo_parcial', $consecutivoObjetivo)
    ->get([
        'id',
        'pedido_produccion_id',
        'prenda_pedido_id',
        'tipo_recibo',
        'consecutivo_original',
        'consecutivo_parcial',
        'created_at',
    ]);

$prendaIdObjetivo = null;
$filaRecibo912 = $reciboNormalObjetivo->firstWhere('id', 912);
if ($filaRecibo912 && isset($filaRecibo912->prenda_id)) {
    $prendaIdObjetivo = (int) $filaRecibo912->prenda_id;
}

$reflectivosMismaPrenda = collect();
if (!empty($prendaIdObjetivo)) {
    $reflectivosMismaPrenda = DB::table('consecutivos_recibos_pedidos')
        ->where('prenda_id', $prendaIdObjetivo)
        ->where('tipo_recibo', 'REFLECTIVO')
        ->orderByDesc('created_at')
        ->get([
            'id',
            'consecutivo_actual',
            'area',
            'activo',
            'notas',
            'created_at',
        ]);
}

/** @var ObtenerPrendasRecibosService $service */
$service = app(ObtenerPrendasRecibosService::class);
$cards = $service->obtenerPrendasConRecibos($usuario);

$matchCards = [];
$cardsPrendaObjetivo = [];
foreach ($cards as $card) {
    if ((int) ($card['prenda_id'] ?? 0) === 1161) {
        $cardsPrendaObjetivo[] = [
            'numero_pedido' => $card['numero_pedido'] ?? null,
            'prenda_id' => $card['prenda_id'] ?? null,
            'nombre_prenda' => $card['nombre_prenda'] ?? null,
            'recibos' => array_map(function ($r) {
                return [
                    'id' => $r['id'] ?? null,
                    'tipo_recibo' => $r['tipo_recibo'] ?? null,
                    'consecutivo_actual' => $r['consecutivo_actual'] ?? null,
                    'area' => $r['area'] ?? null,
                    'pedido_parcial_id' => $r['pedido_parcial_id'] ?? null,
                    'completado_costura' => $r['completado_costura'] ?? null,
                ];
            }, $card['recibos'] ?? []),
        ];
    }

    $recibos = $card['recibos'] ?? [];
    foreach ($recibos as $recibo) {
        $consecutivo = (int) ($recibo['consecutivo_actual'] ?? 0);
        $parcialId = (int) ($recibo['pedido_parcial_id'] ?? 0);
        $tipo = strtoupper(trim((string) ($recibo['tipo_recibo'] ?? '')));
        if (
            ($consecutivo === $consecutivoObjetivo && $tipo === 'REFLECTIVO')
            || ($parcialIdObjetivo > 0 && $parcialId === $parcialIdObjetivo)
        ) {
            $matchCards[] = [
                'numero_pedido' => $card['numero_pedido'] ?? null,
                'cliente' => $card['cliente'] ?? null,
                'nombre_prenda' => $card['nombre_prenda'] ?? null,
                'recibo' => [
                    'id' => $recibo['id'] ?? null,
                    'tipo_recibo' => $recibo['tipo_recibo'] ?? null,
                    'consecutivo_actual' => $recibo['consecutivo_actual'] ?? null,
                    'area' => $recibo['area'] ?? null,
                    'pedido_parcial_id' => $recibo['pedido_parcial_id'] ?? null,
                    'es_parcial' => $recibo['es_parcial'] ?? null,
                    'encargado_costura' => $recibo['encargado_costura'] ?? null,
                    'completado_costura' => $recibo['completado_costura'] ?? null,
                    'completado_corte' => $recibo['completado_corte'] ?? null,
                    'completado_control_calidad' => $recibo['completado_control_calidad'] ?? null,
                ],
            ];
        }
    }
}

$diagnostico = [
    'ok' => true,
    'usuario' => [
        'id' => $usuario->id,
        'name' => $usuario->name,
        'roles' => $roles,
    ],
    'filtro_objetivo' => [
        'numero_recibo' => $consecutivoObjetivo,
        'id_parcial' => $parcialIdObjetivo,
        'tipo_recibo' => 'REFLECTIVO',
    ],
    'prenda_recibo_completado_relacionados' => $completados,
    'prenda_recibo_completado_objetivo_global' => $completadosObjetivoGlobal,
    'consecutivos_recibos_pedidos_objetivo' => $reciboNormalObjetivo,
    'recibo_por_partes_objetivo' => $parcialObjetivo,
    'reflectivos_misma_prenda' => $reflectivosMismaPrenda,
    'cards_prenda_1161' => $cardsPrendaObjetivo,
    'total_cards_dashboard_para_usuario' => $cards->count(),
    'coincidencias_en_cards' => $matchCards,
    'conclusion' => [
        'aparece_en_dashboard_cards' => count($matchCards) > 0,
        'tiene_completado_registrado' => $completados->count() > 0,
    ],
];

echo json_encode($diagnostico, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
