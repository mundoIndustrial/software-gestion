<?php

namespace App\Application\UseCases\Pedidos\DTOs;

/**
 * DTO para salida de ActualizarDescripcionUseCase
 * 
 * Responsabilidad: Encapsular resultado de actualizar descripción/prendas
 * Patrón: Transfer Object
 */
class ActualizarDescripcionOutput
{
    public function __construct(
        public int $numero_pedido,
        public string $mensaje,
        public int $prendas_procesadas,
        public bool $registros_regenerados,
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
            'prendas_procesadas' => $this->prendas_procesadas,
            'registros_regenerados' => $this->registros_regenerados,
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
