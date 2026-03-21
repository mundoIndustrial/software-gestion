<?php

namespace App\Application\UseCases\Pedidos\DTOs;

/**
 * DTO para salida de ObtenerOpcionesGeneralesUseCase
 * 
 * Responsabilidad: Encapsular todas las opciones disponibles para filtros
 * Patrón: Transfer Object
 */
class ObtenerOpcionesGeneralesOutput
{
    public function __construct(
        public array $estados,
        public array $areas,
        public array $clientes,
        public array $asesores,
        public array $formas_pago,
        public array $encargados,
        public array $dias_entrega,
        public ?array $metadata = null,
    ) {}

    /**
     * Convertir a array para response JSON
     */
    public function toArray(): array
    {
        return [
            'estados' => $this->estados,
            'areas' => $this->areas,
            'clientes' => $this->clientes,
            'asesores' => $this->asesores,
            'formas_pago' => $this->formas_pago,
            'encargados' => $this->encargados,
            'dias_entrega' => $this->dias_entrega,
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
