<?php

namespace App\Application\UseCases\Pedidos\DTOs;

/**
 * DTO para salida de CrearOrdenUseCase
 * 
 * Responsabilidad: Encapsular datos de respuesta al crear una orden
 * Patrón: Transfer Object
 */
class CrearOrdenOutput
{
    public function __construct(
        public int $pedido_id,
        public int $numero_pedido,
        public string $cliente,
        public string $estado,
        public string $mensaje,
        public ?array $metadata = null,
    ) {}

    /**
     * Convertir a array para response JSON
     */
    public function toArray(): array
    {
        return [
            'id' => $this->pedido_id,
            'numero_pedido' => $this->numero_pedido,
            'cliente' => $this->cliente,
            'estado' => $this->estado,
            'mensaje' => $this->mensaje,
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Convertir a response JSON
     */
    public function toResponse(): array
    {
        return array_merge(
            $this->toArray(),
            ['success' => true]
        );
    }
}
