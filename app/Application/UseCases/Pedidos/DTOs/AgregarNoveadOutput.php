<?php

namespace App\Application\UseCases\Pedidos\DTOs;

/**
 * DTO para salida de AgregarNoveadUseCase
 * 
 * Responsabilidad: Encapsular resultado de agregar novedad
 * Patrón: Transfer Object
 */
class AgregarNoveadOutput
{
    public function __construct(
        public int $numero_pedido,
        public string $mensaje,
        public string $novedad_agregada,
        public ?string $novedades_completas = null,
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
            'novedad_agregada' => $this->novedad_agregada,
            'novedades' => $this->novedades_completas,
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
