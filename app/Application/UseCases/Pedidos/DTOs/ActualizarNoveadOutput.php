<?php

namespace App\Application\UseCases\Pedidos\DTOs;

/**
 * DTO para salida de ActualizarNoveadUseCase
 * 
 * Responsabilidad: Encapsular resultado de actualizar novedades
 * Patrón: Transfer Object
 */
class ActualizarNoveadOutput
{
    public function __construct(
        public int $numero_pedido,
        public string $mensaje,
        public ?string $novedades_actuales = null,
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
            'novedades' => $this->novedades_actuales,
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
