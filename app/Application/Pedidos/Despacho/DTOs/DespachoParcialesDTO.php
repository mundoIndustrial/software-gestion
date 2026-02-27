<?php

namespace App\Application\Pedidos\Despacho\DTOs;

/**
 * DespachoParcialesDTO
 * 
 * Data Transfer Object para los parciales de despacho
 * de un ítem (prenda o EPP)
 */
class DespachoParcialesDTO
{
    public function __construct(
        public string $tipo,                // 'prenda' | 'epp'
        public int|string $id,              // ID del ítem
        public int|string|null $tallaId = null,  // ID de la talla
        public ?string $genero = null,     // Género (DAMA, CABALLERO, UNISEX)
    ) {}

    /**
     * Convertir a array
     */
    public function toArray(): array
    {
        return [
            'tipo' => $this->tipo,
            'id' => $this->id,
            'talla_id' => $this->tallaId,
            'genero' => $this->genero,
        ];
    }
}
