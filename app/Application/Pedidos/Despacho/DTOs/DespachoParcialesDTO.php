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
        public int $pendienteInicial = 0,   // Cantidad pendiente al inicio
        public int $parcial1 = 0,
        public int $pendiente1 = 0,
        public int $parcial2 = 0,
        public int $pendiente2 = 0,
        public int $parcial3 = 0,
        public int $pendiente3 = 0,
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
            'talla_id' => $this->tallaId,
            'genero' => $this->genero,
            'pendiente_inicial' => $this->pendienteInicial,
            'parcial_1' => $this->parcial1,
            'pendiente_1' => $this->pendiente1,
            'parcial_2' => $this->parcial2,
            'pendiente_2' => $this->pendiente2,
            'parcial_3' => $this->parcial3,
            'pendiente_3' => $this->pendiente3,
        ];
    }
}
