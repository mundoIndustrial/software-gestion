<?php

namespace App\Application\SupervisorPedidos\UseCases;

use App\Application\SupervisorPedidos\DTOs\GetPendingEmbroideryStampingReceiptsRequest;
use App\Application\SupervisorPedidos\DTOs\GetPendingEmbroideryStampingReceiptsResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GetPendingEmbroideryStampingReceiptsUseCase
{
    public function execute(GetPendingEmbroideryStampingReceiptsRequest $request): GetPendingEmbroideryStampingReceiptsResponse
    {
        try {
            $receiptTypes = $request->getReceiptTypes();

            // Debug: Verificar qué tipos de recibos existen
            $tiposRecibos = DB::table('consecutivos_recibos_pedidos')
                ->select('tipo_recibo', 'activo', DB::raw('count(*) as total'))
                ->whereIn('tipo_recibo', $receiptTypes)
                ->where('activo', 1)
                ->groupBy('tipo_recibo', 'activo')
                ->get();

            Log::info('Tipos de recibos encontrados', $tiposRecibos->toArray());

            // Obtener procesos pendientes
            $procesosPendientes = DB::table('consecutivos_recibos_pedidos as crp')
                ->join('pedidos_produccion as p', 'crp.pedido_produccion_id', '=', 'p.id')
                ->join('users as u', 'p.asesor_id', '=', 'u.id')
                ->leftJoin('prendas_pedido as pp', 'crp.prenda_id', '=', 'pp.id')
                ->leftJoin('recibos_fechas_llegada as rfl', 'rfl.recibo_id', '=', 'crp.id')
                ->leftJoin('pedidos_procesos_prenda_detalles as ppd', function($join) use ($receiptTypes) {
                    $join->on('pp.id', '=', 'ppd.prenda_pedido_id')
                        ->where(function ($q) {
                            $q->where('ppd.tipo_recibo', '=', DB::raw('crp.tipo_recibo'))
                                ->orWhere(function ($q2) {
                                    $q2->whereNull('ppd.tipo_recibo')
                                        ->whereRaw("ppd.tipo_proceso_id = (CASE crp.tipo_recibo WHEN 'BORDADO' THEN 2 WHEN 'ESTAMPADO' THEN 3 WHEN 'DTF' THEN 4 WHEN 'SUBLIMADO' THEN 5 ELSE NULL END)");
                                });
                        })
                        ->where(function ($q) {
                            $q->whereNull('ppd.numero_recibo')
                                ->orWhere('ppd.numero_recibo', '=', DB::raw('crp.consecutivo_actual'));
                        });
                })
                ->select([
                    'p.created_at as fecha_creacion',
                    'crp.consecutivo_actual as numero_recibo',
                    'p.cliente',
                    'p.id as pedido_id',
                    'u.name as asesor',
                    'crp.tipo_recibo',
                    'pp.nombre_prenda',
                    'crp.id as recibo_id',
                    'pp.id as prenda_id',
                    'ppd.fecha_aprobacion',
                    'rfl.fecha_llegada'
                ])
                ->whereIn('crp.tipo_recibo', $receiptTypes)
                ->where('crp.activo', 1)
                ->orderBy('p.created_at', 'desc')
                ->get();

            Log::info('Procesos pendientes recuperados', ['count' => $procesosPendientes->count()]);

            // Calcular cantidad total de prendas por recibo
            $procesosConCantidad = $procesosPendientes->map(function($proceso) {
                return $this->formatProcess($proceso);
            });

            return new GetPendingEmbroideryStampingReceiptsResponse($procesosConCantidad->toArray());

        } catch (\Exception $e) {
            Log::error('Error en GetPendingEmbroideryStampingReceipts: ' . $e->getMessage());
            throw $e;
        }
    }

    private function formatProcess($proceso)
    {
        $cantidadTotal = 0;

        // Obtener el registro completo del recibo
        $reciboCompleto = DB::table('consecutivos_recibos_pedidos')
            ->where('id', $proceso->recibo_id)
            ->first();

        if ($reciboCompleto) {
            // Detectar si es anexo (recibo parcial)
            $parcialId = $this->extractParcialId($reciboCompleto);

            if ($parcialId !== null) {
                // Es un anexo - obtener tallas desde pedidos_parciales_tallas
                $cantidadTotal = DB::table('pedidos_parciales_tallas')
                    ->where('pedido_parcial_id', $parcialId)
                    ->sum('cantidad');
            } elseif ($proceso->prenda_id) {
                // Es un recibo normal - obtener tallas desde prenda_pedido_tallas
                $cantidadTotal = DB::table('prenda_pedido_tallas')
                    ->where('prenda_pedido_id', $proceso->prenda_id)
                    ->sum('cantidad');
            }
        }

        $proceso->cantidad_total_prendas = $cantidadTotal;
        return $proceso;
    }

    private function extractParcialId($reciboCompleto): ?int
    {
        $notas = isset($reciboCompleto->notas) ? (string) $reciboCompleto->notas : '';

        if ($notas !== '' && preg_match('/parcial_id:(\d+)/i', $notas, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }
}
