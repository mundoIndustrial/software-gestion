<?php

namespace App\Application\Operario\DTOs;

/**
 * DTO: ObtenerPedidosOperarioDTO
 * 
 * Datos de respuesta para obtener pedidos de un operario
 */
class ObtenerPedidosOperarioDTO
{
    public function __construct(
        public int $operarioId,
        public string $nombreOperario,
        public string $tipoOperario,
        public string $areaOperario,
        public array $pedidos,
        public int $totalPedidos,
        public int $pedidosEnProceso,
        public int $pedidosCompletados
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            operarioId: $data['operario_id'],
            nombreOperario: $data['nombre_operario'],
            tipoOperario: $data['tipo_operario'],
            areaOperario: $data['area_operario'],
            pedidos: $data['pedidos'] ?? [],
            totalPedidos: $data['total_pedidos'] ?? 0,
            pedidosEnProceso: $data['pedidos_en_proceso'] ?? 0,
            pedidosCompletados: $data['pedidos_completados'] ?? 0
        );
    }

    public function toArray(): array
    {
        return [
            'operario_id' => $this->operarioId,
            'nombre_operario' => $this->nombreOperario,
            'tipo_operario' => $this->tipoOperario,
            'area_operario' => $this->areaOperario,
            'pedidos' => $this->pedidos,
            'total_pedidos' => $this->totalPedidos,
            'pedidos_en_proceso' => $this->pedidosEnProceso,
            'pedidos_completados' => $this->pedidosCompletados,
        ];
    }
}
