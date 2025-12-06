<?php

namespace App\DTOs;

/**
 * DTO para datos de prenda con variantes
 * Encapsula toda la información de una prenda a crear
 */
class PrendaCreacionDTO
{
    public function __construct(
        public readonly int $index,
        public readonly string $nombreProducto,
        public readonly ?string $descripcion,
        public readonly ?string $tela,
        public readonly ?string $telaReferencia,
        public readonly ?string $color,
        public readonly ?string $genero,
        public readonly ?string $manga,
        public readonly ?string $broche,
        public readonly ?bool $tieneBolsillos,
        public readonly ?bool $tieneReflectivo,
        public readonly ?string $mangaObs,
        public readonly ?string $bolsillosObs,
        public readonly ?string $brocheObs,
        public readonly ?string $reflectivoObs,
        public readonly ?string $observaciones,
        public readonly array $cantidades, // ['talla' => cantidad]
    ) {}

    /**
     * Factory method desde array de datos
     */
    public static function fromArray(int $index, array $data): self
    {
        return new self(
            index: $index,
            nombreProducto: $data['nombre_producto'] ?? '',
            descripcion: $data['descripcion'] ?? null,
            tela: $data['tela'] ?? null,
            telaReferencia: $data['tela_referencia'] ?? null,
            color: $data['color'] ?? null,
            genero: $data['genero'] ?? null,
            manga: $data['manga'] ?? null,
            broche: $data['broche'] ?? null,
            tieneBolsillos: $data['tiene_bolsillos'] ?? null,
            tieneReflectivo: $data['tiene_reflectivo'] ?? null,
            mangaObs: $data['manga_obs'] ?? null,
            bolsillosObs: $data['bolsillos_obs'] ?? null,
            brocheObs: $data['broche_obs'] ?? null,
            reflectivoObs: $data['reflectivo_obs'] ?? null,
            observaciones: $data['observaciones'] ?? null,
            cantidades: $data['cantidades'] ?? [],
        );
    }

    /**
     * Valida que tenga al menos una cantidad
     */
    public function esValido(): bool
    {
        return count($this->cantidades) > 0 && 
               array_sum($this->cantidades) > 0;
    }

    /**
     * Obtiene cantidad total
     */
    public function cantidadTotal(): int
    {
        return array_sum($this->cantidades);
    }

    /**
     * Convierte a array para persistencia
     */
    public function toArray(): array
    {
        return [
            'nombre_producto' => $this->nombreProducto,
            'descripcion' => $this->descripcion,
            'tela' => $this->tela,
            'tela_referencia' => $this->telaReferencia,
            'color' => $this->color,
            'genero' => $this->genero,
            'manga' => $this->manga,
            'broche' => $this->broche,
            'tiene_bolsillos' => $this->tieneBolsillos,
            'tiene_reflectivo' => $this->tieneReflectivo,
            'manga_obs' => $this->mangaObs,
            'bolsillos_obs' => $this->bolsillosObs,
            'broche_obs' => $this->brocheObs,
            'reflectivo_obs' => $this->reflectivoObs,
            'observaciones' => $this->observaciones,
            'cantidades' => $this->cantidades,
        ];
    }
}
