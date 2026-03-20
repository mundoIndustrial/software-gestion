<?php

namespace App\Application\SupervisorPedidos\UseCases;

use App\Application\SupervisorPedidos\DTOs\GetReceiptDetailsResponse;

class GetReceiptDetailsUseCase
{
    /**
     * Obtener detalles de un recibo específico
     */
    public function execute(int $receiptId): GetReceiptDetailsResponse
    {
        try {
            $recibo = \DB::table('consecutivos_recibos_pedidos as crp')
                ->join('pedidos_produccion as p', 'crp.pedido_produccion_id', '=', 'p.id')
                ->join('users as u', 'p.asesor_id', '=', 'u.id')
                ->leftJoin('prendas_pedido as pp', 'crp.prenda_id', '=', 'pp.id')
                ->leftJoin('pedidos_procesos_prenda_detalles as ppd', 'pp.id', '=', 'ppd.prenda_pedido_id')
                ->select([
                    'crp.*',
                    'p.cliente',
                    'p.created_at as fecha_creacion',
                    'u.name as asesor',
                    'pp.nombre_prenda',
                    'ppd.estado',
                    'ppd.observaciones'
                ])
                ->where('crp.id', $receiptId)
                ->first();

            if (!$recibo) {
                throw new \DomainException('Recibo no encontrado');
            }

            // Obtener tallas de la prenda
            $tallas = [];
            if ($recibo->prenda_id) {
                $tallas = \DB::table('prenda_pedido_tallas')
                    ->where('prenda_pedido_id', $recibo->prenda_id)
                    ->get(['genero', 'talla', 'cantidad'])
                    ->toArray();
            }

            // Obtener imágenes del proceso
            $imagenes = [];
            if ($recibo->prenda_id) {
                $imagenes = \DB::table('pedidos_procesos_imagenes')
                    ->join('pedidos_procesos_prenda_detalles as ppd', 'pedidos_procesos_imagenes.proceso_prenda_detalle_id', '=', 'ppd.id')
                    ->where('ppd.prenda_pedido_id', $recibo->prenda_id)
                    ->orderBy('pedidos_procesos_imagenes.orden')
                    ->get(['pedidos_procesos_imagenes.ruta_original', 'pedidos_procesos_imagenes.ruta_webp'])
                    ->toArray();
            }

            $detalles = [
                'id' => $recibo->id,
                'nombre_prenda' => $recibo->nombre_prenda ?? 'N/A',
                'tipo_recibo' => $recibo->tipo_recibo,
                'estado' => $recibo->estado ?? 'PENDIENTE',
                'observaciones' => $recibo->observaciones,
                'numero_recibo' => $recibo->consecutivo_actual,
                'cliente' => $recibo->cliente,
                'asesor' => $recibo->asesor,
                'fecha_creacion' => $recibo->fecha_creacion,
                'tallas' => $tallas,
                'imagenes' => $imagenes
            ];

            return new GetReceiptDetailsResponse(
                success: true,
                message: 'Detalles del recibo obtenidos',
                details: $detalles
            );

        } catch (\Exception $e) {
            throw new \DomainException('Error al obtener detalles del recibo: ' . $e->getMessage());
        }
    }
}
