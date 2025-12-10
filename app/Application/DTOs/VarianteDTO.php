<?php

namespace App\Application\DTOs;

class VarianteDTO
{
    public function __construct(
        public ?int $tipo_manga_id = null,
        public ?int $tipo_broche_id = null,
        public bool $tiene_bolsillos = false,
        public bool $tiene_reflectivo = false,
        public ?string $descripcion_adicional = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            tipo_manga_id: $data['tipo_manga_id'] ?? null,
            tipo_broche_id: $data['tipo_broche_id'] ?? null,
            tiene_bolsillos: (bool)($data['tiene_bolsillos'] ?? false),
            tiene_reflectivo: (bool)($data['tiene_reflectivo'] ?? false),
            descripcion_adicional: $data['descripcion_adicional'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'tipo_manga_id' => $this->tipo_manga_id,
            'tipo_broche_id' => $this->tipo_broche_id,
            'tiene_bolsillos' => $this->tiene_bolsillos,
            'tiene_reflectivo' => $this->tiene_reflectivo,
            'descripcion_adicional' => $this->descripcion_adicional,
        ];
    }
}
