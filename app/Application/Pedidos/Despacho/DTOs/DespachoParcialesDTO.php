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
        public string $tipo,            // 'prenda' | 'epp'
        public int|string $id,          // ID del ítem
        public int $parcial1 = 0,
        public int $parcial2 = 0,
        public int $parcial3 = 0,
    ) {}

    /**
     * Obtener total despachado
     */
    public function getTotalDespachado(): int
    {
        return $this->parcial1 + $this->parcial2 + $this->parcial3;
    }

    /**
     * Convertir a array
     */
    public function toArray(): array
    {
        return [
            'tipo' => $this->tipo,
            'id' => $this->id,
            'parcial_1' => $this->parcial1,
            'parcial_2' => $this->parcial2,
            'parcial_3' => $this->parcial3,
        ];
    }
}
