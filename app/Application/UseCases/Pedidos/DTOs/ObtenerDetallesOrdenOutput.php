<?php

namespace App\Application\UseCases\Pedidos\DTOs;

/**
 * DTO para salida de ObtenerDetallesOrdenUseCase
 * 
 * Responsabilidad: Encapsular datos de respuesta al obtener detalles de orden
 * Patrón: Transfer Object
 */
class ObtenerDetallesOrdenOutput
{
    public function __construct(
        public int $id,
        public int $numero_pedido,
        public string $cliente,
        public string $asesora,
        public string $estado,
        public string $descripcion,
        public string $forma_de_pago,
        public ?string $novedades = null,
        public ?string $area = null,
        public ?int $numero_recibo = null,
        public ?int $cantidad = null,
        public ?int $total_entregado = null,
        public ?array $prendas = null,
        public ?array $metadata = null,
    ) {}

    /**
     * Convertir a array para response JSON
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'numero_pedido' => $this->numero_pedido,
            'cliente' => $this->cliente,
            'asesora' => $this->asesora,
            'estado' => $this->estado,
            'descripcion' => $this->descripcion,
            'forma_de_pago' => $this->forma_de_pago,
            'novedades' => $this->novedades,
            'area' => $this->area,
            'numero_recibo' => $this->numero_recibo,
            'cantidad' => $this->cantidad,
            'total_entregado' => $this->total_entregado,
            'prendas' => $this->prendas,
            'metadata' => $this->metadata,
        ];
    }
}
