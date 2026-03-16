<?php

namespace App\Application\Pedidos\DTOs;

/**
 * PedidoDetalleDTO
 * 
 * DTO para respuesta de detalle completo de pedido
 */
class PedidoDetalleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly int $numero_pedido,
        public readonly string $cliente,
        public readonly array $prendas,
        public readonly array $epps_transformados = [],
        public readonly ?array $ancho_metraje = null,
        public readonly ?string $estado = null,
        public readonly ?string $fecha_creacion = null,
        public readonly ?\DateTime $fecha_estimada_de_entrega = null,
        public readonly ?string $area = null,
        public readonly ?string $dia_de_entrega = null,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'numero_pedido' => $this->numero_pedido,
            'cliente' => $this->cliente,
            'prendas' => $this->prendas,
            'epps_transformados' => $this->epps_transformados,
            'ancho_metraje' => $this->ancho_metraje,
            'estado' => $this->estado,
            'fecha_creacion' => $this->fecha_creacion,
            'fecha_estimada_de_entrega' => $this->fecha_estimada_de_entrega,
            'area' => $this->area,
            'dia_de_entrega' => $this->dia_de_entrega,
        ];
    }
}
