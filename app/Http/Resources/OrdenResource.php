<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource: OrdenResource
 * 
 * Transforma Orden domain model a JSON serializable.
 */
class OrdenResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'numero_pedido' => $this->getNumeroPedido()->toInt(),
            'cliente' => $this->getCliente(),
            'estado' => $this->getEstado()->toString(),
            'forma_pago' => $this->getFormaPago()->toString(),
            'area' => $this->getArea()->toString(),
            'fecha_creacion' => $this->getFechaCreacion()->toIso8601String(),
            'fecha_ultima_modificacion' => $this->getFechaUltimaModificacion()->toIso8601String(),
            'totales' => [
                'cantidad' => $this->getTotalCantidad(),
                'entregado' => $this->getTotalEntregado(),
                'pendiente' => $this->getTotalPendiente(),
                'porcentaje_completado' => $this->getPorcentajeCompletado(),
            ],
            'prendas' => PrendaResource::collection($this->getPrendas()),
        ];
    }
}
