<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$numeroRecibo = isset($argv[1]) ? (int) $argv[1] : 0;
$tipoRecibo = isset($argv[2]) ? strtoupper(trim((string) $argv[2])) : 'COSTURA';

if ($numeroRecibo <= 0) {
    fwrite(STDERR, "Uso: php scripts/diagnose_recibo_vista_costura.php <numero_recibo> [tipo_recibo]\n");
    exit(1);
}

$crpRows = DB::table('consecutivos_recibos_pedidos')
    ->where('consecutivo_actual', $numeroRecibo)
    ->whereRaw('UPPER(TRIM(tipo_recibo)) = ?', [$tipoRecibo])
    ->orderBy('id')
    ->get();

if ($crpRows->isEmpty()) {
    echo json_encode([
        'error' => "No se encontró recibo {$numeroRecibo} tipo {$tipoRecibo} en consecutivos_recibos_pedidos",
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit(0);
}

$idsRecibo = $crpRows->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
$prendaIds = $crpRows->pluck('prenda_id')->filter()->map(fn ($id) => (int) $id)->unique()->values()->all();
$pedidoIds = $crpRows->pluck('pedido_produccion_id')->filter()->map(fn ($id) => (int) $id)->unique()->values()->all();

$completadosRows = DB::table('prenda_recibo_completado')
    ->whereIn('id_recibo', $idsRecibo)
    ->orderBy('id')
    ->get();

$procesosCostura = DB::table('procesos_prenda')
    ->when(!empty($prendaIds), fn ($q) => $q->whereIn('prenda_pedido_id', $prendaIds))
    ->whereRaw('LOWER(TRIM(proceso)) = ?', ['costura'])
    ->where('numero_recibo', $numeroRecibo)
    ->whereNull('deleted_at')
    ->orderByDesc('created_at')
    ->get();

$encargadosNormalizados = $procesosCostura->pluck('encargado')
    ->filter()
    ->map(fn ($n) => strtolower(trim((string) $n)))
    ->unique()
    ->values();

$rolesCatalogo = DB::table('roles')->pluck('name', 'id');
$rolesEncargados = [];

if ($encargadosNormalizados->isNotEmpty()) {
    $usuarios = DB::table('users')
        ->whereIn(DB::raw('LOWER(TRIM(name))'), $encargadosNormalizados->all())
        ->get();

    foreach ($usuarios as $usuario) {
        $ids = [];
        if (!empty($usuario->roles_ids)) {
            $decoded = json_decode((string) $usuario->roles_ids, true);
            if (is_array($decoded)) {
                $ids = array_merge($ids, array_map('intval', $decoded));
            }
        }
        if (!empty($usuario->role_id)) {
            $ids[] = (int) $usuario->role_id;
        }

        $roles = collect($ids)
            ->map(fn ($rid) => $rolesCatalogo[$rid] ?? null)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $rolesEncargados[strtolower(trim((string) $usuario->name))] = $roles;
    }
}

$tieneCompletadoCostura = $completadosRows
    ->contains(fn ($r) => strtolower(trim((string) $r->area)) === 'costura');
$tieneCompletadoCorte = $completadosRows
    ->contains(fn ($r) => strtolower(trim((string) $r->area)) === 'corte');
$tieneCompletadoControlCalidad = $completadosRows
    ->contains(function ($r) {
        $area = strtolower(trim((string) $r->area));
        return $area === 'control de calidad' || $area === 'control calidad';
    });

$diagnostico = [
    'recibo' => [
        'numero_recibo' => $numeroRecibo,
        'tipo_recibo' => $tipoRecibo,
        'ids_recibo' => $idsRecibo,
        'prenda_ids' => $prendaIds,
        'pedido_ids' => $pedidoIds,
    ],
    'estado_completado' => [
        'costura' => $tieneCompletadoCostura,
        'corte' => $tieneCompletadoCorte,
        'control_calidad' => $tieneCompletadoControlCalidad,
    ],
    'banderas_vista_costura' => [
        'requiere_area_costura_para_COSTURA' => true,
        'requiere_registro_completado_costura_para_flag_UI' => true,
        'exclusion_si_encargado_tiene_rol_costura_reflectivo_en_parciales' => true,
    ],
    'encargados_proceso_costura' => $encargadosNormalizados->all(),
    'roles_encargados' => $rolesEncargados,
];

echo json_encode([
    'diagnostico' => $diagnostico,
    'crp_rows' => $crpRows,
    'completados_rows' => $completadosRows,
    'procesos_costura_rows' => $procesosCostura,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

