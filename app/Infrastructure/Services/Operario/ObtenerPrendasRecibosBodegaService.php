<?php

namespace App\Infrastructure\Services\Operario;

use App\Infrastructure\Repositories\Operario\OperarioRecibosRepository;
use App\Models\ConsecutivoReciboPedido;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ObtenerPrendasRecibosBodegaService
{
    public function __construct(
        private readonly OperarioRecibosRepository $operarioRecibosRepository
    ) {}

    public function obtenerPrendasConRecibosBodegaCortador(User $usuario): Collection
    {
        $usuarioNombre = strtolower(trim((string) $usuario->name));

        $recibos = ConsecutivoReciboPedido::query()
            ->where('activo', 1)
            ->where('tipo_recibo', 'CORTE-PARA-BODEGA')
            ->whereRaw('LOWER(TRIM(area)) = ?', ['corte'])
            ->whereNotNull('prenda_bodega_id')
            ->with(['prendaBodega'])
            ->get();

        if ($recibos->isEmpty()) {
            return collect();
        }

        $prendaBodegaIds = $recibos->pluck('prenda_bodega_id')->filter()->unique()->values()->all();
        $tallasPorPrendaBodega = DB::table('prenda_tallas_bodega')
            ->whereIn('prenda_bodega_id', $prendaBodegaIds)
            ->get(['prenda_bodega_id', 'talla', 'genero', 'cantidad', 'color'])
            ->groupBy('prenda_bodega_id');
        $procesos = $this->operarioRecibosRepository
            ->obtenerProcesosCortePorPrendaBodegaIdsYEncargado($prendaBodegaIds, $usuarioNombre)
            ->groupBy('prenda_bodega_id');

        $resultado = collect();

        foreach ($procesos as $prendaBodegaId => $procesosPrenda) {
            $recibo = $recibos->firstWhere('prenda_bodega_id', $prendaBodegaId);
            if (!$recibo) {
                continue;
            }

            $tallas = ($tallasPorPrendaBodega->get($prendaBodegaId) ?? collect())
                ->map(function ($talla) {
                    return [
                        'id' => 0,
                        'genero' => $talla->genero ?? null,
                        'talla' => $talla->talla ?? null,
                        'cantidad' => (int) ($talla->cantidad ?? 0),
                        'tipo_talla' => null,
                        'es_sobremedida' => false,
                        'tela' => null,
                        'colores' => !empty($talla->color) ? [(string) $talla->color] : [],
                    ];
                })
                ->values()
                ->all();

            $resultado->push([
                'id' => $recibo->id,
                'numero_pedido' => '',
                'prenda_id' => null,
                'nombre_prenda' => $recibo->prendaBodega?->nombre ?? 'N/A',
                'prenda_bodega_id' => $prendaBodegaId,
                'tipo_recibo' => $recibo->tipo_recibo,
                'tallas' => $tallas,
                'total_recibos' => 1,
                'recibos' => [
                    [
                        'id' => $recibo->id,
                        'tipo_recibo' => $recibo->tipo_recibo,
                        'consecutivo_actual' => $recibo->consecutivo_actual,
                        'area' => $recibo->area,
                    ],
                ],
            ]);
        }

        return $resultado;
    }

    public function obtenerConteoPrendasConRecibosBodegaCortador(User $usuario): int
    {
        $usuarioNombre = strtolower(trim((string) $usuario->name));

        $recibos = ConsecutivoReciboPedido::query()
            ->where('activo', 1)
            ->where('tipo_recibo', 'CORTE-PARA-BODEGA')
            ->whereRaw('LOWER(TRIM(area)) = ?', ['corte'])
            ->whereNotNull('prenda_bodega_id')
            ->pluck('prenda_bodega_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($recibos)) {
            return 0;
        }

        return $this->operarioRecibosRepository->contarPrendasBodegaEnProcesoCortePorEncargado($recibos, $usuarioNombre);
    }
}
