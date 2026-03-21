<?php

namespace App\Application\UseCases\Pedidos\DTOs;

/**
 * DTO para salida de EliminarOrdenUseCase
 * 
 * Responsabilidad: Encapsular datos de respuesta al eliminar una orden
 * Patrón: Transfer Object
 */
class EliminarOrdenOutput
{
    public function __construct(
        public int $numero_pedido,
        public string $mensaje,
        public bool $eliminada,
        public ?array $metadata = null,
    ) {}

    /**
     * Convertir a array para response JSON
     */
    public function toArray(): array
    {
        return [
            'numero_pedido' => $this->numero_pedido,
            'mensaje' => $this->mensaje,
            'eliminada' => $this->eliminada,
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
