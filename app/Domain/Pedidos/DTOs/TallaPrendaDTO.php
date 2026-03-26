<?php

namespace App\Domain\Pedidos\DTOs;

class TallaPrendaDTO
{
    public function __construct(
        public readonly array $tallas_por_genero,
        public readonly array $sobremedida,
        public readonly ?string $genero_principal,
        public readonly string $tipo_talla,
        public readonly array $total_por_genero,
        public readonly int $total_general,
        public readonly ?array $validacion,
        public readonly array $tallas_desde_cotizacion,
        public readonly bool $tiene_sobremedida,
        public readonly array $generos_activos
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            tallas_por_genero: $data['tallas_por_genero'] ?? [],
            sobremedida: $data['sobremedida'] ?? [],
            genero_principal: $data['genero_principal'] ?? null,
            tipo_talla: $data['tipo_talla'] ?? '',
            total_por_genero: $data['total_por_genero'] ?? [],
            total_general: $data['total_general'] ?? 0,
            validacion: $data['validacion'] ?? null,
            tallas_desde_cotizacion: $data['tallas_desde_cotizacion'] ?? [],
            tiene_sobremedida: $data['tiene_sobremedida'] ?? false,
            generos_activos: $data['generos_activos'] ?? []
        );
    }

    public function toArray(): array
    {
        return [
            'tallas_por_genero' => $this->tallas_por_genero,
            'sobremedida' => $this->sobremedida,
            'genero_principal' => $this->genero_principal,
            'tipo_talla' => $this->tipo_talla,
            'total_por_genero' => $this->total_por_genero,
            'total_general' => $this->total_general,
            'validacion' => $this->validacion,
            'tallas_desde_cotizacion' => $this->tallas_desde_cotizacion,
            'tiene_sobremedida' => $this->tiene_sobremedida,
            'generos_activos' => $this->generos_activos,
        ];
    }
}
