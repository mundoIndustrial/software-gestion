<?php

namespace App\Application\UseCases\Pedidos\DTOs;

/**
 * DTO para salida de GuardarDiaEntregaUseCase
 * 
 * Responsabilidad: Encapsular resultado de guardar día de entrega
 * Patrón: Transfer Object
 */
class GuardarDiaEntregaOutput
{
    public function __construct(
        public int $numero_pedido,
        public string $mensaje,
        public ?int $dia_de_entrega = null,
        public ?string $fecha_estimada_de_entrega = null,
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
            'dia_de_entrega' => $this->dia_de_entrega,
            'fecha_estimada_de_entrega' => $this->fecha_estimada_de_entrega,
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
