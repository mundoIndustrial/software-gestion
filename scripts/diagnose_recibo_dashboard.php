<?php

declare(strict_types=1);

use App\Application\Operario\UseCases\GetOperarioDashboardUseCase;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$numeroRecibo = isset($argv[1]) ? (int) $argv[1] : 0;
$filtroRecibo = strtolower(trim((string) ($argv[2] ?? 'costura')));
$pagina = max(1, (int) ($argv[3] ?? 1));
$userId = max(1, (int) ($argv[4] ?? 132));
$encargado = isset($argv[5]) ? strtolower(trim((string) $argv[5])) : null;

if ($numeroRecibo <= 0) {
    fwrite(STDERR, "Uso: php scripts/diagnose_recibo_dashboard.php <numero_recibo> [filtro=costura|reflectivo] [pagina] [user_id] [encargado]\n");
    exit(1);
}

if (!in_array($filtroRecibo, ['costura', 'reflectivo'], true)) {
    $filtroRecibo = 'costura';
}

$usuario = User::query()->find($userId);
if (!$usuario) {
    fwrite(STDERR, "No se encontro el usuario con ID {$userId}\n");
    exit(1);
}

Auth::setUser($usuario);
Auth::shouldUse('web');

$query = [
    'filtro' => $filtroRecibo,
    'page' => $pagina,
];

if ($encargado && in_array($encargado, ['todos', 'sin-encargado'], true)) {
    $query['encargado'] = $encargado;
}

$request = Request::create('/operario/dashboard', 'GET', $query);
$request->setUserResolver(fn () => $usuario);

/** @var GetOperarioDashboardUseCase $useCase */
$useCase = app(GetOperarioDashboardUseCase::class);
$dashboard = $useCase->execute($request);

$cards = collect($dashboard->prendasConRecibos);
$pageSize = 12;
$totalCards = $cards->count();
$pageCards = $cards->forPage($pagina, $pageSize)->values();

$matches = [];
foreach ($cards as $index => $card) {
    foreach (($card['recibos'] ?? []) as $recibo) {
        $consecutivo = (int) ($recibo['consecutivo_actual'] ?? 0);
        if ($consecutivo !== $numeroRecibo) {
            continue;
        }

        $matches[] = [
            'posicion_1_based' => $index + 1,
            'pagina' => intdiv($index, $pageSize) + 1,
            'numero_pedido' => $card['numero_pedido'] ?? null,
            'prenda_id' => $card['prenda_id'] ?? null,
            'nombre_prenda' => $card['nombre_prenda'] ?? null,
            'cliente' => $card['cliente'] ?? null,
            'recibo' => [
                'id' => $recibo['id'] ?? null,
                'tipo_recibo' => $recibo['tipo_recibo'] ?? null,
                'area' => $recibo['area'] ?? null,
                'consecutivo_actual' => $recibo['consecutivo_actual'] ?? null,
                'pedido_parcial_id' => $recibo['pedido_parcial_id'] ?? null,
                'encargado_costura' => $recibo['encargado_costura'] ?? null,
                'completado_corte' => $recibo['completado_corte'] ?? null,
                'completado_costura' => $recibo['completado_costura'] ?? null,
                'completado_control_calidad' => $recibo['completado_control_calidad'] ?? null,
                'created_at' => $recibo['created_at'] ?? null,
                'creado_en' => $recibo['creado_en'] ?? null,
            ],
        ];
    }
}

$crpRows = DB::table('consecutivos_recibos_pedidos')
    ->where('consecutivo_actual', $numeroRecibo)
    ->orderBy('id')
    ->get([
        'id',
        'pedido_produccion_id',
        'prenda_id',
        'tipo_recibo',
        'origen_recibo',
        'consecutivo_actual',
        'consecutivo_inicial',
        'activo',
        'area',
        'estado',
        'notas',
        'created_at',
        'updated_at',
    ]);

$completados = DB::table('prenda_recibo_completado')
    ->whereIn('id_recibo', $crpRows->pluck('id')->filter()->map(fn ($id) => (int) $id)->all())
    ->orderBy('id')
    ->get([
        'id',
        'id_recibo',
        'numero_recibo',
        'area',
        'nombre_operario',
        'fecha_completado',
        'id_parcial',
    ]);

$evaluaciones = [];
foreach ($crpRows as $row) {
    $tipo = strtoupper(trim((string) ($row->tipo_recibo ?? '')));
    $area = strtolower(trim((string) ($row->area ?? '')));
    $activo = (bool) ($row->activo ?? false);
    $completadoCorte = $completados->contains(function ($c) use ($row) {
        return (int) $c->id_recibo === (int) $row->id
            && in_array(strtolower(trim((string) $c->area)), ['corte'], true);
    });
    $completadoCostura = $completados->contains(function ($c) use ($row) {
        return (int) $c->id_recibo === (int) $row->id
            && in_array(strtolower(trim((string) $c->area)), ['costura'], true);
    });
    $completadoCC = $completados->contains(function ($c) use ($row) {
        $areaCc = strtolower(trim((string) $c->area));
        return (int) $c->id_recibo === (int) $row->id
            && in_array($areaCc, ['control de calidad', 'control calidad'], true);
    });

    $cumpleCosturaDashboard = $activo
        && $tipo !== 'REFLECTIVO'
        && in_array($area, ['corte', 'costura', 'control calidad', 'control de calidad'], true)
        && $completadoCorte;

    $cumpleReflectivoDashboard = $activo
        && $tipo === 'REFLECTIVO'
        && in_array($area, ['costura', 'control calidad', 'control de calidad'], true);

    $evaluaciones[] = [
        'id' => $row->id,
        'tipo_recibo' => $row->tipo_recibo,
        'area' => $row->area,
        'activo' => $activo,
        'completado_corte' => $completadoCorte,
        'completado_costura' => $completadoCostura,
        'completado_control_calidad' => $completadoCC,
        'cumple_regla_costura' => $cumpleCosturaDashboard,
        'cumple_regla_reflectivo' => $cumpleReflectivoDashboard,
        'motivos_posibles_exclusion' => array_values(array_filter([
            $activo ? null : 'inactivo',
            $tipo === '' ? 'sin_tipo' : null,
            $area === '' ? 'sin_area' : null,
        ])),
    ];
}

$salida = [
    'parametros' => [
        'numero_recibo' => $numeroRecibo,
        'filtro' => $filtroRecibo,
        'pagina' => $pagina,
        'user_id' => $userId,
        'user_name' => $usuario->name,
        'encargado' => $encargado,
    ],
    'dashboard' => [
        'total_cards' => $totalCards,
        'page_size' => $pageSize,
        'cards_en_pagina_solicitada' => $pageCards->count(),
        'matches_en_cards' => $matches,
        'posicion_ultima_pagina' => $totalCards > 0 ? (int) ceil($totalCards / $pageSize) : 0,
    ],
    'consecutivos_recibos_pedidos' => $crpRows,
    'prenda_recibo_completado' => $completados,
    'evaluacion' => $evaluaciones,
    'conclusion' => [
        'existe_en_dashboard' => !empty($matches),
        'existe_en_tabla_base' => $crpRows->isNotEmpty(),
    ],
];

echo json_encode($salida, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
