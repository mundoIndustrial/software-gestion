<?php

namespace App\Application\Pedidos\Despacho\DTOs;

/**
 * FilaDespachoDTO
 * 
 * Data Transfer Object que representa una fila uniforme de despacho
 * (ya sea prenda con talla o EPP)
 * 
 * Utilizado en:
 * - Vistas Blade
 * - JavaScript
 * - APIs
 */
class FilaDespachoDTO
{
    public function __construct(
        public string $tipo,                    // 'prenda' | 'epp'
        public int|string $id,                  // ID del ítem
        public ?int $tallaId,                   // ID de talla (null para EPP)
        public string $descripcion,
        public int $cantidadTotal,
        public string $talla,                   // Talla o '—'
        public ?string $genero,                 // Género (null para EPP)
        public ?array $objetoPrenda = null,     // Datos completos
        public ?array $objetoTalla = null,
        public ?array $objetoEpp = null,
    ) {}

    /**
     * Convertir a array para JSON
     */
    public function toArray(): array
    {
        return [
            'tipo' => $this->tipo,
            'id' => $this->id,
            'talla_id' => $this->tallaId,
            'descripcion' => $this->descripcion,
            'cantidad_total' => $this->cantidadTotal,
            'talla' => $this->talla,
            'genero' => $this->genero,
            'objeto_prenda' => $this->objetoPrenda,
            'objeto_talla' => $this->objetoTalla,
            'objeto_epp' => $this->objetoEpp,
        ];
    }
}
