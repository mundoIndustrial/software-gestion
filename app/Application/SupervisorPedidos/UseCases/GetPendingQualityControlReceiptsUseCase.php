<?php

namespace App\Application\SupervisorPedidos\UseCases;

use App\Application\SupervisorPedidos\DTOs\GetPendingSewingReceiptsRequest;
use App\Application\SupervisorPedidos\DTOs\GetPendingSewingReceiptsResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GetPendingQualityControlReceiptsUseCase
{
    public function execute(GetPendingSewingReceiptsRequest $request): GetPendingSewingReceiptsResponse
    {
        try {
            // Obtener pedidos con recibos de CONTROL-CALIDAD
            $query = DB::table('consecutivos_recibos_pedidos as crp')
                ->join('pedidos_produccion as p', 'crp.pedido_produccion_id', '=', 'p.id')
                ->join('users as u', 'p.asesor_id', '=', 'u.id')
                ->select([
                    'p.created_at as fecha_creacion',
                    'crp.consecutivo_actual as numero_recibo',
                    'crp.prenda_id as prenda_id',
                    'p.cliente',
                    'p.id as pedido_id',
                    'u.name as asesor',
                    'crp.color_costura',
                    'crp.area',
                ])
                ->where('crp.area', 'Control-Calidad')
                ->where('crp.activo', 1)
                ->orderBy('p.created_at', 'desc');

            // Aplicar filtros
            $this->applyFilters($query, $request);

            $recibosControlCalidad = $query->get();

            Log::info('Recibos Control-Calidad recuperados', ['count' => $recibosControlCalidad->count()]);

            // Procesar recibos con prendas
            $procesosConCantidad = $recibosControlCalidad->map(function ($recibo) {
                return $this->formatReceipt($recibo);
            });

            return new GetPendingSewingReceiptsResponse($procesosConCantidad->toArray());

        } catch (\Exception $e) {
            Log::error('Error en GetPendingQualityControlReceipts: ' . $e->getMessage());
            throw $e;
        }
    }

    private function applyFilters($query, GetPendingSewingReceiptsRequest $request): void
    {
        if (!empty($request->getNumeroRecibo())) {
            $numeros = array_filter(array_map('trim', explode(',', $request->getNumeroRecibo())));
            if (count($numeros) > 0) {
                $query->whereIn('crp.consecutivo_actual', $numeros);
            }
        }

        if (!empty($request->getCliente())) {
            $clientes = array_filter(array_map('trim', explode(',', $request->getCliente())));
            if (count($clientes) > 0) {
                $query->whereIn('p.cliente', $clientes);
            }
        }

        if (!empty($request->getAsesor())) {
            $asesores = array_filter(array_map('trim', explode(',', $request->getAsesor())));
            if (count($asesores) > 0) {
                $query->whereIn('u.name', $asesores);
            }
        }

        if (!empty($request->getPrendas())) {
            $prendas = array_filter(array_map('trim', explode(',', $request->getPrendas())));
            if (count($prendas) > 0) {
                $query->whereExists(function ($q) use ($prendas) {
                    $q->select(DB::raw(1))
                        ->from('prendas_pedido as pp')
                        ->whereColumn('pp.id', 'crp.prenda_id')
                        ->whereIn('pp.nombre_prenda', $prendas);
                });
            }
        }

        if (!empty($request->getFechaCreacion())) {
            $fecha = trim($request->getFechaCreacion());
            if ($fecha !== '') {
                $query->whereDate('p.created_at', $fecha);
            }
        }
    }

    private function formatReceipt($recibo): array
    {
        $proceso = [
            'fecha_creacion' => $recibo->fecha_creacion,
            'numero_recibo' => $recibo->numero_recibo,
            'cliente' => $recibo->cliente,
            'area' => $recibo->area,
            'pedido_id' => $recibo->pedido_id,
            'asesor' => $recibo->asesor,
            'color_costura' => $recibo->color_costura,
            'prendas' => collect(),
        ];

        if (empty($recibo->prenda_id)) {
            return $proceso;
        }

        // Obtener prendas con colores
        $prendasConColores = DB::table('prendas_pedido as pp')
            ->join('prenda_pedido_tallas as ppt', 'pp.id', '=', 'ppt.prenda_pedido_id')
            ->join('prenda_pedido_talla_colores as pptc', 'ppt.id', '=', 'pptc.prenda_pedido_talla_id')
            ->select([
                'pp.nombre_prenda',
                'pptc.color_nombre',
                'pptc.cantidad as cantidad_color',
                DB::raw('null as cantidad_talla'),
                DB::raw('null as tela')
            ])
            ->where('pp.id', $recibo->prenda_id)
            ->get();

        // Obtener prendas sin colores
        $prendasSinColores = DB::table('prendas_pedido as pp')
            ->join('prenda_pedido_tallas as ppt', 'pp.id', '=', 'ppt.prenda_pedido_id')
            ->leftJoin('prenda_pedido_talla_colores as pptc', 'ppt.id', '=', 'pptc.prenda_pedido_talla_id')
            ->select([
                'pp.nombre_prenda',
                'ppt.tela',
                'ppt.cantidad as cantidad_talla',
                DB::raw('null as color_nombre'),
                DB::raw('null as cantidad_color')
            ])
            ->where('pp.id', $recibo->prenda_id)
            ->whereNull('pptc.id')
            ->get();

        $proceso['prendas'] = $prendasConColores->merge($prendasSinColores);
        return $proceso;
    }
}
