<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$buscado = isset($argv[1]) ? trim((string) $argv[1]) : '';

if ($buscado === '') {
    fwrite(STDERR, "Uso: php scripts/diagnose_control_calidad_bodega.php <id_o_consecutivo>\n");
    exit(1);
}

$query = DB::table('consecutivos_recibos_pedidos')
    ->whereIn('tipo_recibo', ['BODEGA', 'CORTE-PARA-BODEGA'])
    ->where(function ($q) use ($buscado) {
        if (ctype_digit($buscado)) {
            $q->where('id', (int) $buscado)
              ->orWhere('consecutivo_actual', (int) $buscado);
        } else {
            $q->whereRaw('CAST(consecutivo_actual AS CHAR) = ?', [$buscado]);
        }
    })
    ->orderBy('id');

$recibos = $query->get([
    'id',
    'pedido_produccion_id',
    'prenda_id',
    'prenda_bodega_id',
    'tipo_recibo',
    'origen_recibo',
    'consecutivo_actual',
    'consecutivo_inicial',
    'activo',
    'estado',
    'area',
    'fecha_envio',
    'fecha_llegada',
    'notas',
    'created_at',
    'updated_at',
]);

$ids = $recibos->pluck('id')->map(fn ($id) => (int) $id)->all();

$completados = empty($ids)
    ? collect()
    : DB::table('prenda_recibo_completado as prc')
        ->leftJoin('prenda_recibo_completado_tallas as prct', 'prct.prenda_recibo_completado_id', '=', 'prc.id')
        ->whereIn('prc.id_recibo', $ids)
        ->orWhereIn('prc.numero_recibo', $recibos->pluck('consecutivo_actual')->filter()->map(fn ($n) => (int) $n)->all())
        ->select([
            'prc.id',
            'prc.id_recibo',
            'prc.numero_recibo',
            'prc.area',
            'prc.nombre_operario',
            'prc.fecha_completado',
            'prc.id_parcial',
            'prc.tallas_control_calidad',
            'prct.talla',
            'prct.genero',
            'prct.color_nombre',
            'prct.cantidad',
        ])
        ->orderBy('prc.id')
        ->get();

$salida = [
    'buscado' => $buscado,
    'recibos' => $recibos,
    'completados' => $completados,
    'resumen' => $recibos->map(function ($recibo) use ($completados) {
        $area = strtolower(trim((string) ($recibo->area ?? '')));
        $esAreaCC = in_array($area, ['control calidad', 'control de calidad'], true);
        $completadosDelRecibo = $completados->filter(function ($row) use ($recibo) {
            return (int) ($row->id_recibo ?? 0) === (int) $recibo->id
                || (int) ($row->numero_recibo ?? 0) === (int) $recibo->consecutivo_actual;
        });

        return [
            'id' => (int) $recibo->id,
            'consecutivo_actual' => (int) $recibo->consecutivo_actual,
            'tipo_recibo' => $recibo->tipo_recibo,
            'activo' => (bool) $recibo->activo,
            'estado' => $recibo->estado,
            'area' => $recibo->area,
            'es_area_cc' => $esAreaCC,
            'completados_count' => $completadosDelRecibo->count(),
            'areas_completadas' => $completadosDelRecibo->pluck('area')->unique()->values()->all(),
            'id_recibos_completados' => $completadosDelRecibo->pluck('id')->values()->all(),
        ];
    })->values(),
];

echo json_encode($salida, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
