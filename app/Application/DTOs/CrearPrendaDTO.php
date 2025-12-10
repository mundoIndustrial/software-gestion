<?php

namespace App\Application\DTOs;

use Illuminate\Http\UploadedFile;

class CrearPrendaDTO
{
    public function __construct(
        public string $nombre_producto,
        public string $descripcion,
        public string $tipo_prenda,
        public array $tallas,
        public array $variantes,
        public array $telas,
        public array $fotos,
        public ?string $genero = null,
        public ?int $cotizacion_id = null,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            nombre_producto: $data['nombre_producto'] ?? '',
            descripcion: $data['descripcion'] ?? '',
            tipo_prenda: $data['tipo_prenda'] ?? 'OTRO',
            tallas: $data['tallas'] ?? [],
            variantes: self::parseVariantes($data['variantes'] ?? []),
            telas: self::parseTelas($data['telas'] ?? []),
            fotos: self::parseImagenes($data['fotos'] ?? []),
            genero: $data['genero'] ?? null,
            cotizacion_id: $data['cotizacion_id'] ?? null,
        );
    }

    private static function parseVariantes(array $variantes): array
    {
        return array_map(fn($v) => VarianteDTO::fromArray($v), $variantes);
    }

    private static function parseTelas(array $telas): array
    {
        return array_map(fn($t) => TelaDTO::fromArray($t), $telas);
    }

    private static function parseImagenes(array $fotos): array
    {
        return array_map(fn($f) => ImagenDTO::fromArray($f), $fotos);
    }

    public function toArray(): array
    {
        return [
            'nombre_producto' => $this->nombre_producto,
            'descripcion' => $this->descripcion,
            'tipo_prenda' => $this->tipo_prenda,
            'tallas' => $this->tallas,
            'variantes' => array_map(fn($v) => $v->toArray(), $this->variantes),
            'telas' => array_map(fn($t) => $t->toArray(), $this->telas),
            'fotos' => array_map(fn($f) => $f->toArray(), $this->fotos),
            'genero' => $this->genero,
            'cotizacion_id' => $this->cotizacion_id,
        ];
    }
}
