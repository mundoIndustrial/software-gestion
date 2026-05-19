<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$tipoRecibo = isset($argv[1]) ? strtoupper(trim((string) $argv[1])) : 'COSTURA';
$soloCompletados = in_array('--solo-completados', $argv, true);

$areasControlCalidad = ['control calidad', 'control de calidad'];

$recibos = DB::table('consecutivos_recibos_pedidos as crp')
    ->join('pedidos_produccion as pp', 'pp.id', '=', 'crp.pedido_produccion_id')
    ->leftJoin('prenda_recibo_completado as prc', function ($join) {
        $join->on('prc.id_recibo', '=', 'crp.id')
            ->whereRaw('LOWER(TRIM(prc.area)) IN (?, ?)', ['control calidad', 'control de calidad']);
    })
    ->where('crp.activo', 1)
    ->whereRaw('UPPER(TRIM(crp.tipo_recibo)) = ?', [$tipoRecibo])
    ->whereRaw('LOWER(TRIM(crp.area)) IN (?, ?)', $areasControlCalidad)
    ->when($soloCompletados, function ($query) {
        $query->whereNotNull('prc.id');
    })
    ->select([
        'crp.id as recibo_id',
        'crp.pedido_produccion_id',
        'crp.prenda_id',
        'crp.tipo_recibo',
        'crp.origen_recibo',
        'crp.consecutivo_actual',
        'crp.consecutivo_inicial',
        'crp.area as area_actual',
        'crp.estado',
        'crp.fecha_envio',
        'crp.fecha_llegada',
        'crp.created_at as fecha_creacion',
        'crp.updated_at as fecha_actualizacion',
        'crp.ultima_actividad',
        'crp.notas',
        'pp.numero_pedido',
        'pp.cliente',
        'prc.id as completado_id',
        'prc.nombre_operario as operario_completado',
        'prc.fecha_completado',
        'prc.id_parcial',
        'prc.numero_recibo as numero_recibo_completado',
    ])
    ->orderBy('crp.created_at', 'asc')
    ->get()
    ->map(function ($row) {
        return [
            'recibo_id' => (int) $row->recibo_id,
            'pedido_produccion_id' => (int) $row->pedido_produccion_id,
            'numero_pedido' => (string) $row->numero_pedido,
            'cliente' => (string) $row->cliente,
            'prenda_id' => (int) $row->prenda_id,
            'tipo_recibo' => (string) $row->tipo_recibo,
            'origen_recibo' => (string) $row->origen_recibo,
            'consecutivo_actual' => (int) $row->consecutivo_actual,
            'consecutivo_inicial' => (int) $row->consecutivo_inicial,
            'area_actual' => (string) $row->area_actual,
            'estado' => (string) $row->estado,
            'fecha_creacion' => $row->fecha_creacion,
            'fecha_envio' => $row->fecha_envio,
            'fecha_llegada' => $row->fecha_llegada,
            'ultima_actividad' => $row->ultima_actividad,
            'completado_cc' => !is_null($row->completado_id),
            'completado_id' => $row->completado_id ? (int) $row->completado_id : null,
            'operario_completado' => $row->operario_completado,
            'fecha_completado' => $row->fecha_completado,
            'id_parcial' => $row->id_parcial ? (int) $row->id_parcial : null,
            'numero_recibo_completado' => $row->numero_recibo_completado,
            'notas' => $row->notas,
        ];
    })
    ->values();

$summary = [
    'tipo_recibo' => $tipoRecibo,
    'solo_completados' => $soloCompletados,
    'total_en_control_calidad' => $recibos->count(),
    'total_completados_cc' => $recibos->where('completado_cc', true)->count(),
    'total_pendientes_cc' => $recibos->where('completado_cc', false)->count(),
];

echo json_encode([
    'summary' => $summary,
    'recibos' => $recibos,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

