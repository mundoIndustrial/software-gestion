<?php

namespace App\Application\Pedidos\DTOs;

/**
 * DTO: Respuesta de Pedido
 * 
 * Desde Application Layer hacia HTTP/API
 */
class PedidoResponseDTO
{
    public function __construct(
        public ?int $id,
        public string $numero,
        public int $clienteId,
        public string $estado,
        public string $descripcion,
        public int $totalPrendas,
        public int $totalArticulos,
        public string $mensaje = ''
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'numero' => $this->numero,
            'cliente_id' => $this->clienteId,
            'estado' => $this->estado,
            'descripcion' => $this->descripcion,
            'total_prendas' => $this->totalPrendas,
            'total_articulos' => $this->totalArticulos,
            'mensaje' => $this->mensaje,
        ];
    }
}
