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
        public ?string $numero,
        public ?int $clienteId,
        public ?string $cliente = null,
        public ?string $asesor = null,
        public string $estado,
        public string $descripcion,
        public int $totalPrendas,
        public int $totalArticulos,
        public array $prendas = [],
        public array $epps = [],
        public ?string $formaDePago = null,
        public ?string $fechaCreacion = null,
        public ?string $area = null,
        public string $mensaje = ''
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'numero' => $this->numero,
            'numero_pedido' => $this->numero,
            'cliente_id' => $this->clienteId,
            'cliente' => $this->cliente,
            'asesor' => $this->asesor,
            'estado' => $this->estado,
            'descripcion' => $this->descripcion,
            'total_prendas' => $this->totalPrendas,
            'total_articulos' => $this->totalArticulos,
            'forma_de_pago' => $this->formaDePago,
            'fecha_creacion' => $this->fechaCreacion,
            'fecha' => $this->fechaCreacion,  // También incluir como 'fecha' para compatibilidad
            'area' => $this->area,
            'prendas' => $this->prendas,
            'epps' => $this->epps,
            'mensaje' => $this->mensaje,
        ];
    }
}

