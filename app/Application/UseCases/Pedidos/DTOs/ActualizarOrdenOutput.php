<?php

namespace App\Application\UseCases\Pedidos\DTOs;

/**
 * DTO para salida de ActualizarOrdenUseCase
 * 
 * Responsabilidad: Encapsular datos de respuesta al actualizar una orden
 * Patrón: Transfer Object
 */
class ActualizarOrdenOutput
{
    public function __construct(
        public int $numero_pedido,
        public string $mensaje,
        public array $campos_modificados,
        public ?array $orden_actualizada = null,
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
            'campos_modificados' => $this->campos_modificados,
            'orden_actualizada' => $this->orden_actualizada,
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
